<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\JalurBeasiswa;
use App\Models\Kabupaten;
use App\Models\KategoriBeasiswa;
use App\Models\Kecamatan;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\JalurBeasiswaSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionUktSummaryTest extends TestCase
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

    public function test_selection_page_shows_total_ukt_per_jalur_pengajuan_and_jalur_beasiswa(): void
    {
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, 3000000);
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->nonReguler, 5000000);
        $this->candidate(ApplicationType::AKADEMIK, $this->reguler, 2000000);

        $response = $this->actingAs($this->operator)->get(route('operator.selection', [
            'application_type' => ApplicationType::TIDAK_MAMPU->value,
        ]));

        $response->assertSuccessful();
        $response->assertSee('Total UKT Tidak Mampu');
        $response->assertSee('Total UKT: Rp 2.000.000');
        $response->assertSee('UKT: Rp 8.000.000');
        $response->assertSee('UKT: Rp 3.000.000');
        $response->assertSee('UKT: Rp 5.000.000');
    }

    public function test_selection_page_ukt_total_follows_active_jalur_beasiswa_filter(): void
    {
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, 3000000);
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->nonReguler, 5000000);

        $response = $this->actingAs($this->operator)->get(route('operator.selection', [
            'application_type' => ApplicationType::TIDAK_MAMPU->value,
            'jalur_beasiswa_id' => $this->reguler->id,
        ]));

        $response->assertSuccessful();
        $response->assertSeeInOrder(['Kategori:', 'Semua', 'UKT: Rp 8.000.000', 'Reguler']);
        $response->assertSee('Total UKT Reguler');
        $response->assertSee('Rp 3.000.000');
    }

    public function test_selection_page_ukt_total_excludes_candidates_from_other_kabupaten(): void
    {
        $otherKabupaten = Kabupaten::query()->create([
            'code' => '9999',
            'name' => 'Kabupaten Lain',
        ]);
        $otherKecamatan = Kecamatan::query()->create([
            'kabupaten_id' => $otherKabupaten->id,
            'code' => '999901',
            'name' => 'Kecamatan Lain',
        ]);
        $otherVillage = Village::query()->create([
            'kabupaten_id' => $otherKabupaten->id,
            'kecamatan_id' => $otherKecamatan->id,
            'code' => '9999010001',
            'name' => 'Desa Lain',
            'type' => 'desa',
        ]);

        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, 999000000, $otherVillage);
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, 1000000);

        $response = $this->actingAs($this->operator)->get(route('operator.selection', [
            'application_type' => ApplicationType::TIDAK_MAMPU->value,
        ]));

        $response->assertSuccessful();
        $response->assertSee('Rp 1.000.000');
        $response->assertDontSee('Rp 999.000.000');
    }

    public function test_selection_page_ukt_total_handles_candidates_without_nominal_ukt(): void
    {
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, null);
        $this->candidate(ApplicationType::TIDAK_MAMPU, $this->reguler, 4000000);

        $response = $this->actingAs($this->operator)->get(route('operator.selection', [
            'application_type' => ApplicationType::TIDAK_MAMPU->value,
        ]));

        $response->assertSuccessful();
        $response->assertSee('Rp 4.000.000');
    }

    private function candidate(
        ApplicationType $type,
        JalurBeasiswa $jalur,
        ?float $nominalUkt,
        ?Village $village = null,
    ): Application {
        $village ??= $this->village;

        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212'.str_pad((string) $student->id, 12, '0', STR_PAD_LEFT),
            'nim' => 'KHM-UKT-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
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
            'nomor_pendaftaran' => 'REG-UKT-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'status' => 'submitted',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => 'KHM-UKT-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'nominal_ukt' => $nominalUkt,
        ]);

        return Application::query()->create([
            'mahasiswa_id' => $student->id,
            'pendaftaran_id' => $pendaftaran->id,
            'nomor_pengajuan' => 'KHM-UKT-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'periode' => $periode->nama,
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'application_type' => $type,
            'submitted_at' => now(),
        ]);
    }
}
