<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
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

        $document = $this->documentByCode($application, 'KTP');

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
        $document = $this->documentByCode($application, 'KTP');

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
        $document = $this->documentByCode($application, 'KTP');

        app(AgencyVerificationService::class)->assessDocument(
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
        $document = $this->documentByCode($application, 'KTP');

        app(AgencyVerificationService::class)->assessDocument(
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
        [$student, $village, $application] = $this->applicationInAgencyQueue(ApplicationType::TIDAK_MAMPU);
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);

        $ktp = $this->documentByCode($application, 'KTP');
        $ktm = $this->documentByCode($application, 'KTM');

        $this->actingAs($dukcapil)
            ->post(
                route('operator.applications.documents.verify', [$application, $ktp]),
                ['result' => DocumentVerificationResult::MEMENUHI->value],
            )
            ->assertRedirect();

        $this->actingAs($education)
            ->post(
                route('operator.applications.documents.verify', [$application, $ktm]),
                ['result' => DocumentVerificationResult::MEMENUHI->value],
            )
            ->assertRedirect();

        $this->assertDatabaseHas('document_verifications', [
            'document_id' => $ktp->id,
            'stage' => 'dukcapil',
            'result' => 'memenuhi',
        ]);
        $this->assertDatabaseHas('document_verifications', [
            'document_id' => $ktm->id,
            'stage' => 'pendidikan',
            'result' => 'memenuhi',
        ]);
    }

    public function test_review_only_agency_cannot_assess_documents(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue(ApplicationType::TIDAK_MAMPU);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $ktp = $this->documentByCode($application, 'KTP');

        $this->actingAs($social)
            ->from(route('operator.applications.show', $application))
            ->post(
                route('operator.applications.documents.verify', [$application, $ktp]),
                ['result' => DocumentVerificationResult::MEMENUHI->value],
            )
            ->assertSessionHasErrors('document_verification');

        $this->assertDatabaseMissing('document_verifications', [
            'document_id' => $ktp->id,
            'stage' => 'sosial',
        ]);
    }

    public function test_submitting_verification_locks_document_assessments(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $bkpsdm = $this->operator(UserRole::OPERATOR_BKPSDM, $village);

        $documentVerificationService = app(AgencyVerificationService::class);
        foreach ([$dukcapil, $education, $bkpsdm] as $operator) {
            foreach (AgencyVerificationService::documentsAssessableBy($application, $operator) as $document) {
                $documentVerificationService->assessDocument($application, $document, $operator, DocumentVerificationResult::MEMENUHI);
            }
        }

        $workflow = app(ApplicationWorkflowService::class);
        $workflow->verify($application, $dukcapil, VerificationDecision::MS);
        $workflow->verify($application, $education, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);

        $workflow->verify($application, $bkpsdm, VerificationDecision::MS);

        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->fresh()->status);

        $document = $this->documentByCode($application, 'KTP');

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
        $document = $this->documentByCode($application, 'KTP');

        $workflow = app(ApplicationWorkflowService::class);

        // Verifikator menilai dokumen putaran 1.
        app(AgencyVerificationService::class)->assessDocument(
            $application,
            $document,
            $dukcapil,
            DocumentVerificationResult::TIDAK_MEMENUHI,
            'KTP buram',
        );

        // Aplikasi kembali menjadi draft lalu disubmit ulang oleh mahasiswa.
        $application->update(['status' => ApplicationStatus::DRAFT]);
        $workflow->submit($application, $student);

        $this->assertSame(2, AgencyVerificationService::currentRound($application->fresh()));
    }

    public function test_operator_show_page_renders_with_assessment(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $this->documentByCode($application, 'KTP');

        app(AgencyVerificationService::class)->assessDocument(
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

    public function test_operator_show_page_renders_one_click_ms_without_note_panel(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('assessDocument(', false)
            ->assertSee("'memenuhi'", false)
            ->assertDontSee('Simpan MS')
            ->assertDontSee('Konfirmasi Memenuhi Syarat');
    }

    public function test_ms_decision_is_blocked_when_a_document_is_rejected(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $service = app(AgencyVerificationService::class);

        $service->assessDocument(
            $application,
            $this->documentByCode($application, 'KTP'),
            $operator,
            DocumentVerificationResult::TIDAK_MEMENUHI,
            'KTP tidak terbaca.',
        );
        $service->assessDocument(
            $application,
            $this->documentByCode($application, 'KK'),
            $operator,
            DocumentVerificationResult::MEMENUHI,
        );

        $this->actingAs($operator)
            ->from(route('operator.applications.show', $application))
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::MS->value,
            ])
            ->assertRedirect(route('operator.applications.show', $application))
            ->assertSessionHasErrors([
                'decision' => 'Keputusan Memenuhi Syarat (MS) tidak dapat dipilih karena masih ada dokumen yang dinilai Tidak Memenuhi Syarat (TMS): Kartu Tanda Penduduk.',
            ]);

        $this->assertDatabaseMissing('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'dukcapil',
        ]);
    }

    public function test_tms_decision_is_blocked_when_all_documents_meet(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);

        $this->assessDocumentsFor($application, $operator, DocumentVerificationResult::MEMENUHI);

        $this->actingAs($operator)
            ->from(route('operator.applications.show', $application))
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::TMS->value,
                'notes' => 'Dokumen tetap tidak sesuai.',
            ])
            ->assertRedirect(route('operator.applications.show', $application))
            ->assertSessionHasErrors([
                'decision' => 'Keputusan Tidak Memenuhi Syarat (TMS) tidak dapat dipilih karena seluruh dokumen sudah dinilai Memenuhi Syarat (MS).',
            ]);

        $this->assertDatabaseMissing('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'dukcapil',
        ]);
    }

    public function test_operator_show_page_disables_decisions_that_contradict_assessments(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('x-bind:disabled="! canChooseMs"', false)
            ->assertSee('x-bind:disabled="! canChooseTms"', false)
            ->assertSee('Tidak dapat dipilih karena masih ada dokumen yang belum dinilai atau dinilai TMS.')
            ->assertSee('Tidak dapat dipilih karena masih ada dokumen yang belum dinilai atau seluruh dokumen sudah dinilai MS.');
    }

    public function test_document_assessment_endpoint_returns_json_for_ajax_requests(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $this->documentByCode($application, 'KTP');

        $this->actingAs($operator)
            ->postJson(
                route('operator.applications.documents.verify', [$application, $document]),
                ['result' => DocumentVerificationResult::MEMENUHI->value],
            )
            ->assertOk()
            ->assertJson([
                'result' => 'memenuhi',
                'label' => 'Memenuhi Syarat',
                'notes' => null,
            ]);

        $this->assertDatabaseHas('document_verifications', [
            'document_id' => $document->id,
            'stage' => 'dukcapil',
            'result' => 'memenuhi',
        ]);
    }

    public function test_document_assessment_cancel_endpoint_returns_json_for_ajax_requests(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $this->documentByCode($application, 'KTP');

        app(AgencyVerificationService::class)->assessDocument(
            $application,
            $document,
            $operator,
            DocumentVerificationResult::MEMENUHI,
        );

        $this->actingAs($operator)
            ->deleteJson(route('operator.applications.documents.verify.destroy', [$application, $document]))
            ->assertOk()
            ->assertJson([
                'result' => null,
                'label' => null,
                'notes' => null,
            ]);

        $this->assertDatabaseMissing('document_verifications', ['document_id' => $document->id]);
    }

    public function test_ms_can_be_assessed_without_notes(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $document = $this->documentByCode($application, 'KTP');

        $this->actingAs($operator)
            ->post(
                route('operator.applications.documents.verify', [$application, $document]),
                ['result' => DocumentVerificationResult::MEMENUHI->value],
            )
            ->assertRedirect();

        $this->assertDatabaseHas('document_verifications', [
            'document_id' => $document->id,
            'stage' => 'dukcapil',
            'result' => 'memenuhi',
            'notes' => null,
        ]);
    }

    public function test_dukcapil_only_sees_ktp_and_kk_on_verification_page(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('Kartu Tanda Penduduk')
            ->assertSee('Kartu Keluarga')
            ->assertDontSee('Kartu Tanda Mahasiswa')
            ->assertDontSee('Kartu Hasil Studi')
            ->assertSee('✓ MS');
    }

    public function test_dinsos_sees_ktp_read_only_and_can_decide_without_document_assessment(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue(ApplicationType::TIDAK_MAMPU);
        $operator = $this->operator(UserRole::OPERATOR_SOSIAL, $village);

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('Kartu Tanda Penduduk')
            ->assertDontSee('Kartu Keluarga')
            ->assertSee('Hanya tinjau')
            ->assertDontSee('✓ MS')
            ->assertDontSee('✕ TMS');

        $this->actingAs($operator)
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::MS->value,
                'desil' => 3,
            ])
            ->assertRedirect(route('operator.applications.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'sosial',
            'decision' => VerificationDecision::MS->value,
        ]);
    }

    public function test_bkpsdm_sees_kk_read_only_and_can_decide_without_document_assessment(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_BKPSDM, $village);

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('Kartu Keluarga')
            ->assertDontSee('Kartu Tanda Penduduk')
            ->assertSee('Hanya tinjau')
            ->assertDontSee('✓ MS')
            ->assertDontSee('✕ TMS');

        $this->actingAs($operator)
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::MS->value,
            ])
            ->assertRedirect(route('operator.applications.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('agency_verifications', [
            'application_id' => $application->id,
            'agency' => 'bkpsdm',
            'decision' => VerificationDecision::MS->value,
        ]);
    }

    public function test_dukcapil_cannot_assess_documents_outside_its_scope(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $operator = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $ktm = $this->documentByCode($application, 'KTM');

        $this->actingAs($operator)
            ->from(route('operator.applications.show', $application))
            ->post(
                route('operator.applications.documents.verify', [$application, $ktm]),
                ['result' => DocumentVerificationResult::MEMENUHI->value],
            )
            ->assertSessionHasErrors('document_verification');

        $this->assertDatabaseMissing('document_verifications', [
            'document_id' => $ktm->id,
            'stage' => 'dukcapil',
        ]);
    }

    public function test_buku_rekening_bpdk_is_not_required_for_agency_decisions(): void
    {
        [$student, $village, $application] = $this->applicationInAgencyQueue();
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);

        $this->assessDocumentsFor($application, $dukcapil, DocumentVerificationResult::MEMENUHI);

        $this->actingAs($dukcapil)
            ->post(route('operator.applications.verify', $application), [
                'decision' => VerificationDecision::MS->value,
            ])
            ->assertRedirect(route('operator.applications.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('document_verifications', [
            'application_id' => $application->id,
            'stage' => 'dukcapil',
            'document_id' => $this->documentByCode($application, 'BUKU-REKENING-BPDK')->id,
        ]);
    }

    private function assessDocumentsFor(
        Application $application,
        User $operator,
        DocumentVerificationResult $result,
    ): void {
        $service = app(AgencyVerificationService::class);

        foreach (AgencyVerificationService::documentsAssessableBy($application, $operator) as $document) {
            $service->assessDocument($application, $document, $operator, $result);
        }
    }

    private function applicationInAgencyQueue(ApplicationType $applicationType = ApplicationType::AKADEMIK): array
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => $applicationType]);

        foreach (DocumentType::query()
            ->where('is_required', true)
            ->where(function ($query) use ($applicationType): void {
                $query->whereNull('application_type')
                    ->orWhere('application_type', $applicationType->value);
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

        return [$student, $village, $application->fresh(['documents.type'])];
    }

    private function documentByCode(Application $application, string $code): Document
    {
        return $application->documents->firstOrFail(
            fn (Document $document): bool => $document->type?->code === $code,
        );
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
