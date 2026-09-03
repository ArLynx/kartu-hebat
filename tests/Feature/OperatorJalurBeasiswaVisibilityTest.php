<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Exports\ApplicationsRecapExport;
use App\Exports\CandidatesExport;
use App\Models\Application;
use App\Models\JalurBeasiswa;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Selection;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\JalurBeasiswaSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorJalurBeasiswaVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private JalurBeasiswa $jalurReguler;

    private JalurBeasiswa $jalurNonReguler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(JalurBeasiswaSeeder::class);

        $this->jalurReguler = JalurBeasiswa::query()->where('kode', 'REGULER')->firstOrFail();
        $this->jalurNonReguler = JalurBeasiswa::query()->where('kode', 'NON_REGULER')->firstOrFail();
    }

    public function test_operator_application_index_shows_reguler_and_non_reguler_badges(): void
    {
        $operator = $this->kabupatenOperator();

        $appReguler = $this->createApplicationWithJalur($operator, ApplicationType::AKADEMIK, $this->jalurReguler);
        $appNonReguler = $this->createApplicationWithJalur($operator, ApplicationType::AKADEMIK, $this->jalurNonReguler);

        $response = $this->actingAs($operator)->get(route('operator.applications.index'));

        $response->assertSuccessful();
        $response->assertSee('Reguler');
        $response->assertSee('Non Reguler');
        $response->assertSee($appReguler->nomor_pengajuan);
        $response->assertSee($appNonReguler->nomor_pengajuan);
    }

    public function test_operator_application_index_filters_by_jalur_beasiswa(): void
    {
        $operator = $this->kabupatenOperator();

        $appReguler = $this->createApplicationWithJalur($operator, ApplicationType::AKADEMIK, $this->jalurReguler);
        $appNonReguler = $this->createApplicationWithJalur($operator, ApplicationType::AKADEMIK, $this->jalurNonReguler);

        $response = $this->actingAs($operator)->get(route('operator.applications.index', [
            'jalur_beasiswa_id' => $this->jalurReguler->id,
        ]));

        $response->assertSuccessful();
        $response->assertSee($appReguler->nomor_pengajuan);
        $response->assertDontSee($appNonReguler->nomor_pengajuan);
    }

    public function test_operator_application_show_displays_kategori_mahasiswa(): void
    {
        $operator = $this->kabupatenOperator();
        $application = $this->createApplicationWithJalur($operator, ApplicationType::AKADEMIK, $this->jalurReguler);

        $response = $this->actingAs($operator)->get(route('operator.applications.show', $application));

        $response->assertSuccessful();
        $response->assertSee('Kategori Mahasiswa');
        $response->assertSee('Reguler');
    }

    public function test_operator_selection_shows_reguler_and_non_reguler(): void
    {
        $operator = $this->kabupatenOperator();
        $appReguler = $this->createApplicationWithJalur(
            $operator,
            ApplicationType::AKADEMIK,
            $this->jalurReguler,
            ApplicationStatus::SELEKSI_KABUPATEN,
            score: 95.0,
            rank: 1,
        );

        $response = $this->actingAs($operator)->get(route('operator.selection', [
            'application_type' => 'AKADEMIK',
        ]));

        $response->assertSuccessful();
        $response->assertSee('Reguler');
        $response->assertSee($appReguler->nomor_pengajuan);
    }

    public function test_operator_selection_filters_by_jalur_beasiswa(): void
    {
        $operator = $this->kabupatenOperator();
        $appReguler = $this->createApplicationWithJalur(
            $operator,
            ApplicationType::AKADEMIK,
            $this->jalurReguler,
            ApplicationStatus::SELEKSI_KABUPATEN,
            score: 95.0,
            rank: 1,
        );
        $appNonReguler = $this->createApplicationWithJalur(
            $operator,
            ApplicationType::AKADEMIK,
            $this->jalurNonReguler,
            ApplicationStatus::SELEKSI_KABUPATEN,
            score: 90.0,
            rank: 2,
        );

        $response = $this->actingAs($operator)->get(route('operator.selection', [
            'application_type' => 'AKADEMIK',
            'jalur_beasiswa_id' => $this->jalurReguler->id,
        ]));

        $response->assertSuccessful();
        $response->assertSee($appReguler->nomor_pengajuan);
        $response->assertDontSee($appNonReguler->nomor_pengajuan);
    }

    public function test_operator_dashboard_shows_kategori_mahasiswa(): void
    {
        $operator = $this->kabupatenOperator();
        $this->createApplicationWithJalur($operator, ApplicationType::AKADEMIK, $this->jalurReguler);

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertSuccessful();
        $response->assertSee('Reguler');
    }

    public function test_operator_reconciliation_shows_kategori_mahasiswa(): void
    {
        $operator = $this->kabupatenOperator();
        $this->createApplicationWithJalur(
            $operator,
            ApplicationType::AKADEMIK,
            $this->jalurNonReguler,
            ApplicationStatus::VERIFIKASI_DINAS,
        );

        $response = $this->actingAs($operator)->get(route('operator.reconciliation'));

        $response->assertSuccessful();
        $response->assertSee('Non Reguler');
    }

    public function test_candidates_export_includes_kategori_mahasiswa(): void
    {
        $operator = $this->kabupatenOperator();
        $application = $this->createApplicationWithJalur(
            $operator,
            ApplicationType::AKADEMIK,
            $this->jalurReguler,
            ApplicationStatus::SELEKSI_KABUPATEN,
        );

        $export = new CandidatesExport($operator->kabupaten_id, ApplicationType::AKADEMIK);
        $headings = $export->headings();
        $this->assertContains('Kategori Mahasiswa', $headings);

        $rows = $export->collection();
        $this->assertCount(1, $rows);

        $mapped = $export->map($rows->first());
        $this->assertSame('Reguler', $mapped[1]);
    }

    public function test_applications_recap_export_includes_kategori_mahasiswa(): void
    {
        $operator = $this->kabupatenOperator();
        $this->createApplicationWithJalur(
            $operator,
            ApplicationType::AKADEMIK,
            $this->jalurNonReguler,
            ApplicationStatus::SELEKSI_KABUPATEN,
        );

        $export = ApplicationsRecapExport::forUser($operator);
        $headings = $export->headings();
        $this->assertContains('Kategori Mahasiswa', $headings);

        $rows = $export->collection();
        $this->assertCount(1, $rows);

        $mapped = $export->map($rows->first());
        $this->assertSame('Non Reguler', $mapped[2]);
    }

    private function kabupatenOperator(): User
    {
        $village = Village::query()->firstOrFail();

        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function createApplicationWithJalur(
        User $operator,
        ApplicationType $type,
        JalurBeasiswa $jalur,
        ApplicationStatus $status = ApplicationStatus::SELEKSI_KABUPATEN,
        float $score = 80.0,
        ?int $rank = 1,
    ): Application {
        $village = Village::query()->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212'.str_pad((string) $student->id, 12, '0', STR_PAD_LEFT),
            'nim' => 'KHM-NIM-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Sistem Informasi',
            'semester' => 4,
            'ipk' => 3.80,
            'alamat' => 'Jalan Uji No. 123',
        ]);

        $periode = Periode::query()->firstOrCreate(
            ['tahun' => (int) config('kartu_hebat.current_period')],
            [
                'nama' => 'Periode 2026',
                'is_active' => true,
                'tanggal_mulai' => now()->subDay(),
                'tanggal_selesai' => now()->addDays(30),
            ],
        );

        $kategori = KategoriBeasiswa::query()->firstOrCreate(
            ['periode_id' => $periode->id, 'kode' => 'AKADEMIK'],
            ['nama' => 'Prestasi Akademik', 'aktif' => true, 'urutan' => 1],
        );

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'jalur_beasiswa_id' => $jalur->id,
            'nomor_pendaftaran' => 'REG-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'status' => 'submitted',
        ]);

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'pendaftaran_id' => $pendaftaran->id,
            'nomor_pengajuan' => 'KHM-2026-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'periode' => config('kartu_hebat.current_period'),
            'status' => $status,
            'application_type' => $type,
            'submitted_at' => now(),
        ]);

        Selection::query()->create([
            'application_id' => $application->id,
            'final_score' => $score,
            'rank' => $rank,
        ]);

        return $application;
    }
}
