<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Criterion;
use App\Models\JalurBeasiswa;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use App\Services\SelectionScoringService;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SelectionScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);
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

    public function test_rejects_criteria_weights_that_do_not_sum_to_100(): void
    {
        Criterion::query()->create([
            'code' => 'bonus',
            'name' => 'Bonus Uji',
            'weight' => 25,
            'application_type' => ApplicationType::AKADEMIK->value,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $application = $this->application(ApplicationType::AKADEMIK, [
            'ipk' => 3.50,
            'semester' => 5,
        ]);

        try {
            app(SelectionScoringService::class)->calculate($application);
            $this->fail('Bobot kriteria yang tidak berjumlah 100 harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('criteria', $exception->errors());
            $this->assertStringContainsString('harus 100', $exception->errors()['criteria'][0]);
        }
    }

    public function test_rejects_criterion_code_unknown_to_strategy(): void
    {
        Criterion::query()->create([
            'code' => 'kode_tak_dikenal',
            'name' => 'Kriteria Tak Dikenal',
            'weight' => 0,
            'application_type' => ApplicationType::AKADEMIK->value,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $application = $this->application(ApplicationType::AKADEMIK, [
            'ipk' => 3.50,
            'semester' => 5,
        ]);

        try {
            app(SelectionScoringService::class)->calculate($application);
            $this->fail('Kode kriteria yang tidak dikenal strategi harus gagal keras.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('criteria', $exception->errors());
            $this->assertStringContainsString('kode_tak_dikenal', $exception->errors()['criteria'][0]);
        }

        $this->assertSame(0, $application->scores()->count());
    }

    public function test_ranking_recalculation_writes_all_ranks_in_one_statement(): void
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

        $writeStatements = 0;
        DB::listen(function ($query) use (&$writeStatements): void {
            if (preg_match('/^(insert|update)\s/i', $query->sql) && str_contains($query->sql, 'selections')) {
                $writeStatements++;
            }
        });

        $scoring->recalculateRanking();

        $this->assertLessThanOrEqual(1, $writeStatements, 'Rank harus ditulis dalam satu statement, bukan satu UPDATE per baris.');
        $this->assertSame(1, $academic->selection()->firstOrFail()->rank);
        $this->assertSame(1, $unable->selection()->firstOrFail()->rank);
    }

    public function test_ranking_is_separated_by_jalur_beasiswa_reguler_and_non_reguler(): void
    {
        $jalurReguler = JalurBeasiswa::query()->create([
            'kode' => 'REGULER',
            'nama' => 'Reguler',
            'aktif' => true,
            'urutan' => 1,
        ]);
        $jalurNonReguler = JalurBeasiswa::query()->create([
            'kode' => 'NON_REGULER',
            'nama' => 'Non Reguler',
            'aktif' => true,
            'urutan' => 2,
        ]);

        $appReguler1 = $this->application(ApplicationType::AKADEMIK, ['ipk' => 3.80, 'semester' => 6], $jalurReguler->id);
        $appReguler2 = $this->application(ApplicationType::AKADEMIK, ['ipk' => 3.50, 'semester' => 4], $jalurReguler->id);
        $appNonReguler1 = $this->application(ApplicationType::AKADEMIK, ['ipk' => 3.70, 'semester' => 6], $jalurNonReguler->id);
        $appNonReguler2 = $this->application(ApplicationType::AKADEMIK, ['ipk' => 3.40, 'semester' => 4], $jalurNonReguler->id);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($appReguler1);
        $scoring->calculate($appReguler2);
        $scoring->calculate($appNonReguler1);
        $scoring->calculate($appNonReguler2);

        $scoring->recalculateRanking();

        $this->assertSame(1, $appReguler1->selection()->firstOrFail()->rank);
        $this->assertSame(2, $appReguler2->selection()->firstOrFail()->rank);
        $this->assertSame(1, $appNonReguler1->selection()->firstOrFail()->rank);
        $this->assertSame(2, $appNonReguler2->selection()->firstOrFail()->rank);
    }

    private function application(ApplicationType $type, array $profileOverrides, ?int $jalurBeasiswaId = null): Application
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

        $pendaftaran = null;
        if ($jalurBeasiswaId !== null) {
            $periode = Periode::query()->first() ?? Periode::query()->create([
                'nama' => config('kartu_hebat.current_period'),
                'tahun' => 2026,
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-12-31',
                'status' => 'aktif',
            ]);

            $kategori = KategoriBeasiswa::query()->where('application_type', $type->value)->first();

            $pendaftaran = Pendaftaran::query()->create([
                'user_id' => $student->id,
                'periode_id' => $periode->id,
                'kategori_beasiswa_id' => $kategori?->id,
                'jalur_beasiswa_id' => $jalurBeasiswaId,
                'nomor_pendaftaran' => 'REG-'.fake()->unique()->numerify('######'),
                'status' => 'submitted',
            ]);
        }

        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'pendaftaran_id' => $pendaftaran?->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => $type,
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }
}
