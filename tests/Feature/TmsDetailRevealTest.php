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
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use App\Notifications\ApplicationStatusChanged;
use App\Services\AgencyVerificationService;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Rincian keputusan TMS (dinas penolak, catatan verifikator, dan berkas yang
 * dinilai tidak memenuhi) ditahan sampai Masa Sanggah periode dimulai.
 */
class TmsDetailRevealTest extends TestCase
{
    use RefreshDatabase;

    private const DINAS_DUKCAPIL = 'Dinas Kependudukan dan Pencatatan Sipil';

    private const CATATAN_TMS = 'NIK tidak sesuai dengan dokumen kependudukan.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        Notification::fake();
        $this->travelTo('2026-11-01');
    }

    public function test_submit_page_hides_tms_details_before_masa_sanggah_starts(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-12-02',
            'tanggal_selesai_sanggah' => '2026-12-04',
        ]);
        $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $response = $this->actingAs($student)->get(route('mahasiswa.submit.index'));

        $response->assertOk();
        $response->assertSee('Menunggu Pengumuman Hasil');
        $response->assertSee('Rincian hasil verifikasi akan diumumkan saat Masa Sanggah dimulai pada 02 Dec 2026.');
        $response->assertDontSee('Tidak Memenuhi Syarat');
        $response->assertDontSee(self::DINAS_DUKCAPIL);
        $response->assertDontSee(self::CATATAN_TMS);
        $response->assertDontSee('Kartu Tanda Penduduk');
    }

    public function test_sanggahan_page_hides_tms_details_before_masa_sanggah_starts(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai_sanggah' => '2026-12-02',
            'tanggal_selesai_sanggah' => '2026-12-04',
        ]);
        $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $response = $this->actingAs($student)->get(route('mahasiswa.sanggahan.index'));

        $response->assertOk();
        $response->assertSee('Menunggu Pengumuman Hasil');
        $response->assertDontSee(self::DINAS_DUKCAPIL);
        $response->assertDontSee(self::CATATAN_TMS);
        $response->assertDontSee('Uraian Sanggahan');
    }

    public function test_dashboard_and_bukti_pendaftaran_hide_tms_status_and_notes_before_reveal(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-12-02',
            'tanggal_selesai_sanggah' => '2026-12-04',
        ]);
        $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $dashboard = $this->actingAs($student)->get(route('mahasiswa.dashboard'));
        $dashboard->assertOk();
        $dashboard->assertSee('Menunggu Pengumuman Hasil');
        $dashboard->assertDontSee('Catatan petugas:');
        $dashboard->assertDontSee(self::CATATAN_TMS);
        $dashboard->assertDontSee('Rejected');

        $bukti = $this->actingAs($student)->get(route('mahasiswa.bukti-pendaftaran.index'));
        $bukti->assertOk();
        $bukti->assertDontSee(self::CATATAN_TMS);
    }

    public function test_details_are_revealed_on_the_first_day_of_masa_sanggah(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-01',
            'tanggal_selesai_sanggah' => '2026-11-03',
        ]);
        [, $application] = $this->applicationFor($student, $periode, ApplicationStatus::TMS);
        $this->assessDocument($application, $student, DocumentVerificationResult::TIDAK_MEMENUHI, 'KTP hasil pindai tidak terbaca.');

        $response = $this->actingAs($student)->get(route('mahasiswa.submit.index'));

        $response->assertOk();
        $response->assertSee('Hasil Verifikasi: TMS');
        $response->assertSee(self::DINAS_DUKCAPIL);
        $response->assertSee(self::CATATAN_TMS);
        $response->assertSee('Berkas yang dinilai Tidak Memenuhi Syarat');
        $response->assertSee('Kartu Tanda Penduduk');
        $response->assertDontSee('Menunggu Pengumuman Hasil');
    }

    public function test_details_stay_visible_after_masa_sanggah_ends(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-10-10',
            'tanggal_selesai_sanggah' => '2026-10-12',
        ]);
        $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee(self::DINAS_DUKCAPIL)
            ->assertSee(self::CATATAN_TMS)
            ->assertDontSee('Menunggu Pengumuman Hasil');
    }

    public function test_details_stay_hidden_when_schedule_is_not_set_on_an_open_period(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => null,
            'tanggal_selesai_sanggah' => null,
        ]);
        $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->get(route('mahasiswa.submit.index'))
            ->assertOk()
            ->assertSee('Rincian hasil verifikasi akan diumumkan melalui Masa Sanggah periode ini.')
            ->assertDontSee(self::CATATAN_TMS);
    }

    public function test_details_are_shown_for_closed_periods_without_a_schedule(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-01-31',
            'tanggal_mulai_sanggah' => null,
            'tanggal_selesai_sanggah' => null,
            'status' => 'ditutup',
        ]);
        $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee(self::CATATAN_TMS)
            ->assertDontSee('Menunggu Pengumuman Hasil');
    }

    public function test_existing_sanggahan_reveals_details_even_before_the_window(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai_sanggah' => '2026-12-02',
            'tanggal_selesai_sanggah' => '2026-12-04',
        ]);
        [, $application] = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $application->sanggahan()->create([
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIAJUKAN,
            'uraian' => 'Sanggahan yang sudah terlanjur diajukan sebelum jadwal diubah pengelola.',
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee(self::CATATAN_TMS);
    }

    public function test_review_page_hides_document_assessments_before_reveal(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-12-02',
            'tanggal_selesai_sanggah' => '2026-12-04',
        ]);
        [$pendaftaran, $application] = $this->applicationFor($student, $periode, ApplicationStatus::TMS);

        $this->assessDocument($application, $student, DocumentVerificationResult::TIDAK_MEMENUHI, 'KTP terlihat buram.');

        $response = $this->actingAs($student)->get(route('mahasiswa.review.index'));

        $response->assertOk();
        $response->assertDontSee('KTP terlihat buram.');
        $response->assertDontSee('Tidak Memenuhi Syarat');
    }

    public function test_ms_applications_are_not_masked(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai_sanggah' => '2026-12-02',
            'tanggal_selesai_sanggah' => '2026-12-04',
        ]);
        $this->applicationFor($student, $periode, ApplicationStatus::SELEKSI_KABUPATEN);

        $this->actingAs($student)
            ->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Seleksi Kabupaten')
            ->assertDontSee('Menunggu Pengumuman Hasil');
    }

    public function test_tms_notification_before_reveal_does_not_leak_verifier_notes(): void
    {
        $student = $this->student();
        $periode = $this->periode([
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-12-02',
            'tanggal_selesai_sanggah' => '2026-12-04',
        ]);
        [, $application] = $this->applicationFor($student, $periode, ApplicationStatus::VERIFIKASI_DINAS);
        $village = $student->profile->village;

        $service = app(AgencyVerificationService::class);

        $service->submitDecision($application, $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL, $village), VerificationDecision::MS);
        $service->submitDecision($application, $this->agencyOperator(UserRole::OPERATOR_PENDIDIKAN, $village), VerificationDecision::MS);
        $service->submitDecision(
            $application,
            $this->agencyOperator(UserRole::OPERATOR_BKPSDM, $village),
            VerificationDecision::TMS,
            'Dokumen KK tidak terbaca.',
        );

        $this->assertSame(ApplicationStatus::TMS, $application->fresh()->status);

        Notification::assertSentTo(
            $student,
            ApplicationStatusChanged::class,
            function (ApplicationStatusChanged $notification): bool {
                return str_contains($notification->message, 'akan diumumkan melalui Masa Sanggah')
                    && ! str_contains($notification->message, 'Dokumen KK tidak terbaca.');
            },
        );
    }

    /**
     * @return array{0: Pendaftaran, 1: Application}
     */
    private function applicationFor(
        User $student,
        Periode $periode,
        ApplicationStatus $status,
    ): array {
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
            'nomor_pendaftaran' => 'KHM-REV-'.$periode->id.'-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'status' => $status === ApplicationStatus::TMS ? 'rejected' : 'verification',
            'submitted_at' => now(),
        ]);

        $application = Application::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $pendaftaran->nomor_pendaftaran,
            'periode' => $periode->nama,
            'status' => $status,
            'application_type' => ApplicationType::AKADEMIK,
            'catatan' => $status === ApplicationStatus::TMS ? self::CATATAN_TMS : null,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);

        if ($status === ApplicationStatus::TMS) {
            AgencyVerification::query()->create([
                'application_id' => $application->id,
                'verifier_id' => User::factory()->create(['role' => UserRole::OPERATOR_DUKCAPIL])->id,
                'agency' => 'dukcapil',
                'decision' => VerificationDecision::TMS,
                'notes' => self::CATATAN_TMS,
                'verified_at' => now(),
            ]);
        }

        return [$pendaftaran, $application];
    }

    private function assessDocument(
        Application $application,
        User $student,
        DocumentVerificationResult $result,
        string $notes,
    ): void {
        $type = DocumentType::query()->create([
            'code' => 'KTP',
            'name' => 'Kartu Tanda Penduduk',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $document = Document::query()->create([
            'application_id' => $application->id,
            'document_type_id' => $type->id,
            'uploaded_by' => $student->id,
            'path' => 'applications/'.$application->id.'/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'mime_type' => 'application/pdf',
            'size' => 4,
            'checksum' => hash('sha256', 'test'),
        ]);

        DocumentVerification::query()->create([
            'application_id' => $application->id,
            'document_id' => $document->id,
            'verifier_id' => User::factory()->create(['role' => UserRole::OPERATOR_DUKCAPIL])->id,
            'stage' => 'dukcapil',
            'round' => 1,
            'result' => $result,
            'notes' => $notes,
            'verified_at' => now(),
        ]);
    }

    private function student(): User
    {
        $village = Village::query()->with('kecamatan')->firstOrFail();

        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212123456789'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT),
            'nim' => 'KHM-REV-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jalan Pengujian',
        ]);

        return $student->fresh();
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

    private function agencyOperator(UserRole $role, Village $village): User
    {
        return User::factory()->create([
            'role' => $role,
            'kabupaten_id' => $village->kabupaten_id,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
        ]);
    }
}
