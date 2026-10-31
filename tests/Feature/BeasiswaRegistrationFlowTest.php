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
                'nomor_kk' => '6212123456789099',
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
                'nominal_ukt' => 5000000,
                'tahun_masuk' => 2024,
                'akreditasi_perguruan_tinggi' => 'A',
                'alamat_perguruan_tinggi' => 'Jl. Kampus No. 1',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Pengujian',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Lainnya',
                'pekerjaan_ayah_lainnya' => 'Petani',
                'nama_ibu' => 'Ibu Pengujian',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Lainnya',
                'pekerjaan_ibu_lainnya' => 'Pedagang',
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
                'nomor_kk' => '6212123456789099',
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
                'nominal_ukt' => 5000000,
                'tahun_masuk' => 2024,
                'akreditasi_perguruan_tinggi' => 'A',
                'alamat_perguruan_tinggi' => 'Jl. Kampus No. 1',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Pengujian',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Lainnya',
                'pekerjaan_ayah_lainnya' => 'Petani',
                'nama_ibu' => 'Ibu Pengujian',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Lainnya',
                'pekerjaan_ibu_lainnya' => 'Pedagang',
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

    public function test_nominal_ukt_tampil_dan_tersimpan_pada_data_pendidikan(): void
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
            ->assertSee('Besaran Nilai UKT')
            ->assertSee('Indeks Prestasi Total')
            ->assertDontSee('Nilai Rata-rata Raport Kelas XII Semester Genap');

        $this->actingAs($userTidakMampu)
            ->put(route('mahasiswa.pendidikan.update'), [
                'nim' => 'NIM-TM-001',
                'universitas' => 'Universitas Indonesia',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Teknik',
                'jurusan' => 'Teknik Elektro',
                'program_studi' => 'Teknik Elektro',
                'jenjang' => 'S1',
                'semester' => 3,
                'ipk' => 3.20,
                'nominal_ukt' => 2500000,
                'tahun_masuk' => 2024,
                'akreditasi_perguruan_tinggi' => 'Unggul',
                'alamat_perguruan_tinggi' => 'Depok',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->assertDatabaseHas('pendidikans', [
            'pendaftaran_id' => $pendaftaranTidakMampu->id,
            'nominal_ukt' => 2500000,
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
                'nominal_ukt' => 4500000,
                'tahun_masuk' => 2023,
                'akreditasi_perguruan_tinggi' => 'Unggul',
                'alamat_perguruan_tinggi' => 'Yogyakarta',
            ])
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->assertDatabaseHas('pendidikans', [
            'pendaftaran_id' => $pendaftaranAkademik->id,
            'nominal_ukt' => 4500000,
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
                'nominal_ukt' => 3000000,
                'tahun_masuk' => 2023,
                'akreditasi_perguruan_tinggi' => 'A',
                'alamat_perguruan_tinggi' => 'Banjarmasin',
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
                'nominal_ukt' => 5000000,
                'tahun_masuk' => 2023,
                'akreditasi_perguruan_tinggi' => 'Unggul',
                'alamat_perguruan_tinggi' => 'Bandung',
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
                'pekerjaan_ayah' => 'Wirausaha',
                'nama_ibu' => 'Ibu Tes',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Wirausaha',
                'memiliki_wali' => false,
            ])
            ->assertSessionHasErrors(['nik_ayah']);

        // Ibu hidup tanpa NIK
        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Tes',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Wirausaha',
                'nama_ibu' => 'Ibu Tes',
                'status_ibu' => 'hidup',
                'nik_ibu' => '',
                'pekerjaan_ibu' => 'Wirausaha',
                'memiliki_wali' => false,
            ])
            ->assertSessionHasErrors(['nik_ibu']);
    }

    public function test_nik_dan_pekerjaan_ayah_ibu_opsional_jika_status_meninggal_dunia(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->draftFor($user);

        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Alm. Ayah Tes',
                'status_ayah' => 'meninggal_dunia',
                'nik_ayah' => null,
                'pekerjaan_ayah' => '',
                'nama_ibu' => 'Almh. Ibu Tes',
                'status_ibu' => 'meninggal_dunia',
                'nik_ibu' => '',
                'pekerjaan_ibu' => '',
                'memiliki_wali' => true,
                'nama_wali' => 'Wali Tes',
                'nik_wali' => '6212123456789015',
                'pekerjaan_wali' => 'Wirausaha',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('mahasiswa.dokumen.index'));

        $orangTua = $pendaftaran->fresh()->orangTua;
        $this->assertNull($orangTua->nik_ayah);
        $this->assertNull($orangTua->pekerjaan_ayah);
        $this->assertNull($orangTua->nik_ibu);
        $this->assertNull($orangTua->pekerjaan_ibu);
        $this->assertSame('6212123456789015', $orangTua->nik_wali);
        $this->assertSame('Wirausaha', $orangTua->pekerjaan_wali);
    }

    public function test_pekerjaan_asn_wajib_jabatan_dan_lainnya_wajib_sebutkan_pekerjaan(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->draftFor($user);

        // 1. Pilih ASN tapi jabatan kosong -> error
        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah ASN',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'ASN',
                'jabatan_ayah' => '',
                'nama_ibu' => 'Ibu Lainnya',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Lainnya',
                'pekerjaan_ibu_lainnya' => '',
                'memiliki_wali' => false,
            ])
            ->assertSessionHasErrors(['jabatan_ayah', 'pekerjaan_ibu_lainnya']);

        // 2. Pilih ASN dengan jabatan & Lainnya dengan sebutkan pekerjaan -> sukses
        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah ASN',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'ASN',
                'jabatan_ayah' => 'Kepala Bidang Pendidikan',
                'nama_ibu' => 'Ibu Lainnya',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Lainnya',
                'pekerjaan_ibu_lainnya' => 'Ibu Rumah Tangga',
                'memiliki_wali' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('mahasiswa.dokumen.index'));

        $orangTua = $pendaftaran->fresh()->orangTua;
        $this->assertSame('ASN', $orangTua->pekerjaan_ayah);
        $this->assertSame('Kepala Bidang Pendidikan', $orangTua->jabatan_ayah);
        $this->assertSame('Ibu Rumah Tangga', $orangTua->pekerjaan_ibu);
        $this->assertNull($orangTua->jabatan_ibu);
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
                'pekerjaan_ayah' => 'Wirausaha',
                'nama_ibu' => 'Ibu Tes',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Wirausaha',
                'memiliki_wali' => true,
                'nama_wali' => 'Wali Tes',
                'nik_wali' => '',
                'pekerjaan_wali' => 'Wirausaha',
            ])
            ->assertSessionHasErrors(['nik_wali']);

        // Memiliki wali dan NIK wali 16 digit
        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Tes',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Wirausaha',
                'nama_ibu' => 'Ibu Tes',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Wirausaha',
                'memiliki_wali' => true,
                'nama_wali' => 'Wali Tes',
                'nik_wali' => '6212123456789015',
                'pekerjaan_wali' => 'Wirausaha',
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
            'nama_ibu' => 'Ibu Tes',
            'status_ibu' => 'hidup',
            'nik_ibu' => '6212123456789014',
            'pekerjaan_ibu' => 'Pedagang',
            'memiliki_wali' => false,
        ]);

        $this->assertFalse($service->completion($pendaftaran->fresh())[4]);

        // Case 2: Status ayah hidup dan nik_ayah diisi => Step 4 lengkap
        $orangTua->update(['nik_ayah' => '6212123456789013']);
        $this->assertTrue($service->completion($pendaftaran->fresh())[4]);

        // Case 3: Status ayah meninggal_dunia, nik_ayah dan pekerjaan_ayah null => tetap lengkap
        $orangTua->update([
            'status_ayah' => 'meninggal_dunia',
            'nik_ayah' => null,
            'pekerjaan_ayah' => null,
        ]);
        $this->assertTrue($service->completion($pendaftaran->fresh())[4]);

        // Case 4: Memiliki wali tapi nik_wali null => Step 4 belum lengkap
        $orangTua->update([
            'memiliki_wali' => true,
            'nama_wali' => 'Wali Tes',
            'nik_wali' => null,
            'pekerjaan_wali' => 'Petani',
        ]);
        $this->assertFalse($service->completion($pendaftaran->fresh())[4]);

        // Case 5: Memiliki wali dan nik_wali diisi => Step 4 lengkap
        $orangTua->update(['nik_wali' => '6212123456789015']);
        $this->assertTrue($service->completion($pendaftaran->fresh())[4]);
    }

    public function test_student_must_provide_valid_16_digit_nomor_kk(): void
    {
        $user = User::factory()->create();
        $pendaftaran = $this->draftFor($user);
        $village = Village::query()->with(['kecamatan', 'kabupaten'])->firstOrFail();

        // 1. Nomor KK kosong -> Error
        $this->actingAs($user)
            ->put(route('mahasiswa.data-pribadi.update'), [
                'nik' => '6212123456789012',
                'nomor_kk' => '',
                'nomor_rekening' => '1234567890',
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
            ->assertSessionHasErrors(['nomor_kk']);

        // 2. Nomor KK kurang dari 16 digit -> Error
        $this->actingAs($user)
            ->put(route('mahasiswa.data-pribadi.update'), [
                'nik' => '6212123456789012',
                'nomor_kk' => '12345',
                'nomor_rekening' => '1234567890',
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
            ->assertSessionHasErrors(['nomor_kk']);

        // 3. Nomor KK valid 16 digit -> Berhasil disimpan
        $this->actingAs($user)
            ->put(route('mahasiswa.data-pribadi.update'), [
                'nik' => '6212123456789012',
                'nomor_kk' => '6212123456789099',
                'nomor_rekening' => '1234567890',
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
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('mahasiswa.pendidikan.index'));

        $this->assertSame('6212123456789099', $pendaftaran->fresh()->dataPribadi->nomor_kk);

        // 4. Kelengkapan tahap 1 (Data Pribadi)
        $service = app(MahasiswaPendaftaranService::class);
        $this->assertTrue($service->completion($pendaftaran->fresh())[1]);
    }

    public function test_tahap_prestasi_wajib_minimal_satu_data_dan_bisa_lomba_atau_organisasi(): void
    {
        $periode = Periode::query()->firstOrFail();
        $jalur = JalurBeasiswa::query()->firstOrCreate(
            ['kode' => 'REGULER'],
            ['nama' => 'Reguler', 'aktif' => true, 'urutan' => 1]
        );

        $user = User::factory()->create();
        $kategoriNonAkademik = KategoriBeasiswa::query()
            ->where('application_type', ApplicationType::NON_AKADEMIK)
            ->firstOrFail();

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategoriNonAkademik->id,
            'nomor_pendaftaran' => 'KHM-2026-NA-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);

        // 1. Coba konfirmasi saat data prestasi masih kosong -> Harus ditolak
        $this->actingAs($user)
            ->post(route('mahasiswa.prestasi.confirm'))
            ->assertSessionHas('error');

        // 2. Tambah data kompetisi lomba
        $this->actingAs($user)
            ->post(route('mahasiswa.prestasi.store'), [
                'kategori_bukti' => 'lomba',
                'nama_prestasi' => 'Juara 1 Lomba MTQ Mahasiswa',
                'tingkat' => 'provinsi',
                'peringkat' => 'juara 1',
                'penyelenggara' => 'LPTQ Kalteng',
                'tahun' => 2025,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $lomba = $pendaftaran->fresh()->prestasis()->where('tingkat', 'provinsi')->first();
        $this->assertNotNull($lomba);
        $this->assertFalse((bool) $lomba->is_pengurus_inti_ormawa);
        $this->assertSame('non_akademik', $lomba->jenis);
        $this->assertSame('juara 1', $lomba->peringkat);

        // 3. Tambah data kepengurusan organisasi mahasiswa
        $this->actingAs($user)
            ->post(route('mahasiswa.prestasi.store'), [
                'kategori_bukti' => 'organisasi',
                'nama_organisasi' => 'Badan Eksekutif Mahasiswa (BEM)',
                'jabatan_ormawa' => 'Ketua Umum BEM',
                'penyelenggara' => 'Universitas Palangka Raya',
                'tahun' => 2025,
                'keterangan' => 'SK Rektor No. 12/2025',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $ormawa = $pendaftaran->fresh()->prestasis()->where('is_pengurus_inti_ormawa', true)->first();
        $this->assertNotNull($ormawa);
        $this->assertSame('Badan Eksekutif Mahasiswa (BEM)', $ormawa->nama_prestasi);
        $this->assertSame('Ketua Umum BEM', $ormawa->jabatan_ormawa);
        $this->assertSame('kampus', $ormawa->tingkat);
        $this->assertSame('non_akademik', $ormawa->jenis);

        // 4. Setelah ada data, konfirmasi berhasil dan lanjut ke data orang tua
        $this->actingAs($user)
            ->post(route('mahasiswa.prestasi.confirm'))
            ->assertRedirect(route('mahasiswa.orang-tua.index'))
            ->assertSessionHas('success');

        $service = app(MahasiswaPendaftaranService::class);
        $this->assertTrue($service->completion($pendaftaran->fresh())[3]);
    }
}
