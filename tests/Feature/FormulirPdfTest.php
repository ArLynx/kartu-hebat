<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DataPribadi;
use App\Models\JalurBeasiswa;
use App\Models\KategoriBeasiswa;
use App\Models\OrangTua;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormulirPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_surat_permohonan_b_renders_correctly(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'email_verified_at' => now(),
        ]);

        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => '2026/2027 Ganjil',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'TIDAK_MAMPU',
            'nama' => 'Tidak Mampu',
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $jalur = JalurBeasiswa::query()->create([
            'kode' => 'REGULER',
            'nama' => 'Reguler',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'status' => 'draft',
            'nomor_pendaftaran' => 'REG-2026-0001',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap' => 'Budi Santoso',
            'nik' => '6212010101010001',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Jl. Jenderal Sudirman No. 12, Puruk Cahu',
            'desa' => 'Beriwit',
            'kecamatan' => 'Murung',
            'kabupaten' => 'Murung Raya',
            'provinsi' => 'Kalimantan Tengah',
            'no_hp' => '081234567890',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => '20230001',
            'universitas' => 'Universitas Palangka Raya',
            'status_perguruan_tinggi' => 'negeri',
            'jenjang' => 'S1',
            'jurusan' => 'Pendidikan Bahasa dan Seni',
            'program_studi' => 'Pendidikan Bahasa Indonesia',
            'akreditasi_perguruan_tinggi' => 'Unggul',
            'akreditasi_program_studi' => 'A',
            'nama_ketua_prodi' => 'Dr. Ahmad Dahlan, M.Pd',
            'nama_ketua_jurusan' => 'Dr. Siti Nurhaliza, M.Pd',
            'nama_direktur' => '-',
            'nama_rektor' => 'Prof. Dr. Ir. Salampak, M.S',
            'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            'no_telp_perguruan_tinggi' => '0536-3221234',
            'tahun_masuk' => 2023,
            'semester' => 4,
            'ipk' => 2.50,
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Suryadi',
            'status_ayah' => 'hidup',
            'nama_ibu' => 'Siti Rahmah',
            'status_ibu' => 'hidup',
            'alamat_orang_tua' => 'Puruk Cahu',
        ]);

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'jalurBeasiswa',
            'dataPribadi',
            'pendidikan',
            'orangTua',
        ]);

        $pdf = Pdf::loadView('mahasiswa.formulir.surat-permohonan-b', [
            'pendaftaran' => $pendaftaran,
            'jenisForm' => 'B',
        ]);
        $pdf->setPaper([0, 0, 612, 935.43]);
        $output = $pdf->output();
        file_put_contents(storage_path('app/test_surat_b.pdf'), $output);

        $pageCount = $pdf->getCanvas()->get_page_count();
        $this->assertSame(3, $pageCount);
    }

    public function test_controller_downloads_form_b_when_ipk_below_threshold(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'email_verified_at' => now(),
        ]);

        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => '2026/2027 Ganjil',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'TIDAK_MAMPU',
            'nama' => 'Tidak Mampu',
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $jalur = JalurBeasiswa::query()->create([
            'kode' => 'REGULER',
            'nama' => 'Reguler',
            'aktif' => true,
            'urutan' => 1,
        ]);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'status' => 'draft',
            'nomor_pendaftaran' => 'REG-2026-0002',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap' => 'Budi Santoso',
            'nik' => '6212010101010001',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Jl. Jenderal Sudirman No. 12, Puruk Cahu',
            'desa' => 'Beriwit',
            'kecamatan' => 'Murung',
            'kabupaten' => 'Murung Raya',
            'provinsi' => 'Kalimantan Tengah',
            'no_hp' => '081234567890',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => '20230002',
            'universitas' => 'Universitas Palangka Raya',
            'status_perguruan_tinggi' => 'negeri',
            'jenjang' => 'S1',
            'jurusan' => 'Pendidikan Bahasa dan Seni',
            'program_studi' => 'Pendidikan Bahasa Indonesia',
            'akreditasi_perguruan_tinggi' => 'Unggul',
            'akreditasi_program_studi' => 'A',
            'nama_ketua_prodi' => 'Dr. Ahmad Dahlan, M.Pd',
            'nama_ketua_jurusan' => 'Dr. Siti Nurhaliza, M.Pd',
            'nama_direktur' => '-',
            'nama_rektor' => 'Prof. Dr. Ir. Salampak, M.S',
            'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            'no_telp_perguruan_tinggi' => '0536-3221234',
            'tahun_masuk' => 2023,
            'semester' => 4,
            'ipk' => 2.50,
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Suryadi',
            'status_ayah' => 'hidup',
            'nama_ibu' => 'Siti Rahmah',
            'status_ibu' => 'hidup',
            'alamat_orang_tua' => 'Puruk Cahu',
        ]);

        $response = $this->actingAs($user)->get(route('mahasiswa.formulir.surat-permohonan'));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=Surat-Permohonan-REG-2026-0002-Form-B.pdf');
    }
}
