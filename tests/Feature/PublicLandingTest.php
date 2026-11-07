<?php

namespace Tests\Feature;

use App\Enums\ApplicationType;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\Periode;
use Database\Seeders\BeasiswaMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_displays_active_period_dynamically_and_hides_quota(): void
    {
        $this->travelTo('2026-11-07');
        config(['kartu_hebat.quota' => 580]);

        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => '2026/2027 Ganjil',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'aktif',
        ]);

        $ktp = JenisDokumen::query()->create([
            'kode' => 'KTP',
            'nama' => 'Kartu Tanda Penduduk',
            'deskripsi' => 'KTP aktif mahasiswa.',
            'format_file' => 'pdf,jpg,jpeg,png',
            'maksimal_ukuran' => 2048,
            'aktif' => true,
        ]);

        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'BEASISWA-AKADEMIK-TEST',
            'application_type' => ApplicationType::AKADEMIK,
            'nama' => 'Beasiswa Prestasi Akademik',
            'deskripsi' => 'Berdasarkan capaian IPK.',
            'kuota' => 250,
            'aktif' => true,
            'urutan' => 1,
        ]);
        $kategori->jenisDokumens()->attach($ktp->id, ['urutan' => 1]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Pendaftaran Tahun 2026 Dibuka');
        $response->assertSee('Pendaftaran Beasiswa Tahun 2026');
        $response->assertSee('2026/2027 Ganjil');
        $response->assertSee('23');
        $response->assertSee('Hari tersisa');
        $response->assertSee($periode->tanggal_mulai->translatedFormat('d F Y'));
        $response->assertSee($periode->tanggal_selesai->translatedFormat('d F Y'));
        $response->assertDontSee('Kuota');
        $response->assertDontSee('KUOTA');
        $response->assertDontSee('580');
        $response->assertDontSee('Belum Dibuka (Akan Datang)');
        $response->assertDontSee('Pendaftaran Tahun 2026 Telah Ditutup');
        $this->assertStringNotContainsString('250', $this->extractElementById($response->getContent(), 'jadwal'));
        $this->assertStringNotContainsString('250', $this->extractElementById($response->getContent(), 'persyaratan'));
    }

    public function test_landing_page_follows_current_year_for_closed_and_draft_periods_and_shows_reference_banner(): void
    {
        $this->seed(BeasiswaMasterSeeder::class);

        $periode2026 = Periode::query()->where('tahun', 2026)->firstOrFail();
        $periode2026->update(['status' => 'ditutup']);

        $periode2027 = Periode::query()->create([
            'tahun' => 2027,
            'nama' => '2027/2028 Ganjil',
            'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2027-12-31',
            'status' => 'draft',
        ]);

        // 1. Saat masih tahun berjalan 2026: tampilkan Pendaftaran Tahun 2026 Telah Ditutup (meski draf 2027 sudah ada)
        $this->travelTo('2026-12-15');

        $closedResponse = $this->get(route('home'));
        $closedResponse->assertOk();
        $closedResponse->assertSee('Pendaftaran Beasiswa Tahun 2026 Telah Ditutup');
        $closedResponse->assertSee('Pendaftaran Tahun 2026 Telah Ditutup');
        $closedResponse->assertSee('Cek Hasil Seleksi');
        $closedResponse->assertDontSee('Pendaftaran Beasiswa Tahun 2027 Akan Datang');
        // Tab persyaratan tetap tampil sebagai acuan persiapan
        $closedResponse->assertSee('Beasiswa Prestasi Akademik');
        $closedResponse->assertSee('acuan persyaratan periode tahun');

        // 2. Saat masuk tahun berjalan 2027 dan status 2027 masih draft: tampilkan Pendaftaran Tahun 2027 Akan Datang
        $this->travelTo('2027-01-10');

        $draftResponse = $this->get(route('home'));
        $draftResponse->assertOk();
        $draftResponse->assertSee('Pendaftaran Beasiswa Tahun 2027 Akan Datang');
        $draftResponse->assertSee('Akan Datang');
        $draftResponse->assertSee('Belum dibuka');
        $draftResponse->assertSee($periode2027->tanggal_mulai->translatedFormat('d F Y'));
        $draftResponse->assertSee('Pendaftaran Tahun 2027 Belum Dibuka (Akan Datang)');
        // Tab persyaratan tetap tampil sebagai persiapan awal (fallback dari master kategori terakhir)
        $draftResponse->assertSee('Beasiswa Prestasi Akademik');
        $draftResponse->assertSee('Beasiswa Tidak Mampu');
    }

    public function test_landing_page_handles_empty_database_and_upcoming_scheduled_active_period(): void
    {
        $this->travelTo('2027-02-01');

        // 1. Database kosong pada tahun 2027 tetap mengikuti tahun berjalan (2027 Akan Datang)
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pendaftaran Beasiswa Tahun 2027 Akan Datang')
            ->assertSee('Pendaftaran Tahun 2027 Belum Dibuka (Akan Datang)')
            ->assertSee('Data persyaratan belum tersedia.');

        // 2. Periode 2027 sudah diset aktif tetapi tanggal mulai masih 10 hari lagi
        Periode::query()->create([
            'tahun' => 2027,
            'nama' => '2027/2028 Ganjil',
            'tanggal_mulai' => '2027-02-11',
            'tanggal_selesai' => '2027-06-30',
            'status' => 'aktif',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Pendaftaran Beasiswa Tahun 2027 Akan Datang')
            ->assertSee('10')
            ->assertSee('Hari menuju buka')
            ->assertSee('Buat Akun Lebih Awal');
    }

    public function test_landing_page_renders_tabs_and_specific_documents_per_scholarship_category(): void
    {
        $this->travelTo('2026-11-07');
        $this->seed(BeasiswaMasterSeeder::class);

        $activePeriode = Periode::query()->where('status', 'aktif')->firstOrFail();

        $inactiveCategory = KategoriBeasiswa::query()->create([
            'periode_id' => $activePeriode->id,
            'kode' => 'BEASISWA-NONAKTIF',
            'application_type' => ApplicationType::AKADEMIK,
            'nama' => 'Beasiswa Jalur Nonaktif',
            'deskripsi' => 'Tidak boleh tampil di beranda.',
            'kuota' => 10,
            'aktif' => false,
            'urutan' => 99,
        ]);

        $inactiveDoc = JenisDokumen::query()->create([
            'kode' => 'DOKUMEN-NONAKTIF',
            'nama' => 'Dokumen Arsip Nonaktif',
            'deskripsi' => 'Tidak boleh tampil.',
            'format_file' => 'pdf',
            'maksimal_ukuran' => 1024,
            'aktif' => false,
        ]);

        $akademik = KategoriBeasiswa::query()
            ->where('periode_id', $activePeriode->id)
            ->where('application_type', ApplicationType::AKADEMIK)
            ->where('aktif', true)
            ->firstOrFail();
        $akademik->jenisDokumens()->attach($inactiveDoc->id, ['urutan' => 99]);

        $tidakMampu = KategoriBeasiswa::query()
            ->where('periode_id', $activePeriode->id)
            ->where('application_type', ApplicationType::TIDAK_MAMPU)
            ->firstOrFail();

        $disabilitas = KategoriBeasiswa::query()
            ->where('periode_id', $activePeriode->id)
            ->where('application_type', ApplicationType::DISABILITAS)
            ->firstOrFail();

        $nonAkademik = KategoriBeasiswa::query()
            ->where('periode_id', $activePeriode->id)
            ->where('application_type', ApplicationType::NON_AKADEMIK)
            ->firstOrFail();

        $response = $this->get(route('home'));
        $html = $response->getContent();

        $response->assertOk();
        $response->assertSee('Beasiswa Prestasi Akademik');
        $response->assertSee('Beasiswa Tidak Mampu');
        $response->assertSee('Beasiswa Mahasiswa Disabilitas');
        $response->assertSee('Beasiswa Prestasi Non Akademik');
        $response->assertDontSee($inactiveCategory->nama);
        $response->assertDontSee($inactiveDoc->nama);

        $akademikPanel = $this->extractElementById($html, 'persyaratan-panel-'.$akademik->id);
        $tidakMampuPanel = $this->extractElementById($html, 'persyaratan-panel-'.$tidakMampu->id);
        $disabilitasPanel = $this->extractElementById($html, 'persyaratan-panel-'.$disabilitas->id);
        $nonAkademikPanel = $this->extractElementById($html, 'persyaratan-panel-'.$nonAkademik->id);

        $this->assertStringContainsString('Kartu Hasil Studi (KHS) / Transkrip Nilai', $akademikPanel);
        $this->assertStringContainsString('PDF, JPG, JPEG, PNG', $akademikPanel);
        $this->assertStringContainsString('2 MB', $akademikPanel);
        $this->assertStringNotContainsString('Surat Keterangan Tidak Mampu', $akademikPanel);
        $this->assertStringNotContainsString('Surat Keterangan Disabilitas', $akademikPanel);
        $this->assertStringNotContainsString('Sertifikat Prestasi / SK Organisasi', $akademikPanel);

        $this->assertStringContainsString('Surat Keterangan Tidak Mampu', $tidakMampuPanel);
        $this->assertStringContainsString('Surat Keterangan Disabilitas', $disabilitasPanel);
        $this->assertStringContainsString('Sertifikat Prestasi / SK Organisasi', $nonAkademikPanel);
    }

    private function extractElementById(string $html, string $id): string
    {
        $document = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $element = $document->getElementById($id);
        $this->assertNotNull($element, "Elemen #{$id} tidak ditemukan.");

        return $document->saveHTML($element) ?: '';
    }
}
