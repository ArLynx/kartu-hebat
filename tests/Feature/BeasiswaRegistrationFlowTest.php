<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Document;
use App\Models\JalurBeasiswa;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use App\Services\AgencyVerificationService;
use App\Services\MahasiswaPendaftaranService;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BeasiswaRegistrationFlowTest extends TestCase
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

    public function test_student_can_complete_steps_two_through_seven_and_submit(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->draftFor($user);
        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();

        $this->actingAs($user)
            ->put(route('mahasiswa.data-pribadi.update'), [
                'nik' => '6212123456789012',
                'nomor_rekening' => '1234567890',
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
                'kode_pos' => '73911',
            ])
            ->assertRedirect(route('mahasiswa.pendidikan.index'));

        $this->actingAs($user)
            ->put(route('mahasiswa.pendidikan.update'), [
                'nim' => 'KHM-TEST-001',
                'universitas' => 'Universitas Pengujian',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Teknik',
                'jurusan' => 'Teknik Informatika',
                'program_studi' => 'Teknik Informatika',
                'jenjang' => 'S1',
                'semester' => 5,
                'ipk' => 3.75,
                'tahun_masuk' => 2024,
                'tahun_lulus' => null,
                'status_mahasiswa' => 'aktif',
                'akreditasi_perguruan_tinggi' => 'A',
                'alamat_perguruan_tinggi' => 'Jl. Kampus No. 1',
                'no_telp_perguruan_tinggi' => '0812345678',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Pengujian',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Petani',
                'penghasilan_ayah' => 2000000,
                'nama_ibu' => 'Ibu Pengujian',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Pedagang',
                'penghasilan_ibu' => 1500000,
                'memiliki_wali' => false,
            ])
            ->assertRedirect(route('mahasiswa.dokumen.index'));

        $requiredTypes = app(MahasiswaPendaftaranService::class)
            ->requiredDocumentTypes($pendaftaran);

        $this->assertNotEmpty($requiredTypes);

        foreach ($requiredTypes as $type) {
            $this->actingAs($user)
                ->post(route('mahasiswa.dokumen.store'), [
                    'jenis_dokumen_id' => $type->id,
                    'file' => UploadedFile::fake()->create(strtolower($type->kode).'.pdf', 100, 'application/pdf'),
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($user)
            ->post(route('mahasiswa.formulir.upload'), [
                'jenis' => 'surat',
                'file' => UploadedFile::fake()->create('surat-permohonan.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('mahasiswa.formulir.upload'), [
                'jenis' => 'pakta',
                'file' => UploadedFile::fake()->create('pakta-integritas.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get(route('mahasiswa.review.index'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('mahasiswa.review.confirm'))
            ->assertRedirect(route('mahasiswa.submit.index'));

        $this->actingAs($user)
            ->post(route('mahasiswa.submit.store'), [
                'pernyataan_kebenaran' => '1',
                'pernyataan_final' => '1',
            ])
            ->assertRedirect(route('mahasiswa.bukti-pendaftaran.index'));

        $pendaftaran->refresh();
        $this->assertSame('verification', $pendaftaran->status);
        $this->assertNotNull($pendaftaran->submitted_at);

        $application = Application::query()->where('pendaftaran_id', $pendaftaran->id)->firstOrFail();
        $this->assertSame(ApplicationStatus::VERIFIKASI_DINAS, $application->status);
        $this->assertSame($pendaftaran->nomor_pendaftaran, $application->nomor_pengajuan);
        $this->assertSame($village->id, $user->fresh()->profile?->village_id);
        $this->assertSame($requiredTypes->count(), $application->documents()->count());
    }

    public function test_reuploading_document_resets_only_that_document_verification(): void
    {
        $user = User::factory()->create();
        $application = $this->completeAndSubmit($user);

        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_DUKCAPIL,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        $documentsByCode = $application->documents()->with('type')->get()
            ->keyBy(fn (Document $document): string => $document->type->code);

        $documentVerifier = app(AgencyVerificationService::class);
        $documentVerifier->assessDocument($application, $documentsByCode['KTP'], $operator, DocumentVerificationResult::TIDAK_MEMENUHI, 'KTP buram');
        $documentVerifier->assessDocument($application, $documentsByCode['KHS'], $operator, DocumentVerificationResult::MEMENUHI);

        $this->assertSame(1, $documentsByCode['KTP']->verifications()->count());
        $this->assertSame(1, $documentsByCode['KHS']->verifications()->count());

        // Kembalikan ke draf agar dokumen dapat diubah oleh mahasiswa.
        $application->update(['status' => ApplicationStatus::DRAFT]);

        $jenisKtp = JenisDokumen::query()->where('kode', 'KTP')->firstOrFail();

        $this->actingAs($user)
            ->post(route('mahasiswa.dokumen.store'), [
                'jenis_dokumen_id' => $jenisKtp->id,
                'file' => UploadedFile::fake()->create('ktp-baru.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            0,
            $documentsByCode['KTP']->fresh()->verifications()->count(),
            'Verifikasi dokumen yang diunggah ulang harus direset.',
        );
        $this->assertSame(
            1,
            $documentsByCode['KHS']->fresh()->verifications()->count(),
            'Verifikasi dokumen lain tidak boleh ikut terreset.',
        );
    }

    public function test_submit_is_rejected_when_required_steps_are_incomplete(): void
    {
        $user = User::factory()->create();
        $this->draftFor($user);

        $this->actingAs($user)
            ->post(route('mahasiswa.submit.store'), [
                'pernyataan_kebenaran' => '1',
                'pernyataan_final' => '1',
            ])
            ->assertRedirect(route('mahasiswa.review.index'))
            ->assertSessionHas('error');
    }

    private function draftFor(User $user): Pendaftaran
    {
        $periode = Periode::query()->firstOrFail();
        $kategori = KategoriBeasiswa::query()->firstOrFail();
        $jalur = JalurBeasiswa::query()->firstOrCreate(
            ['kode' => 'REGULER'],
            ['nama' => 'Reguler', 'aktif' => true, 'urutan' => 1]
        );

        return Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-2026-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);
    }

    private function completeAndSubmit(User $user): Application
    {
        $pendaftaran = $this->draftFor($user);
        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();

        $this->actingAs($user)
            ->put(route('mahasiswa.data-pribadi.update'), [
                'nik' => '6212123456789012',
                'nomor_rekening' => '1234567890',
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
                'kode_pos' => '73911',
            ])
            ->assertRedirect(route('mahasiswa.pendidikan.index'));

        $this->actingAs($user)
            ->put(route('mahasiswa.pendidikan.update'), [
                'nim' => 'KHM-TEST-001',
                'universitas' => 'Universitas Pengujian',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Teknik',
                'jurusan' => 'Teknik Informatika',
                'program_studi' => 'Teknik Informatika',
                'jenjang' => 'S1',
                'semester' => 5,
                'ipk' => 3.75,
                'tahun_masuk' => 2024,
                'tahun_lulus' => null,
                'status_mahasiswa' => 'aktif',
                'akreditasi_perguruan_tinggi' => 'A',
                'alamat_perguruan_tinggi' => 'Jl. Kampus No. 1',
                'no_telp_perguruan_tinggi' => '0812345678',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Pengujian',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Petani',
                'penghasilan_ayah' => 2000000,
                'nama_ibu' => 'Ibu Pengujian',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Pedagang',
                'penghasilan_ibu' => 1500000,
                'memiliki_wali' => false,
            ])
            ->assertRedirect(route('mahasiswa.dokumen.index'));

        $requiredTypes = app(MahasiswaPendaftaranService::class)
            ->requiredDocumentTypes($pendaftaran);

        foreach ($requiredTypes as $type) {
            $this->actingAs($user)
                ->post(route('mahasiswa.dokumen.store'), [
                    'jenis_dokumen_id' => $type->id,
                    'file' => UploadedFile::fake()->create(strtolower($type->kode).'.pdf', 100, 'application/pdf'),
                ])
                ->assertSessionHasNoErrors();
        }

        $this->actingAs($user)
            ->post(route('mahasiswa.formulir.upload'), [
                'jenis' => 'surat',
                'file' => UploadedFile::fake()->create('surat-permohonan.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('mahasiswa.formulir.upload'), [
                'jenis' => 'pakta',
                'file' => UploadedFile::fake()->create('pakta-integritas.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('mahasiswa.review.confirm'))
            ->assertRedirect(route('mahasiswa.submit.index'));

        $this->actingAs($user)
            ->post(route('mahasiswa.submit.store'), [
                'pernyataan_kebenaran' => '1',
                'pernyataan_final' => '1',
            ])
            ->assertRedirect(route('mahasiswa.bukti-pendaftaran.index'));

        return Application::query()->where('pendaftaran_id', $pendaftaran->id)->firstOrFail();
    }

    public function test_nilai_raport_hanya_tampil_dan_tersimpan_pada_kategori_tidak_mampu(): void
    {
        $userTidakMampu = User::factory()->create();
        $periode = Periode::query()->firstOrFail();
        $kategoriTidakMampu = KategoriBeasiswa::query()
            ->where('application_type', ApplicationType::TIDAK_MAMPU)
            ->firstOrFail();
        $jalur = JalurBeasiswa::query()->firstOrCreate(
            ['kode' => 'REGULER'],
            ['nama' => 'Reguler', 'aktif' => true, 'urutan' => 1]
        );

        $pendaftaranTidakMampu = Pendaftaran::query()->create([
            'user_id' => $userTidakMampu->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategoriTidakMampu->id,
            'nomor_pendaftaran' => 'KHM-2026-TM-'.str_pad((string) $userTidakMampu->id, 6, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);

        $this->actingAs($userTidakMampu)
            ->get(route('mahasiswa.pendidikan.index'))
            ->assertOk()
            ->assertSee('Nilai Rata-rata Raport Kelas XII Semester Genap');

        $this->actingAs($userTidakMampu)
            ->put(route('mahasiswa.pendidikan.update'), [
                'nim' => 'NIM-TM-001',
                'universitas' => 'Universitas Indonesia',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Teknik',
                'jurusan' => 'Teknik Elektro',
                'program_studi' => 'Teknik Elektro',
                'jenjang' => 'S1',
                'semester' => 1,
                'ipk' => 0.00,
                'nilai_raport' => 88.50,
                'tahun_masuk' => 2024,
                'tahun_lulus' => null,
                'status_mahasiswa' => 'aktif',
                'akreditasi_perguruan_tinggi' => 'Unggul',
                'alamat_perguruan_tinggi' => 'Depok',
                'no_telp_perguruan_tinggi' => '0211234567',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->assertDatabaseHas('pendidikans', [
            'pendaftaran_id' => $pendaftaranTidakMampu->id,
            'nilai_raport' => 88.50,
        ]);

        $userAkademik = User::factory()->create();
        $kategoriAkademik = KategoriBeasiswa::query()
            ->where('application_type', ApplicationType::AKADEMIK)
            ->firstOrFail();

        $pendaftaranAkademik = Pendaftaran::query()->create([
            'user_id' => $userAkademik->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategoriAkademik->id,
            'nomor_pendaftaran' => 'KHM-2026-AK-'.str_pad((string) $userAkademik->id, 6, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);

        $this->actingAs($userAkademik)
            ->get(route('mahasiswa.pendidikan.index'))
            ->assertOk()
            ->assertDontSee('Nilai Rata-rata Raport Kelas XII Semester Genap');

        $this->actingAs($userAkademik)
            ->put(route('mahasiswa.pendidikan.update'), [
                'nim' => 'NIM-AK-001',
                'universitas' => 'Universitas Gadjah Mada',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'MIPA',
                'jurusan' => 'Ilmu Komputer',
                'program_studi' => 'Ilmu Komputer',
                'jenjang' => 'S1',
                'semester' => 3,
                'ipk' => 3.85,
                'nilai_raport' => 95.00,
                'tahun_masuk' => 2023,
                'tahun_lulus' => null,
                'status_mahasiswa' => 'aktif',
                'akreditasi_perguruan_tinggi' => 'Unggul',
                'alamat_perguruan_tinggi' => 'Yogyakarta',
                'no_telp_perguruan_tinggi' => '0274123456',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->assertDatabaseHas('pendidikans', [
            'pendaftaran_id' => $pendaftaranAkademik->id,
            'nilai_raport' => null,
        ]);
    }

    public function test_tahap_prestasi_hanya_berlaku_untuk_kategori_non_akademik(): void
    {
        $periode = Periode::query()->firstOrFail();
        $jalur = JalurBeasiswa::query()->firstOrCreate(
            ['kode' => 'REGULER'],
            ['nama' => 'Reguler', 'aktif' => true, 'urutan' => 1]
        );

        // 1. Mahasiswa jalur Non-Akademik
        $userNonAkademik = User::factory()->create();
        $kategoriNonAkademik = KategoriBeasiswa::query()
            ->where('application_type', ApplicationType::NON_AKADEMIK)
            ->firstOrFail();

        $pendaftaranNonAkademik = Pendaftaran::query()->create([
            'user_id' => $userNonAkademik->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategoriNonAkademik->id,
            'nomor_pendaftaran' => 'KHM-2026-NA-'.str_pad((string) $userNonAkademik->id, 6, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);

        $this->actingAs($userNonAkademik)
            ->put(route('mahasiswa.pendidikan.update'), [
                'nim' => 'NIM-NA-001',
                'universitas' => 'Universitas Lambung Mangkurat',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'FKIP',
                'jurusan' => 'Pendidikan Olahraga',
                'program_studi' => 'Pendidikan Jasmani',
                'jenjang' => 'S1',
                'semester' => 3,
                'ipk' => 3.50,
                'tahun_masuk' => 2023,
                'status_mahasiswa' => 'aktif',
                'akreditasi_perguruan_tinggi' => 'A',
                'alamat_perguruan_tinggi' => 'Banjarmasin',
                'no_telp_perguruan_tinggi' => '0511123456',
            ])
            ->assertRedirect(route('mahasiswa.prestasi.index'));

        $this->actingAs($userNonAkademik)
            ->get(route('mahasiswa.prestasi.index'))
            ->assertOk();

        $this->actingAs($userNonAkademik)
            ->get(route('mahasiswa.pendidikan.index'))
            ->assertSee('Lanjut ke Prestasi');

        // 2. Mahasiswa jalur Akademik
        $userAkademik = User::factory()->create();
        $kategoriAkademik = KategoriBeasiswa::query()
            ->where('application_type', ApplicationType::AKADEMIK)
            ->firstOrFail();

        $pendaftaranAkademik = Pendaftaran::query()->create([
            'user_id' => $userAkademik->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategoriAkademik->id,
            'nomor_pendaftaran' => 'KHM-2026-AK2-'.str_pad((string) $userAkademik->id, 6, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);

        $this->actingAs($userAkademik)
            ->put(route('mahasiswa.pendidikan.update'), [
                'nim' => 'NIM-AK-002',
                'universitas' => 'Institut Teknologi Bandung',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'STEI',
                'jurusan' => 'Teknik Elektro',
                'program_studi' => 'Teknik Elektro',
                'jenjang' => 'S1',
                'semester' => 3,
                'ipk' => 3.90,
                'tahun_masuk' => 2023,
                'status_mahasiswa' => 'aktif',
                'akreditasi_perguruan_tinggi' => 'Unggul',
                'alamat_perguruan_tinggi' => 'Bandung',
                'no_telp_perguruan_tinggi' => '0221234567',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->actingAs($userAkademik)
            ->get(route('mahasiswa.prestasi.index'))
            ->assertRedirect(route('mahasiswa.orang-tua.index'))
            ->assertSessionHas('info');

        $this->actingAs($userAkademik)
            ->post(route('mahasiswa.prestasi.confirm'))
            ->assertForbidden();

        $this->actingAs($userAkademik)
            ->get(route('mahasiswa.pendidikan.index'))
            ->assertSee('Lanjut ke Data Orang Tua');

        $this->actingAs($userAkademik)
            ->get(route('mahasiswa.orang-tua.index'))
            ->assertSee('Kembali ke Pendidikan');
    }

    public function test_nik_ayah_dan_ibu_wajib_jika_status_hidup(): void
    {
        $user = User::factory()->create();
        $this->draftFor($user);

        // Ayah hidup tanpa NIK
        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Tes',
                'status_ayah' => 'hidup',
                'nik_ayah' => '',
                'pekerjaan_ayah' => 'Petani',
                'penghasilan_ayah' => 2000000,
                'nama_ibu' => 'Ibu Tes',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Pedagang',
                'penghasilan_ibu' => 1500000,
                'memiliki_wali' => false,
            ])
            ->assertSessionHasErrors(['nik_ayah']);

        // Ibu hidup tanpa NIK
        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Tes',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Petani',
                'penghasilan_ayah' => 2000000,
                'nama_ibu' => 'Ibu Tes',
                'status_ibu' => 'hidup',
                'nik_ibu' => '',
                'pekerjaan_ibu' => 'Pedagang',
                'penghasilan_ibu' => 1500000,
                'memiliki_wali' => false,
            ])
            ->assertSessionHasErrors(['nik_ibu']);
    }

    public function test_nik_ayah_dan_ibu_opsional_jika_status_meninggal_dunia(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->draftFor($user);

        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Alm. Ayah Tes',
                'status_ayah' => 'meninggal_dunia',
                'nik_ayah' => null,
                'pekerjaan_ayah' => '-',
                'penghasilan_ayah' => 0,
                'nama_ibu' => 'Almh. Ibu Tes',
                'status_ibu' => 'meninggal_dunia',
                'nik_ibu' => '',
                'pekerjaan_ibu' => '-',
                'penghasilan_ibu' => 0,
                'memiliki_wali' => true,
                'nama_wali' => 'Wali Tes',
                'nik_wali' => '6212123456789015',
                'pekerjaan_wali' => 'Wiraswasta',
                'penghasilan_wali' => 3000000,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('mahasiswa.dokumen.index'));

        $orangTua = $pendaftaran->fresh()->orangTua;
        $this->assertNull($orangTua->nik_ayah);
        $this->assertNull($orangTua->nik_ibu);
        $this->assertSame('6212123456789015', $orangTua->nik_wali);
    }

    public function test_nik_wali_wajib_jika_memiliki_wali(): void
    {
        $user = User::factory()->create();
        $this->draftFor($user);

        // Memiliki wali tapi NIK wali kosong
        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Tes',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Petani',
                'penghasilan_ayah' => 2000000,
                'nama_ibu' => 'Ibu Tes',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Pedagang',
                'penghasilan_ibu' => 1500000,
                'memiliki_wali' => true,
                'nama_wali' => 'Wali Tes',
                'nik_wali' => '',
                'pekerjaan_wali' => 'Wiraswasta',
                'penghasilan_wali' => 3000000,
            ])
            ->assertSessionHasErrors(['nik_wali']);

        // Memiliki wali dan NIK wali 16 digit
        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Tes',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Petani',
                'penghasilan_ayah' => 2000000,
                'nama_ibu' => 'Ibu Tes',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Pedagang',
                'penghasilan_ibu' => 1500000,
                'memiliki_wali' => true,
                'nama_wali' => 'Wali Tes',
                'nik_wali' => '6212123456789015',
                'pekerjaan_wali' => 'Wiraswasta',
                'penghasilan_wali' => 3000000,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('mahasiswa.dokumen.index'));
    }

    public function test_completion_step_orang_tua_depends_on_status_ayah_dan_ibu_dan_wali(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->draftFor($user);
        $service = app(MahasiswaPendaftaranService::class);

        // Case 1: Status hidup tapi nik_ayah null => Step 4 belum lengkap
        $orangTua = $pendaftaran->orangTua()->create([
            'nama_ayah' => 'Ayah Tes',
            'status_ayah' => 'hidup',
            'nik_ayah' => null,
            'pekerjaan_ayah' => 'Petani',
            'penghasilan_ayah' => 2000000,
            'nama_ibu' => 'Ibu Tes',
            'status_ibu' => 'hidup',
            'nik_ibu' => '6212123456789014',
            'pekerjaan_ibu' => 'Pedagang',
            'penghasilan_ibu' => 1500000,
            'memiliki_wali' => false,
        ]);

        $this->assertFalse($service->completion($pendaftaran->fresh())[4]);

        // Case 2: Status ayah hidup dan nik_ayah diisi => Step 4 lengkap
        $orangTua->update(['nik_ayah' => '6212123456789013']);
        $this->assertTrue($service->completion($pendaftaran->fresh())[4]);

        // Case 3: Status ayah meninggal_dunia, nik_ayah null => tetap lengkap
        $orangTua->update([
            'status_ayah' => 'meninggal_dunia',
            'nik_ayah' => null,
        ]);
        $this->assertTrue($service->completion($pendaftaran->fresh())[4]);

        // Case 4: Memiliki wali tapi nik_wali null => Step 4 belum lengkap
        $orangTua->update([
            'memiliki_wali' => true,
            'nama_wali' => 'Wali Tes',
            'nik_wali' => null,
            'pekerjaan_wali' => 'Petani',
            'penghasilan_wali' => 1000000,
        ]);
        $this->assertFalse($service->completion($pendaftaran->fresh())[4]);

        // Case 5: Memiliki wali dan nik_wali diisi => Step 4 lengkap
        $orangTua->update(['nik_wali' => '6212123456789015']);
        $this->assertTrue($service->completion($pendaftaran->fresh())[4]);
    }
}
