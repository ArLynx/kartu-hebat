<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Exports\TmsCandidatesExport;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use App\Models\Periode;
use App\Models\Sanggahan;
use App\Models\User;
use App\Models\VerificationLog;
use App\Models\Village;
use App\Services\AgencyVerificationService;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class OperatorSelectionTabsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
    }

    public function test_operator_kabupaten_can_view_selection_page_with_2_tabs(): void
    {
        $operator = $this->kabupatenOperator();

        // Tab 1 (Lolos - Default)
        $response = $this->actingAs($operator)->get(route('operator.selection'));

        $response->assertSuccessful();
        $response->assertSee('Calon Penerima Lolos (Siap Ditetapkan)');
        $response->assertSee('Daftar Berkas TMS (Gugur Administrasi / Masa Sanggah)');
        $response->assertSee('Unduh Excel Jalur Ini');
        $response->assertSee('Unduh Semua Jalur');
        $response->assertDontSee('Unduh Excel TMS (Jalur Ini)');

        // Tab 2 (TMS)
        $tmsResponse = $this->actingAs($operator)->get(route('operator.selection', ['tab' => 'tms']));
        $tmsResponse->assertSuccessful();
        $tmsResponse->assertSee('Unduh Excel TMS (Jalur Ini)');
        $tmsResponse->assertSee('Unduh TMS (Semua)');
        $tmsResponse->assertSee('Hak Sanggah Mahasiswa');
    }

    public function test_operator_kabupaten_can_view_tms_tab_with_rejection_notes(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        $student = User::factory()->create([
            'name' => 'Budi Santoso Gugur',
            'role' => UserRole::MAHASISWA,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212999988887777',
            'nim' => 'NIM-TMS-001',
            'universitas' => 'Universitas Palangka Raya',
            'program_studi' => 'Teknik Sipil',
            'semester' => 4,
            'ipk' => 2.80,
            'alamat' => 'Desa Puruk Cahu',
        ]);

        $app = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-2026-TMS001',
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::TMS,
            'application_type' => ApplicationType::AKADEMIK,
        ]);

        AgencyVerification::query()->create([
            'application_id' => $app->id,
            'verifier_id' => $operator->id,
            'agency' => AgencyVerificationService::STAGE_DUKCAPIL,
            'decision' => VerificationDecision::TMS,
            'notes' => 'NIK tidak terdaftar dalam database SIAK Murung Raya.',
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($operator)
            ->get(route('operator.selection', ['tab' => 'tms', 'application_type' => 'AKADEMIK']));

        $response->assertSuccessful();
        $response->assertSee('Budi Santoso Gugur');
        $response->assertSee('KHM-2026-TMS001');
        $response->assertSee('Dukcapil');
        $response->assertSee('NIK tidak terdaftar dalam database SIAK Murung Raya.');
        $response->assertSee('Belum Ada Sanggahan');
    }

    public function test_operator_kabupaten_can_see_sanggahan_status_and_link_to_antrean_sanggahan_index(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        $periode = Periode::query()->firstOrCreate(
            ['tahun' => (int) config('kartu_hebat.current_period')],
            [
                'nama' => '2026/2027 Ganjil',
                'is_active' => true,
                'tanggal_mulai' => now()->subMonths(2),
                'tanggal_selesai' => now()->addMonths(2),
            ],
        );

        $student = User::factory()->create([
            'name' => 'Rendra Saputra',
            'role' => UserRole::MAHASISWA,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212888877776666',
            'nim' => 'NIM-TMS-002',
            'universitas' => 'Universitas Muhammadiyah Palangka Raya',
            'program_studi' => 'Teknik Informatika',
            'semester' => 4,
            'ipk' => 3.20,
            'alamat' => 'Desa Bahitom',
        ]);

        $app = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-SG-002',
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::TMS,
            'application_type' => ApplicationType::AKADEMIK,
        ]);

        AgencyVerification::query()->create([
            'application_id' => $app->id,
            'verifier_id' => $operator->id,
            'agency' => AgencyVerificationService::STAGE_PENDIDIKAN,
            'decision' => VerificationDecision::TMS,
            'notes' => 'Dokumen KHS terbaca sebagai milik program studi lain.',
            'verified_at' => now(),
        ]);

        Sanggahan::query()->create([
            'application_id' => $app->id,
            'submitted_by' => $student->id,
            'periode_id' => $periode->id,
            'status' => SanggahanStatus::DIAJUKAN,
            'uraian' => 'KHS yang diunggah sudah sesuai dan tertera prodi Informatika.',
        ]);

        $response = $this->actingAs($operator)
            ->get(route('operator.selection', ['tab' => 'tms', 'application_type' => 'AKADEMIK']));

        $response->assertSuccessful();
        $response->assertSee('Rendra Saputra');
        $response->assertSee('Diajukan');
        $response->assertSee(route('operator.sanggahan.index'));
        $response->assertSee('Lihat di Antrean Sanggahan →');
    }

    public function test_operator_kabupaten_can_see_granted_sanggahan_awaiting_reverification_in_tms_tab(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        $periode = Periode::query()->firstOrCreate(
            ['tahun' => (int) config('kartu_hebat.current_period')],
            [
                'nama' => '2026/2027 Ganjil',
                'is_active' => true,
                'tanggal_mulai' => now()->subMonths(2),
                'tanggal_selesai' => now()->addMonths(2),
            ],
        );

        $student = User::factory()->create([
            'name' => 'Nadia Puspita Demo',
            'role' => UserRole::MAHASISWA,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212019000000003',
            'nim' => '2026SANGG003',
            'universitas' => 'UPR',
            'program_studi' => 'Teknik Informatika',
            'semester' => 2,
            'ipk' => 3.95,
            'alamat' => 'Desa Bahitom',
        ]);

        $app = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-SG-003',
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
            'application_type' => ApplicationType::AKADEMIK,
        ]);

        Sanggahan::query()->create([
            'application_id' => $app->id,
            'submitted_by' => $student->id,
            'periode_id' => $periode->id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'NIK pada KTP sudah benar.',
            'catatan_keputusan' => 'NIK pada KTP sudah sesuai. Keputusan TMS Dukcapil dianulir.',
            'decided_by' => $operator->id,
            'decided_at' => now(),
        ]);

        VerificationLog::query()->create([
            'application_id' => $app->id,
            'actor_id' => $operator->id,
            'from_status' => ApplicationStatus::TMS->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'sanggahan_granted',
            'notes' => 'NIK pada KTP sudah sesuai.',
            'metadata' => [
                'annulled_agencies' => ['dukcapil'],
                'annulled_verifications' => [
                    [
                        'agency' => 'dukcapil',
                        'agency_label' => 'Dukcapil',
                        'decision' => 'TMS',
                        'notes' => 'NIK pada KTP berbeda satu digit dengan data pribadi.',
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($operator)
            ->get(route('operator.selection', ['tab' => 'tms', 'application_type' => 'AKADEMIK']));

        $response->assertSuccessful();
        $response->assertSee('Nadia Puspita Demo');
        $response->assertSee('KHM-SG-003');
        $response->assertSee('Dikabulkan');
        $response->assertSee('Menunggu dinas menilai ulang');
        $response->assertSee('NIK pada KTP berbeda satu digit dengan data pribadi.');
        $response->assertSee(route('operator.sanggahan.index'));
    }

    public function test_operator_kabupaten_can_search_in_tms_tab(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        $student1 = User::factory()->create(['name' => 'Ahmad Fulan', 'role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create([
            'user_id' => $student1->id,
            'village_id' => $village->id,
            'nik' => '6212111100001111',
            'nim' => 'NIM-001',
            'universitas' => 'UPR',
            'program_studi' => 'Informatika',
            'semester' => 2,
            'ipk' => 2.50,
            'alamat' => 'Alamat 1',
        ]);
        Application::query()->create([
            'mahasiswa_id' => $student1->id,
            'nomor_pengajuan' => 'KHM-2026-AHMAD01',
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::TMS,
            'application_type' => ApplicationType::AKADEMIK,
        ]);

        $student2 = User::factory()->create(['name' => 'Siti Khadijah', 'role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create([
            'user_id' => $student2->id,
            'village_id' => $village->id,
            'nik' => '6212111100002222',
            'nim' => 'NIM-002',
            'universitas' => 'UPR',
            'program_studi' => 'Manajemen',
            'semester' => 4,
            'ipk' => 2.70,
            'alamat' => 'Alamat 2',
        ]);
        Application::query()->create([
            'mahasiswa_id' => $student2->id,
            'nomor_pengajuan' => 'KHM-2026-SITI02',
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::TMS,
            'application_type' => ApplicationType::AKADEMIK,
        ]);

        $response = $this->actingAs($operator)
            ->get(route('operator.selection', ['tab' => 'tms', 'application_type' => 'AKADEMIK', 'search' => 'Ahmad']));

        $response->assertSuccessful();
        $response->assertSee('Ahmad Fulan');
        $response->assertDontSee('Siti Khadijah');
    }

    public function test_operator_kabupaten_can_export_tms_candidates_to_excel(): void
    {
        Excel::fake();
        $operator = $this->kabupatenOperator();

        $response = $this->actingAs($operator)
            ->get(route('operator.selection.export-tms', ['periode' => '2026', 'application_type' => 'AKADEMIK']));

        $response->assertSuccessful();
        Excel::assertDownloaded('rekap-tms-administrasi-2026-akademik.xlsx');
    }

    public function test_tms_candidates_export_includes_granted_sanggahan_awaiting_reverification(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        $periode = Periode::query()->firstOrCreate(
            ['tahun' => 2026],
            ['nama' => '2026/2027 Ganjil', 'is_active' => true, 'tanggal_mulai' => now()->subMonth(), 'tanggal_selesai' => now()->addMonth()],
        );

        $student = User::factory()->create(['name' => 'Nadia Export Test', 'role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212019999990001',
            'nim' => '2026EXP001',
            'universitas' => 'UPR',
            'program_studi' => 'Informatika',
            'semester' => 2,
            'ipk' => 3.95,
            'alamat' => 'Desa Bahitom',
        ]);

        $app = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-EXP-001',
            'periode' => '2026',
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
            'application_type' => ApplicationType::AKADEMIK,
        ]);

        Sanggahan::query()->create([
            'application_id' => $app->id,
            'submitted_by' => $student->id,
            'periode_id' => $periode->id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'Koreksi NIK KTP.',
        ]);

        $export = new TmsCandidatesExport((int) $operator->kabupaten_id, '2026', ApplicationType::AKADEMIK);
        $collection = $export->collection();

        $this->assertTrue($collection->contains('id', $app->id));
        $mapped = $export->map($app);
        $this->assertStringContainsString('Dikabulkan (Menunggu dinas menilai ulang)', $mapped[15]);
    }

    public function test_non_kabupaten_operator_cannot_export_tms_candidates(): void
    {
        $dinasUser = User::factory()->create([
            'role' => UserRole::OPERATOR_DUKCAPIL,
            'kabupaten_id' => 1,
        ]);
        $dinasUser->forceFill(['two_factor_confirmed_at' => now()])->save();

        $response = $this->actingAs($dinasUser)
            ->get(route('operator.selection.export-tms'));

        $response->assertForbidden();
    }

    private function kabupatenOperator(): User
    {
        $village = Village::firstOrFail();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }
}
