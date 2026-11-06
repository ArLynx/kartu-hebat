<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\Kabupaten;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Sanggahan;
use App\Models\User;
use App\Models\Village;
use App\Notifications\ApplicationStatusChanged;
use Database\Seeders\RegionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MahasiswaSanggahanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        Notification::fake();
        $this->travelTo('2026-11-11');
    }

    public function test_student_can_submit_sanggahan_for_own_tms_application_during_masa_sanggah(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $kabupatenOperator = $this->kabupatenOperatorFor($student);
        $secondKabupatenOperator = $this->kabupatenOperatorFor($student);
        $otherKabupatenOperator = $this->kabupatenOperator($this->otherKabupaten());

        $response = $this->actingAs($student)->post(route('mahasiswa.sanggahan.store'), [
            'application_id' => $application->id,
            'uraian' => 'Saya keberatan atas keputusan TMS karena dokumen yang saya unggah sudah sesuai.',
            'pernyataan_kebenaran' => '1',
        ]);

        $response->assertRedirect(route('mahasiswa.sanggahan.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sanggahans', [
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIAJUKAN->value,
        ]);

        $this->assertDatabaseHas('verification_logs', [
            'application_id' => $application->id,
            'action' => 'sanggahan_submitted',
        ]);

        $this->assertSame(ApplicationStatus::TMS, $application->fresh()->status);

        Notification::assertSentTo($kabupatenOperator, ApplicationStatusChanged::class);
        Notification::assertSentTo($secondKabupatenOperator, ApplicationStatusChanged::class);
        Notification::assertNotSentTo($otherKabupatenOperator, ApplicationStatusChanged::class);
    }

    public function test_database_rejects_a_second_sanggahan_row_for_the_same_application(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $attributes = [
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIAJUKAN,
            'uraian' => 'Sanggahan pertama yang sudah tercatat pada basis data.',
        ];

        Sanggahan::query()->create($attributes);

        $this->expectException(QueryException::class);

        Sanggahan::query()->create($attributes);
    }

    public function test_sanggahan_requires_minimum_length_uraian_and_truth_statement(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), [
                'application_id' => $application->id,
                'uraian' => 'Terlalu pendek',
                'pernyataan_kebenaran' => '1',
            ])
            ->assertSessionHasErrors([
                'uraian' => 'Uraian sanggahan minimal 40 karakter.',
            ]);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), [
                'application_id' => $application->id,
                'uraian' => '',
                'pernyataan_kebenaran' => '1',
            ])
            ->assertSessionHasErrors(['uraian' => 'Uraian sanggahan wajib diisi.']);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), [
                'application_id' => $application->id,
                'uraian' => str_repeat('a', 40),
            ])
            ->assertSessionHasErrors([
                'pernyataan_kebenaran' => 'Pernyataan kebenaran sanggahan wajib disetujui.',
            ]);

        $this->assertDatabaseCount('sanggahans', 0);
    }

    public function test_sanggahan_is_rejected_when_application_is_not_tms(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::SELEKSI_KABUPATEN);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertRedirect()
            ->assertSessionHas('error', 'Sanggahan hanya dapat diajukan untuk pengajuan berstatus Tidak Memenuhi Syarat (TMS).');

        $this->assertDatabaseCount('sanggahans', 0);
    }

    public function test_sanggahan_is_rejected_when_application_belongs_to_another_student(): void
    {
        $owner = $this->student();
        $intruder = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($owner, $periode, ApplicationStatus::TMS);

        $this->actingAs($intruder)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertForbidden();

        $this->assertDatabaseCount('sanggahans', 0);
    }

    public function test_sanggahan_is_rejected_outside_masa_sanggah_window(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->travelTo('2026-11-13');

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertRedirect()
            ->assertSessionHas('error', 'Masa sanggah periode ini belum dibuka atau sudah berakhir.');

        $this->assertDatabaseCount('sanggahans', 0);
    }

    public function test_sanggahan_is_rejected_when_periode_is_not_active(): void
    {
        $student = $this->student();
        $periode = $this->periode(['status' => 'ditutup']);
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertRedirect()
            ->assertSessionHas('error', 'Sanggahan hanya dapat diajukan selama periode beasiswa masih aktif.');

        $this->assertDatabaseCount('sanggahans', 0);
    }

    public function test_second_sanggahan_for_the_same_application_is_rejected(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertSessionHas('success');

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertRedirect()
            ->assertSessionHas('error', 'Sanggahan untuk pengajuan ini sudah pernah diajukan dan tidak dapat diubah.');

        $this->assertDatabaseCount('sanggahans', 1);
    }

    public function test_after_submission_there_is_no_route_to_change_or_withdraw_sanggahan(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertSessionHas('success');

        $sanggahan = $application->sanggahan()->firstOrFail();

        $this->actingAs($student)
            ->put(route('mahasiswa.sanggahan.index'), $this->payload($application, 'Uraian baru yang cukup panjang untuk menggantikan isi sebelumnya.'))
            ->assertMethodNotAllowed();

        $this->actingAs($student)
            ->delete(route('mahasiswa.sanggahan.index'))
            ->assertMethodNotAllowed();

        $this->assertSame(
            $this->payload($application)['uraian'],
            $sanggahan->fresh()->uraian,
        );
    }

    public function test_submit_page_shows_tms_card_with_agency_and_verifier_notes(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->get(route('mahasiswa.submit.index'))
            ->assertOk()
            ->assertSee('Dinas Kependudukan dan Pencatatan Sipil')
            ->assertSee('NIK tidak sesuai dengan dokumen kependudukan.')
            ->assertSee(route('mahasiswa.sanggahan.index'));
    }

    public function test_submit_page_sanggahan_button_is_disabled_outside_masa_sanggah_window(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->travelTo('2026-11-13');

        $this->actingAs($student)
            ->get(route('mahasiswa.submit.index'))
            ->assertOk()
            ->assertSee('Masa sanggah periode ini belum dibuka atau sudah berakhir.');
    }

    public function test_sanggahan_page_remains_accessible_after_registration_date_has_passed(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-10-31',
        ]);
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->travelTo('2026-11-11');

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee('Sedang berlangsung')
            ->assertSee('10 Nov 2026')
            ->assertSee('12 Nov 2026');
    }

    public function test_sanggahan_page_lists_history_across_periods(): void
    {
        $student = $this->student();
        $currentPeriode = $this->periode();
        $currentApplication = $this->applicationFor($student, $currentPeriode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($currentApplication))
            ->assertSessionHas('success');

        $pastPeriode = $this->periode([
            'tahun' => 2025,
            'nama' => 'Periode Beasiswa 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'tanggal_mulai_sanggah' => '2025-11-10',
            'tanggal_selesai_sanggah' => '2025-11-12',
            'status' => 'ditutup',
        ]);
        $pastApplication = $this->applicationFor($student, $pastPeriode, ApplicationStatus::TMS);

        Sanggahan::query()->create([
            'application_id' => $pastApplication->id,
            'periode_id' => $pastPeriode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DITOLAK,
            'uraian' => 'Sanggahan lama yang sudah diputuskan oleh operator kabupaten.',
            'catatan_keputusan' => 'Dokumen terbukti tidak sesuai.',
            'decided_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Periode Beasiswa 2025',
                'Sanggahan lama yang sudah diputuskan oleh operator kabupaten.',
                'Ditolak',
                'Dokumen terbukti tidak sesuai.',
            ]);
    }

    public function test_sanggahan_menu_is_available_in_student_sidebar(): void
    {
        $student = $this->student();
        $this->periode();

        $this->actingAs($student)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee(route('mahasiswa.sanggahan.index'))
            ->assertSee('Sanggahan');
    }

    public function test_superadmin_cannot_close_periode_with_unresolved_sanggahan(): void
    {
        $superadmin = $this->superadmin();
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        Sanggahan::query()->create([
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIAJUKAN,
            'uraian' => 'Sanggahan yang masih menunggu keputusan operator kabupaten.',
        ]);

        $response = $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
            'status' => 'ditutup',
        ]);

        $response->assertSessionHasErrors('status');
        $response->assertSessionHasErrors([
            'status' => 'Periode tidak dapat ditutup karena masih ada sanggahan yang belum selesai diproses.',
        ]);

        $this->assertSame('aktif', $periode->fresh()->status);
    }

    public function test_superadmin_can_close_periode_when_all_sanggahans_are_final(): void
    {
        $superadmin = $this->superadmin();
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        Sanggahan::query()->create([
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DITOLAK,
            'uraian' => 'Sanggahan yang sudah diputuskan oleh operator kabupaten.',
            'catatan_keputusan' => 'Dokumen tidak sesuai.',
            'decided_at' => now(),
        ]);

        $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
            'status' => 'ditutup',
        ])->assertSessionHasNoErrors();

        $this->assertSame('ditutup', $periode->fresh()->status);
    }

    public function test_superadmin_cannot_delete_periode_with_unresolved_sanggahan(): void
    {
        $superadmin = $this->superadmin();
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        Sanggahan::query()->create([
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIAJUKAN,
            'uraian' => 'Sanggahan yang masih menunggu keputusan operator kabupaten.',
        ]);

        $this->actingAs($superadmin)
            ->delete(route('superadmin.periodes.destroy', $periode))
            ->assertRedirect()
            ->assertSessionHas('error', 'Periode tidak dapat dihapus karena memiliki riwayat sanggahan yang harus dipertahankan.');

        $this->assertDatabaseHas('periodes', ['id' => $periode->id]);
    }

    public function test_superadmin_cannot_close_periode_with_granted_sanggahan_whose_application_is_not_final(): void
    {
        $superadmin = $this->superadmin();
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::VERIFIKASI_DINAS);

        Sanggahan::query()->create([
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'Sanggahan yang sudah dikabulkan dan menunggu verifikasi ulang dinas.',
            'catatan_keputusan' => 'Keputusan TMS dianulir.',
            'decided_at' => now(),
        ]);

        $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
            'status' => 'ditutup',
        ])->assertSessionHasErrors('status');

        $this->assertSame('aktif', $periode->fresh()->status);
    }

    public function test_superadmin_can_close_periode_when_granted_sanggahan_application_is_already_final(): void
    {
        $superadmin = $this->superadmin();
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::DITERIMA);

        Sanggahan::query()->create([
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'Sanggahan yang sudah dikabulkan dan prosesnya tuntas.',
            'catatan_keputusan' => 'Keputusan TMS dianulir.',
            'decided_at' => now(),
        ]);

        $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => $periode->tahun,
            'nama' => $periode->nama,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
            'status' => 'ditutup',
        ])->assertSessionHasNoErrors();

        $this->assertSame('ditutup', $periode->fresh()->status);
    }

    public function test_superadmin_cannot_delete_periode_that_only_has_decided_sanggahans(): void
    {
        $superadmin = $this->superadmin();
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        Sanggahan::query()->create([
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DITOLAK,
            'uraian' => 'Sanggahan lama yang harus tetap tersimpan sebagai riwayat.',
            'catatan_keputusan' => 'Dokumen tidak sesuai.',
            'decided_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->delete(route('superadmin.periodes.destroy', $periode))
            ->assertRedirect()
            ->assertSessionHas('error', 'Periode tidak dapat dihapus karena memiliki riwayat sanggahan yang harus dipertahankan.');

        $this->assertDatabaseHas('periodes', ['id' => $periode->id]);
    }

    public function test_sanggahan_page_loads_without_any_application_or_periode(): void
    {
        $student = $this->student();

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee('Belum dijadwalkan')
            ->assertSee('Belum ada sanggahan yang diajukan.');
    }

    public function test_submit_page_disables_sanggahan_button_without_an_eligible_application(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->travelTo('2026-11-13');

        $this->actingAs($student)
            ->get(route('mahasiswa.submit.index'))
            ->assertOk()
            ->assertSee('aria-disabled="true"', false);
    }

    public function test_sanggahan_page_renders_submission_form_for_eligible_application(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee('Dinas Kependudukan dan Pencatatan Sipil')
            ->assertSee('NIK tidak sesuai dengan dokumen kependudukan.')
            ->assertSee('Uraian Sanggahan')
            ->assertSee('Kirim Sanggahan')
            ->assertSee('name="application_id" value="'.$application->id.'"', false);
    }

    public function test_sanggahan_page_shows_status_of_an_existing_sanggahan_instead_of_the_form(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertSessionHas('success');

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee('Sanggahan sudah dikirim dan menunggu pemeriksaan operator. Isi tidak dapat diubah atau ditarik.')
            ->assertDontSee('Kirim Sanggahan');
    }

    public function test_sanggahan_is_rejected_when_uraian_is_only_whitespace_padding(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application, str_repeat(' ', 45).'x'))
            ->assertSessionHasErrors(['uraian' => 'Uraian sanggahan minimal 40 karakter.']);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application, str_repeat('a', 2001)))
            ->assertSessionHasErrors(['uraian' => 'Uraian sanggahan maksimal 2000 karakter.']);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application, str_repeat('a', 40)))
            ->assertSessionHasNoErrors();

        $this->assertSame(str_repeat('a', 40), $application->sanggahan()->firstOrFail()->uraian);
    }

    public function test_sanggahan_page_explains_why_the_form_is_unavailable_when_periode_is_closed(): void
    {
        $student = $this->student();
        $periode = $this->periode(['status' => 'ditutup']);
        $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee('Masa sanggah hanya dapat digunakan selama periode beasiswa masih aktif.')
            ->assertDontSee('Kirim Sanggahan');
    }

    public function test_submit_page_shows_existing_sanggahan_status(): void
    {
        $student = $this->student();
        $periode = $this->periode();
        $application = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), $this->payload($application))
            ->assertSessionHas('success');

        $this->actingAs($student)
            ->get(route('mahasiswa.submit.index'))
            ->assertOk()
            ->assertSee('Sanggahan Anda')
            ->assertSee('Diajukan')
            ->assertSee('Lihat Sanggahan')
            ->assertDontSee('Ajukan Sanggahan');
    }

    private function superadmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    private function payload(Application $application, ?string $uraian = null): array
    {
        return [
            'application_id' => $application->id,
            'uraian' => $uraian ?? 'Saya keberatan atas keputusan TMS karena dokumen yang saya unggah sudah sesuai.',
            'pernyataan_kebenaran' => '1',
        ];
    }

    private function student(): User
    {
        $village = Village::query()->with('kecamatan')->firstOrFail();

        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212123456789'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT),
            'nim' => 'KHM-SNG-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jalan Pengujian',
        ]);

        return $student;
    }

    private function periode(array $attributes = []): Periode
    {
        return Periode::query()->create(array_merge([
            'tahun' => 2026,
            'nama' => 'Periode Beasiswa '.Str::random(8),
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
            'status' => 'aktif',
        ], $attributes));
    }

    private function applicationFor(
        User $student,
        Periode $periode,
        ApplicationStatus $status,
        array $attributes = [],
    ): Application {
        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'KAT-'.Str::random(10),
            'application_type' => ApplicationType::AKADEMIK,
            'nama' => 'Kategori Pengujian',
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-REG-'.$periode->id.'-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'status' => 'verification',
            'submitted_at' => now(),
        ]);

        $application = Application::query()->create(array_merge([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $pendaftaran->nomor_pendaftaran,
            'periode' => $periode->nama,
            'status' => $status,
            'application_type' => ApplicationType::AKADEMIK,
            'submitted_at' => now(),
            'locked_at' => now(),
        ], $attributes));

        if ($status === ApplicationStatus::TMS) {
            AgencyVerification::query()->create([
                'application_id' => $application->id,
                'verifier_id' => User::factory()->create(['role' => UserRole::OPERATOR_DUKCAPIL])->id,
                'agency' => 'dukcapil',
                'decision' => VerificationDecision::TMS,
                'notes' => 'NIK tidak sesuai dengan dokumen kependudukan.',
                'verified_at' => now(),
            ]);
        }

        return $application;
    }

    private function kabupatenOperatorFor(User $student): User
    {
        return $this->kabupatenOperator($student->profile->village->kabupaten_id);
    }

    private function kabupatenOperator(int $kabupatenId): User
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $kabupatenId,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function otherKabupaten(): int
    {
        return Kabupaten::query()->create([
            'code' => '9999',
            'name' => 'Kabupaten Lain',
        ])->id;
    }
}
