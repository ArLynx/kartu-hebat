<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\DocumentType;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\MahasiswaProfile;
use App\Models\User;
use App\Models\Village;
use App\Services\ApplicationWorkflowService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $application = $workflow->getOrCreateCurrent($student);
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
        $districtOperator = $this->operator(UserRole::OPERATOR_KECAMATAN, $village);
        $dukcapil = $this->operator(UserRole::OPERATOR_DUKCAPIL, $village);
        $social = $this->operator(UserRole::OPERATOR_SOSIAL, $village);
        $education = $this->operator(UserRole::OPERATOR_PENDIDIKAN, $village);
        $this->operator(UserRole::OPERATOR_KABUPATEN, $village);

        $application = $workflow->submit($application, $student);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DESA, $application->status);

        $application = $workflow->verify($application, $villageOperator, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_KECAMATAN, $application->status);

        $application = $workflow->verify($application, $districtOperator, VerificationDecision::MS);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

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
        $application = $workflow->getOrCreateCurrent($student);
        $application->update(['application_type' => ApplicationType::AKADEMIK]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $workflow->submit($application, $student);
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
