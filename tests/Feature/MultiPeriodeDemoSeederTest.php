<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\FormulirPendaftaran;
use App\Models\LaporanPertanggungjawaban;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use Database\Seeders\MultiPeriodeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiPeriodeDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_periode_demo_seeder_runs_cleanly_and_idempotently(): void
    {
        // Jalankan pertama kali
        $this->seed(MultiPeriodeDemoSeeder::class);

        // Verifikasi 2 Periode ada
        $periode2026 = Periode::query()->where('nama', '2026/2027 Ganjil')->first();
        $this->assertNotNull($periode2026);
        $this->assertEquals('ditutup', $periode2026->status);

        $periode2027 = Periode::query()->where('nama', '2027/2028 Ganjil')->first();
        $this->assertNotNull($periode2027);
        $this->assertEquals('aktif', $periode2027->status);

        // Verifikasi Rizky Pratama (Penerima 2026)
        $rizky = User::query()->where('email', 'penerima.beasiswa@kartuhebat.test')->first();
        $this->assertNotNull($rizky);
        $this->assertEquals(UserRole::MAHASISWA, $rizky->role);

        $pendaftaranRizky = Pendaftaran::query()
            ->where('user_id', $rizky->id)
            ->where('periode_id', $periode2026->id)
            ->first();
        $this->assertNotNull($pendaftaranRizky);
        $this->assertEquals('approved', $pendaftaranRizky->status);

        // Verifikasi LPJ Rizky disetujui
        $lpj = LaporanPertanggungjawaban::query()->where('pendaftaran_id', $pendaftaranRizky->id)->first();
        $this->assertNotNull($lpj);
        $this->assertEquals('disetujui', $lpj->status);

        // Verifikasi Formulir Pendaftaran (Surat & Pakta)
        $formulir = FormulirPendaftaran::query()->where('pendaftaran_id', $pendaftaranRizky->id)->first();
        $this->assertNotNull($formulir);
        $this->assertNotNull($formulir->surat_permohonan);
        $this->assertNotNull($formulir->pakta_integritas);

        // Verifikasi Dimas Pratama (2027 Seleksi Kabupaten)
        $dimas = User::query()->where('email', 'dimas.seleksi@kartuhebat.test')->first();
        $this->assertNotNull($dimas);

        $pendaftaranDimas = Pendaftaran::query()
            ->where('user_id', $dimas->id)
            ->where('periode_id', $periode2027->id)
            ->first();
        $this->assertNotNull($pendaftaranDimas);

        $appDimas = Application::query()->where('pendaftaran_id', $pendaftaranDimas->id)->first();
        $this->assertNotNull($appDimas);
        $this->assertEquals(ApplicationStatus::SELEKSI_KABUPATEN, $appDimas->status);
        $this->assertNotNull($appDimas->selection);

        // Verifikasi Siti Aisyah (2027 Verifikasi Dinas)
        $siti = User::query()->where('email', 'siti.aisyah@kartuhebat.test')->first();
        $this->assertNotNull($siti);

        $pendaftaranSiti = Pendaftaran::query()
            ->where('user_id', $siti->id)
            ->where('periode_id', $periode2027->id)
            ->first();
        $this->assertNotNull($pendaftaranSiti);

        $appSiti = Application::query()->where('pendaftaran_id', $pendaftaranSiti->id)->first();
        $this->assertNotNull($appSiti);
        $this->assertEquals(ApplicationStatus::VERIFIKASI_DINAS, $appSiti->status);

        // Uji Idempotensi: Jalankan seeder sekali lagi untuk memastikan tidak ada Duplicate Key error
        $this->seed(MultiPeriodeDemoSeeder::class);
        $this->assertEquals(2, Periode::query()->count());
    }

    public function test_sequential_run_with_demo_data_seeder_succeeds_without_conflict(): void
    {
        // Master seeders prasyarat bawaan
        $this->seed(\Database\Seeders\RegionSeeder::class);
        $this->seed(\Database\Seeders\MasterDataSeeder::class);

        // Jalankan DemoDataSeeder bawaan teman terlebih dahulu
        $this->seed(\Database\Seeders\DemoDataSeeder::class);

        // Lanjut jalankan MultiPeriodeDemoSeeder kita
        $this->seed(MultiPeriodeDemoSeeder::class);

        // Verifikasi kedua periode terbentuk
        $this->assertEquals(2, Periode::query()->count());

        // Verifikasi akun dari DemoDataSeeder (Andi Saputra) tetap ada
        $andi = User::query()->where('email', 'mahasiswa@kartuhebat.test')->first();
        $this->assertNotNull($andi);

        // Verifikasi akun dari MultiPeriodeDemoSeeder (Rizky Pratama) juga ada
        $rizky = User::query()->where('email', 'penerima.beasiswa@kartuhebat.test')->first();
        $this->assertNotNull($rizky);
    }

    public function test_mahasiswa_can_view_archive_documents_from_seeded_data(): void
    {
        $this->seed(MultiPeriodeDemoSeeder::class);

        $rizky = User::query()->where('email', 'penerima.beasiswa@kartuhebat.test')->first();
        $pendaftaran2026 = Pendaftaran::query()->where('user_id', $rizky->id)->first();

        // 1. Dashboard menampilkan riwayat 2026
        $response = $this->actingAs($rizky)->get(route('mahasiswa.dashboard'));
        $response->assertOk();
        $response->assertSee('2026/2027 Ganjil');
        $response->assertSee('KH-2026-LULUS-001');

        // 2. Akses Surat Permohonan
        $this->actingAs($rizky)
            ->get(route('mahasiswa.formulir.lihat', ['jenis' => 'surat', 'pendaftaran_id' => $pendaftaran2026->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // 3. Akses Pakta Integritas
        $this->actingAs($rizky)
            ->get(route('mahasiswa.formulir.lihat', ['jenis' => 'pakta', 'pendaftaran_id' => $pendaftaran2026->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // 4. Akses File LPJ
        $this->actingAs($rizky)
            ->get(route('mahasiswa.lpj.file', ['pendaftaran_id' => $pendaftaran2026->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_operator_kabupaten_archive_and_active_periods(): void
    {
        $this->seed(MultiPeriodeDemoSeeder::class);

        $operator = User::query()->where('email', 'kabupaten@kartuhebat.test')->first();

        // 1. Lihat Periode 2026 (Arsip Terkunci)
        $response2026 = $this->actingAs($operator)
            ->get(route('operator.selection', ['periode' => '2026/2027 Ganjil']));
        $response2026->assertOk();
        $response2026->assertSee('Mode Arsip (Read-Only)');
        $response2026->assertSee('Arsip Terkunci');

        // 2. Lihat Periode 2027 (Periode Berjalan Aktif)
        $response2027 = $this->actingAs($operator)
            ->get(route('operator.selection', ['periode' => '2027/2028 Ganjil']));
        $response2027->assertOk();
        $response2027->assertDontSee('Mode Arsip (Read-Only)');
        $response2027->assertSee('Dimas Pratama');
        $response2027->assertSee('KH-2027-SELEKSI-001');
    }

    public function test_fajar_is_blocked_by_lpj_and_unblocked_by_operator(): void
    {
        $this->seed(MultiPeriodeDemoSeeder::class);

        $fajar = User::query()->where('email', 'fajar.lpj.blokir@kartuhebat.test')->first();
        $this->assertNotNull($fajar);

        // 1. Dashboard Fajar menampilkan informasi pendaftaran 2026
        $responseFajar = $this->actingAs($fajar)->get(route('mahasiswa.dashboard'));
        $this->assertEquals(200, $responseFajar->getStatusCode(), 'Fajar dashboard redirected to: '.$responseFajar->headers->get('Location'));
        $responseFajar->assertSee('KH-2026-LULUS-003');

        // 2. Operator Kabupaten melihat Fajar di menu LPJ berstatus Perlu Penyelesaian
        $this->flushSession();
        $operator = User::query()->where('email', 'kabupaten@kartuhebat.test')->first();
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();
        $responseLpj = $this->actingAs($operator)->get(route('operator.lpj.index'));
        $this->assertEquals(200, $responseLpj->getStatusCode(), 'Operator LPJ redirected to: '.$responseLpj->headers->get('Location'));
        $responseLpj->assertSee('Fajar Ramadhan');
        $responseLpj->assertSee('Perlu Penyelesaian');

        // 3. Operator menyetujui LPJ Fajar (unblock)
        $pendaftaranFajar = Pendaftaran::query()->where('user_id', $fajar->id)->first();
        $this->actingAs($operator)
            ->post(route('operator.lpj.blacklist', $pendaftaranFajar), ['action' => 'unblock'])
            ->assertRedirect();

        // 4. Status Fajar di blacklist sekarang tidak aktif
        $this->assertFalse((bool) \App\Models\Blacklist::query()->where('nik', '6212052345670009')->first()->aktif);
    }
}
