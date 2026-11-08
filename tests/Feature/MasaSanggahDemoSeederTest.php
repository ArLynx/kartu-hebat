<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\Sanggahan;
use App\Models\Selection;
use App\Models\User;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\MasaSanggahDemoSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasaSanggahDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('kartu_hebat.document_disk', 'local'));
        Storage::fake('public');

        $this->seed(RegionSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);
    }

    public function test_seeder_prepares_every_masa_sanggah_scenario(): void
    {
        $this->seed(MasaSanggahDemoSeeder::class);

        $belumMenyanggah = Application::query()->where('nomor_pengajuan', 'KHM-SG-001')->firstOrFail();
        $this->assertSame(ApplicationStatus::TMS, $belumMenyanggah->status);
        $this->assertNull($belumMenyanggah->sanggahan);

        $antrean = Application::query()->where('nomor_pengajuan', 'KHM-SG-002')->firstOrFail();
        $this->assertSame(ApplicationStatus::TMS, $antrean->status);
        $this->assertSame(SanggahanStatus::DIAJUKAN, $antrean->sanggahan?->status);

        $dikabulkan = Application::query()->where('nomor_pengajuan', 'KHM-SG-003')->firstOrFail();
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $dikabulkan->status);
        $this->assertSame(SanggahanStatus::DIKABULKAN, $dikabulkan->sanggahan?->status);

        $arsip = Application::query()->where('nomor_pengajuan', 'KHM-ARSIP-001')->firstOrFail();
        $this->assertSame(ApplicationStatus::DITERIMA, $arsip->status);
        $this->assertNotNull($arsip->selection?->published_at);
        $this->assertSame('ditutup', $arsip->resolvedPeriode()?->status);
    }

    public function test_seeder_opens_the_masa_sanggah_window_for_today(): void
    {
        $this->seed(MasaSanggahDemoSeeder::class);

        $periode = Application::query()
            ->where('nomor_pengajuan', 'KHM-SG-001')
            ->firstOrFail()
            ->resolvedPeriode();

        $this->assertNotNull($periode);
        $this->assertTrue($periode->isMasaSanggahOpen());
        $this->assertSame('aktif', $periode->status);
    }

    public function test_granted_scenario_only_reopens_the_agency_that_decided_tms(): void
    {
        $this->seed(MasaSanggahDemoSeeder::class);

        $application = Application::query()->where('nomor_pengajuan', 'KHM-SG-003')->firstOrFail();

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

        $this->assertSame(0, $application->agencyVerifications()
            ->where('decision', VerificationDecision::TMS->value)
            ->count());
    }

    public function test_seeder_is_idempotent_and_resets_manual_experiments(): void
    {
        $this->seed(MasaSanggahDemoSeeder::class);

        $belumMenyanggah = Application::query()->where('nomor_pengajuan', 'KHM-SG-001')->firstOrFail();

        // Meniru percobaan manual: mahasiswa mengajukan sanggahan lalu aplikasi diterima.
        Sanggahan::query()->create([
            'application_id' => $belumMenyanggah->id,
            'periode_id' => $belumMenyanggah->pendaftaran->periode_id,
            'submitted_by' => $belumMenyanggah->mahasiswa_id,
            'status' => SanggahanStatus::DIAJUKAN,
            'uraian' => 'Sanggahan dari percobaan manual yang seharusnya dibersihkan seeder.',
        ]);
        Selection::query()->create([
            'application_id' => $belumMenyanggah->id,
            'final_score' => 80,
            'manual_decision' => ApplicationStatus::DITERIMA->value,
            'published_at' => now(),
        ]);

        $this->seed(MasaSanggahDemoSeeder::class);

        $this->assertSame(0, Sanggahan::query()->where('application_id', $belumMenyanggah->id)->count());
        $this->assertSame(SanggahanStatus::DIAJUKAN, Application::query()
            ->where('nomor_pengajuan', 'KHM-SG-002')
            ->firstOrFail()
            ->sanggahan
            ?->status);
        $this->assertNull($belumMenyanggah->fresh()->sanggahan);
        $this->assertNull($belumMenyanggah->fresh()->selection);

        $this->assertSame(1, Application::query()->where('nomor_pengajuan', 'KHM-SG-001')->count());
        $this->assertSame(1, Announcement::query()->count());
    }

    public function test_seeder_refuses_to_run_outside_local_or_testing(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MasaSanggahDemoSeeder hanya boleh dijalankan di lingkungan local/testing.');

        (new MasaSanggahDemoSeeder)->run();
    }

    public function test_seeded_students_can_open_the_sanggahan_page(): void
    {
        $this->seed(MasaSanggahDemoSeeder::class);

        $student = User::query()->where('email', 'sanggahan.ajukan@kartuhebat.test')->firstOrFail();
        $this->assertSame(UserRole::MAHASISWA, $student->role);

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee('KHM-SG-001')
            ->assertSee('Uraian Sanggahan');
    }

    public function test_seeded_kabupaten_operator_sees_the_pending_sanggahan(): void
    {
        $this->seed(MasaSanggahDemoSeeder::class);

        $operator = User::query()->where('email', 'kabupaten@kartuhebat.test')->firstOrFail();

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.index'))
            ->assertOk()
            ->assertSee('KHM-SG-002');
    }

    public function test_seeded_reopened_application_appears_in_the_agency_sanggahan_queue(): void
    {
        $this->seed(MasaSanggahDemoSeeder::class);

        $dukcapil = User::query()->where('email', 'dukcapil@kartuhebat.test')->firstOrFail();

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertOk()
            ->assertSee('KHM-SG-003');

        $this->actingAs($dukcapil)
            ->get(route('operator.applications.index'))
            ->assertOk()
            ->assertDontSee('KHM-SG-003')
            ->assertSee('Buka Antrean Sanggahan');
    }

    public function test_seeded_archive_result_is_searchable_on_the_public_page(): void
    {
        $this->seed(MasaSanggahDemoSeeder::class);

        $this->get(route('public.results', ['nomor' => 'KHM-ARSIP-001']))
            ->assertOk()
            ->assertSee('KHM-ARSIP-001')
            ->assertSee('Diterima');
    }
}
