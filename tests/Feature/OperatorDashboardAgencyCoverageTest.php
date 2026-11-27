<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\MahasiswaProfile;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorDashboardAgencyCoverageTest extends TestCase
{
    use RefreshDatabase;

    private Village $village;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);

        $this->village = Village::query()->firstOrFail();

        $this->operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $this->village->kabupaten_id,
        ]);
        $this->operator->forceFill(['two_factor_confirmed_at' => now()])->save();
    }

    public function test_kabupaten_operator_sees_agency_coverage_summary(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);

        $akademik = $this->application(ApplicationType::AKADEMIK);
        $this->application(ApplicationType::AKADEMIK);
        $tidakMampu = $this->application(ApplicationType::TIDAK_MAMPU);

        $this->verification($akademik, $dukcapil, 'dukcapil');
        $this->verification($tidakMampu, $dukcapil, 'dukcapil');

        $response = $this->actingAs($this->operator)->get(route('operator.dashboard'));

        $response->assertOk();
        $response->assertSee('Cakupan Verifikasi per SOPD');
        $response->assertSee('Dinas Kependudukan dan Pencatatan Sipil');
        $response->assertSee('BKPSDM');
        $response->assertSee('Jalur Tidak Mampu');
        $response->assertSee('Jalur Disabilitas');
        $response->assertSee('Jalur Non-Akademik');

        $coverage = $response->viewData('agencyCoverage')->keyBy('code');

        $this->assertCount(6, $coverage);

        $this->assertSame(3, $coverage['dukcapil']['total']);
        $this->assertSame(2, $coverage['dukcapil']['verified']);
        $this->assertSame(1, $coverage['dukcapil']['remaining']);
        $this->assertSame(66.7, $coverage['dukcapil']['percent_verified']);

        $this->assertSame(1, $coverage['sosial']['total']);
        $this->assertSame(0, $coverage['sosial']['verified']);
        $this->assertSame('Jalur Tidak Mampu', $coverage['sosial']['requirement']);

        $this->assertSame(0, $coverage['kesehatan']['total']);
        $this->assertSame(0, $coverage['kesehatan']['remaining']);
        $this->assertNull($coverage['kesehatan']['percent_verified']);
    }

    public function test_coverage_excludes_drafts_other_periods_and_other_kabupaten(): void
    {
        $this->application(ApplicationType::AKADEMIK);

        $this->application(ApplicationType::AKADEMIK, status: ApplicationStatus::DRAFT);
        $this->application(ApplicationType::AKADEMIK, periode: '2025/2026 Ganjil');

        $otherStudent = $this->student($this->otherKabupatenVillage());
        $this->application(ApplicationType::AKADEMIK, student: $otherStudent);

        $coverage = $this->actingAs($this->operator)
            ->get(route('operator.dashboard'))
            ->viewData('agencyCoverage')
            ->keyBy('code');

        $this->assertSame(1, $coverage['dukcapil']['total']);
        $this->assertSame(1, $coverage['pendidikan']['total']);
        $this->assertSame(1, $coverage['bkpsdm']['total']);
    }

    public function test_agency_operator_does_not_see_other_agencies_coverage(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($dukcapil)
            ->get(route('operator.dashboard'))
            ->assertOk()
            ->assertDontSee('Cakupan Verifikasi per SOPD');
    }

    private function application(
        ApplicationType $type,
        ApplicationStatus $status = ApplicationStatus::VERIFIKASI_DINAS,
        ?string $periode = null,
        ?User $student = null,
    ): Application {
        $student ??= $this->student($this->village);

        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-DASH-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'periode' => $periode ?? config('kartu_hebat.current_period'),
            'application_type' => $type,
            'status' => $status,
            'submitted_at' => $status === ApplicationStatus::DRAFT ? null : now(),
        ]);
    }

    private function verification(Application $application, User $verifier, string $agency): void
    {
        AgencyVerification::query()->create([
            'application_id' => $application->id,
            'verifier_id' => $verifier->id,
            'agency' => $agency,
            'decision' => VerificationDecision::MS,
            'verified_at' => now(),
        ]);
    }

    private function student(Village $village): User
    {
        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'nik' => fake()->unique()->numerify('6212############'),
            'nim' => fake()->unique()->numerify('KHM#######'),
            'phone' => '081200000000',
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.50,
            'alamat' => 'Alamat pengujian',
            'village_id' => $village->id,
            'penghasilan_keluarga' => 2500000,
            'jumlah_tanggungan' => 4,
        ]);

        return $student;
    }

    private function agencyOperator(UserRole $role): User
    {
        $operator = User::factory()->create([
            'role' => $role,
            'kabupaten_id' => $this->village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function otherKabupatenVillage(): Village
    {
        $kabupaten = Kabupaten::query()->create([
            'code' => '9999',
            'name' => 'Kabupaten Lain',
        ]);

        $kecamatan = Kecamatan::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'code' => '999901',
            'name' => 'Kecamatan Lain',
        ]);

        return Village::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'kecamatan_id' => $kecamatan->id,
            'code' => '9999010001',
            'name' => 'Desa Lain',
            'type' => 'desa',
        ]);
    }
}
