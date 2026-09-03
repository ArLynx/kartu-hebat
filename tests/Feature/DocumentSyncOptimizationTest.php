<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Models\DataPribadi;
use App\Models\Document;
use App\Models\Dokumen;
use App\Models\KategoriBeasiswa;
use App\Models\OrangTua;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use App\Services\ApplicationWorkflowService;
use App\Services\DocumentVerificationService;
use App\Services\MahasiswaPendaftaranService;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentSyncOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RegionSeeder::class,
            MasterDataSeeder::class,
            BeasiswaMasterSeeder::class,
        ]);
        Storage::fake('local');
    }

    public function test_resubmit_without_file_change_does_not_read_file_and_keeps_checksum_and_size(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->completeDraft($user);
        $bridge = app(ApplicationWorkflowService::class);

        $application = $bridge->submit($pendaftaran, $user);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $before = $application->documents()->get()
            ->mapWithKeys(fn (Document $document): array => [$document->type->code => [
                'path' => $document->path,
                'checksum' => $document->checksum,
                'size' => $document->size,
            ]]);

        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_DUKCAPIL,
            'village_id' => $village->id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        $doc = $application->documents()->firstOrFail();
        app(DocumentVerificationService::class)->save($application, $doc, $operator, DocumentVerificationResult::TIDAK_MEMENUHI, 'Perlu perbaikan');

        $application->update(['status' => ApplicationStatus::DRAFT]);

        $pendaftaran->forceFill([
            'status' => 'revision',
            'review_dikonfirmasi_at' => now(),
        ])->save();

        $application = $bridge->submit($pendaftaran->fresh(), $user);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $after = $application->documents()->get()
            ->mapWithKeys(fn (Document $document): array => [$document->type->code => [
                'path' => $document->path,
                'checksum' => $document->checksum,
                'size' => $document->size,
            ]]);

        foreach ($before as $code => $document) {
            $this->assertSame($document['path'], $after[$code]['path'], 'Path tidak berubah untuk '.$code);
            $this->assertSame($document['checksum'], $after[$code]['checksum'], 'Checksum dipertahankan untuk '.$code);
            $this->assertSame($document['size'], $after[$code]['size'], 'Size dipertahankan untuk '.$code);
        }
    }

    public function test_resubmit_without_file_change_does_not_read_file_contents(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->completeDraft($user);
        $bridge = app(ApplicationWorkflowService::class);

        $application = $bridge->submit($pendaftaran, $user);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $document = $application->documents()->with('type')->get()
            ->first(fn (Document $document): bool => $document->type->code === 'KTP');
        $originalChecksum = $document->checksum;

        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_DUKCAPIL,
            'village_id' => $village->id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        app(DocumentVerificationService::class)->save($application, $document, $operator, DocumentVerificationResult::TIDAK_MEMENUHI, 'Perlu perbaikan');

        $application->update(['status' => ApplicationStatus::DRAFT]);

        $pendaftaran->forceFill([
            'status' => 'revision',
            'review_dikonfirmasi_at' => now(),
        ])->save();

        Storage::disk('local')->put($document->path, 'isi-file-berubah');

        $application = $bridge->submit($pendaftaran->fresh(), $user);

        $document = $application->documents()->with('type')->get()
            ->first(fn (Document $document): bool => $document->type->code === 'KTP');
        $this->assertSame(
            $originalChecksum,
            $document->checksum,
            'Checksum lama dipertahankan saat path tidak berubah; isi file tidak boleh dibaca ulang.',
        );
    }

    public function test_resubmit_after_replacing_file_updates_checksum_and_size(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->completeDraft($user);
        $bridge = app(ApplicationWorkflowService::class);

        $application = $bridge->submit($pendaftaran, $user);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $ktpDokumen = Dokumen::query()->where('pendaftaran_id', $pendaftaran->id)
            ->whereHas('jenisDokumen', fn ($query) => $query->where('kode', 'KTP'))
            ->firstOrFail();

        $newPath = 'uploads/'.$pendaftaran->id.'/KTP/new.pdf';
        Storage::disk('local')->put($newPath, 'new-content');

        $ktpDokumen->forceFill([
            'file_path' => $newPath,
            'ukuran_file' => strlen('new-content'),
            'verified_at' => null,
        ])->save();

        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_DUKCAPIL,
            'village_id' => $village->id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        $doc = $application->documents()->firstOrFail();
        app(DocumentVerificationService::class)->save($application, $doc, $operator, DocumentVerificationResult::TIDAK_MEMENUHI, 'Perlu perbaikan');

        $application->update(['status' => ApplicationStatus::DRAFT]);

        $pendaftaran->forceFill([
            'status' => 'revision',
            'review_dikonfirmasi_at' => now(),
        ])->save();

        $application = $bridge->submit($pendaftaran->fresh(), $user);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);

        $ktp = $application->documents()->with('type')->get()
            ->first(fn (Document $document): bool => $document->type->code === 'KTP');

        $this->assertSame($newPath, $ktp->path);
        $this->assertSame(hash('sha256', 'new-content'), $ktp->checksum);
        $this->assertSame(strlen('new-content'), (int) $ktp->size);
    }

    private function completeDraft(User $user): Pendaftaran
    {
        $periode = Periode::query()->firstOrFail();
        $kategori = KategoriBeasiswa::query()->where('kode', 'BEASISWA-MAHASISWA')->firstOrFail();
        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-DOC-'.$user->id,
            'status' => 'draft',
            'prestasi_dikonfirmasi_at' => now(),
            'review_dikonfirmasi_at' => now(),
        ]);

        DataPribadi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nik' => '6212123456789012',
            'nisn' => '1234567890',
            'nama_lengkap' => 'Mahasiswa Pengujian',
            'tempat_lahir' => 'Puruk Cahu',
            'tanggal_lahir' => '2003-01-01',
            'jenis_kelamin' => 'L',
            'agama' => 'Islam',
            'no_hp' => '081234567890',
            'alamat' => 'Jalan Pengujian',
            'provinsi' => 'Kalimantan Tengah',
            'village_id' => $village->id,
            'kabupaten' => $village->kabupaten->name,
            'kecamatan' => $village->kecamatan->name,
            'desa' => $village->name,
            'kode_pos' => '73911',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => 'KHM-DOC-'.$user->id,
            'universitas' => 'Universitas Pengujian',
            'fakultas' => 'Teknik',
            'program_studi' => 'Teknik Informatika',
            'jenjang' => 'S1',
            'semester' => 5,
            'ipk' => 3.75,
            'tahun_masuk' => 2024,
            'tahun_lulus' => null,
            'status_mahasiswa' => 'aktif',
        ]);

        OrangTua::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_ayah' => 'Ayah Pengujian',
            'nik_ayah' => '6212123456789013',
            'pekerjaan_ayah' => 'Petani',
            'penghasilan_ayah' => 2000000,
            'nama_ibu' => 'Ibu Pengujian',
            'nik_ibu' => '6212123456789014',
            'pekerjaan_ibu' => 'Pedagang',
            'penghasilan_ibu' => 1500000,
            'memiliki_wali' => false,
        ]);

        foreach (app(MahasiswaPendaftaranService::class)->requiredDocumentTypes($pendaftaran) as $type) {
            $contents = strtolower($type->kode).'-content';
            $path = 'uploads/'.$pendaftaran->id.'/'.$type->kode.'.pdf';
            Storage::disk('local')->put($path, $contents);

            Dokumen::query()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'jenis_dokumen_id' => $type->id,
                'file_path' => $path,
                'nama_file_asli' => $type->kode.'.pdf',
                'mime_type' => 'application/pdf',
                'ukuran_file' => strlen($contents),
                'status' => 'uploaded',
            ]);
        }

        return $pendaftaran;
    }
}
