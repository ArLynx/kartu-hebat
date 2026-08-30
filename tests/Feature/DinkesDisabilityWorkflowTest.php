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
use App\Services\ApplicationWorkflowService;
use App\Services\DocumentVerificationService;
use Database\Seeders\DisabilityTrackSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DinkesDisabilityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(DisabilityTrackSeeder::class);
        Storage::fake('local');
    }

    public function test_disability_track_requires_four_agencies_including_dinkes(): void
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $documentService = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => ApplicationType::DISABILITAS]);

        $this->seedDocuments($application, $student);

        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $dinkes = $this->operator(UserRole::OPERATOR_DINKES, $village);

        $application = $workflow->submit($application, $student);

        foreach ($application->documents as $document) {
            $documentService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $social, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $education, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $dinkes, DocumentVerificationResult::MEMENUHI);
        }

        $workflow->verify($application, $dukcapil, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);

        $workflow->verify($application, $social, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);

        $workflow->verify($application, $education, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);

        $application = $workflow->verify($application, $dinkes, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->status);
        $this->assertCount(4, $application->agencyVerifications);
        $this->assertNotNull($application->selection);
    }

    public function test_dinkes_tms_rejects_disability_application(): void
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $documentService = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => ApplicationType::DISABILITAS]);

        $this->seedDocuments($application, $student);

        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $dinkes = $this->operator(UserRole::OPERATOR_DINKES, $village);

        $application = $workflow->submit($application, $student);

        foreach ($application->documents as $document) {
            $documentService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $social, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $education, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $dinkes, DocumentVerificationResult::MEMENUHI);
        }

        $workflow->verify($application, $dukcapil, VerificationDecision::MS);
        $workflow->verify($application, $social, VerificationDecision::MS);
        $workflow->verify($application, $education, VerificationDecision::MS);

        $application = $workflow->verify($application, $dinkes, VerificationDecision::TMS, 'Keterangan disabilitas tidak sesuai');
        $this->assertSame(ApplicationStatus::TMS, $application->status);
    }

    public function test_dinkes_cannot_verify_non_disability_track(): void
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $documentService = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => ApplicationType::AKADEMIK]);

        $this->seedDocuments($application, $student);

        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $dinkes = $this->operator(UserRole::OPERATOR_DINKES, $village);

        $application = $workflow->submit($application, $student);

        $this->assertFalse($dinkes->can('verify', $application));
        $this->assertFalse(DocumentVerificationService::canVerifyStage($application, 'kesehatan'));

        foreach ($application->documents as $document) {
            $documentService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
        }

        try {
            $workflow->verify($application, $dinkes, VerificationDecision::MS);
            $this->fail('Dinkes harus ditolak memverifikasi jalur non-Disabilitas.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('tidak berwenang', $exception->errors()['decision'][0]);
        }
    }

    public function test_non_disability_track_advances_after_three_agencies_without_dinkes(): void
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $documentService = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => ApplicationType::AKADEMIK]);

        $this->seedDocuments($application, $student);

        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);

        $application = $workflow->submit($application, $student);

        foreach ($application->documents as $document) {
            $documentService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $social, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $education, DocumentVerificationResult::MEMENUHI);
        }

        $workflow->verify($application, $dukcapil, VerificationDecision::MS);
        $workflow->verify($application, $social, VerificationDecision::MS);

        $application = $workflow->verify($application, $education, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->status);
        $this->assertCount(3, $application->agencyVerifications);
        $this->assertFalse($application->agencyVerifications->contains('agency', 'kesehatan'));
    }

    private function seedDocuments(Application $application, User $student): void
    {
        foreach (DocumentType::query()
            ->where('is_required', true)
            ->where(function ($query) use ($application): void {
                $query->whereNull('application_type')
                    ->orWhere('application_type', $application->application_type->value);
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
    }

    private function draftApplication(User $student): Application
    {
        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => null,
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
            'nik' => '6212010101010003',
            'nim' => 'KHM-TEST-003',
            'phone' => '081200000003',
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Pendidikan Luar Biasa',
            'semester' => 4,
            'ipk' => 3.55,
            'disability_type' => 'TUNANETRA',
            'disability_grade' => 'SEDANG',
            'disability_document_number' => 'DOK-TEST-001',
            'alamat' => 'Alamat pengujian',
            'village_id' => $village->id,
            'penghasilan_keluarga' => 3500000,
            'jumlah_tanggungan' => 3,
            'prestasi' => 'Prestasi pengujian',
        ]);

        return [$student->fresh(), $village];
    }

    private function operator(UserRole $role, Village $village): User
    {
        return User::factory()->create([
            'role' => $role,
            'village_id' => $role === UserRole::OPERATOR_DESA ? $village->id : null,
            'kecamatan_id' => $role === UserRole::OPERATOR_KECAMATAN ? $village->kecamatan_id : null,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
    }
}
