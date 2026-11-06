<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Exports\CandidatesExport;
use App\Exports\CandidatesWorkbookExport;
use App\Models\Application;
use App\Models\JalurBeasiswa;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\Selection;
use App\Models\User;
use App\Models\Village;
use App\Services\SelectionWorkflowService;
use Database\Seeders\JalurBeasiswaSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SelectionWorkbookExportTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Village $village;

    private JalurBeasiswa $reguler;

    private JalurBeasiswa $nonReguler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(JalurBeasiswaSeeder::class);

        $this->village = Village::query()->firstOrFail();

        $this->operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $this->village->kabupaten_id,
        ]);
        $this->operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->reguler = JalurBeasiswa::query()->where('kode', 'REGULER')->firstOrFail();
        $this->nonReguler = JalurBeasiswa::query()->where('kode', 'NON_REGULER')->firstOrFail();
    }

    public function test_workbook_export_creates_one_sheet_per_jalur_and_kategori(): void
    {
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, rank: 1);
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->nonReguler, rank: 1);
        $this->candidate(ApplicationType::AKADEMIK, $this->reguler, rank: 1);

        $sheets = (new CandidatesWorkbookExport($this->operator->kabupaten_id, ApplicationType::TIDAK_MAMPU))->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('Tidak Mampu - Reguler', $sheets[0]->title());
        $this->assertSame('Tidak Mampu - Non Reguler', $sheets[1]->title());
        $this->assertCount(1, $sheets[0]->collection());
        $this->assertCount(1, $sheets[1]->collection());
    }

    public function test_workbook_export_respects_selected_jalur_beasiswa_filter(): void
    {
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, rank: 1);
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->nonReguler, rank: 1);

        $sheets = (new CandidatesWorkbookExport(
            $this->operator->kabupaten_id,
            ApplicationType::TIDAK_MAMPU,
            $this->nonReguler->id,
        ))->sheets();

        $this->assertCount(1, $sheets);
        $this->assertSame('Tidak Mampu - Non Reguler', $sheets[0]->title());
    }

    public function test_workbook_export_without_application_type_creates_sheets_for_all_jalur_and_kategori(): void
    {
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, rank: 1);
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->nonReguler, rank: 1);
        $this->candidate(ApplicationType::AKADEMIK, $this->reguler, rank: 1);

        $sheets = (new CandidatesWorkbookExport($this->operator->kabupaten_id))->sheets();

        $this->assertCount(3, $sheets);
        $this->assertSame('Akademik - Reguler', $sheets[0]->title());
        $this->assertSame('Tidak Mampu - Reguler', $sheets[1]->title());
        $this->assertSame('Tidak Mampu - Non Reguler', $sheets[2]->title());
    }

    public function test_jalur_ini_export_is_a_single_sheet_containing_all_selected_categories(): void
    {
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, rank: 1);
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->nonReguler, rank: 1);

        $workflow = app(SelectionWorkflowService::class);

        $singleSheet = $workflow->exportCandidates($this->operator, ApplicationType::TIDAK_MAMPU);

        $this->assertInstanceOf(CandidatesExport::class, $singleSheet);
        $this->assertCount(2, $singleSheet->collection());

        $allJalur = $workflow->exportCandidatesWorkbook($this->operator);

        $this->assertInstanceOf(CandidatesWorkbookExport::class, $allJalur);
        $this->assertCount(2, $allJalur->sheets());
    }

    public function test_import_reads_decisions_from_all_exported_sheets(): void
    {
        $reguler = $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, rank: 1);
        $nonReguler = $this->candidate(ApplicationType::TIDAK_MAMPU, $this->nonReguler, rank: 1);

        Storage::fake('local');
        $path = 'exports/rekap-seleksi-tidak-mampu.xlsx';
        Excel::store(
            new CandidatesWorkbookExport($this->operator->kabupaten_id, ApplicationType::TIDAK_MAMPU),
            $path,
            'local',
        );

        $file = new UploadedFile(
            Storage::disk('local')->path($path),
            'rekap-seleksi-tidak-mampu.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $imported = app(SelectionWorkflowService::class)->importDecisionsFromExcel($this->operator, $file);

        $this->assertSame(2, $imported);
        $this->assertSame(ApplicationStatus::DITERIMA->value, $reguler->fresh()->selection->manual_decision);
        $this->assertSame(ApplicationStatus::DITERIMA->value, $nonReguler->fresh()->selection->manual_decision);
    }

    public function test_export_reads_lowercase_manual_decisions_case_insensitively(): void
    {
        $candidate = $this->candidate(ApplicationType::AKADEMIK, $this->reguler, rank: 1);
        $candidate->selection->update(['manual_decision' => 'diterima']);

        $export = new CandidatesExport($this->operator->kabupaten_id, ApplicationType::AKADEMIK);
        $row = $export->map($export->collection()->first());

        $this->assertSame(ApplicationStatus::DITERIMA->value, $row[19]);
    }

    private function candidate(ApplicationType $type, JalurBeasiswa $jalur, int $rank): Application
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $this->village->id,
            'nik' => '6212'.str_pad((string) $student->id, 12, '0', STR_PAD_LEFT),
            'nim' => 'KHM-WB-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Sistem Informasi',
            'semester' => 4,
            'ipk' => 3.50,
            'alamat' => 'Jalan Uji No. 123',
        ]);

        $periode = Periode::query()->firstOrCreate(
            ['tahun' => (int) config('kartu_hebat.current_period')],
            [
                'nama' => config('kartu_hebat.current_period'),
                'status' => 'aktif',
                'tanggal_mulai' => now()->subDay(),
                'tanggal_selesai' => now()->addDays(30),
            ],
        );

        $kategori = KategoriBeasiswa::query()->firstOrCreate(
            ['periode_id' => $periode->id, 'kode' => $type->value],
            ['nama' => $type->label(), 'aktif' => true, 'urutan' => 1],
        );

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'jalur_beasiswa_id' => $jalur->id,
            'nomor_pendaftaran' => 'REG-WB-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'status' => 'submitted',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => 'KHM-WB-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'nominal_ukt' => 3000000,
        ]);

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'pendaftaran_id' => $pendaftaran->id,
            'nomor_pengajuan' => 'KHM-WB-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'periode' => $periode->nama,
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'application_type' => $type,
            'submitted_at' => now(),
        ]);

        Selection::query()->create([
            'application_id' => $application->id,
            'final_score' => 80.0,
            'rank' => $rank,
        ]);

        return $application;
    }
}
