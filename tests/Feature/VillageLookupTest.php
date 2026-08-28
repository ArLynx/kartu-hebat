<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\DataPribadi;
use App\Models\Dokumen;
use App\Models\Kabupaten;
use App\Models\KategoriBeasiswa;
use App\Models\Kecamatan;
use App\Models\OrangTua;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use App\Services\MahasiswaPendaftaranService;
use App\Services\PendaftaranWorkflowBridgeService;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VillageLookupTest extends TestCase
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

    public function test_submit_resolves_village_from_text_when_village_id_missing(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->completeDraft($user, 'valid');
        $expected = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();

        $application = app(PendaftaranWorkflowBridgeService::class)->submit($pendaftaran, $user);

        $data = $pendaftaran->fresh()->dataPribadi;
        $this->assertSame($expected->id, $data->village_id);
        $this->assertSame($expected->name, $data->desa);
        $this->assertSame($expected->kecamatan->name, $data->kecamatan);
        $this->assertSame($expected->kabupaten->name, $data->kabupaten);
        $this->assertSame(ApplicationStatus::VERIFIKASI_DESA, $application->status);
    }

    public function test_submit_rejects_unknown_village_text(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->completeDraft($user, 'unknown');

        try {
            app(PendaftaranWorkflowBridgeService::class)->submit($pendaftaran, $user);
            $this->fail('Teks wilayah yang tidak dikenal harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('village_id', $exception->errors());
            $this->assertStringContainsString('tidak ditemukan', $exception->errors()['village_id'][0]);
        }
    }

    public function test_submit_rejects_ambiguous_village_text(): void
    {
        $seeded = Village::query()->with('kecamatan')->firstOrFail();
        $duplicateKabupaten = Kabupaten::query()->create([
            'code' => '9999',
            'name' => $seeded->kecamatan->kabupaten->name,
        ]);
        $duplicateKecamatan = Kecamatan::query()->create([
            'code' => '999901',
            'kabupaten_id' => $duplicateKabupaten->id,
            'name' => $seeded->kecamatan->name,
        ]);
        Village::query()->create([
            'code' => '9999010001',
            'kabupaten_id' => $duplicateKabupaten->id,
            'kecamatan_id' => $duplicateKecamatan->id,
            'name' => $seeded->name,
            'type' => 'desa',
        ]);

        $user = User::factory()->create();
        $pendaftaran = $this->completeDraft($user, 'ambiguous');

        try {
            app(PendaftaranWorkflowBridgeService::class)->submit($pendaftaran, $user);
            $this->fail('Teks wilayah yang ambigu harus ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('village_id', $exception->errors());
            $this->assertStringContainsString('ambigu', $exception->errors()['village_id'][0]);
        }
    }

    public function test_village_lookup_is_filtered_in_database_not_full_table_scan(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->completeDraft($user, 'valid');

        $villageQueries = [];
        DB::listen(function ($query) use (&$villageQueries): void {
            if (preg_match('/\bfrom\s+"?villages"?\b/i', $query->sql)) {
                $villageQueries[] = $query->sql;
            }
        });

        app(PendaftaranWorkflowBridgeService::class)->submit($pendaftaran, $user);

        $this->assertNotEmpty($villageQueries, 'Submit harus memuat tabel villages.');
        foreach ($villageQueries as $sql) {
            $this->assertMatchesRegularExpression(
                '/\bwhere\b/i',
                $sql,
                'Pencarian desa harus dibatasi WHERE di DB, bukan memuat seluruh tabel villages: '.$sql,
            );
        }
    }

    public function test_double_submit_does_not_create_duplicate_application(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->completeDraft($user, 'valid');
        $bridge = app(PendaftaranWorkflowBridgeService::class);

        $first = $bridge->submit($pendaftaran, $user);

        try {
            $bridge->submit($pendaftaran->fresh(), $user);
            $this->fail('Submit kedua saat berstatus verifikasi harus ditolak.');
        } catch (ValidationException) {
        }

        $applications = Application::query()->where('mahasiswa_id', $user->id)->get();
        $this->assertCount(1, $applications);
        $this->assertSame($first->id, $applications->sole()->id);
    }

    public function test_data_pribadi_villages_are_scoped_to_configured_kabupaten(): void
    {
        $user = User::factory()->create();
        $this->completeDraft($user, 'valid');

        $villageQueries = [];
        DB::listen(function ($query) use (&$villageQueries): void {
            if (preg_match('/\bfrom\s+"?villages"?\b/i', $query->sql)) {
                $villageQueries[] = $query->sql;
            }
        });

        $response = $this->actingAs($user)->get(route('mahasiswa.data-pribadi.index'));

        $response->assertOk();

        $villages = $response->viewData('villages');
        $this->assertNotEmpty($villages);

        foreach ($villages as $village) {
            $this->assertSame(config('kartu_hebat.kabupaten_code'), $village->kabupaten->code);
        }

        foreach ($villageQueries as $sql) {
            $this->assertMatchesRegularExpression(
                '/\bwhere\b/i',
                $sql,
                'Dropdown desa harus dibatasi WHERE per kabupaten, bukan memuat seluruh tabel villages: '.$sql,
            );
        }
    }

    private function completeDraft(User $user, string $variant): Pendaftaran
    {
        $periode = Periode::query()->firstOrFail();
        $kategori = KategoriBeasiswa::query()->where('kode', 'BEASISWA-MAHASISWA')->firstOrFail();
        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();

        [$kabupaten, $kecamatan, $desa] = match ($variant) {
            'unknown' => ['Kabupaten Tidak Ada', 'Kecamatan Tidak Ada', 'Desa Tidak Ada'],
            'ambiguous' => [$village->kabupaten->name, $village->kecamatan->name, $village->name],
            default => [$village->kabupaten->name, $village->kecamatan->name, $village->name],
        };

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-VILLAGE-'.$user->id,
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
            'village_id' => null,
            'kabupaten' => $kabupaten,
            'kecamatan' => $kecamatan,
            'desa' => $desa,
            'kode_pos' => '73911',
        ]);

        Pendidikan::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'nim' => 'KHM-VILLAGE-'.$user->id,
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
            $path = 'uploads/'.$pendaftaran->id.'/'.$type->kode.'.pdf';
            Storage::disk('local')->put($path, 'x');

            Dokumen::query()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'jenis_dokumen_id' => $type->id,
                'file_path' => $path,
                'nama_file_asli' => $type->kode.'.pdf',
                'mime_type' => 'application/pdf',
                'ukuran_file' => 1,
                'status' => 'uploaded',
            ]);
        }

        return $pendaftaran;
    }
}
