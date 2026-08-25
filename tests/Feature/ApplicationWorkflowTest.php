<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\Application;
use App\Models\DocumentType;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use App\Services\ApplicationWorkflowService;
use App\Services\DocumentVerificationService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    public function test_complete_verification_flow_reaches_county_selection(): void
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $documentVerificationService = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => ApplicationType::AKADEMIK]);

        $documents = [];
        foreach (DocumentType::query()
            ->where('is_required', true)
            ->where(function ($query): void {
                $query->whereNull('application_type')
                    ->orWhere('application_type', ApplicationType::AKADEMIK->value);
            })
            ->get() as $type) {
            $path = 'applications/'.$application->id.'/'.$type->code.'/test.pdf';
            Storage::disk('local')->put($path, 'test');
            $documents[] = $application->documents()->create([
                'document_type_id' => $type->id,
                'uploaded_by' => $student->id,
                'path' => $path,
                'original_name' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'size' => 4,
                'checksum' => hash('sha256', 'test'),
            ]);
        }

        $villageOperator = $this->operator(UserRole::OPERATOR_DESA, $village);
        $districtOperator = $this->operator(UserRole::OPERATOR_KECAMATAN, $village);
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $this->operator(UserRole::OPERATOR_KABUPATEN, $village);

        $application = $workflow->submit($application, $student);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DESA, $application->status);

        foreach ($documents as $document) {
            $documentVerificationService->save($application, $document, $villageOperator, DocumentVerificationResult::MEMENUHI);
        }

        $application = $workflow->verify($application, $villageOperator, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_KECAMATAN, $application->status);

        foreach ($documents as $document) {
            $documentVerificationService->save($application, $document, $districtOperator, DocumentVerificationResult::MEMENUHI);
        }

        $application = $workflow->verify($application, $districtOperator, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        foreach ($documents as $document) {
            $documentVerificationService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
            $documentVerificationService->save($application, $document, $social, DocumentVerificationResult::MEMENUHI);
            $documentVerificationService->save($application, $document, $education, DocumentVerificationResult::MEMENUHI);
        }

        $application = $workflow->verify($application, $dukcapil, VerificationDecision::MS, score: 90);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $application = $workflow->verify($application, $social, VerificationDecision::MS, score: 85, desil: 2);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $application = $workflow->verify($application, $education, VerificationDecision::MS, score: 88, desil: 3);
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->status);
        $this->assertNotNull($application->selection);
        $this->assertCount(2, $application->scores);
        $this->assertCount(3, $application->agencyVerifications);
        $this->assertSame('sesuai', $student->profile->fresh()->status_kependudukan);
        $this->assertSame(2, $student->profile->fresh()->desil_sosial);
        $this->assertSame(3, $student->profile->fresh()->desil_pendidikan);
    }

    public function test_student_cannot_submit_without_required_documents(): void
    {
        [$student] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $period = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Periode Pengujian',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);
        $category = KategoriBeasiswa::query()->create([
            'periode_id' => $period->id,
            'kode' => 'AKADEMIK-TEST',
            'nama' => 'Akademik Test',
            'application_type' => ApplicationType::AKADEMIK,
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);
        $requiredType = DocumentType::query()->where('is_required', true)->firstOrFail();
        $sourceType = JenisDokumen::query()->firstOrCreate(
            ['kode' => $requiredType->code],
            [
                'nama' => $requiredType->name,
                'format_file' => 'pdf',
                'maksimal_ukuran' => 2048,
                'aktif' => true,
            ],
        );
        $category->jenisDokumens()->attach($sourceType->id, ['urutan' => 1]);
        $registration = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $period->id,
            'kategori_beasiswa_id' => $category->id,
            'nomor_pendaftaran' => 'KHM-TEST-DOC',
            'status' => 'draft',
        ]);
        $application = Application::query()->create([
            'pendaftaran_id' => $registration->id,
            'nomor_pengajuan' => 'KHM-TEST-DOC',
            'mahasiswa_id' => $student->id,
            'periode' => $period->nama,
            'application_type' => ApplicationType::AKADEMIK,
            'status' => ApplicationStatus::DRAFT,
        ]);

        $this->expectException(ValidationException::class);

        $workflow->submit($application, $student);
    }

    public function test_submit_rejects_when_required_jenis_dokumen_has_no_master_match(): void
    {
        [$student] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $period = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Periode Pengujian',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);
        $category = KategoriBeasiswa::query()->create([
            'periode_id' => $period->id,
            'kode' => 'AKADEMIK-ORPHAN',
            'nama' => 'Akademik Orphan Test',
            'application_type' => ApplicationType::AKADEMIK,
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);
        $orphanType = JenisDokumen::query()->create([
            'kode' => 'KODE-TANPA-PADANAN',
            'nama' => 'Dokumen Tanpa Padanan',
            'format_file' => 'pdf',
            'maksimal_ukuran' => 2048,
            'aktif' => true,
        ]);
        $this->assertFalse(DocumentType::query()->where('code', 'KODE-TANPA-PADANAN')->exists());
        $category->jenisDokumens()->attach($orphanType->id, ['urutan' => 1]);
        $registration = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $period->id,
            'kategori_beasiswa_id' => $category->id,
            'nomor_pendaftaran' => 'KHM-TEST-ORPHAN',
            'status' => 'draft',
        ]);
        $application = Application::query()->create([
            'pendaftaran_id' => $registration->id,
            'nomor_pengajuan' => 'KHM-TEST-ORPHAN',
            'mahasiswa_id' => $student->id,
            'periode' => $period->nama,
            'application_type' => ApplicationType::AKADEMIK,
            'status' => ApplicationStatus::DRAFT,
        ]);

        try {
            $workflow->submit($application, $student);
            $this->fail('Submit harus gagal saat jenis dokumen wajib tidak punya padanan di data master.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('tidak ditemukan pada data master', $exception->errors()['documents'][0]);
            $this->assertSame(ApplicationStatus::DRAFT, $application->fresh()->status);
        }
    }

    public function test_cannot_verify_ms_without_document_assessment(): void
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

        $villageOperator = $this->operator(UserRole::OPERATOR_DESA, $village);

        $application = $workflow->submit($application, $student);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DESA, $application->status);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Semua dokumen harus dinilai terlebih dahulu');

        $workflow->verify($application, $villageOperator, VerificationDecision::MS);
    }

    public function test_can_verify_btl_without_document_assessment(): void
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

        $villageOperator = $this->operator(UserRole::OPERATOR_DESA, $village);

        $application = $workflow->submit($application, $student);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DESA, $application->status);

        $application = $workflow->verify($application, $villageOperator, VerificationDecision::BTL, 'Dokumen belum lengkap');
        $this->assertSame(ApplicationStatus::BTL_DESA, $application->status);
    }

    public function test_can_verify_ms_after_all_documents_assessed(): void
    {
        [$student, $village] = $this->studentWithCompleteProfile();
        $workflow = app(ApplicationWorkflowService::class);
        $documentVerification = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => ApplicationType::AKADEMIK]);

        $documents = [];
        foreach (DocumentType::query()
            ->where('is_required', true)
            ->where(function ($query): void {
                $query->whereNull('application_type')
                    ->orWhere('application_type', ApplicationType::AKADEMIK->value);
            })
            ->get() as $type) {
            $path = 'applications/'.$application->id.'/'.$type->code.'/test.pdf';
            Storage::disk('local')->put($path, 'test');
            $documents[] = $application->documents()->create([
                'document_type_id' => $type->id,
                'uploaded_by' => $student->id,
                'path' => $path,
                'original_name' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'size' => 4,
                'checksum' => hash('sha256', 'test'),
            ]);
        }

        $villageOperator = $this->operator(UserRole::OPERATOR_DESA, $village);

        $application = $workflow->submit($application, $student);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DESA, $application->status);

        foreach ($documents as $document) {
            $documentVerification->save($application, $document, $villageOperator, DocumentVerificationResult::MEMENUHI);
        }

        $application = $workflow->verify($application, $villageOperator, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_KECAMATAN, $application->status);
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
            'nik' => '6212010101010001',
            'nim' => 'KHM-TEST-001',
            'phone' => '081200000001',
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
        return User::factory()->create([
            'role' => $role,
            'village_id' => $role === UserRole::OPERATOR_DESA ? $village->id : null,
            'kecamatan_id' => $role === UserRole::OPERATOR_KECAMATAN ? $village->kecamatan_id : null,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
    }
}
