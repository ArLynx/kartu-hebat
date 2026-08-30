<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\Application;
use App\Models\DocumentType;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Prestasi;
use App\Models\User;
use App\Models\Village;
use App\Services\ApplicationWorkflowService;
use App\Services\DocumentVerificationService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\PrestasiTrackSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParseporPrestasiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(PrestasiTrackSeeder::class);
        Storage::fake('local');
    }

    public function test_prestasi_track_requires_four_agencies_including_parsepor(): void
    {
        [$student, $village, $registration] = $this->studentWithPendaftaran();
        $workflow = app(ApplicationWorkflowService::class);
        $documentService = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student, $registration);
        $application->update(['application_type' => ApplicationType::NON_AKADEMIK]);

        $this->seedDocuments($application, $student);

        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $parsepor = $this->operator(UserRole::OPERATOR_PARSEPOR, $village);

        $application = $workflow->submit($application, $student);

        foreach ($application->documents as $document) {
            $documentService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $social, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $education, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $parsepor, DocumentVerificationResult::MEMENUHI);
        }

        $workflow->verify($application, $dukcapil, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);

        $workflow->verify($application, $social, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);

        $workflow->verify($application, $education, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->fresh()->status);

        $application = $workflow->verify($application, $parsepor, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $application->status);
        $this->assertCount(4, $application->agencyVerifications);
        $this->assertNotNull($application->selection);
    }

    public function test_parsepor_tms_rejects_prestasi_application(): void
    {
        [$student, $village, $registration] = $this->studentWithPendaftaran();
        $workflow = app(ApplicationWorkflowService::class);
        $documentService = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student, $registration);
        $application->update(['application_type' => ApplicationType::NON_AKADEMIK]);

        $this->seedDocuments($application, $student);

        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $parsepor = $this->operator(UserRole::OPERATOR_PARSEPOR, $village);

        $application = $workflow->submit($application, $student);

        foreach ($application->documents as $document) {
            $documentService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $social, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $education, DocumentVerificationResult::MEMENUHI);
            $documentService->save($application, $document, $parsepor, DocumentVerificationResult::MEMENUHI);
        }

        $workflow->verify($application, $dukcapil, VerificationDecision::MS);
        $workflow->verify($application, $social, VerificationDecision::MS);
        $workflow->verify($application, $education, VerificationDecision::MS);

        $application = $workflow->verify($application, $parsepor, VerificationDecision::TMS, 'Dokumen prestasi tidak sesuai');
        $this->assertSame(ApplicationStatus::TMS, $application->status);
    }

    public function test_parsepor_cannot_verify_non_prestasi_track(): void
    {
        [$student, $village] = $this->studentWithPendaftaran();
        $workflow = app(ApplicationWorkflowService::class);
        $documentService = app(DocumentVerificationService::class);
        $application = $this->draftApplication($student);
        $application->update(['application_type' => ApplicationType::AKADEMIK]);

        $this->seedDocuments($application, $student);

        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $parsepor = $this->operator(UserRole::OPERATOR_PARSEPOR, $village);

        $application = $workflow->submit($application, $student);

        $this->assertFalse($parsepor->can('verify', $application));
        $this->assertFalse(DocumentVerificationService::canVerifyStage($application, 'parsepor'));

        foreach ($application->documents as $document) {
            $documentService->save($application, $document, $dukcapil, DocumentVerificationResult::MEMENUHI);
        }

        try {
            $workflow->verify($application, $parsepor, VerificationDecision::MS);
            $this->fail('Parsepor harus ditolak memverifikasi jalur non-Prestasi.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('tidak berwenang', $exception->errors()['decision'][0]);
        }
    }

    public function test_non_prestasi_track_advances_after_three_agencies_without_parsepor(): void
    {
        [$student, $village] = $this->studentWithPendaftaran();
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
        $this->assertFalse($application->agencyVerifications->contains('agency', 'parsepor'));
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

    private function draftApplication(User $student, ?Pendaftaran $pendaftaran = null): Application
    {
        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'pendaftaran_id' => $pendaftaran?->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => null,
            'status' => ApplicationStatus::DRAFT,
        ]);
    }

    private function studentWithPendaftaran(): array
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
            'nik' => '6212010101010004',
            'nim' => 'KHM-TEST-004',
            'phone' => '081200000004',
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Ilmu Komunikasi',
            'semester' => 5,
            'ipk' => 3.61,
            'alamat' => 'Alamat pengujian',
            'village_id' => $village->id,
            'penghasilan_keluarga' => 5000000,
            'jumlah_tanggungan' => 3,
            'prestasi' => 'Juara 1 Debat Tingkat Nasional (2025)',
        ]);

        $period = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Periode Uji',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        $category = KategoriBeasiswa::query()->create([
            'periode_id' => $period->id,
            'kode' => 'PRESTASI-TEST',
            'nama' => 'Prestasi Test',
            'application_type' => ApplicationType::NON_AKADEMIK,
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $registration = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $period->id,
            'kategori_beasiswa_id' => $category->id,
            'nomor_pendaftaran' => 'KHM-TEST-PRESTASI',
            'status' => 'draft',
        ]);

        Prestasi::query()->create([
            'pendaftaran_id' => $registration->id,
            'jenis' => 'non_akademik',
            'nama_prestasi' => 'Juara 1 Debat',
            'tingkat' => 'nasional',
            'peringkat' => 'juara_1',
            'penyelenggara' => 'Kemendikbud',
            'tahun' => 2025,
        ]);

        return [$student->fresh(), $village, $registration];
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
