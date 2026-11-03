<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Dokumen;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegionSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);

        Storage::fake('local');
        config(['kartu_hebat.document_disk' => 'local']);
    }

    public function test_student_can_preview_and_download_their_past_period_document(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $otherStudent = User::factory()->create(['role' => 'mahasiswa']);

        $pastPeriod = Periode::query()->create([
            'nama' => '2025/2026 Ganjil',
            'tahun' => 2025,
            'status' => 'ditutup',
            'tanggal_mulai' => now()->subYear(),
            'tanggal_selesai' => now()->subMonths(6),
        ]);

        $kategori = KategoriBeasiswa::query()->first();
        $jenisDokumen = JenisDokumen::query()->first();

        $pastPendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $pastPeriod->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KH-PAST-001',
            'status' => 'approved',
        ]);

        $fileContent = '%PDF-1.4 Fake PDF Content';
        $path = 'pendaftarans/'.$pastPendaftaran->id.'/dokumen/ktp/dummy.pdf';
        Storage::disk('local')->put($path, $fileContent);

        $dokumen = Dokumen::query()->create([
            'pendaftaran_id' => $pastPendaftaran->id,
            'jenis_dokumen_id' => $jenisDokumen->id,
            'file_path' => $path,
            'nama_file_asli' => 'ktp_asli.pdf',
            'mime_type' => 'application/pdf',
            'ukuran_file' => strlen($fileContent),
            'status' => 'verified',
        ]);

        // 1. Mahasiswa pemilik bisa preview
        $this->actingAs($student)
            ->get(route('mahasiswa.dokumen.preview', $dokumen))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // 2. Mahasiswa pemilik bisa unduh dengan nama otomatis rapi
        $expectedName = \Illuminate\Support\Str::slug($jenisDokumen->nama, '-').'-'.$pastPendaftaran->nomor_pendaftaran.'.pdf';
        $this->actingAs($student)
            ->get(route('mahasiswa.dokumen.download', $dokumen))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename='.$expectedName);

        // 3. Mahasiswa lain DITOLAK (403)
        $this->actingAs($otherStudent)
            ->get(route('mahasiswa.dokumen.preview', $dokumen))
            ->assertForbidden();

        $this->actingAs($otherStudent)
            ->get(route('mahasiswa.dokumen.download', $dokumen))
            ->assertForbidden();
    }

    public function test_dashboard_renders_archive_document_button_and_modal(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $pastPeriod = Periode::query()->create([
            'nama' => '2025/2026 Ganjil',
            'tahun' => 2025,
            'status' => 'ditutup',
            'tanggal_mulai' => now()->subYear(),
            'tanggal_selesai' => now()->subMonths(6),
        ]);

        $kategori = KategoriBeasiswa::query()->first();
        $jenisDokumen = JenisDokumen::query()->first();

        $pastPendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $pastPeriod->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KH-PAST-001',
            'status' => 'approved',
        ]);

        $path = 'pendaftarans/'.$pastPendaftaran->id.'/dokumen/ktp/dummy.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        Dokumen::query()->create([
            'pendaftaran_id' => $pastPendaftaran->id,
            'jenis_dokumen_id' => $jenisDokumen->id,
            'file_path' => $path,
            'nama_file_asli' => 'ktp_asli.pdf',
            'mime_type' => 'application/pdf',
            'ukuran_file' => 1024,
            'status' => 'verified',
        ]);

        $response = $this->actingAs($student)
            ->get(route('mahasiswa.dashboard'));

        $response->assertOk();
        $response->assertSee('Dokumen Arsip — Periode 2025/2026 Ganjil');
        $response->assertSee('ktp_asli.pdf');
    }

    public function test_student_can_preview_and_download_their_past_formulirs_and_lpj(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $pastPeriod = Periode::query()->create([
            'nama' => '2025/2026 Ganjil',
            'tahun' => 2025,
            'status' => 'ditutup',
            'tanggal_mulai' => now()->subYear(),
            'tanggal_selesai' => now()->subMonths(6),
        ]);

        $kategori = KategoriBeasiswa::query()->first();

        $pastPendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $pastPeriod->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KH-PAST-002',
            'status' => 'approved',
        ]);

        $suratPath = 'pendaftarans/'.$pastPendaftaran->id.'/formulir/surat.pdf';
        $paktaPath = 'pendaftarans/'.$pastPendaftaran->id.'/formulir/pakta.pdf';
        $lpjPath = 'lpj/'.$pastPendaftaran->id.'/lpj.pdf';

        Storage::disk('local')->put($suratPath, '%PDF-1.4 Surat Content');
        Storage::disk('local')->put($paktaPath, '%PDF-1.4 Pakta Content');
        Storage::disk('local')->put($lpjPath, '%PDF-1.4 LPJ Content');

        \App\Models\FormulirPendaftaran::query()->create([
            'pendaftaran_id' => $pastPendaftaran->id,
            'surat_permohonan' => $suratPath,
            'pakta_integritas' => $paktaPath,
        ]);

        \App\Models\LaporanPertanggungjawaban::query()->create([
            'pendaftaran_id' => $pastPendaftaran->id,
            'file_path' => $lpjPath,
            'original_name' => 'laporan_dana.pdf',
            'status' => 'disetujui',
        ]);

        // Preview & Download Surat Permohonan masa lalu
        $this->actingAs($student)
            ->get(route('mahasiswa.formulir.lihat', ['jenis' => 'surat', 'pendaftaran_id' => $pastPendaftaran->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($student)
            ->get(route('mahasiswa.formulir.download', ['jenis' => 'surat', 'pendaftaran_id' => $pastPendaftaran->id]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=Surat-Permohonan-KH-PAST-002.pdf');

        // Preview & Download Pakta Integritas masa lalu
        $this->actingAs($student)
            ->get(route('mahasiswa.formulir.lihat', ['jenis' => 'pakta', 'pendaftaran_id' => $pastPendaftaran->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($student)
            ->get(route('mahasiswa.formulir.download', ['jenis' => 'pakta', 'pendaftaran_id' => $pastPendaftaran->id]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=Pakta-Integritas-KH-PAST-002.pdf');

        // Preview & Download LPJ masa lalu
        $this->actingAs($student)
            ->get(route('mahasiswa.lpj.file', ['pendaftaran_id' => $pastPendaftaran->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($student)
            ->get(route('mahasiswa.lpj.file', ['pendaftaran_id' => $pastPendaftaran->id, 'download' => 1]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=Berkas-LPJ-KH-PAST-002.pdf');
    }
}
