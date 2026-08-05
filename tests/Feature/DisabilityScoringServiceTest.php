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
use Database\Seeders\DisabilityTrackSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DisabilityScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(DisabilityTrackSeeder::class);
    }

    public function test_disability_track_combines_ipk_semester_grade_and_type(): void
    {
        $application = $this->application([
            'ipk' => 3.20,
            'semester' => 6,
            'disability_type' => 'TUNANETRA',
            'disability_grade' => 'SEDANG',
        ]);

        $selection = app(SelectionScoringService::class)->calculate($application);

        $this->assertEqualsWithDelta(81.25, (float) $selection->final_score, 0.0001);

        $codes = $application->scores()->with('criterion')->get()->pluck('criterion.code')->sort()->values()->all();
        $this->assertSame(['disability_grade', 'disability_type', 'ipk', 'semester'], $codes);
    }

    public function test_higher_grade_yields_higher_score_with_same_other_inputs(): void
    {
        $berat = app(SelectionScoringService::class)
            ->calculate($this->application([
                'ipk' => 3.0, 'semester' => 4,
                'disability_type' => 'TUNADAKSA', 'disability_grade' => 'BERAT',
            ]));

        $ringan = app(SelectionScoringService::class)
            ->calculate($this->application([
                'ipk' => 3.0, 'semester' => 4,
                'disability_type' => 'TUNADAKSA', 'disability_grade' => 'RINGAN',
            ]));

        $this->assertGreaterThan(
            (float) $ringan->final_score,
            (float) $berat->final_score,
        );
    }

    public function test_missing_disability_type_throws_validation_exception(): void
    {
        $application = $this->application([
            'ipk' => 3.0,
            'semester' => 4,
        ]);

        $this->expectException(ValidationException::class);
        app(SelectionScoringService::class)->calculate($application);
    }

    public function test_disability_track_ranked_separately_from_other_tracks(): void
    {
        $disabilityHigh = $this->application([
            'ipk' => 3.8, 'semester' => 7,
            'disability_type' => 'DISABILITAS_GANDA', 'disability_grade' => 'BERAT',
        ]);
        $academic = $this->application(
            profileOverrides: [
                'ipk' => 3.8, 'semester' => 7,
            ],
            applicationType: ApplicationType::AKADEMIK,
        );

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($disabilityHigh);
        $scoring->calculate($academic);
        $scoring->recalculateRanking();

        $this->assertSame(1, $disabilityHigh->selection()->firstOrFail()->rank);
        $this->assertSame(1, $academic->selection()->firstOrFail()->rank);
    }

    private function application(
        array $profileOverrides = [],
        ?ApplicationType $applicationType = null,
    ): Application {
        $type = $applicationType ?? ApplicationType::DISABILITAS;
        $village = Village::query()->firstOrFail();

        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        $profileData = array_merge([
            'user_id' => $student->id,
            'nik' => fake()->unique()->numerify('6212############'),
            'nim' => fake()->unique()->bothify('KHM-DIS-#####'),
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Ilmu Komputer',
            'semester' => 4,
            'ipk' => 3.00,
            'alamat' => 'Alamat pengujian disabilitas',
            'village_id' => $village->id,
        ], $profileOverrides);

        if (! array_key_exists('disability_type', $profileOverrides)) {
            unset($profileData['disability_type']);
        }

        MahasiswaProfile::query()->create($profileData);

        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-DIS-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => $type,
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }
}
