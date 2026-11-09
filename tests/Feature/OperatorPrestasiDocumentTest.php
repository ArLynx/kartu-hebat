<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Prestasi;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperatorPrestasiDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    public function test_operator_application_page_shows_prestasi_document_links(): void
    {
        [$operator, $application, $prestasi] = $this->applicationWithPrestasi();

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('Daftar Prestasi')
            ->assertSee(route('operator.applications.prestasi.unduh', [$application, $prestasi]), false);
    }

    public function test_operator_can_preview_prestasi_document(): void
    {
        [$operator, $application, $prestasi] = $this->applicationWithPrestasi();

        $this->actingAs($operator)
            ->get(route('operator.applications.prestasi.lihat', [$application, $prestasi]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_operator_can_download_prestasi_document(): void
    {
        [$operator, $application, $prestasi] = $this->applicationWithPrestasi();

        $this->actingAs($operator)
            ->get(route('operator.applications.prestasi.unduh', [$application, $prestasi]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_prestasi_without_document_is_not_found(): void
    {
        [$operator, $application, $prestasi] = $this->applicationWithPrestasi(withDocument: false);

        $this->actingAs($operator)
            ->get(route('operator.applications.prestasi.unduh', [$application, $prestasi]))
            ->assertNotFound();

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('Berkas bukti belum diunggah');
    }

    public function test_prestasi_from_other_application_is_not_found(): void
    {
        [$operator, $application] = $this->applicationWithPrestasi();
        [, , $otherPrestasi] = $this->applicationWithPrestasi();

        $this->actingAs($operator)
            ->get(route('operator.applications.prestasi.unduh', [$application, $otherPrestasi]))
            ->assertNotFound();
    }

    public function test_operator_outside_region_cannot_access_prestasi_document(): void
    {
        [, $application, $prestasi] = $this->applicationWithPrestasi();

        $otherVillage = Village::query()
            ->where('id', '!=', $application->mahasiswa->profile->village_id)
            ->firstOrFail();

        $outsider = $this->operator(UserRole::OPERATOR_DESA, $otherVillage);

        $this->actingAs($outsider)
            ->get(route('operator.applications.prestasi.lihat', [$application, $prestasi]))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Application, 2: Prestasi}
     */
    private function applicationWithPrestasi(bool $withDocument = true): array
    {
        $village = Village::query()->with('kecamatan')->firstOrFail();

        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'nik' => fake()->unique()->numerify('621201##########'),
            'nim' => fake()->unique()->numerify('KHM-TEST-#####'),
            'phone' => '081200000003',
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Ilmu Komunikasi',
            'semester' => 5,
            'ipk' => 3.55,
            'alamat' => 'Alamat pengujian',
            'village_id' => $village->id,
            'penghasilan_keluarga' => 2500000,
            'jumlah_tanggungan' => 3,
        ]);

        $periode = Periode::query()->firstOrCreate(
            ['tahun' => (int) config('kartu_hebat.current_period')],
            [
                'nama' => 'Periode 2026',
                'tanggal_mulai' => now()->subDay(),
                'tanggal_selesai' => now()->addDays(30),
                'status' => 'aktif',
            ],
        );

        $kategori = KategoriBeasiswa::query()->firstOrCreate(
            ['periode_id' => $periode->id, 'kode' => 'NON-AKADEMIK-TEST'],
            [
                'nama' => 'Prestasi Non-Akademik',
                'application_type' => ApplicationType::NON_AKADEMIK,
                'kuota' => 10,
                'aktif' => true,
                'urutan' => 1,
            ],
        );

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'REG-PRESTASI-'.$student->id,
            'status' => 'submitted',
        ]);

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'pendaftaran_id' => $pendaftaran->id,
            'nomor_pengajuan' => 'KHM-NON-'.$student->id,
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
            'application_type' => ApplicationType::NON_AKADEMIK,
            'submitted_at' => now(),
        ]);

        $path = 'pendaftarans/'.$pendaftaran->id.'/prestasi/sertifikat.pdf';

        if ($withDocument) {
            Storage::disk('local')->put($path, "%PDF-1.4\n% Sertifikat uji\n");
        }

        $prestasi = Prestasi::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'jenis' => 'non_akademik',
            'nama_prestasi' => 'Juara 1 Debat Nasional',
            'tingkat' => 'nasional',
            'peringkat' => 'juara 1',
            'penyelenggara' => 'Kemendikbud',
            'tahun' => 2025,
            'dokumen_prestasi' => $withDocument ? $path : null,
        ]);

        $operator = $this->operator(UserRole::OPERATOR_KABUPATEN, $village);

        return [$operator, $application, $prestasi];
    }

    private function operator(UserRole $role, Village $village): User
    {
        $operator = User::factory()->create([
            'role' => $role,
            'village_id' => $role === UserRole::OPERATOR_DESA ? $village->id : null,
            'kecamatan_id' => $role === UserRole::OPERATOR_KECAMATAN ? $village->kecamatan_id : null,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }
}
