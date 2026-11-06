<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentVerificationResult;
use App\Enums\SanggahanStatus;
use App\Enums\VerificationDecision;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\DocumentVerification;
use App\Models\Sanggahan;
use App\Models\User;
use App\Services\AgencyVerificationService;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\MasaSanggahDemoSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menjalankan seluruh perjalanan Masa Sanggah di atas data demo, memakai
 * route yang sama dengan yang dipakai pengguna.
 */
class MasaSanggahDemoJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('kartu_hebat.document_disk', 'local'));
        Storage::fake('public');
        Notification::fake();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);
        $this->seed(MasaSanggahDemoSeeder::class);
    }

    public function test_student_submits_a_sanggahan_from_the_demo_scenario(): void
    {
        $student = User::query()->where('email', 'sanggahan.ajukan@kartuhebat.test')->firstOrFail();
        $application = Application::query()->where('nomor_pengajuan', 'KHM-SG-001')->firstOrFail();

        $this->actingAs($student)
            ->get(route('mahasiswa.sanggahan.index'))
            ->assertOk()
            ->assertSee('KHM-SG-001')
            ->assertSee('Uraian Sanggahan');

        $this->actingAs($student)
            ->post(route('mahasiswa.sanggahan.store'), [
                'application_id' => $application->id,
                'uraian' => 'KTP asli saya jelas dan terbaca. Mohon dinas memeriksa kembali berkas yang saya unggah.',
                'pernyataan_kebenaran' => '1',
            ])
            ->assertRedirect(route('mahasiswa.sanggahan.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('sanggahans', [
            'application_id' => $application->id,
            'status' => SanggahanStatus::DIAJUKAN->value,
        ]);

        $this->assertSame(ApplicationStatus::TMS, $application->fresh()->status);
    }

    public function test_kabupaten_operator_grants_the_pending_demo_sanggahan_and_only_that_agency_reopens(): void
    {
        $operator = User::query()->where('email', 'kabupaten@kartuhebat.test')->firstOrFail();
        $application = Application::query()->where('nomor_pengajuan', 'KHM-SG-002')->firstOrFail();
        $sanggahan = Sanggahan::query()->where('application_id', $application->id)->firstOrFail();

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.index'))
            ->assertOk()
            ->assertSee('KHM-SG-002');

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.show', $sanggahan))
            ->assertOk()
            ->assertSee('Dokumen KHS terbaca sebagai milik program studi lain.');

        $this->actingAs($operator)
            ->post(route('operator.sanggahan.decide', $sanggahan), [
                'decision' => SanggahanStatus::DIKABULKAN->value,
                'catatan_keputusan' => 'KHS terbukti atas nama mahasiswa. Keputusan TMS dianulir.',
            ])
            ->assertRedirect(route('operator.sanggahan.show', $sanggahan))
            ->assertSessionHas('success');

        $application->refresh();

        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);
        $this->assertNull($application->catatan);

        $this->assertDatabaseMissing('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'pendidikan',
        ]);

        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'dukcapil',
            'decision' => VerificationDecision::MS->value,
        ]);

        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'bkpsdm',
            'decision' => VerificationDecision::MS->value,
        ]);
    }

    public function test_reopened_agency_reverifies_the_demo_application_until_it_reaches_selection(): void
    {
        $dukcapil = User::query()->where('email', 'dukcapil@kartuhebat.test')->firstOrFail();
        $application = Application::query()->where('nomor_pengajuan', 'KHM-SG-003')->firstOrFail();
        $round = AgencyVerificationService::currentRound($application);

        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        foreach ($application->documents()->get() as $document) {
            $this->actingAs($dukcapil)
                ->post(route('operator.applications.documents.verify', [$application, $document]), [
                    'result' => DocumentVerificationResult::MEMENUHI->value,
                ])
                ->assertSessionHas('success');
        }

        $this->assertSame(
            $application->documents()->count(),
            DocumentVerification::query()
                ->where('application_id', $application->id)
                ->where('stage', 'dukcapil')
                ->where('round', $round)
                ->count(),
        );

        $this->actingAs($dukcapil)
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::MS->value,
            ])
            ->assertRedirect(route('operator.applications.index'))
            ->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->fresh()->status);
        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'dukcapil',
            'decision' => VerificationDecision::MS->value,
        ]);
    }

    public function test_demo_archive_result_keeps_its_original_announcement_while_a_correction_is_published_separately(): void
    {
        $archiveAnnouncement = Announcement::query()->firstOrFail();
        $originalPath = $archiveAnnouncement->attachment_path;

        $this->get(route('public.results', ['nomor' => 'KHM-ARSIP-001']))
            ->assertOk()
            ->assertSee('KHM-ARSIP-001')
            ->assertSee('Diterima');

        $this->assertDatabaseHas('announcements', [
            'id' => $archiveAnnouncement->id,
            'attachment_path' => $originalPath,
        ]);

        $this->assertSame(1, Announcement::query()->count());
        $this->assertSame(0, Application::query()
            ->whereNull('applications.pendaftaran_id')
            ->count());
    }
}
