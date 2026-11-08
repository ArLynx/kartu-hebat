<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\DataPribadi;
use App\Models\JalurBeasiswa;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\OrangTua;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorBkpsdmKkWarningTest extends TestCase
{
    use RefreshDatabase;

    private Periode $periode;
    private JalurBeasiswa $jalur;
    private KategoriBeasiswa $kategori;
    private Village $village;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Beasiswa 2026',
            'tanggal_mulai' => now()->subMonths(2)->format('Y-m-d'),
            'tanggal_selesai' => now()->subMonth()->format('Y-m-d'),
            'status' => 'aktif',
        ]);

        $this->jalur = JalurBeasiswa::query()->create([
            'kode' => 'REGULER',
            'nama' => 'Reguler',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $this->kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $this->periode->id,
            'kode' => 'PRESTASI_AKADEMIK',
            'nama' => 'Prestasi Akademik',
            'application_type' => ApplicationType::AKADEMIK,
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $this->seed(\Database\Seeders\RegionSeeder::class);
        $this->village = Village::query()->first();
    }

    private function createApplicant(string $name, string $nik, string $nomorKk, string $nomorPendaftaran, ?string $pekerjaanAyah = 'ASN', ?string $jabatanAyah = 'Kepala Bidang (Eselon III)'): Application
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => UserRole::MAHASISWA,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $user->id,
            'nik' => $nik,
            'nim' => 'NIM-'.$nik,
            'phone' => '081234567890',
            'universitas' => 'Universitas Palangka Raya',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jl. Jenderal Sudirman',
            'village_id' => $this->village->id,
        ]);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $this->periode->id,
            'jalur_beasiswa_id' => $this->jalur->id,
            'kategori_beasiswa_id' => $this->kategori->id,
            'nomor_pendaftaran' => $nomorPendaftaran,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap' => $name,
            'nik' => $nik,
            'nomor_kk' => $nomorKk,
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2004-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Jl. Jenderal Sudirman',
            'provinsi' => 'Kalimantan Tengah',
            'kabupaten' => 'Murung Raya',
            'kecamatan' => 'Murung',
            'desa' => 'Beriwit',
            'no_hp' => '081234567890',
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Ayah '.$name,
            'status_ayah' => 'hidup',
            'pekerjaan_ayah' => $pekerjaanAyah,
            'jabatan_ayah' => $jabatanAyah,
            'nama_ibu' => 'Ibu '.$name,
            'status_ibu' => 'hidup',
            'pekerjaan_ibu' => 'Ibu Rumah Tangga',
        ]);

        return Application::query()->create([
            'mahasiswa_id' => $user->id,
            'pendaftaran_id' => $pendaftaran->id,
            'periode' => $this->periode->tahun,
            'nomor_pengajuan' => 'APP-'.$nomorPendaftaran,
            'application_type' => ApplicationType::AKADEMIK,
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
            'submitted_at' => now(),
        ]);
    }

    public function test_bkpsdm_sees_warning_when_applicant_shares_same_kk_with_another_applicant(): void
    {
        $sharedKk = '6212010101019001';

        $appAndi = $this->createApplicant('Andi Saputra', '6212010101010001', $sharedKk, 'KHM-2026-000128');
        $appBudi = $this->createApplicant('Budi Saputra', '6212010101010002', $sharedKk, 'KHM-2026-000129');

        $bkpsdmOperator = User::factory()->create([
            'role' => UserRole::OPERATOR_BKPSDM,
            'kabupaten_id' => $this->village->kabupaten_id,
            'two_factor_confirmed_at' => now(),
        ]);

        // Periksa pengajuan Andi: harus memunculkan warning tentang kesamaan KK dengan Budi
        $response = $this->actingAs($bkpsdmOperator)
            ->get(route('operator.applications.show', $appAndi));

        $response->assertOk();
        $response->assertSee('Verifikasi BKPSDM — Deteksi Kesamaan KK');
        $response->assertSee('Terdeteksi Pendaftar Lain dalam 1 Kartu Keluarga (KK) yang Sama');
        $response->assertSee($sharedKk);
        $response->assertSee('Budi Saputra');
        $response->assertSee('KHM-2026-000129');
        $response->assertSee('Kepala Bidang (Eselon III)');
        $response->assertSee('anak ASN Eselon III ke atas dibatasi maksimal 1 anak per KK');
        $response->assertSee(route('operator.applications.show', $appBudi));

        // Data orang tua pada profil pemohon
        $response->assertSee('Nama Ayah');
        $response->assertSee('Ayah Andi Saputra');
        $response->assertSee('Pekerjaan &amp; Jabatan Ayah', false);
        $response->assertSee('ASN (Kepala Bidang (Eselon III))');
        $response->assertSee('Nama Ibu');
        $response->assertSee('Ibu Andi Saputra');
        $response->assertSee('Pekerjaan &amp; Jabatan Ibu', false);
        $response->assertSee('Ibu Rumah Tangga');

        // Penghasilan dan tanggungan telah dihapus sesuai struktur data pendaftaran
        $response->assertDontSee('Penghasilan Keluarga');
        $response->assertDontSee('Tanggungan');
    }

    public function test_other_agency_operator_does_not_see_bkpsdm_kk_warning(): void
    {
        $sharedKk = '6212010101019001';

        $appAndi = $this->createApplicant('Andi Saputra', '6212010101010001', $sharedKk, 'KHM-2026-000128');
        $this->createApplicant('Budi Saputra', '6212010101010002', $sharedKk, 'KHM-2026-000129');

        // Operator Dukcapil
        $dukcapilOperator = User::factory()->create([
            'role' => UserRole::OPERATOR_DUKCAPIL,
            'kabupaten_id' => $this->village->kabupaten_id,
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($dukcapilOperator)
            ->get(route('operator.applications.show', $appAndi));

        $response->assertOk();
        $response->assertDontSee('Verifikasi BKPSDM — Deteksi Kesamaan KK');
        $response->assertDontSee('Terdeteksi Pendaftar Lain dalam 1 Kartu Keluarga (KK) yang Sama');
    }

    public function test_bkpsdm_does_not_see_warning_when_applicant_has_unique_kk(): void
    {
        $appUnique = $this->createApplicant('Citra Lestari', '6212010101010099', '6212010101019999', 'KHM-2026-000199');

        $bkpsdmOperator = User::factory()->create([
            'role' => UserRole::OPERATOR_BKPSDM,
            'kabupaten_id' => $this->village->kabupaten_id,
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($bkpsdmOperator)
            ->get(route('operator.applications.show', $appUnique));

        $response->assertOk();
        $response->assertDontSee('Verifikasi BKPSDM — Deteksi Kesamaan KK');
        $response->assertDontSee('Terdeteksi Pendaftar Lain dalam 1 Kartu Keluarga (KK) yang Sama');
    }
}
