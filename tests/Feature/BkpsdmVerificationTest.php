<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\Application;
use App\Models\DocumentType;
use App\Models\MahasiswaProfile;
use App\Models\User;
use App\Models\Village;
use App\Services\AgencyVerificationService;
use App\Services\ApplicationWorkflowService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BkpsdmVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    public function test_bkpsdm_is_required_for_every_application_type(): void
    {
        $expectedCounts = [
            ApplicationType::AKADEMIK->value => 3,
            ApplicationType::TIDAK_MAMPU->value => 4,
            ApplicationType::DISABILITAS->value => 4,
            ApplicationType::NON_AKADEMIK->value => 4,
        ];

        foreach ($expectedCounts as $applicationType => $expectedCount) {
            $application = new Application(['application_type' => $applicationType]);
            $required = AgencyVerificationService::requiredAgencies($application);

            $this->assertContains('bkpsdm', $required, "BKPSDM harus wajib pada jalur {$applicationType}.");
            $this->assertCount($expectedCount, $required, "Jumlah dinas wajib jalur {$applicationType} tidak sesuai.");

            if ($applicationType === ApplicationType::TIDAK_MAMPU->value) {
                $this->assertContains('sosial', $required, 'Dinas Sosial harus wajib pada jalur TIDAK_MAMPU.');
            } else {
                $this->assertNotContains('sosial', $required, "Dinas Sosial tidak boleh wajib pada jalur {$applicationType}.");
            }
        }
    }

    public function test_superadmin_can_choose_bkpsdm_role_when_creating_operator(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.operators.create'))
            ->assertOk()
            ->assertSee('Operator BKPSDM');
    }

    public function test_bkpsdm_operator_can_access_operator_dashboard(): void
    {
        $village = Village::query()->firstOrFail();
        $bkpsdm = $this->operator(UserRole::OPERATOR_BKPSDM, $village);

        $this->actingAs($bkpsdm)
            ->get(route('operator.dashboard'))
            ->assertOk();
    }

    public function test_bkpsdm_is_the_final_required_agency_for_academic_track(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $service = app(AgencyVerificationService::class);

        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $pendidikan = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $bkpsdm = $this->operator(UserRole::OPERATOR_BKPSDM, $village);

        foreach ([$dukcapil, $pendidikan, $bkpsdm] as $operator) {
            foreach ($application->documents as $document) {
                $service->assessDocument(
                    $application,
                    $document,
                    $operator,
                    DocumentVerificationResult::MEMENUHI,
                );
            }
        }

        $this->assertSame(AgencyVerificationService::STAGE_BKPSDM, AgencyVerificationService::stageFor($bkpsdm));
        $this->assertTrue(AgencyVerificationService::canVerifyStage($application, AgencyVerificationService::STAGE_BKPSDM));
        $this->assertTrue($bkpsdm->can('verify', $application));

        $service->submitDecision($application, $dukcapil, VerificationDecision::MS);
        $service->submitDecision($application, $pendidikan, VerificationDecision::MS);

        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);

        $application = $service->submitDecision($application, $bkpsdm, VerificationDecision::MS);

        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->status);
        $this->assertCount(3, $application->agencyVerifications);
        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'bkpsdm',
            'decision' => VerificationDecision::MS->value,
        ]);
    }

    private function applicationInAgencyQueue(): array
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $application = $this->draftApplication($student);

        foreach (DocumentType::query()
            ->where('is_required', true)
            ->where(function ($query): void {
                $query->whereNull('application_type')
                    ->orWhere('application_type', ApplicationType::AKADEMIK->value);
            })
            ->get() as $type) {
            $path = 'applications/'.$application->id.'/'.$type->code.'/test.pdf';
            Storage::disk('local')->put($path, 'test');
            $application->documents()->create([
                'document_type_id' => $type->id,
                'uploaded_by' => $student->id,
                'path' => $path,
                'original_name' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'size' => 4,
                'checksum' => hash('sha256', 'test'),
            ]);
        }

        $application = $workflow->submit($application->fresh(), $student);

        return [$student, $village, $application->fresh(['documents'])];
    }

    private function draftApplication(User $student): Application
    {
        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => ApplicationType::AKADEMIK,
            'status' => ApplicationStatus::DRAFT,
        ]);
    }

    private function studentWithCompleteProfile(): array
    {
        $village = Village::query()->with('kecamatan')->firstOrFail();
        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'nik' => '6212010101010005',
            'nim' => 'KHM-TEST-005',
            'phone' => '081200000005',
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.50,
            'alamat' => 'Alamat pengujian',
            'village_id' => $village->id,
            'penghasilan_keluarga' => 2500000,
            'jumlah_tanggungan' => 4,
            'prestasi' => 'Prestasi pengujian',
        ]);

        return [$student->fresh(), $village];
    }

    private function operator(UserRole $role, Village $village): User
    {
        $operator = User::factory()->create([
            'role' => $role,
            'village_id' => $role === UserRole::OPERATOR_DESA ? $village->id : null,
            'kecamatan_id' => $role === UserRole::OPERATOR_KECAMATAN ? $village->kecamatan_id : null,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }
}
