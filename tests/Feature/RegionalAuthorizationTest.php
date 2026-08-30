<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\MahasiswaProfile;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RegionalAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_village_operator_cannot_view_application_from_another_village(): void
    {
        $kabupaten = Kabupaten::query()->create(['code' => '6200', 'name' => 'Kabupaten Uji']);
        $kecamatan = Kecamatan::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'code' => '620001',
            'name' => 'Kecamatan Uji',
        ]);
        $villageA = Village::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'kecamatan_id' => $kecamatan->id,
            'code' => '6200010001',
            'name' => 'Desa A',
            'type' => 'desa',
        ]);
        $villageB = Village::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'kecamatan_id' => $kecamatan->id,
            'code' => '6200010002',
            'name' => 'Desa B',
            'type' => 'desa',
        ]);

        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'village_id' => $villageB->id,
            'kecamatan_id' => $kecamatan->id,
            'kabupaten_id' => $kabupaten->id,
        ]);
        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'nik' => '6212010101010002',
            'nim' => 'KHM-TEST-002',
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Informatika',
            'semester' => 4,
            'alamat' => 'Alamat uji',
            'village_id' => $villageB->id,
        ]);
        $application = Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-000002',
            'mahasiswa_id' => $student->id,
            'periode' => '2026/2027 Ganjil',
            'application_type' => ApplicationType::AKADEMIK,
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
        ]);

        $operatorA = User::factory()->create([
            'role' => UserRole::OPERATOR_DESA,
            'village_id' => $villageA->id,
            'kecamatan_id' => $kecamatan->id,
            'kabupaten_id' => $kabupaten->id,
        ]);

        $this->assertFalse(Gate::forUser($operatorA)->allows('view', $application));
        $this->assertFalse(Gate::forUser($operatorA)->allows('verify', $application));
    }

    public function test_non_village_operator_roles_pass_the_variadic_role_middleware(): void
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_SOSIAL,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->actingAs($operator)
            ->get(route('operator.dashboard'))
            ->assertOk();
    }
}
