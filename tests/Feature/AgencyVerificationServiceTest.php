<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\Document;
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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AgencyVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgencyVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
        $this->service = app(AgencyVerificationService::class);
    }

    public function test_assess_document_saves_and_cancels_properly(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $application->documents->first();

        $verification = $this->service->assessDocument(
            $application,
            $document,
            $dukcapil,
            DocumentVerificationResult::MEMENUHI,
            'Dokumen sah',
        );

        $this->assertSame(DocumentVerificationResult::MEMENUHI, $verification->result);
        $this->assertSame('dukcapil', $verification->stage);
        $this->assertSame(1, $verification->round);

        // Cancel
        $this->service->cancelDocumentAssessment($application, $document, $dukcapil);
        $this->assertDatabaseMissing('document_verifications', [
            'document_id' => $document->id,
            'stage' => 'dukcapil',
        ]);
    }

    public function test_submit_decision_fails_when_documents_are_unverified(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);

        $this->expectException(ValidationException::class);
        $this->service->submitDecision(
            $application,
            $dukcapil,
            VerificationDecision::MS,
        );
    }

    public function test_multi_agency_consensus_transitions_to_seleksi_kabupaten(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue(ApplicationType::TIDAK_MAMPU);
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $sosial = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $pendidikan = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $bkpsdm = $this->operator(UserRole::OPERATOR_BKPSDM, $village);

        // Nilai semua dokumen untuk keempat dinas
        foreach ([$dukcapil, $sosial, $pendidikan, $bkpsdm] as $op) {
            foreach ($application->documents as $doc) {
                $this->service->assessDocument(
                    $application,
                    $doc,
                    $op,
                    DocumentVerificationResult::MEMENUHI,
                );
            }
        }

        // 1. Dukcapil MS -> status kependudukan sesuai, tetap VERIFIKASI_DINAS
        $app1 = $this->service->submitDecision($application, $dukcapil, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $app1->status);
        $this->assertSame('sesuai', $student->profile->fresh()->status_kependudukan);

        // 2. Sosial MS -> desil_sosial = 2, tetap VERIFIKASI_DINAS
        $app2 = $this->service->submitDecision($application, $sosial, VerificationDecision::MS, desil: 2);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $app2->status);
        $this->assertSame(2, $student->profile->fresh()->desil_sosial);

        // 3. Pendidikan MS -> masih menunggu BKPSDM
        $app3 = $this->service->submitDecision($application, $pendidikan, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $app3->status);

        // 4. BKPSDM MS -> konsensus lengkap -> transisi ke SELEKSI_KABUPATEN
        $app4 = $this->service->submitDecision($application, $bkpsdm, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $app4->status);
        $this->assertNotNull($app4->selection);
        $this->assertCount(4, $app4->agencyVerifications);
    }

    public function test_agency_verifications_with_tms_resolves_to_tms(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue(ApplicationType::TIDAK_MAMPU);
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $sosial = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $pendidikan = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $bkpsdm = $this->operator(UserRole::OPERATOR_BKPSDM, $village);

        foreach ([$dukcapil, $sosial, $pendidikan, $bkpsdm] as $op) {
            foreach ($application->documents as $doc) {
                $this->service->assessDocument(
                    $application,
                    $doc,
                    $op,
                    DocumentVerificationResult::MEMENUHI,
                );
            }
        }

        // 1. Dukcapil MS
        $this->service->submitDecision($application, $dukcapil, VerificationDecision::MS);

        // 2. Sosial MS
        $this->service->submitDecision($application, $sosial, VerificationDecision::MS, desil: 2);

        // 3. Pendidikan MS -> masih menunggu BKPSDM
        $app = $this->service->submitDecision($application, $pendidikan, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $app->status);

        // 4. BKPSDM TMS -> konsensus lengkap dengan satu TMS -> transisi ke TMS
        $app = $this->service->submitDecision(
            $application,
            $bkpsdm,
            VerificationDecision::TMS,
            'Berkas tidak memenuhi syarat',
        );

        $this->assertSame(ApplicationStatus::TMS, $app->status);
        $this->assertSame('Berkas tidak memenuhi syarat', $app->catatan);
    }

    public function test_stale_social_verification_does_not_complete_academic_track(): void
    {
        [, $village, $application] = $this->applicationInAgencyQueue(ApplicationType::AKADEMIK);
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $pendidikan = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $bkpsdm = $this->operator(UserRole::OPERATOR_BKPSDM, $village);
        $sosial = $this->operator(UserRole::OPERATOR_SOSIAL, $village);

        // Data lama sebelum aturan baru: Sosial pernah memverifikasi jalur Akademik.
        AgencyVerification::query()->create([
            'application_id' => $application->id,
            'verifier_id' => $sosial->id,
            'agency' => 'sosial',
            'decision' => VerificationDecision::MS,
            'verified_at' => now(),
        ]);

        foreach ([$dukcapil, $pendidikan, $bkpsdm] as $operator) {
            foreach ($application->documents as $document) {
                $this->service->assessDocument(
                    $application,
                    $document,
                    $operator,
                    DocumentVerificationResult::MEMENUHI,
                );
            }
        }

        $application = $this->service->submitDecision($application, $dukcapil, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $application = $this->service->submitDecision($application, $pendidikan, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $application = $this->service->submitDecision($application, $bkpsdm, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->status);
    }

    public function test_social_agency_only_verifies_unable_track(): void
    {
        [, $village, $application] = $this->applicationInAgencyQueue(ApplicationType::AKADEMIK);
        $sosial = $this->operator(UserRole::OPERATOR_SOSIAL, $village);

        $academic = new Application(['application_type' => ApplicationType::AKADEMIK]);
        $unable = new Application(['application_type' => ApplicationType::TIDAK_MAMPU]);

        $this->assertNotContains('sosial', AgencyVerificationService::requiredAgencies($academic));
        $this->assertContains('sosial', AgencyVerificationService::requiredAgencies($unable));
        $this->assertFalse($sosial->can('verify', $application));
        $this->assertFalse(AgencyVerificationService::canVerifyStage($application, AgencyVerificationService::STAGE_SOSIAL));

        $this->expectException(ValidationException::class);
        $this->service->assessDocument(
            $application,
            $application->documents->first(),
            $sosial,
            DocumentVerificationResult::MEMENUHI,
        );
    }

    private function applicationInAgencyQueue(ApplicationType $applicationType = ApplicationType::AKADEMIK): array
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);

        $application = $this->draftApplication($student, $applicationType);

        foreach (DocumentType::query()->where('is_required', true)->get() as $type) {
            Document::query()->create([
                'application_id' => $application->id,
                'document_type_id' => $type->id,
                'path' => 'test/'.$type->code.'.pdf',
                'original_name' => $type->code.'.pdf',
                'mime_type' => 'application/pdf',
                'size' => 4,
                'checksum' => hash('sha256', 'test'),
            ]);
        }

        $application = $workflow->submit($application->fresh(), $student);

        return [$student, $village, $application->fresh(['documents'])];
    }

    private function draftApplication(User $student, ApplicationType $applicationType = ApplicationType::AKADEMIK): Application
    {
        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => $applicationType,
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
            'nik' => '6212010101010002',
            'nim' => 'KHM-TEST-002',
            'phone' => '081200000002',
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
