<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\DocumentVerification;
use App\Models\Pendaftaran;
use App\Models\Selection;
use App\Models\VerificationLog;
use App\Services\AgencyVerificationService;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DisabilityTrackSeeder;
use Database\Seeders\JalurBeasiswaSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\PrestasiTrackSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Bekukan waktu agar periode aktif dan tanggal verifikasi konsisten.
        $this->travelTo(Carbon::parse('2026-08-15 09:00:00'));

        Storage::fake(config('kartu_hebat.document_disk'));

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);
        $this->seed(JalurBeasiswaSeeder::class);
        $this->seed(DisabilityTrackSeeder::class);
        $this->seed(PrestasiTrackSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    public function test_every_application_is_attached_to_a_submitted_pendaftaran(): void
    {
        $applications = Application::query()->with('pendaftaran')->get();

        $this->assertGreaterThan(0, $applications->count(), 'Seeder tidak menghasilkan data pengajuan.');
        $this->assertSame(0, Application::query()->whereNull('pendaftaran_id')->count(), 'Masih ada pengajuan tanpa pendaftaran.');
        $this->assertSame(0, Application::query()->where('status', ApplicationStatus::DRAFT->value)->count(), 'Data demo tidak boleh berhenti pada status DRAFT.');

        foreach ($applications as $application) {
            $this->assertInstanceOf(Pendaftaran::class, $application->pendaftaran, "Pengajuan {$application->nomor_pengajuan} tidak memiliki pendaftaran.");
            $this->assertNotNull($application->pendaftaran->nomor_pendaftaran, "Pendaftaran pengajuan {$application->nomor_pengajuan} belum bernomor.");
            $this->assertNotNull($application->pendaftaran->submitted_at, "Pendaftaran pengajuan {$application->nomor_pengajuan} belum dikirim.");
        }
    }

    public function test_every_application_is_verified_by_bkpsdm(): void
    {
        foreach (ApplicationType::cases() as $type) {
            $count = AgencyVerification::query()
                ->where('agency', AgencyVerificationService::STAGE_BKPSDM)
                ->whereHas('application', fn ($query) => $query->where('application_type', $type->value))
                ->count();

            $this->assertGreaterThan(0, $count, "BKPSDM belum memverifikasi satu pun pengajuan jalur {$type->value}.");
        }
    }

    public function test_applications_in_selection_stage_have_all_required_agencies_and_scores(): void
    {
        $applications = Application::query()
            ->where('status', ApplicationStatus::SELEKSI_KABUPATEN->value)
            ->with(['agencyVerifications', 'selection'])
            ->get();

        $this->assertGreaterThan(0, $applications->count(), 'Tidak ada data yang mencapai tahap seleksi kabupaten.');

        foreach ($applications as $application) {
            $required = AgencyVerificationService::requiredAgencies($application);
            $verified = $application->agencyVerifications->pluck('agency')->all();

            foreach ($required as $agency) {
                $this->assertContains($agency, $verified, "Pengajuan {$application->nomor_pengajuan} belum diverifikasi {$agency}.");
            }

            $this->assertNotNull($application->selection, "Pengajuan {$application->nomor_pengajuan} belum memiliki skor seleksi.");
            $this->assertGreaterThan(0, (float) $application->selection->final_score, "Skor seleksi pengajuan {$application->nomor_pengajuan} masih nol.");
            $this->assertNotNull($application->selection->rank, "Peringkat pengajuan {$application->nomor_pengajuan} belum dihitung.");
        }
    }

    public function test_selection_decisions_are_left_empty_for_kabupaten_operator(): void
    {
        $this->assertSame(
            0,
            Application::query()
                ->whereIn('status', [
                    ApplicationStatus::DITERIMA->value,
                    ApplicationStatus::DITOLAK->value,
                    ApplicationStatus::TMS->value,
                ])
                ->count(),
            'Seeder tidak boleh mengisi keputusan akhir; antrean seleksi harus tetap terbuka untuk operator kabupaten.',
        );

        $this->assertSame(
            0,
            Selection::query()
                ->where(fn ($query) => $query
                    ->whereNotNull('manual_decision')
                    ->orWhereNotNull('decided_at')
                    ->orWhereNotNull('decided_by')
                    ->orWhereNotNull('published_at'))
                ->count(),
            'Data seleksi seharusnya belum memiliki keputusan, pemutus, maupun tanggal publikasi.',
        );

        $this->assertSame(
            0,
            VerificationLog::query()->where('action', 'selection_result_published')->count(),
            'Belum boleh ada log publikasi hasil seleksi.',
        );
    }

    public function test_applications_in_verification_stage_only_have_partial_agency_decisions(): void
    {
        $applications = Application::query()
            ->where('status', ApplicationStatus::VERIFIKASI_DINAS->value)
            ->with('agencyVerifications')
            ->get();

        $this->assertGreaterThan(0, $applications->count(), 'Tidak ada data pada tahap verifikasi dinas.');

        foreach ($applications as $application) {
            $required = AgencyVerificationService::requiredAgencies($application);

            $this->assertLessThan(
                count($required),
                $application->agencyVerifications->count(),
                "Pengajuan {$application->nomor_pengajuan} sudah lengkap sehingga seharusnya keluar dari VERIFIKASI_DINAS.",
            );
        }
    }

    public function test_final_agency_verification_promotes_application_without_deciding_result(): void
    {
        $applications = Application::query()
            ->where('status', ApplicationStatus::SELEKSI_KABUPATEN->value)
            ->with('verificationLogs')
            ->get();

        $this->assertGreaterThan(0, $applications->count(), 'Tidak ada pengajuan yang lolos ke seleksi kabupaten.');

        foreach ($applications as $application) {
            $logs = $application->verificationLogs;

            $this->assertTrue(
                $logs->contains(fn (VerificationLog $log) => $log->action === 'submitted'),
                "Pengajuan {$application->nomor_pengajuan} tidak memiliki log pengiriman.",
            );
            $this->assertTrue(
                $logs->contains(fn (VerificationLog $log) => $log->action === 'verification_MS'),
                "Pengajuan {$application->nomor_pengajuan} tidak memiliki log lolos verifikasi dinas.",
            );
            $this->assertFalse(
                $logs->contains(fn (VerificationLog $log) => $log->action === 'selection_result_published'),
                "Pengajuan {$application->nomor_pengajuan} sudah memiliki log publikasi hasil.",
            );
            $this->assertNull($application->catatan, "Pengajuan {$application->nomor_pengajuan} seharusnya belum memiliki catatan keputusan.");
        }
    }

    public function test_document_assessments_exist_for_every_agency_stage(): void
    {
        $applications = Application::query()
            ->where('status', ApplicationStatus::SELEKSI_KABUPATEN->value)
            ->with(['agencyVerifications', 'documentVerifications'])
            ->get();

        $this->assertGreaterThan(0, $applications->count(), 'Tidak ada pengajuan yang sudah selesai diverifikasi dinas.');

        foreach ($applications as $application) {
            $stages = $application->documentVerifications->pluck('stage')->unique()->all();
            $documentCount = $application->documents()->count();

            $this->assertGreaterThan(0, $documentCount, "Pengajuan {$application->nomor_pengajuan} tidak memiliki dokumen.");

            foreach (AgencyVerificationService::requiredAgencies($application) as $stage) {
                $this->assertContains($stage, $stages, "Pengajuan {$application->nomor_pengajuan} belum dinilai tahap {$stage}.");
            }
        }
    }

    public function test_seeder_can_be_run_repeatedly_without_duplicating_data(): void
    {
        $counts = [
            Application::query()->count(),
            Pendaftaran::query()->count(),
            AgencyVerification::query()->count(),
            DocumentVerification::query()->count(),
            VerificationLog::query()->count(),
            Selection::query()->count(),
        ];

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($counts, [
            Application::query()->count(),
            Pendaftaran::query()->count(),
            AgencyVerification::query()->count(),
            DocumentVerification::query()->count(),
            VerificationLog::query()->count(),
            Selection::query()->count(),
        ]);
    }
}
