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
use Database\Seeders\DisabilityTrackSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\PrestasiTrackSeeder;
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
        ], null, ['tahun_masuk' => 2023, 'akreditasi_perguruan_tinggi' => 'C']);

        // B: Desil 2, IPK 3.99 (harus kalah dari A karena Desil 1 > Desil 2)
        $appB = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 2,
            'ipk' => 3.99,
        ], null, ['tahun_masuk' => 2020, 'akreditasi_perguruan_tinggi' => 'Unggul']);

        // C: Desil 2, IPK 3.80, Masuk 2023, Akr B
        $appC = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 2,
            'ipk' => 3.80,
        ], null, ['tahun_masuk' => 2023, 'akreditasi_perguruan_tinggi' => 'B']);

        // D: Desil 2, IPK 3.80, Masuk 2021 (harus menang dari C karena angkatan lebih awal)
        $appD = $this->application(ApplicationType::TIDAK_MAMPU, [
            'desil_sosial' => 2,
            'ipk' => 3.80,
        ], null, ['tahun_masuk' => 2021, 'akreditasi_perguruan_tinggi' => 'B']);

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
        $this->seed(PrestasiTrackSeeder::class);

        // 1: Ketua BEM Universitas (pengurus inti) -> Tier 1
        $app1 = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [
            'tahun_masuk' => 2023,
            'akreditasi_perguruan_tinggi' => 'B',
        ], [
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'universitas', 'posisi_organisasi' => 'ketua', 'is_pengurus_inti_ormawa' => true, 'jabatan_ormawa' => 'Ketua BEM', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
        ]);

        // 2: Juara 1 Internasional (non-ormawa) -> Tier 2, menang dari Juara 1 Nasional
        $app2 = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [
            'tahun_masuk' => 2021,
            'akreditasi_perguruan_tinggi' => 'B',
        ], [
            ['kategori_prestasi' => 'lomba', 'tingkat' => 'internasional', 'peringkat' => 'juara 1', 'is_pengurus_inti_ormawa' => false],
        ]);

        // 3: Juara 1 Nasional (non-ormawa) -> Tier 2, kalah tingkat dari app2
        $app3 = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [
            'tahun_masuk' => 2021,
            'akreditasi_perguruan_tinggi' => 'B',
        ], [
            ['kategori_prestasi' => 'lomba', 'tingkat' => 'nasional', 'peringkat' => 'juara 1', 'is_pengurus_inti_ormawa' => false],
        ]);

        // 4: Anggota BEM Universitas -> Tier 3, kalah dari semua juara
        $app4 = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [
            'tahun_masuk' => 2021,
            'akreditasi_perguruan_tinggi' => 'B',
        ], [
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'universitas', 'posisi_organisasi' => 'anggota', 'is_pengurus_inti_ormawa' => false, 'jabatan_ormawa' => 'Anggota', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
        ]);

        // 5: Peserta lomba internasional -> Tier 4, paling bawah
        $app5 = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [
            'tahun_masuk' => 2021,
            'akreditasi_perguruan_tinggi' => 'B',
        ], [
            ['kategori_prestasi' => 'lomba', 'tingkat' => 'internasional', 'peringkat' => 'peserta', 'is_pengurus_inti_ormawa' => false],
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($app1);
        $scoring->calculate($app2);
        $scoring->calculate($app3);
        $scoring->calculate($app4);
        $scoring->calculate($app5);

        $scoring->recalculateRanking();

        $this->assertSame(1, $app1->selection()->firstOrFail()->rank);
        $this->assertSame(2, $app2->selection()->firstOrFail()->rank);
        $this->assertSame(3, $app3->selection()->firstOrFail()->rank);
        $this->assertSame(4, $app4->selection()->firstOrFail()->rank);
        $this->assertSame(5, $app5->selection()->firstOrFail()->rank);
    }

    public function test_non_academic_pengurus_inti_orders_position_then_organisation_level(): void
    {
        $this->seed(PrestasiTrackSeeder::class);

        // 1: Ketua BEM Fakultas (Ketua > tingkat Universitas)
        $ketuaFakultas = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'fakultas', 'posisi_organisasi' => 'ketua', 'is_pengurus_inti_ormawa' => true, 'jabatan_ormawa' => 'Ketua BEM Fakultas', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
        ]);

        // 2: Koordinator BEM Universitas (jabatan di bawah Ketua)
        $koordinatorUniversitas = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'universitas', 'posisi_organisasi' => 'koordinator', 'is_pengurus_inti_ormawa' => true, 'jabatan_ormawa' => 'Koordinator Bidang', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
        ]);

        // 3: Sekretaris BEM Universitas
        $sekretarisUniversitas = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'universitas', 'posisi_organisasi' => 'sekretaris', 'is_pengurus_inti_ormawa' => true, 'jabatan_ormawa' => 'Sekretaris', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($ketuaFakultas);
        $scoring->calculate($koordinatorUniversitas);
        $scoring->calculate($sekretarisUniversitas);

        $scoring->recalculateRanking();

        $this->assertSame(1, $ketuaFakultas->selection()->firstOrFail()->rank);
        $this->assertSame(2, $sekretarisUniversitas->selection()->firstOrFail()->rank);
        $this->assertSame(3, $koordinatorUniversitas->selection()->firstOrFail()->rank);
    }

    public function test_non_academic_uses_best_record_instead_of_stacking(): void
    {
        $this->seed(PrestasiTrackSeeder::class);

        // Punya 3 SK ormawa tetapi hanya satu yang dihitung (Ketua Universitas).
        $banyakSertifikat = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'fakultas', 'posisi_organisasi' => 'anggota', 'is_pengurus_inti_ormawa' => false, 'jabatan_ormawa' => 'Anggota', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'fakultas', 'posisi_organisasi' => 'koordinator', 'is_pengurus_inti_ormawa' => true, 'jabatan_ormawa' => 'Koordinator Bidang', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'universitas', 'posisi_organisasi' => 'ketua', 'is_pengurus_inti_ormawa' => true, 'jabatan_ormawa' => 'Ketua BEM', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
        ]);

        $juara3Nasional = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'lomba', 'tingkat' => 'nasional', 'peringkat' => 'juara 3', 'is_pengurus_inti_ormawa' => false],
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($banyakSertifikat);
        $scoring->calculate($juara3Nasional);

        $scoring->recalculateRanking();

        $this->assertSame(1, $banyakSertifikat->selection()->firstOrFail()->rank);
        $this->assertSame(2, $juara3Nasional->selection()->firstOrFail()->rank);
    }

    public function test_non_academic_peserta_kabupaten_ranks_last(): void
    {
        $this->seed(PrestasiTrackSeeder::class);

        $peserta = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'lomba', 'tingkat' => 'kabupaten', 'peringkat' => 'peserta', 'is_pengurus_inti_ormawa' => false],
        ]);

        $koordinatorFakultas = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'fakultas', 'posisi_organisasi' => 'koordinator', 'is_pengurus_inti_ormawa' => true, 'jabatan_ormawa' => 'Koordinator Bidang', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($peserta);
        $scoring->calculate($koordinatorFakultas);

        $scoring->recalculateRanking();

        $this->assertSame(1, $koordinatorFakultas->selection()->firstOrFail()->rank);
        $this->assertSame(2, $peserta->selection()->firstOrFail()->rank);
    }

    public function test_non_academic_anggota_ormawa_outranks_peserta_lomba(): void
    {
        $this->seed(PrestasiTrackSeeder::class);

        $anggotaFakultas = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'organisasi', 'tingkat_organisasi' => 'fakultas', 'posisi_organisasi' => 'anggota', 'is_pengurus_inti_ormawa' => false, 'jabatan_ormawa' => 'Anggota', 'tingkat' => 'kampus', 'peringkat' => 'Pengurus Organisasi'],
        ]);

        $pesertaInternasional = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'lomba', 'tingkat' => 'internasional', 'peringkat' => 'peserta', 'is_pengurus_inti_ormawa' => false],
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($anggotaFakultas);
        $scoring->calculate($pesertaInternasional);

        $scoring->recalculateRanking();

        $this->assertSame(1, $anggotaFakultas->selection()->firstOrFail()->rank);
        $this->assertSame(2, $pesertaInternasional->selection()->firstOrFail()->rank);
    }

    public function test_non_academic_juara_3_kabupaten_outranks_peserta_internasional(): void
    {
        $this->seed(PrestasiTrackSeeder::class);

        $juara3Kabupaten = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'lomba', 'tingkat' => 'kabupaten', 'peringkat' => 'juara 3', 'is_pengurus_inti_ormawa' => false],
        ]);

        $pesertaInternasional = $this->application(ApplicationType::NON_AKADEMIK, ['ipk' => 3.50], null, [], [
            ['kategori_prestasi' => 'lomba', 'tingkat' => 'internasional', 'peringkat' => 'peserta', 'is_pengurus_inti_ormawa' => false],
        ]);

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($juara3Kabupaten);
        $scoring->calculate($pesertaInternasional);

        $scoring->recalculateRanking();

        $this->assertSame(1, $juara3Kabupaten->selection()->firstOrFail()->rank);
        $this->assertSame(2, $pesertaInternasional->selection()->firstOrFail()->rank);
    }

    public function test_disability_track_orders_strictly_by_perbup_criteria(): void
    {
        $this->seed(DisabilityTrackSeeder::class);

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
            'nominal_ukt' => $profileOverrides['nominal_ukt'] ?? 3500000,
            'tahun_masuk' => $profileOverrides['tahun_masuk'] ?? 2022,
            'akreditasi_perguruan_tinggi' => $profileOverrides['akreditasi_perguruan_tinggi'] ?? 'B',
            'alamat_perguruan_tinggi' => 'Palangka Raya',
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
