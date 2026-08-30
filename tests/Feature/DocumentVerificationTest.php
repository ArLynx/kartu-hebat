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
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    public function test_agency_can_assess_documents_at_its_stage(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);

        $document = $application->documents->first();

        $this->actingAs($operator)->post(
            route('operator.applications.documents.verify', [$application, $document]),
            ['result' => DocumentVerificationResult::MEMENUHI->value],
        )->assertRedirect();

        $this->assertDatabaseHas('document_verifications', [
            'document_id' => $document->id,
            'stage' => 'dukcapil',
            'result' => 'memenuhi',
        ]);
    }

    public function test_notes_required_when_document_rejected(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $application->documents->first();

        $response = $this->actingAs($operator)
            ->from(route('operator.applications.show', $application))
            ->post(
                route('operator.applications.documents.verify', [$application, $document]),
                ['result' => DocumentVerificationResult::TIDAK_MEMENUHI->value],
            );

        $response->assertSessionHasErrors('notes');
        $this->assertDatabaseMissing('document_verifications', ['document_id' => $document->id]);
    }

    public function test_assessment_cancellable_while_stage_active(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $application->documents->first();

        app(DocumentVerificationService::class)->save(
            $application,
            $document,
            $operator,
            DocumentVerificationResult::MEMENUHI,
        );

        $this->actingAs($operator)
            ->delete(route('operator.applications.documents.verify.destroy', [$application, $document]))
            ->assertRedirect();

        $this->assertDatabaseMissing('document_verifications', ['document_id' => $document->id]);
    }

    public function test_assessment_not_deletable_after_application_advances(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $application->documents->first();

        app(DocumentVerificationService::class)->save(
            $application,
            $document,
            $operator,
            DocumentVerificationResult::MEMENUHI,
        );
        $application->update(['status' => ApplicationStatus::SELEKSI_KABUPATEN]);

        $this->actingAs($operator)
            ->from(route('operator.applications.show', $application))
            ->delete(route('operator.applications.documents.verify.destroy', [$application, $document]))
            ->assertSessionHasErrors('document_verification');

        $this->assertDatabaseHas('document_verifications', [
            'document_id' => $document->id,
            'stage' => 'dukcapil',
        ]);
    }

    public function test_assessment_editable_while_application_in_agency_queue(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);

        $document = $application->documents->first();

        $this->actingAs($dukcapil)
            ->post(
                route('operator.applications.documents.verify', [$application, $document]),
                ['result' => DocumentVerificationResult::MEMENUHI->value],
            )
            ->assertRedirect();

        $this->actingAs($social)
            ->post(
                route('operator.applications.documents.verify', [$application, $document]),
                ['result' => DocumentVerificationResult::MEMENUHI->value],
            )
            ->assertRedirect();

        $this->assertDatabaseHas('document_verifications', [
            'document_id' => $document->id,
            'stage' => 'dukcapil',
            'result' => 'memenuhi',
        ]);
        $this->assertDatabaseHas('document_verifications', [
            'document_id' => $document->id,
            'stage' => 'sosial',
            'result' => 'memenuhi',
        ]);
    }

    public function test_submitting_verification_locks_document_assessments(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);

        $documentVerificationService = app(DocumentVerificationService::class);
        foreach ($application->documents as $document) {
            $documentVerificationService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
            $documentVerificationService->save($application, $document, $social, DocumentVerificationResult::MEMENUHI);
            $documentVerificationService->save($application, $document, $education, DocumentVerificationResult::MEMENUHI);
        }

        $workflow = app(ApplicationWorkflowService::class);
        $workflow->verify($application, $dukcapil, VerificationDecision::MS);
        $workflow->verify($application, $social, VerificationDecision::MS);
        $workflow->verify($application, $education, VerificationDecision::MS);

        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->fresh()->status);

        $document = $application->documents->first();

        $this->actingAs($dukcapil)
            ->from(route('operator.applications.show', $application))
            ->post(
                route('operator.applications.documents.verify', [$application, $document]),
                ['result' => DocumentVerificationResult::TIDAK_MEMENUHI->value, 'notes' => 'sudah lanjut'],
            )
            ->assertSessionHasErrors('document_verification');
    }

    public function test_round_increments_and_previous_round_kept_on_resubmit(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $application->documents->first();

        $workflow = app(ApplicationWorkflowService::class);

        // Verifikator menilai dokumen putaran 1.
        app(DocumentVerificationService::class)->save(
            $application,
            $document,
            $dukcapil,
            DocumentVerificationResult::TIDAK_MEMENUHI,
            'KTP buram',
        );

        // Aplikasi kembali menjadi draft lalu disubmit ulang oleh mahasiswa.
        $application->update(['status' => ApplicationStatus::DRAFT]);
        $workflow->submit($application, $student);

        $this->assertSame(2, DocumentVerificationService::currentRound($application->fresh()));
    }

    public function test_operator_show_page_renders_with_assessment(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $application->documents->first();

        app(DocumentVerificationService::class)->save(
            $application,
            $document,
            $operator,
            DocumentVerificationResult::TIDAK_MEMENUHI,
            'KTP buram',
        );

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('Dokumen Persyaratan')
            ->assertSee('Tidak Memenuhi')
            ->assertSee('KTP buram');
    }

    private function applicationInAgencyQueue(): array
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => ApplicationType::AKADEMIK]);

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
