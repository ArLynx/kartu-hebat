<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use App\Models\User;
use App\Models\Village;
use App\Services\SelectionScoringService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
    }

    public function test_academic_track_uses_only_ipk_and_semester(): void
    {
        $application = $this->application(ApplicationType::AKADEMIK, [
            'ipk' => 3.50,
            'semester' => 5,
        ]);

        $selection = app(SelectionScoringService::class)->calculate($application);

        $this->assertEqualsWithDelta(81.25, (float) $selection->final_score, 0.0001);
        $this->assertSame(['ipk', 'semester'], $application->scores()->with('criterion')->get()->pluck('criterion.code')->sort()->values()->all());
    }

    public function test_unable_track_uses_verified_desil_and_lower_desil_scores_higher(): void
    {
        $application = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 2,
            'desil_pendidikan' => 4,
        ]);

        $selection = app(SelectionScoringService::class)->calculate($application);
        $score = $application->scores()->with('criterion')->firstOrFail();

        $this->assertSame('desil', $score->criterion->code);
        $this->assertEqualsWithDelta(3.0, (float) $score->raw_value, 0.0001);
        $this->assertEqualsWithDelta(77.7778, (float) $selection->final_score, 0.0001);
    }

    public function test_ranking_is_reset_for_each_application_track(): void
    {
        $academic = $this->application(ApplicationType::AKADEMIK, [
            'ipk' => 3.80,
            'semester' => 6,
        ]);
        $unable = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 1,
            'desil_pendidikan' => 2,
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($academic);
        $scoring->calculate($unable);
        $scoring->recalculateRanking();

        $this->assertSame(1, $academic->selection()->firstOrFail()->rank);
        $this->assertSame(1, $unable->selection()->firstOrFail()->rank);
    }

    private function application(ApplicationType $type, array $profileOverrides): Application
    {
        $village = Village::query()->firstOrFail();
        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        MahasiswaProfile::query()->create(array_merge([
            'user_id' => $student->id,
            'nik' => fake()->unique()->numerify('6212############'),
            'nim' => fake()->unique()->bothify('KHM-TEST-#####'),
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Teknik Informatika',
            'semester' => 4,
            'ipk' => 3.00,
            'alamat' => 'Alamat pengujian',
            'village_id' => $village->id,
        ], $profileOverrides));

        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => $type,
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }
}
