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
        ]);

        $selection = app(SelectionScoringService::class)->calculate($application);
        $score = $application->scores()->with('criterion')->firstOrFail();

        $this->assertSame('desil', $score->criterion->code);
        $this->assertEqualsWithDelta(2.0, (float) $score->raw_value, 0.0001);
        $this->assertEqualsWithDelta(88.8889, (float) $selection->final_score, 0.0001);
    }

    public function test_ranking_is_reset_for_each_application_track(): void
    {
        $academic = $this->application(ApplicationType::AKADEMIK, [
            'ipk' => 3.80,
            'semester' => 6,
        ]);
        $unable = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 1,
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

    public function test_unable_track_orders_strictly_by_perbup_criteria(): void
    {
        // A: Desil 1, IPK 3.10
        $appA = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 1,
            'ipk' => 3.10,
        ], null, ['nilai_raport' => 75.0, 'tahun_masuk' => 2023, 'akreditasi_perguruan_tinggi' => 'C']);

        // B: Desil 2, IPK 3.99 (harus kalah dari A karena Desil 1 > Desil 2)
        $appB = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 2,
            'ipk' => 3.99,
        ], null, ['nilai_raport' => 98.0, 'tahun_masuk' => 2020, 'akreditasi_perguruan_tinggi' => 'Unggul']);

        // C: Desil 2, IPK 3.80, Raport 90, Masuk 2022, Akr B
        $appC = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 2,
            'ipk' => 3.80,
        ], null, ['nilai_raport' => 90.0, 'tahun_masuk' => 2022, 'akreditasi_perguruan_tinggi' => 'B']);

        // D: Desil 2, IPK 3.80, Raport 95 (harus menang dari C karena Raport lebih tinggi)
        $appD = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 2,
            'ipk' => 3.80,
        ], null, ['nilai_raport' => 95.0, 'tahun_masuk' => 2022, 'akreditasi_perguruan_tinggi' => 'B']);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($appA);
        $scoring->calculate($appB);
        $scoring->calculate($appC);
        $scoring->calculate($appD);

        $scoring->recalculateRanking();

        $this->assertSame(1, $appA->selection()->firstOrFail()->rank);
        $this->assertSame(2, $appB->selection()->firstOrFail()->rank);
        $this->assertSame(3, $appD->selection()->firstOrFail()->rank);
        $this->assertSame(4, $appC->selection()->firstOrFail()->rank);
    }

    public function test_academic_track_orders_strictly_by_perbup_criteria(): void
    {
        // 1: IPK 3.90, Masuk 2023, Akr B
        $app1 = $this->application(ApplicationType::AKADEMIK, ['ipk' => 3.90], null, [
            'ipk' => 3.90,
            'tahun_masuk' => 2023,
            'akreditasi_perguruan_tinggi' => 'B',
        ]);

        // 2: IPK 3.80, Masuk 2020, Akr Unggul (Kalah dari 1 karena IPK, tapi menang dari 3 karena Angkatan lebih awal)
        $app2 = $this->application(ApplicationType::AKADEMIK, ['ipk' => 3.80], null, [
            'ipk' => 3.80,
            'tahun_masuk' => 2020,
            'akreditasi_perguruan_tinggi' => 'Unggul',
        ]);

        // 3: IPK 3.80, Masuk 2021, Akr Unggul (Kalah dari 2 karena angkatan)
        $app3 = $this->application(ApplicationType::AKADEMIK, ['ipk' => 3.80], null, [
            'ipk' => 3.80,
            'tahun_masuk' => 2021,
            'akreditasi_perguruan_tinggi' => 'Unggul',
        ]);

        // 4: IPK 3.80, Masuk 2020, Akr Baik (Kalah dari 2 karena akreditasi)
        $app4 = $this->application(ApplicationType::AKADEMIK, ['ipk' => 3.80], null, [
            'ipk' => 3.80,
            'tahun_masuk' => 2020,
            'akreditasi_perguruan_tinggi' => 'Baik',
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($app1);
        $scoring->calculate($app2);
        $scoring->calculate($app3);
        $scoring->calculate($app4);

        $scoring->recalculateRanking();

        $this->assertSame(1, $app1->selection()->firstOrFail()->rank);
        $this->assertSame(2, $app2->selection()->firstOrFail()->rank);
        $this->assertSame(3, $app4->selection()->firstOrFail()->rank);
        $this->assertSame(4, $app3->selection()->firstOrFail()->rank);
    }

    public function test_non_academic_track_orders_strictly_by_perbup_criteria(): void
    {
        $this->seed(\Database\Seeders\PrestasiTrackSeeder::class);

        // 1: Juara 1 Internasional, non-ormawa
        $app1 = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [
            'tahun_masuk' => 2023,
            'akreditasi_perguruan_tinggi' => 'B',
        ], [
            ['tingkat' => 'internasional', 'peringkat' => 'Juara 1', 'is_pengurus_inti_ormawa' => false],
        ]);

        // 2: Juara 1 Nasional, ormawa (Kalah dari 1 karena tingkat lomba, tapi menang dari 3 karena Ormawa)
        $app2 = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [
            'tahun_masuk' => 2021,
            'akreditasi_perguruan_tinggi' => 'B',
        ], [
            ['tingkat' => 'nasional', 'peringkat' => 'Juara 1', 'is_pengurus_inti_ormawa' => false],
            ['tingkat' => 'kampus', 'peringkat' => 'Ketua', 'is_pengurus_inti_ormawa' => true, 'jabatan_ormawa' => 'Ketua BEM'],
        ]);

        // 3: Juara 1 Nasional, non-ormawa
        $app3 = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [
            'tahun_masuk' => 2021,
            'akreditasi_perguruan_tinggi' => 'B',
        ], [
            ['tingkat' => 'nasional', 'peringkat' => 'Juara 1', 'is_pengurus_inti_ormawa' => false],
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($app1);
        $scoring->calculate($app2);
        $scoring->calculate($app3);

        $scoring->recalculateRanking();

        $this->assertSame(1, $app1->selection()->firstOrFail()->rank);
        $this->assertSame(2, $app2->selection()->firstOrFail()->rank);
        $this->assertSame(3, $app3->selection()->firstOrFail()->rank);
    }

    public function test_disability_track_orders_strictly_by_perbup_criteria(): void
    {
        $this->seed(\Database\Seeders\DisabilityTrackSeeder::class);

        // 1: Masuk 2020, Akr Unggul
        $app1 = $this->application(ApplicationType::DISABILITAS, [
            'disability_type' => 'TUNANETRA',
            'disability_grade' => 'BERAT',
        ], null, [
            'tahun_masuk' => 2020,
            'akreditasi_perguruan_tinggi' => 'Unggul',
        ]);

        // 2: Masuk 2020, Akr Baik Sekali (Kalah dari 1 karena akreditasi)
        $app2 = $this->application(ApplicationType::DISABILITAS, [
            'disability_type' => 'TUNANETRA',
            'disability_grade' => 'BERAT',
        ], null, [
            'tahun_masuk' => 2020,
            'akreditasi_perguruan_tinggi' => 'Baik Sekali',
        ]);

        // 3: Masuk 2022, Akr Unggul (Kalah dari 1 dan 2 karena tahun masuk lebih akhir)
        $app3 = $this->application(ApplicationType::DISABILITAS, [
            'disability_type' => 'TUNANETRA',
            'disability_grade' => 'BERAT',
        ], null, [
            'tahun_masuk' => 2022,
            'akreditasi_perguruan_tinggi' => 'Unggul',
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($app1);
        $scoring->calculate($app2);
        $scoring->calculate($app3);

        $scoring->recalculateRanking();

        $this->assertSame(1, $app1->selection()->firstOrFail()->rank);
        $this->assertSame(2, $app2->selection()->firstOrFail()->rank);
        $this->assertSame(3, $app3->selection()->firstOrFail()->rank);
    }

    private function application(
        ApplicationType $type,
        array $profileOverrides,
        ?int $jalurBeasiswaId = null,
        array $pendidikanOverrides = [],
        array $prestasiList = []
    ): Application {
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

        $pendaftaran->pendidikan()->create(array_merge([
            'nim' => fake()->unique()->bothify('NIM-#####'),
            'universitas' => 'Universitas Uji',
            'status_perguruan_tinggi' => 'negeri',
            'fakultas' => 'Teknik',
            'jurusan' => 'Informatika',
            'jenjang' => 'S1',
            'semester' => $profileOverrides['semester'] ?? 4,
            'ipk' => $profileOverrides['ipk'] ?? 3.00,
            'nilai_raport' => $profileOverrides['nilai_raport'] ?? 80.0,
            'tahun_masuk' => $profileOverrides['tahun_masuk'] ?? 2022,
            'akreditasi_perguruan_tinggi' => $profileOverrides['akreditasi_perguruan_tinggi'] ?? 'B',
            'alamat_perguruan_tinggi' => 'Palangka Raya',
            'no_telp_perguruan_tinggi' => '0812345678',
        ], $pendidikanOverrides));

        foreach ($prestasiList as $prestasi) {
            $pendaftaran->prestasis()->create(array_merge([
                'jenis' => 'non_akademik',
                'nama_prestasi' => 'Lomba Uji',
                'tingkat' => 'nasional',
                'peringkat' => 'Juara 1',
                'penyelenggara' => 'DIKTI',
                'tahun' => 2024,
            ], $prestasi));
        }

        return Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'pendaftaran_id' => $pendaftaran->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => $type,
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }
}
