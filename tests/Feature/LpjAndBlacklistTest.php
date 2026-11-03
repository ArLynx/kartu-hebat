<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Blacklist;
use App\Models\DataPribadi;
use App\Models\JalurBeasiswa;
use App\Models\KategoriBeasiswa;
use App\Models\LaporanPertanggungjawaban;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\JalurBeasiswaSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LpjAndBlacklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RegionSeeder::class,
            MasterDataSeeder::class,
            BeasiswaMasterSeeder::class,
            JalurBeasiswaSeeder::class,
        ]);

        Storage::fake('local');
    }

    public function test_student_sees_informative_lpj_page_when_not_yet_accepted(): void
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $periode = Periode::query()->first();
        $kategori = KategoriBeasiswa::query()->first();

        // Pendaftaran masih berstatus draft
        Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-TEST-001',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($student)->get(route('mahasiswa.lpj.index'));

        $response->assertOk();
        $response->assertSee('Pengumpulan LPJ Belum Aktif');
        $response->assertSee('Saat ini tahapan pendaftaran dan seleksi beasiswa sedang berlangsung', false);
    }

    public function test_accepted_student_can_upload_lpj_pdf_within_limit(): void
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $village = Village::query()->first();

        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Beasiswa 2026',
            'tanggal_mulai' => now()->subMonths(2)->format('Y-m-d'),
            'tanggal_selesai' => now()->subMonth()->format('Y-m-d'),
            'tanggal_mulai_lpj' => now()->subDays(5)->format('Y-m-d'),
            'tanggal_selesai_lpj' => now()->addDays(25)->format('Y-m-d'),
            'status' => 'aktif',
        ]);

        $kategori = KategoriBeasiswa::query()->first();

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-TEST-002',
            'status' => 'approved',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nik' => '6212000000000001',
            'nama_lengkap' => 'Mahasiswa Penerima Beasiswa',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jalan Ahmad Yani',
            'village_id' => $village->id,
            'provinsi' => 'Kalimantan Tengah',
            'kabupaten' => 'Murung Raya',
            'kecamatan' => 'Murung',
            'desa' => 'Puruk Cahu',
            'no_hp' => '081234567890',
            'nomor_rekening' => '100200300',
        ]);

        // Akses halaman LPJ
        $viewResponse = $this->actingAs($student)->get(route('mahasiswa.lpj.index'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Unggah Bukti Pembayaran UKT / LPJ');

        // Upload berkas PDF 500 KB
        $pdfFile = UploadedFile::fake()->create('bukti-ukt.pdf', 500, 'application/pdf');

        $uploadResponse = $this->actingAs($student)->post(route('mahasiswa.lpj.store'), [
            'file' => $pdfFile,
        ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success');

        $this->assertDatabaseHas('laporan_pertanggungjawabans', [
            'pendaftaran_id' => $pendaftaran->id,
            'status' => 'menunggu_verifikasi',
            'original_name' => 'bukti-ukt.pdf',
        ]);
    }

    public function test_upload_fails_if_file_exceeds_2mb(): void
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Beasiswa 2026',
            'tanggal_mulai' => now()->subMonths(2)->format('Y-m-d'),
            'tanggal_selesai' => now()->subMonth()->format('Y-m-d'),
            'tanggal_mulai_lpj' => now()->subDays(5)->format('Y-m-d'),
            'tanggal_selesai_lpj' => now()->addDays(25)->format('Y-m-d'),
            'status' => 'aktif',
        ]);
        $kategori = KategoriBeasiswa::query()->first();

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-TEST-003',
            'status' => 'approved',
        ]);

        // Upload file melebihi 2 MB (misal 3 MB / 3072 KB)
        $oversizedFile = UploadedFile::fake()->create('besar.pdf', 3072, 'application/pdf');

        $response = $this->actingAs($student)->post(route('mahasiswa.lpj.store'), [
            'file' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('laporan_pertanggungjawabans', [
            'pendaftaran_id' => $pendaftaran->id,
        ]);
    }

    public function test_operator_kabupaten_can_view_monitoring_lpj_and_verify(): void
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'two_factor_confirmed_at' => now(),
        ]);

        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $village = Village::query()->first();
        $periode = Periode::query()->first();
        $kategori = KategoriBeasiswa::query()->first();

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-TEST-004',
            'status' => 'approved',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nik' => '6212000000000002',
            'nama_lengkap' => 'Budi Santoso Penerima',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-02-02',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jalan Veteran',
            'village_id' => $village->id,
            'provinsi' => 'Kalimantan Tengah',
            'kabupaten' => 'Murung Raya',
            'kecamatan' => 'Murung',
            'desa' => 'Puruk Cahu',
            'no_hp' => '081234567891',
            'nomor_rekening' => '100200301',
        ]);

        // Buat data LPJ
        $lpjPath = UploadedFile::fake()->create('ukt.pdf', 200, 'application/pdf')->store('lpj', 'local');
        LaporanPertanggungjawaban::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'file_path' => $lpjPath,
            'original_name' => 'ukt.pdf',
            'size' => 200 * 1024,
            'mime_type' => 'application/pdf',
            'status' => 'menunggu_verifikasi',
            'uploaded_at' => now(),
        ]);

        // Operator membuka halaman monitoring
        $indexResponse = $this->actingAs($operator)->get(route('operator.lpj.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Budi Santoso Penerima');
        $indexResponse->assertSee('6212000000000002');

        // Operator menyetujui berkas LPJ
        $verifyResponse = $this->actingAs($operator)->post(route('operator.lpj.verify', $pendaftaran), [
            'status' => 'disetujui',
            'catatan' => 'Bukti pembayaran valid dan jelas.',
        ]);

        $verifyResponse->assertRedirect();
        $verifyResponse->assertSessionHas('success');

        $this->assertDatabaseHas('laporan_pertanggungjawabans', [
            'pendaftaran_id' => $pendaftaran->id,
            'status' => 'disetujui',
            'catatan' => 'Bukti pembayaran valid dan jelas.',
            'verified_by' => $operator->id,
        ]);
    }

    public function test_operator_kabupaten_can_block_and_unblock_nik(): void
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'two_factor_confirmed_at' => now(),
        ]);

        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $village = Village::query()->first();
        $periode = Periode::query()->first();
        $kategori = KategoriBeasiswa::query()->first();

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-TEST-005',
            'status' => 'approved',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nik' => '6212999988887777',
            'nama_lengkap' => 'Mahasiswa Belum LPJ',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-03-03',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jalan Sudirman',
            'village_id' => $village->id,
            'provinsi' => 'Kalimantan Tengah',
            'kabupaten' => 'Murung Raya',
            'kecamatan' => 'Murung',
            'desa' => 'Puruk Cahu',
            'no_hp' => '081234567892',
            'nomor_rekening' => '100200302',
        ]);

        // Operator memblokir NIK
        $blockResponse = $this->actingAs($operator)->post(route('operator.lpj.blacklist', $pendaftaran), [
            'action' => 'block',
            'alasan' => 'Melewati batas akhir pengumpulan LPJ.',
        ]);

        $blockResponse->assertRedirect();
        $this->assertDatabaseHas('blacklists', [
            'nik' => '6212999988887777',
            'aktif' => true,
            'alasan' => 'Melewati batas akhir pengumpulan LPJ.',
        ]);

        // Operator membuka blokir NIK
        $unblockResponse = $this->actingAs($operator)->post(route('operator.lpj.blacklist', $pendaftaran), [
            'action' => 'unblock',
        ]);

        $unblockResponse->assertRedirect();
        $this->assertDatabaseHas('blacklists', [
            'nik' => '6212999988887777',
            'aktif' => false,
        ]);
    }

    public function test_blacklisted_student_is_blocked_from_new_registration(): void
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $village = Village::query()->first();

        // Periode lama (2026)
        $oldPeriode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Beasiswa 2026',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-06-30',
            'status' => 'ditutup',
        ]);

        // Periode baru (2027) sedang aktif
        $newPeriode = Periode::query()->create([
            'tahun' => 2027,
            'nama' => 'Beasiswa 2027',
            'tanggal_mulai' => now()->subDay()->format('Y-m-d'),
            'tanggal_selesai' => now()->addMonths(2)->format('Y-m-d'),
            'status' => 'aktif',
        ]);

        $kategori = KategoriBeasiswa::query()->first();

        $oldPendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $oldPeriode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-2026-001',
            'status' => 'approved',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $oldPendaftaran->id,
            'nik' => '6212555544443333',
            'nama_lengkap' => 'Mahasiswa Kena Blokir',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-04-04',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jalan Diponegoro',
            'village_id' => $village->id,
            'provinsi' => 'Kalimantan Tengah',
            'kabupaten' => 'Murung Raya',
            'kecamatan' => 'Murung',
            'desa' => 'Puruk Cahu',
            'no_hp' => '081234567893',
            'nomor_rekening' => '100200303',
        ]);

        // NIK diblokir di tabel blacklists
        Blacklist::query()->create([
            'nik' => '6212555544443333',
            'tahun_berlaku' => 2026,
            'pendaftaran_id' => $oldPendaftaran->id,
            'alasan' => 'Belum mengumpulkan LPJ 2026',
            'aktif' => true,
        ]);

        // Coba buka form pendaftaran baru periode 2027
        $createResponse = $this->actingAs($student)->get(route('mahasiswa.pendaftaran.create'));
        $createResponse->assertRedirect(route('mahasiswa.dashboard'));
        $createResponse->assertSessionHas('error_lpj');

        // Buka dashboard, terlihat alert kewajiban LPJ dan link ke LPJ
        $dashboardResponse = $this->actingAs($student)->get(route('mahasiswa.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Kewajiban Laporan Pertanggungjawaban (LPJ) Belum Selesai');
        $dashboardResponse->assertSee('Ke Menu LPJ Beasiswa');
    }

    public function test_student_with_unsubmitted_lpj_from_previous_period_is_auto_held(): void
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $village = Village::query()->first();

        // Periode lama (2026) dengan jadwal LPJ yang sudah lewat
        $oldPeriode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Beasiswa 2026',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-06-30',
            'tanggal_mulai_lpj' => '2026-07-01',
            'tanggal_selesai_lpj' => '2026-07-31',
            'status' => 'ditutup',
        ]);

        // Periode baru (2027) sedang aktif
        $newPeriode = Periode::query()->create([
            'tahun' => 2027,
            'nama' => 'Beasiswa 2027',
            'tanggal_mulai' => now()->subDay()->format('Y-m-d'),
            'tanggal_selesai' => now()->addMonths(2)->format('Y-m-d'),
            'status' => 'aktif',
        ]);

        $kategori = KategoriBeasiswa::query()->first();

        $oldPendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $oldPeriode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-2026-AUTO',
            'status' => 'approved',
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $oldPendaftaran->id,
            'nik' => '6212111122223333',
            'nama_lengkap' => 'Mahasiswa Belum Kumpul LPJ',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-05-05',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jalan Diponegoro',
            'village_id' => $village->id,
            'provinsi' => 'Kalimantan Tengah',
            'kabupaten' => 'Murung Raya',
            'kecamatan' => 'Murung',
            'desa' => 'Puruk Cahu',
            'no_hp' => '081234567894',
            'nomor_rekening' => '100200304',
        ]);

        // ADMIN LUPA KLIK BLOKIR (Tabel Blacklist belum diisi admin)
        $this->assertDatabaseMissing('blacklists', ['nik' => '6212111122223333']);

        // Mahasiswa mencoba daftar di periode 2027
        $response = $this->actingAs($student)->get(route('mahasiswa.pendaftaran.create'));
        $response->assertRedirect(route('mahasiswa.dashboard'));
        $response->assertSessionHas('error_lpj');

        // Sistem otomatis mencatat penangguhan di Blacklist
        $this->assertDatabaseHas('blacklists', [
            'nik' => '6212111122223333',
            'aktif' => true,
        ]);
    }

    public function test_new_user_cannot_enter_blacklisted_nik(): void
    {
        $newStudent = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $village = Village::query()->first();
        $periode = Periode::query()->first();
        $kategori = KategoriBeasiswa::query()->first();
        $jalur = JalurBeasiswa::query()->first();

        // NIK 6212777788889999 sedang diblokir
        Blacklist::query()->create([
            'nik' => '6212777788889999',
            'tahun_berlaku' => 2026,
            'alasan' => 'Belum menyelesaikan LPJ',
            'aktif' => true,
        ]);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $newStudent->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-TEST-NEW',
            'status' => 'draft',
        ]);

        // Mahasiswa baru mencoba mengisi Data Pribadi menggunakan NIK yang diblokir
        $response = $this->actingAs($newStudent)->put(route('mahasiswa.data-pribadi.update'), [
            'nik' => '6212777788889999',
            'nomor_kk' => '6212777788880000',
            'nomor_rekening' => '1234567890',
            'nama_lengkap' => 'Nama Samaran',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2004-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jalan Merdeka',
            'provinsi' => 'Kalimantan Tengah',
            'village_id' => $village->id,
            'no_hp' => '081234567899',
        ]);

        $response->assertSessionHasErrors('nik');
        $this->assertDatabaseMissing('data_pribadis', [
            'nik' => '6212777788889999',
        ]);
    }

    public function test_student_can_register_new_period_with_same_nim_and_other_user_is_prevented(): void
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $village = Village::query()->first();
        $oldPeriode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => '2026/2027 Ganjil',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-06-30',
            'tanggal_mulai_lpj' => '2026-07-01',
            'tanggal_selesai_lpj' => '2026-07-31',
            'status' => 'ditutup',
        ]);
        $newPeriode = Periode::query()->create([
            'tahun' => 2027,
            'nama' => '2027/2028 Genap',
            'tanggal_mulai' => now()->subDay()->format('Y-m-d'),
            'tanggal_selesai' => now()->addMonths(2)->format('Y-m-d'),
            'status' => 'aktif',
        ]);
        $kategori = KategoriBeasiswa::query()->first();
        $jalur = JalurBeasiswa::query()->first();

        // Pendaftaran periode lama
        $oldPendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $oldPeriode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KH-2026-NIM-01',
            'status' => 'approved',
        ]);

        \App\Models\Pendidikan::query()->create([
            'pendaftaran_id' => $oldPendaftaran->id,
            'nim' => '2100011223',
            'universitas' => 'Universitas Palangka Raya',
            'status_perguruan_tinggi' => 'negeri',
            'akreditasi_perguruan_tinggi' => 'Baik Sekali',
            'fakultas' => 'Teknik',
            'jurusan' => 'Teknik Sipil',
            'program_studi' => 'Teknik Sipil',
            'jenjang' => 'S1',
            'semester' => 6,
            'ipk' => 3.85,
            'nominal_ukt' => 3500000,
            'tahun_masuk' => 2021,
        ]);

        // LPJ periode lama sudah disetujui (sehingga tidak tertahan)
        LaporanPertanggungjawaban::query()->create([
            'pendaftaran_id' => $oldPendaftaran->id,
            'file_path' => 'demo/lpj.pdf',
            'status' => 'disetujui',
            'uploaded_at' => now(),
        ]);

        // 1. Mahasiswa yang sama membuat pendaftaran di periode baru -> auto copy NIM berhasil tanpa error
        $storeResponse = $this->actingAs($student)->post(route('mahasiswa.pendaftaran.store'), [
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'persetujuan' => '1',
        ]);

        $storeResponse->assertRedirect(route('mahasiswa.data-pribadi.index'));
        $this->assertEquals(2, \App\Models\Pendidikan::where('nim', '2100011223')->count());

        // 2. User lain mencoba memakai NIM yang sama -> ditolak oleh validasi
        $otherStudent = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $otherPendaftaran = Pendaftaran::query()->create([
            'user_id' => $otherStudent->id,
            'periode_id' => $newPeriode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KH-2027-OTHER',
            'status' => 'draft',
        ]);

        $updateResponse = $this->actingAs($otherStudent)->put(route('mahasiswa.pendidikan.update'), [
            'nim' => '2100011223', // NIM milik Rizky
            'universitas' => 'Universitas Palangka Raya',
            'status_perguruan_tinggi' => 'negeri',
            'fakultas' => 'Teknik',
            'jurusan' => 'Teknik Sipil',
            'jenjang' => 'S1',
            'semester' => 2,
            'ipk' => 3.50,
            'nominal_ukt' => 2500000,
            'tahun_masuk' => 2023,
        ]);

        $updateResponse->assertSessionHasErrors('nim');
    }

    public function test_operator_kabupaten_selection_shows_all_periods_and_filters_candidates(): void
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'two_factor_confirmed_at' => now(),
        ]);

        $oldPeriode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => '2026/2027 Ganjil',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-06-30',
            'status' => 'ditutup',
        ]);
        $newPeriode = Periode::query()->create([
            'tahun' => 2027,
            'nama' => '2027/2028 Genap',
            'tanggal_mulai' => now()->subDay()->format('Y-m-d'),
            'tanggal_selesai' => now()->addMonths(2)->format('Y-m-d'),
            'status' => 'aktif',
        ]);

        // Akses halaman penetapan penerima
        $response = $this->actingAs($operator)->get(route('operator.selection'));
        $response->assertOk();
        // Memastikan kedua periode muncul di dropdown
        $response->assertSee('2026/2027 Ganjil');
        $response->assertSee('2027/2028 Genap');

        // Beralih ke periode lama
        $responseOld = $this->actingAs($operator)->get(route('operator.selection', ['periode' => '2026/2027 Ganjil']));
        $responseOld->assertOk();
    }

    public function test_operator_can_filter_lpj_by_application_type_card(): void
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'two_factor_confirmed_at' => now(),
        ]);

        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => '2026 Ganjil',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-06-30',
            'tanggal_mulai_lpj' => '2026-07-01',
            'tanggal_selesai_lpj' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $kategoriAkademik = KategoriBeasiswa::query()->where('kode', 'like', '%AKADEMIK%')->first();
        $kategoriTidakMampu = KategoriBeasiswa::query()->where('kode', 'like', '%TIDAK-MAMPU%')->first();
        $village = Village::query()->first();

        // Mahasiswa Akademik
        $userA = User::factory()->create(['name' => 'Mahasiswa Akademik Testing']);
        $pA = Pendaftaran::query()->create([
            'user_id' => $userA->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategoriAkademik->id,
            'nomor_pendaftaran' => 'KH-2026-AKADEMIK-01',
            'status' => 'approved',
        ]);
        DataPribadi::query()->create([
            'pendaftaran_id' => $pA->id,
            'nama_lengkap' => 'Mahasiswa Akademik Testing',
            'nik' => '6212000000000001',
            'tempat_lahir' => 'Murung Raya',
            'tanggal_lahir' => '2003-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'alamat' => 'Puruk Cahu',
            'village_id' => $village->id,
            'provinsi' => 'Kalimantan Tengah',
            'kabupaten' => 'Murung Raya',
            'kecamatan' => 'Murung',
            'desa' => 'Puruk Cahu',
            'no_hp' => '081234567001',
        ]);
        $pA->application()->create([
            'nomor_pengajuan' => 'APP-2026-AKADEMIK-01',
            'mahasiswa_id' => $userA->id,
            'periode' => '2026 Ganjil',
            'application_type' => \App\Enums\ApplicationType::AKADEMIK,
            'status' => ApplicationStatus::DITERIMA,
        ]);

        // Mahasiswa Tidak Mampu
        $userB = User::factory()->create(['name' => 'Mahasiswa Afirmasi Testing']);
        $pB = Pendaftaran::query()->create([
            'user_id' => $userB->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategoriTidakMampu->id,
            'nomor_pendaftaran' => 'KH-2026-TM-01',
            'status' => 'approved',
        ]);
        DataPribadi::query()->create([
            'pendaftaran_id' => $pB->id,
            'nama_lengkap' => 'Mahasiswa Afirmasi Testing',
            'nik' => '6212000000000002',
            'tempat_lahir' => 'Murung Raya',
            'tanggal_lahir' => '2003-02-02',
            'jenis_kelamin' => 'P',
            'agama' => 'Kristen',
            'alamat' => 'Puruk Cahu',
            'village_id' => $village->id,
            'provinsi' => 'Kalimantan Tengah',
            'kabupaten' => 'Murung Raya',
            'kecamatan' => 'Murung',
            'desa' => 'Puruk Cahu',
            'no_hp' => '081234567002',
        ]);
        $pB->application()->create([
            'nomor_pengajuan' => 'APP-2026-TM-01',
            'mahasiswa_id' => $userB->id,
            'periode' => '2026 Ganjil',
            'application_type' => \App\Enums\ApplicationType::TIDAK_MAMPU,
            'status' => ApplicationStatus::DITERIMA,
        ]);

        // 1. Tampilkan semua jalur
        $resAll = $this->actingAs($operator)->get(route('operator.lpj.index', ['periode_id' => $periode->id]));
        $resAll->assertOk();
        $resAll->assertSee('Jalur Akademik');
        $resAll->assertSee('Jalur Tidak Mampu');
        $resAll->assertSee('Mahasiswa Akademik Testing');
        $resAll->assertSee('Mahasiswa Afirmasi Testing');

        // 2. Filter ke Jalur Akademik
        $resAkademik = $this->actingAs($operator)->get(route('operator.lpj.index', [
            'periode_id' => $periode->id,
            'application_type' => 'AKADEMIK',
        ]));
        $resAkademik->assertOk();
        $resAkademik->assertSee('Mahasiswa Akademik Testing');
        $resAkademik->assertDontSee('Mahasiswa Afirmasi Testing');

        // 3. Filter ke Jalur Tidak Mampu
        $resTM = $this->actingAs($operator)->get(route('operator.lpj.index', [
            'periode_id' => $periode->id,
            'application_type' => 'TIDAK_MAMPU',
        ]));
        $resTM->assertOk();
        $resTM->assertSee('Mahasiswa Afirmasi Testing');
        $resTM->assertDontSee('Mahasiswa Akademik Testing');
    }
}
