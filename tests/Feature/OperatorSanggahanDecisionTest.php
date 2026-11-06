<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVerification;
use App\Models\Kabupaten;
use App\Models\KategoriBeasiswa;
use App\Models\Kecamatan;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Sanggahan;
use App\Models\Selection;
use App\Models\User;
use App\Models\VerificationLog;
use App\Models\Village;
use App\Notifications\ApplicationStatusChanged;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperatorSanggahanDecisionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, User>
     */
    private array $agencyOperators = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        Notification::fake();
        $this->travelTo('2026-11-11');
    }

    public function test_kabupaten_operator_can_grant_sanggahan_and_only_the_tms_agency_is_reopened(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();
        $student = $this->student();

        $application = $this->tmsApplication($student, $periode);
        $document = $application->documents->firstOrFail();

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $student->id,
            'from_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'submitted',
            'notes' => 'Pengajuan dikirim ulang.',
            'created_at' => now()->subDay(),
        ]);
        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $student->id,
            'from_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'submitted',
            'notes' => 'Pengajuan dikirim ulang lagi.',
            'created_at' => now()->subHours(2),
        ]);

        $this->documentVerification($application, $document, 'dukcapil', 1, DocumentVerificationResult::TIDAK_MEMENUHI, 'Penilaian putaran lama.');
        $this->documentVerification($application, $document, 'dukcapil', 2, DocumentVerificationResult::TIDAK_MEMENUHI, 'NIK tidak terbaca.');
        $this->documentVerification($application, $document, 'pendidikan', 2, DocumentVerificationResult::MEMENUHI);
        $this->documentVerification($application, $document, 'bkpsdm', 2, DocumentVerificationResult::MEMENUHI);

        $sanggahan = $this->sanggahanFor($application);

        $otherApplication = $this->tmsApplication($this->student('Mahasiswa Lain'), $periode);

        $response = $this->actingAs($operator)->post(route('operator.sanggahan.decide', $sanggahan), [
            'decision' => SanggahanStatus::DIKABULKAN->value,
            'catatan_keputusan' => 'NIK pada KTP sudah benar, keputusan TMS dianulir.',
        ]);

        $response->assertRedirect(route('operator.sanggahan.show', $sanggahan));
        $response->assertSessionHas('success');

        $sanggahan->refresh();
        $this->assertSame(SanggahanStatus::DIKABULKAN, $sanggahan->status);
        $this->assertSame($operator->id, $sanggahan->decided_by);
        $this->assertNotNull($sanggahan->decided_at);
        $this->assertSame('NIK pada KTP sudah benar, keputusan TMS dianulir.', $sanggahan->catatan_keputusan);

        $application->refresh();
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);
        $this->assertNull($application->catatan);
        $this->assertNotNull($application->locked_at);

        $this->assertDatabaseMissing('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'dukcapil',
        ]);
        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'pendidikan',
            'decision' => VerificationDecision::MS->value,
        ]);
        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'bkpsdm',
            'decision' => VerificationDecision::MS->value,
        ]);

        $this->assertDatabaseMissing('document_verifications', [
            'application_id' => $application->id,
            'stage' => 'dukcapil',
            'round' => 2,
        ]);
        $this->assertDatabaseHas('document_verifications', [
            'application_id' => $application->id,
            'stage' => 'dukcapil',
            'round' => 1,
        ]);
        $this->assertDatabaseHas('document_verifications', [
            'application_id' => $application->id,
            'stage' => 'pendidikan',
            'round' => 2,
        ]);
        $this->assertDatabaseHas('document_verifications', [
            'application_id' => $application->id,
            'stage' => 'bkpsdm',
            'round' => 2,
        ]);

        $this->assertSame('belum_diverifikasi', $student->profile->fresh()->status_kependudukan);

        $this->assertDatabaseHas('verification_logs', [
            'application_id' => $application->id,
            'action' => 'sanggahan_granted',
            'from_status' => ApplicationStatus::TMS->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
        ]);

        $log = VerificationLog::query()
            ->where('application_id', $application->id)
            ->where('action', 'sanggahan_granted')
            ->firstOrFail();

        $this->assertSame(['dukcapil'], $log->metadata['annulled_agencies']);
        $this->assertSame('Dinas Kependudukan dan Pencatatan Sipil', $log->metadata['annulled_verifications'][0]['agency_label']);
        $this->assertSame('TMS', $log->metadata['annulled_verifications'][0]['decision']);

        Notification::assertSentTo($student, ApplicationStatusChanged::class);
        Notification::assertSentTo($this->agencyOperator(UserRole::OPERATOR_DUKCAPIL), ApplicationStatusChanged::class);
        Notification::assertNotSentTo($this->agencyOperator(UserRole::OPERATOR_PENDIDIKAN), ApplicationStatusChanged::class);
        Notification::assertNotSentTo($this->agencyOperator(UserRole::OPERATOR_BKPSDM), ApplicationStatusChanged::class);

        $this->assertSame(ApplicationStatus::TMS, $otherApplication->fresh()->status);
        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $otherApplication->id,
            'agency' => 'dukcapil',
            'decision' => VerificationDecision::TMS->value,
        ]);
    }

    public function test_grant_restores_social_desil_when_dinas_sosial_decided_tms(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();
        $student = $this->student();
        $student->profile->update(['desil_sosial' => 3, 'status_kependudukan' => 'sesuai']);

        $application = $this->tmsApplication($student, $periode, ApplicationType::TIDAK_MAMPU, [
            'dukcapil' => VerificationDecision::MS,
            'sosial' => VerificationDecision::TMS,
            'pendidikan' => VerificationDecision::MS,
            'bkpsdm' => VerificationDecision::MS,
        ]);
        $sanggahan = $this->sanggahanFor($application);

        $this->actingAs($operator)->post(route('operator.sanggahan.decide', $sanggahan), [
            'decision' => SanggahanStatus::DIKABULKAN->value,
            'catatan_keputusan' => 'Desil sosial perlu dinilai ulang.',
        ])->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);
        $this->assertNull($student->profile->fresh()->desil_sosial);
        $this->assertSame('sesuai', $student->profile->fresh()->status_kependudukan);
        $this->assertDatabaseMissing('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'sosial',
        ]);
        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'dukcapil',
            'decision' => VerificationDecision::MS->value,
        ]);
    }

    public function test_reject_keeps_the_application_tms_and_notifies_the_student(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();
        $student = $this->student();
        $application = $this->tmsApplication($student, $periode);
        $sanggahan = $this->sanggahanFor($application);

        $response = $this->actingAs($operator)->post(route('operator.sanggahan.decide', $sanggahan), [
            'decision' => SanggahanStatus::DITOLAK->value,
            'catatan_keputusan' => 'Dokumen KTP terbukti tidak sesuai dengan data kependudukan.',
        ]);

        $response->assertRedirect(route('operator.sanggahan.show', $sanggahan));
        $response->assertSessionHas('success');

        $sanggahan->refresh();
        $this->assertSame(SanggahanStatus::DITOLAK, $sanggahan->status);
        $this->assertSame($operator->id, $sanggahan->decided_by);
        $this->assertNotNull($sanggahan->decided_at);

        $application->refresh();
        $this->assertSame(ApplicationStatus::TMS, $application->status);
        $this->assertCount(3, $application->agencyVerifications);
        $this->assertNotNull($application->catatan);

        $this->assertDatabaseHas('verification_logs', [
            'application_id' => $application->id,
            'action' => 'sanggahan_rejected',
            'from_status' => ApplicationStatus::TMS->value,
            'to_status' => ApplicationStatus::TMS->value,
            'notes' => 'Dokumen KTP terbukti tidak sesuai dengan data kependudukan.',
        ]);

        Notification::assertSentTo($student, ApplicationStatusChanged::class);
        Notification::assertNotSentTo($this->agencyOperator(UserRole::OPERATOR_DUKCAPIL), ApplicationStatusChanged::class);
    }

    public function test_both_decisions_require_a_non_empty_note(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();
        $application = $this->tmsApplication($this->student(), $periode);
        $sanggahan = $this->sanggahanFor($application);

        foreach ([SanggahanStatus::DIKABULKAN, SanggahanStatus::DITOLAK] as $decision) {
            $this->actingAs($operator)
                ->post(route('operator.sanggahan.decide', $sanggahan), [
                    'decision' => $decision->value,
                    'catatan_keputusan' => '',
                ])
                ->assertSessionHasErrors([
                    'catatan_keputusan' => 'Catatan keputusan wajib diisi.',
                ]);

            $this->actingAs($operator)
                ->post(route('operator.sanggahan.decide', $sanggahan), [
                    'decision' => $decision->value,
                    'catatan_keputusan' => '   ',
                ])
                ->assertSessionHasErrors([
                    'catatan_keputusan' => 'Catatan keputusan wajib diisi.',
                ]);

            $this->actingAs($operator)
                ->post(route('operator.sanggahan.decide', $sanggahan), [
                    'decision' => $decision->value,
                    'catatan_keputusan' => str_repeat('a', 2001),
                ])
                ->assertSessionHasErrors([
                    'catatan_keputusan' => 'Catatan keputusan maksimal 2000 karakter.',
                ]);
        }

        $this->actingAs($operator)
            ->post(route('operator.sanggahan.decide', $sanggahan), [
                'decision' => 'LAINNYA',
                'catatan_keputusan' => 'Catatan yang cukup jelas.',
            ])
            ->assertSessionHasErrors('decision');

        $this->assertSame(SanggahanStatus::DIAJUKAN, $sanggahan->fresh()->status);
        $this->assertDatabaseMissing('verification_logs', [
            'application_id' => $application->id,
            'action' => 'sanggahan_granted',
        ]);
    }

    public function test_decided_sanggahan_cannot_be_changed_by_any_route(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $afterGrant = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();
        $application = $this->tmsApplication($this->student(), $periode);
        $sanggahan = $this->sanggahanFor($application);

        $this->actingAs($operator)->post(route('operator.sanggahan.decide', $sanggahan), [
            'decision' => SanggahanStatus::DIKABULKAN->value,
            'catatan_keputusan' => 'Keputusan pertama.',
        ])->assertSessionHas('success');

        $decidedAt = $sanggahan->fresh()->decided_at;
        $logCount = VerificationLog::query()->where('application_id', $application->id)->count();

        $this->actingAs($afterGrant)
            ->post(route('operator.sanggahan.decide', $sanggahan), [
                'decision' => SanggahanStatus::DITOLAK->value,
                'catatan_keputusan' => 'Mencoba mengubah keputusan.',
            ])
            ->assertSessionHasErrors('decision');

        $this->actingAs($afterGrant)
            ->put(route('operator.sanggahan.decide', $sanggahan), [
                'decision' => SanggahanStatus::DITOLAK->value,
                'catatan_keputusan' => 'Mencoba mengubah keputusan.',
            ])
            ->assertMethodNotAllowed();

        $this->actingAs($afterGrant)
            ->delete(route('operator.sanggahan.decide', $sanggahan))
            ->assertMethodNotAllowed();

        $sanggahan->refresh();
        $this->assertSame(SanggahanStatus::DIKABULKAN, $sanggahan->status);
        $this->assertSame($operator->id, $sanggahan->decided_by);
        $this->assertSame('Keputusan pertama.', $sanggahan->catatan_keputusan);
        $this->assertTrue($decidedAt->equalTo($sanggahan->decided_at));
        $this->assertSame($logCount, VerificationLog::query()->where('application_id', $application->id)->count());
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);
    }

    public function test_only_kabupaten_operator_of_the_application_region_can_decide(): void
    {
        $periode = $this->periode();
        $application = $this->tmsApplication($this->student(), $periode);
        $sanggahan = $this->sanggahanFor($application);

        $outsideOperator = $this->kabupatenOperator($this->otherKabupaten());
        $agencyOperator = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $student = $this->student('Mahasiswa Ketiga');
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'two_factor_confirmed_at' => now(),
        ]);

        $payload = [
            'decision' => SanggahanStatus::DIKABULKAN->value,
            'catatan_keputusan' => 'Mencoba memutuskan dari luar kewenangan.',
        ];

        $this->actingAs($outsideOperator)
            ->post(route('operator.sanggahan.decide', $sanggahan), $payload)
            ->assertForbidden();

        $this->actingAs($agencyOperator)
            ->post(route('operator.sanggahan.decide', $sanggahan), $payload)
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('operator.sanggahan.decide', $sanggahan), $payload)
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->post(route('operator.sanggahan.decide', $sanggahan), $payload)
            ->assertForbidden();

        $sanggahan->refresh();
        $this->assertSame(SanggahanStatus::DIAJUKAN, $sanggahan->status);
        $this->assertNull($sanggahan->decided_by);
        $this->assertSame(ApplicationStatus::TMS, $application->fresh()->status);
    }

    public function test_granted_agency_can_verify_again_after_registration_closed_and_reach_selection(): void
    {
        $kabupatenOperator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode([
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-10-31',
        ]);
        $student = $this->student();
        $application = $this->tmsApplication($student, $periode);
        $sanggahan = $this->sanggahanFor($application);
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($kabupatenOperator)->post(route('operator.sanggahan.decide', $sanggahan), [
            'decision' => SanggahanStatus::DIKABULKAN->value,
            'catatan_keputusan' => 'Dokumen sudah sesuai, dinas dapat menilai ulang.',
        ])->assertSessionHas('success');

        $this->assessAllDocuments($application, $dukcapil, DocumentVerificationResult::MEMENUHI);

        $this->actingAs($dukcapil)
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::MS->value,
            ])
            ->assertRedirect(route('operator.applications.index'))
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->status);
        $this->assertCount(3, $application->agencyVerifications);
        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'dukcapil',
            'decision' => VerificationDecision::MS->value,
        ]);
    }

    public function test_reopened_agency_deciding_tms_again_is_final_and_blocks_a_second_sanggahan(): void
    {
        $kabupatenOperator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode([
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-10-31',
        ]);
        $student = $this->student();
        $application = $this->tmsApplication($student, $periode);
        $sanggahan = $this->sanggahanFor($application);
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($kabupatenOperator)->post(route('operator.sanggahan.decide', $sanggahan), [
            'decision' => SanggahanStatus::DIKABULKAN->value,
            'catatan_keputusan' => 'Dokumen akan dinilai ulang.',
        ])->assertSessionHas('success');

        $this->assessAllDocuments($application, $dukcapil, DocumentVerificationResult::TIDAK_MEMENUHI, 'KTP masih tidak terbaca.');

        $this->actingAs($dukcapil)
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::TMS->value,
                'notes' => 'KTP masih tidak terbaca setelah penilaian ulang.',
            ])
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(ApplicationStatus::TMS, $application->status);
        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'dukcapil',
            'decision' => VerificationDecision::TMS->value,
        ]);

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), [
                'application_id' => $application->id,
                'uraian' => 'Saya ingin mengajukan sanggahan kedua atas keputusan TMS yang sama.',
                'pernyataan_kebenaran' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Sanggahan untuk pengajuan ini sudah pernah diajukan dan tidak dapat diubah.');

        $this->assertSame(1, Sanggahan::query()->where('application_id', $application->id)->count());

        $this->actingAs($dukcapil)
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::MS->value,
            ])
            ->assertForbidden();
    }

    public function test_verification_gate_is_unchanged_for_applications_without_granted_sanggahan(): void
    {
        $periode = $this->periode([
            'nama' => config('kartu_hebat.current_period'),
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-10-31',
        ]);
        $student = $this->student();
        $application = $this->tmsApplication($student, $periode);
        $application->update(['status' => ApplicationStatus::VERIFIKASI_DINAS]);
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($dukcapil)
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::MS->value,
            ])
            ->assertSessionHasErrors([
                'decision' => 'Pengajuan periode historis tidak dapat diverifikasi ulang.',
            ]);

        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);
    }

    public function test_selection_decision_allows_granted_sanggahan_even_outside_the_configured_active_period(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode([
            'tahun' => 2025,
            'nama' => 'Periode Beasiswa 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'tanggal_mulai_sanggah' => '2025-11-10',
            'tanggal_selesai_sanggah' => '2025-11-12',
            'status' => 'ditutup',
        ]);

        $application = $this->tmsApplication($this->student(), $periode);
        $application->update(['status' => ApplicationStatus::SELEKSI_KABUPATEN]);
        Selection::query()->create([
            'application_id' => $application->id,
            'final_score' => 82.5,
        ]);
        $this->sanggahanFor($application, SanggahanStatus::DIKABULKAN, attributes: [
            'catatan_keputusan' => 'Sanggahan dikabulkan.',
            'decided_at' => now(),
        ]);

        $this->actingAs($operator)
            ->post(route('operator.selection.store', $application), [
                'decision' => ApplicationStatus::DITERIMA->value,
                'notes' => 'Diterima setelah verifikasi ulang.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('DITERIMA', $application->fresh()->selection->manual_decision);
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->fresh()->status);

        $otherApplication = $this->tmsApplication($this->student('Mahasiswa Tanpa Sanggahan'), $periode);
        $otherApplication->update(['status' => ApplicationStatus::SELEKSI_KABUPATEN]);
        Selection::query()->create([
            'application_id' => $otherApplication->id,
            'final_score' => 70.0,
        ]);

        $this->actingAs($operator)
            ->post(route('operator.selection.store', $otherApplication), [
                'decision' => ApplicationStatus::DITERIMA->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Penetapan penerima untuk periode lampau telah dikunci dan tidak dapat diubah.');

        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $otherApplication->fresh()->status);
    }

    public function test_application_detail_page_marks_granted_sanggahan_with_a_link_to_its_detail(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();
        $application = $this->tmsApplication($this->student(), $periode);
        $sanggahan = $this->sanggahanFor($application, SanggahanStatus::DIKABULKAN, attributes: [
            'catatan_keputusan' => 'Sanggahan diterima.',
            'decided_by' => $operator->id,
            'decided_at' => now(),
        ]);

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('Hasil Sanggahan')
            ->assertSee(route('operator.sanggahan.show', $sanggahan));

        $plainApplication = $this->tmsApplication($this->student('Mahasiswa Biasa'), $periode);

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $plainApplication))
            ->assertOk()
            ->assertDontSee('Hasil Sanggahan');
    }

    public function test_reopened_application_moves_to_the_sanggahan_queue_from_a_historical_period(): void
    {
        $periode = $this->periode([
            'tahun' => 2025,
            'nama' => 'Periode Beasiswa 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-10-31',
            'tanggal_mulai_sanggah' => '2025-11-10',
            'tanggal_selesai_sanggah' => '2025-11-12',
        ]);

        $student = $this->student();
        $application = $this->tmsApplication($student, $periode);
        $this->sanggahanFor($application, SanggahanStatus::DIKABULKAN, attributes: [
            'catatan_keputusan' => 'Sanggahan dikabulkan.',
            'decided_at' => now(),
        ]);
        $application->update(['status' => ApplicationStatus::VERIFIKASI_DINAS]);
        $application->agencyVerifications()->where('agency', 'dukcapil')->delete();

        $control = $this->tmsApplication($this->student('Mahasiswa Tanpa Sanggahan'), $periode);
        $control->update(['status' => ApplicationStatus::VERIFIKASI_DINAS]);

        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertDontSee($control->nomor_pengajuan);

        $this->actingAs($dukcapil)
            ->get(route('operator.applications.index'))
            ->assertOk()
            ->assertDontSee($application->nomor_pengajuan)
            ->assertDontSee($control->nomor_pengajuan);
    }

    public function test_pending_sanggahan_detail_offers_kabul_and_tolak_form(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();
        $application = $this->tmsApplication($this->student(), $periode);
        $sanggahan = $this->sanggahanFor($application);

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.show', $sanggahan))
            ->assertOk()
            ->assertSee('Kabulkan Sanggahan')
            ->assertSee('Tolak Sanggahan')
            ->assertSee('Catatan Keputusan')
            ->assertSee(route('operator.sanggahan.decide', $sanggahan));
    }

    private function assessAllDocuments(
        Application $application,
        User $verifier,
        DocumentVerificationResult $result,
        ?string $notes = null,
    ): void {
        foreach ($application->documents as $document) {
            $this->actingAs($verifier)
                ->post(route('operator.applications.documents.verify', [$application, $document]), [
                    'result' => $result->value,
                    'notes' => $notes,
                ])
                ->assertSessionHasNoErrors();
        }
    }

    private function mainKabupaten(): int
    {
        return (int) Village::query()->with('kecamatan')->firstOrFail()->kabupaten_id;
    }

    private function otherKabupaten(): int
    {
        $kabupaten = Kabupaten::query()->create([
            'code' => '9999',
            'name' => 'Kabupaten Lain',
        ]);

        $kecamatan = Kecamatan::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'code' => '999901',
            'name' => 'Kecamatan Lain',
        ]);

        Village::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'kecamatan_id' => $kecamatan->id,
            'code' => '9999010001',
            'name' => 'Desa Lain',
            'type' => 'desa',
        ]);

        return $kabupaten->id;
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

    private function agencyOperator(UserRole $role): User
    {
        if (! isset($this->agencyOperators[$role->value])) {
            $operator = User::factory()->create([
                'role' => $role,
                'kabupaten_id' => $this->mainKabupaten(),
            ]);
            $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

            $this->agencyOperators[$role->value] = $operator;
        }

        return $this->agencyOperators[$role->value];
    }

    private function student(string $name = 'Fajar Pratama'): User
    {
        $village = Village::query()->where('kabupaten_id', $this->mainKabupaten())->firstOrFail();

        $student = User::factory()->create([
            'name' => $name,
            'role' => UserRole::MAHASISWA,
        ]);

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
            'status_kependudukan' => 'tidak_sesuai',
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

    /**
     * @param  array<string, VerificationDecision>  $decisions
     */
    private function tmsApplication(
        User $student,
        Periode $periode,
        ApplicationType $type = ApplicationType::AKADEMIK,
        ?array $decisions = null,
    ): Application {
        $decisions ??= [
            'dukcapil' => VerificationDecision::TMS,
            'pendidikan' => VerificationDecision::MS,
            'bkpsdm' => VerificationDecision::MS,
        ];

        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'KAT-'.Str::random(10),
            'application_type' => $type,
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
            'status' => 'rejected',
            'submitted_at' => now(),
        ]);

        $application = Application::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $pendaftaran->nomor_pendaftaran,
            'periode' => $periode->nama,
            'status' => ApplicationStatus::TMS,
            'application_type' => $type,
            'submitted_at' => now(),
            'locked_at' => now(),
            'catatan' => 'Keputusan TMS dari dinas.',
        ]);

        $documentType = DocumentType::query()->create([
            'code' => 'KTP-'.Str::random(6),
            'name' => 'Kartu Tanda Penduduk',
            'is_required' => true,
            'is_active' => true,
        ]);

        $document = Document::query()->create([
            'application_id' => $application->id,
            'document_type_id' => $documentType->id,
            'path' => 'documents/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'checksum' => Str::random(64),
        ]);

        $application->setRelation('documents', collect([$document]));

        foreach ($decisions as $agency => $decision) {
            $verifier = $this->agencyOperator(
                collect(UserRole::cases())
                    ->first(fn (UserRole $role) => $role->isAgency() && $role->agencyCode() === $agency),
            );

            AgencyVerification::query()->create([
                'application_id' => $application->id,
                'verifier_id' => $verifier->id,
                'agency' => $agency,
                'decision' => $decision,
                'notes' => $decision === VerificationDecision::TMS
                    ? 'NIK tidak sesuai dengan dokumen kependudukan.'
                    : 'Dokumen sesuai.',
                'verified_at' => now(),
            ]);
        }

        return $application->fresh(['documents', 'agencyVerifications']);
    }

    private function documentVerification(
        Application $application,
        Document $document,
        string $stage,
        int $round,
        DocumentVerificationResult $result,
        ?string $notes = null,
    ): DocumentVerification {
        return DocumentVerification::query()->create([
            'application_id' => $application->id,
            'document_id' => $document->id,
            'verifier_id' => $this->agencyOperator(
                collect(UserRole::cases())
                    ->first(fn (UserRole $role) => $role->isAgency() && $role->agencyCode() === $stage),
            )->id,
            'stage' => $stage,
            'round' => $round,
            'result' => $result,
            'notes' => $notes,
            'verified_at' => now(),
        ]);
    }

    private function sanggahanFor(
        Application $application,
        SanggahanStatus $status = SanggahanStatus::DIAJUKAN,
        ?string $uraian = null,
        array $attributes = [],
    ): Sanggahan {
        return Sanggahan::query()->create(array_merge([
            'application_id' => $application->id,
            'periode_id' => $application->pendaftaran?->periode_id ?? Periode::query()->firstOrFail()->id,
            'submitted_by' => $application->mahasiswa_id,
            'status' => $status,
            'uraian' => $uraian ?? 'Saya keberatan atas keputusan TMS karena dokumen yang saya unggah sudah sesuai.',
        ], $attributes));
    }
}
