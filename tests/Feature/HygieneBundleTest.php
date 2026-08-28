<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use App\Models\Selection;
use App\Models\User;
use App\Models\Village;
use App\Services\DocumentVerificationService;
use App\Services\PendaftaranWorkflowBridgeService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HygieneBundleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
    }

    public function test_bridge_uses_constructor_injected_document_verification_service(): void
    {
        $bridge = app(PendaftaranWorkflowBridgeService::class);

        $this->assertSame(
            DocumentVerificationService::class,
            $this->documentVerificationServiceOf($bridge),
            'PendaftaranWorkflowBridgeService harus menerima DocumentVerificationService lewat constructor, bukan app().',
        );
    }

    public function test_selection_index_reports_each_type_once(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->with('kecamatan')->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        $this->candidate($village, ApplicationType::AKADEMIK);
        $this->candidate($village, ApplicationType::AKADEMIK);
        $this->candidate($village, ApplicationType::TIDAK_MAMPU);

        $response = $this->actingAs($operator)
            ->get(route('operator.selection', ['application_type' => ApplicationType::AKADEMIK->value]))
            ->assertOk()
            ->assertViewIs('operator.selection');

        $typeCounts = $response->viewData('typeCounts');
        $this->assertNotNull($typeCounts, 'View harus menerima variabel typeCounts.');
        $this->assertSame(2, (int) ($typeCounts[ApplicationType::AKADEMIK->value] ?? 0));
        $this->assertSame(1, (int) ($typeCounts[ApplicationType::TIDAK_MAMPU->value] ?? 0));
        $this->assertSame(0, (int) ($typeCounts[ApplicationType::DISABILITAS->value] ?? 0));
        $this->assertSame(0, (int) ($typeCounts[ApplicationType::NON_AKADEMIK->value] ?? 0));
    }

    public function test_selection_index_type_counts_use_single_grouped_query(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->with('kecamatan')->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        $this->candidate($village, ApplicationType::AKADEMIK);
        $this->candidate($village, ApplicationType::TIDAK_MAMPU);

        $groupedCountQueries = 0;

        DB::listen(function ($query) use (&$groupedCountQueries): void {
            if (str_contains($query->sql, 'applications') && str_contains($query->sql, 'count') && str_contains($query->sql, 'group by')) {
                $groupedCountQueries++;
            }
        });

        $this->actingAs($operator)
            ->get(route('operator.selection', ['application_type' => ApplicationType::AKADEMIK->value]))
            ->assertOk();

        $this->assertSame(1, $groupedCountQueries, 'typeCounts harus dihitung dari satu query groupBy, bukan satu COUNT per jalur.');
    }

    public function test_user_privileged_columns_are_not_mass_assignable(): void
    {
        $user = User::factory()->create(['role' => UserRole::MAHASISWA]);

        $user->fill([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'status' => 'inactive',
            'kabupaten_id' => 999,
        ])->save();

        $fresh = $user->fresh();
        $this->assertSame(UserRole::MAHASISWA, $fresh->role);
        $this->assertSame('active', $fresh->status);
    }

    private function documentVerificationServiceOf(PendaftaranWorkflowBridgeService $bridge): ?string
    {
        $property = new \ReflectionProperty($bridge, 'documentVerification');
        $property->setAccessible(true);
        $value = $property->getValue($bridge);

        return $value ? $value::class : null;
    }

    private function kabupatenOperator(): User
    {
        $village = Village::query()->with('kecamatan')->firstOrFail();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function candidate(Village $village, ApplicationType $type): Application
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212'.str_pad((string) $student->id, 12, '0', STR_PAD_LEFT),
            'nim' => 'KHM-HYB-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.50,
            'alamat' => 'Jalan Pengujian',
        ]);

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-2026-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'application_type' => $type,
        ]);

        Selection::query()->create([
            'application_id' => $application->id,
            'final_score' => 80.0,
        ]);

        return $application;
    }
}
