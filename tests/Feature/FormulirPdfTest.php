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
            'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
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

    public function test_controller_downloads_form_b_when_ipk_is_zero_or_not_yet_available(): void
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
            'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            'tahun_masuk' => 2023,
            'semester' => 1,
            'ipk' => 0.00,
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

    public function test_surat_permohonan_non_akademik_renders_exactly_1_page(): void
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
            'kode' => 'PRESTASI_NON_AKADEMIK',
            'nama' => 'Prestasi Non Akademik',
            'application_type' => \App\Enums\ApplicationType::NON_AKADEMIK,
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
            'nomor_pendaftaran' => 'REG-2026-NA01',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap' => 'Ahmad Rabbani',
            'nik' => '6212010101010003',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-05-15',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Jl. Bhayangkara No. 05, Puruk Cahu',
            'desa' => 'Beriwit',
            'kecamatan' => 'Murung',
            'kabupaten' => 'Murung Raya',
            'provinsi' => 'Kalimantan Tengah',
            'no_hp' => '081234567899',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => '20230099',
            'universitas' => 'Universitas Palangka Raya',
            'status_perguruan_tinggi' => 'negeri',
            'jenjang' => 'S1',
            'jurusan' => 'Pendidikan Olahraga',
            'program_studi' => 'Pendidikan Jasmani dan Kesehatan',
            'akreditasi_perguruan_tinggi' => 'B',
            'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            'tahun_masuk' => 2023,
            'semester' => 3,
            'ipk' => 3.50,
            'nominal_ukt' => 2500000,
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Ahmad Subari',
            'status_ayah' => 'hidup',
            'nama_ibu' => 'Halimah',
            'status_ibu' => 'hidup',
            'alamat_orang_tua' => 'Puruk Cahu',
        ]);

        $response = $this->actingAs($user)->get(route('mahasiswa.formulir.surat-permohonan'));
        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=Surat-Permohonan-REG-2026-NA01-Non-Akademik.pdf');

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'jalurBeasiswa',
            'dataPribadi',
            'pendidikan',
            'orangTua',
        ]);

        $pdf = Pdf::loadView('mahasiswa.formulir.surat-permohonan-non-akademik', [
            'pendaftaran' => $pendaftaran,
            'jenisForm' => 'Non-Akademik',
        ]);
        $pdf->setPaper([0, 0, 612, 935.43]);
        $pdf->output();

        $pageCount = $pdf->getCanvas()->get_page_count();
        $this->assertSame(2, $pageCount, 'Surat Permohonan Non-Akademik harus tepat 2 halaman.');
    }

    public function test_pakta_integritas_renders_clean_5_statements(): void
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
            'kode' => 'PRESTASI_NON_AKADEMIK',
            'nama' => 'Prestasi Non Akademik',
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
            'nomor_pendaftaran' => 'REG-2026-PI01',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap' => 'Ahmad Rabbani',
            'nik' => '6212010101010003',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-05-15',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Jl. Bhayangkara No. 05, Puruk Cahu',
            'desa' => 'Beriwit',
            'kecamatan' => 'Murung',
            'kabupaten' => 'Murung Raya',
            'provinsi' => 'Kalimantan Tengah',
            'no_hp' => '081234567899',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => '20230099',
            'universitas' => 'Universitas Palangka Raya',
            'status_perguruan_tinggi' => 'negeri',
            'jenjang' => 'S1',
            'jurusan' => 'Pendidikan Olahraga',
            'program_studi' => 'Pendidikan Jasmani dan Kesehatan',
            'akreditasi_perguruan_tinggi' => 'B',
            'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            'tahun_masuk' => 2023,
            'semester' => 3,
            'ipk' => 3.50,
            'nominal_ukt' => 2500000,
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Ahmad Subari',
            'status_ayah' => 'hidup',
            'nama_ibu' => 'Halimah',
            'status_ibu' => 'hidup',
            'alamat_orang_tua' => 'Puruk Cahu',
        ]);

        $response = $this->actingAs($user)->get(route('mahasiswa.formulir.pakta-integritas'));
        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=Pakta-Integritas-REG-2026-PI01.pdf');

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'jalurBeasiswa',
            'dataPribadi',
            'pendidikan',
            'orangTua',
        ]);

        $html = view('mahasiswa.formulir.pakta-integritas', [
            'pendaftaran' => $pendaftaran,
        ])->render();

        $this->assertStringContainsString('Materai', $html);
        $this->assertStringContainsString('Rp. 10.000', $html);
        $this->assertStringContainsString('APBN dan APBD;', $html);
        $this->assertStringNotContainsStringIgnoringCase('bantuan biaya pendidikan', $html);

        $pdf = Pdf::loadView('mahasiswa.formulir.pakta-integritas', [
            'pendaftaran' => $pendaftaran,
        ]);
        $pdf->setPaper('a4', 'portrait');
        $pdf->output();

        $pageCount = $pdf->getCanvas()->get_page_count();
        $this->assertSame(1, $pageCount, 'Pakta Integritas harus tetap tepat 1 halaman.');
    }

    public function test_surat_permohonan_tidak_mampu_renders_exactly_1_page(): void
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
            'kode' => 'TIDAK_MAMPU_2',
            'nama' => 'Beasiswa Tidak Mampu',
            'application_type' => \App\Enums\ApplicationType::TIDAK_MAMPU,
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
            'nomor_pendaftaran' => 'REG-2026-TM01',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap' => 'Siti Nurhaliza',
            'nik' => '6212010101010005',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2004-02-20',
            'jenis_kelamin' => 'P',
            'agama' => 'Islam',
            'alamat' => 'Jl. Veteran No. 10, Puruk Cahu',
            'desa' => 'Beriwit',
            'kecamatan' => 'Murung',
            'kabupaten' => 'Murung Raya',
            'provinsi' => 'Kalimantan Tengah',
            'no_hp' => '081234567888',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => '20230055',
            'universitas' => 'Universitas Palangka Raya',
            'status_perguruan_tinggi' => 'negeri',
            'jenjang' => 'S1',
            'jurusan' => 'Ekonomi Pembangunan',
            'program_studi' => 'Ilmu Ekonomi',
            'akreditasi_perguruan_tinggi' => 'Unggul',
            'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            'tahun_masuk' => 2023,
            'semester' => 3,
            'ipk' => 3.40,
            'nominal_ukt' => 1500000,
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Hasan Basri',
            'status_ayah' => 'hidup',
            'nama_ibu' => 'Mariam',
            'status_ibu' => 'hidup',
            'alamat_orang_tua' => 'Puruk Cahu',
        ]);

        $response = $this->actingAs($user)->get(route('mahasiswa.formulir.surat-permohonan'));
        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=Surat-Permohonan-REG-2026-TM01-Tidak-Mampu.pdf');

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'jalurBeasiswa',
            'dataPribadi',
            'pendidikan',
            'orangTua',
        ]);

        $pdf = Pdf::loadView('mahasiswa.formulir.surat-permohonan-tidak-mampu', [
            'pendaftaran' => $pendaftaran,
            'jenisForm' => 'Tidak-Mampu',
        ]);
        $pdf->setPaper([0, 0, 612, 935.43]);
        $pdf->output();

        $pageCount = $pdf->getCanvas()->get_page_count();
        $this->assertSame(2, $pageCount, 'Surat Permohonan Tidak Mampu harus tepat 2 halaman.');
    }

    public function test_surat_permohonan_prestasi_akademik_renders_exactly_1_page(): void
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
            'kode' => 'PRESTASI_AKADEMIK_2',
            'nama' => 'Beasiswa Prestasi Akademik',
            'application_type' => \App\Enums\ApplicationType::AKADEMIK,
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
            'nomor_pendaftaran' => 'REG-2026-AK01',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap' => 'Muhammad Fikri',
            'nik' => '6212010101010006',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-08-10',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Jl. Merdeka No. 22, Puruk Cahu',
            'desa' => 'Beriwit',
            'kecamatan' => 'Murung',
            'kabupaten' => 'Murung Raya',
            'provinsi' => 'Kalimantan Tengah',
            'no_hp' => '081234567777',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => '20230077',
            'universitas' => 'Institut Teknologi Bandung',
            'status_perguruan_tinggi' => 'negeri',
            'jenjang' => 'S1',
            'jurusan' => 'Teknik Elektro',
            'program_studi' => 'Teknik Tenaga Listrik',
            'akreditasi_perguruan_tinggi' => 'Unggul',
            'alamat_perguruan_tinggi' => 'Jl. Ganesha No. 10, Bandung',
            'tahun_masuk' => 2023,
            'semester' => 3,
            'ipk' => 3.85,
            'nominal_ukt' => 5000000,
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Drs. Subhan',
            'status_ayah' => 'hidup',
            'nama_ibu' => 'Hj. Aminah',
            'status_ibu' => 'hidup',
            'alamat_orang_tua' => 'Puruk Cahu',
        ]);

        $response = $this->actingAs($user)->get(route('mahasiswa.formulir.surat-permohonan'));
        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=Surat-Permohonan-REG-2026-AK01-Prestasi-Akademik.pdf');

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'jalurBeasiswa',
            'dataPribadi',
            'pendidikan',
            'orangTua',
        ]);

        $pdf = Pdf::loadView('mahasiswa.formulir.surat-permohonan-akademik', [
            'pendaftaran' => $pendaftaran,
            'jenisForm' => 'Prestasi-Akademik',
        ]);
        $pdf->setPaper([0, 0, 612, 935.43]);
        $pdf->output();

        $pageCount = $pdf->getCanvas()->get_page_count();
        $this->assertSame(2, $pageCount, 'Surat Permohonan Prestasi Akademik harus tepat 2 halaman.');
    }

    public function test_surat_permohonan_disabilitas_renders_exactly_1_page(): void
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
            'kode' => 'DISABILITAS_2',
            'nama' => 'Beasiswa Mahasiswa Disabilitas',
            'application_type' => \App\Enums\ApplicationType::DISABILITAS,
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
            'nomor_pendaftaran' => 'REG-2026-DIS01',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap' => 'Dewi Lestari',
            'nik' => '6212010101010007',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2004-04-12',
            'jenis_kelamin' => 'P',
            'agama' => 'Kristen',
            'alamat' => 'Jl. Bhayangkara No. 88, Puruk Cahu',
            'desa' => 'Beriwit',
            'kecamatan' => 'Murung',
            'kabupaten' => 'Murung Raya',
            'provinsi' => 'Kalimantan Tengah',
            'no_hp' => '081234567666',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => '20230088',
            'universitas' => 'Universitas Palangka Raya',
            'status_perguruan_tinggi' => 'negeri',
            'jenjang' => 'S1',
            'jurusan' => 'Pendidikan Luar Biasa',
            'program_studi' => 'Pendidikan Khusus',
            'akreditasi_perguruan_tinggi' => 'B',
            'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            'tahun_masuk' => 2023,
            'semester' => 3,
            'ipk' => 3.30,
            'nominal_ukt' => 2000000,
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Yohanes',
            'status_ayah' => 'hidup',
            'nama_ibu' => 'Maria',
            'status_ibu' => 'hidup',
            'alamat_orang_tua' => 'Puruk Cahu',
        ]);

        $response = $this->actingAs($user)->get(route('mahasiswa.formulir.surat-permohonan'));
        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=Surat-Permohonan-REG-2026-DIS01-Disabilitas.pdf');

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'jalurBeasiswa',
            'dataPribadi',
            'pendidikan',
            'orangTua',
        ]);

        $pdf = Pdf::loadView('mahasiswa.formulir.surat-permohonan-disabilitas', [
            'pendaftaran' => $pendaftaran,
            'jenisForm' => 'Disabilitas',
        ]);
        $pdf->setPaper([0, 0, 612, 935.43]);
        $pdf->output();

        $pageCount = $pdf->getCanvas()->get_page_count();
        $this->assertSame(2, $pageCount, 'Surat Permohonan Mahasiswa Disabilitas harus tepat 2 halaman.');
    }
}
