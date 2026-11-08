<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\FormulirPendaftaran;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperatorApplicationFormulirTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        Storage::fake('local');
    }

    public function test_operator_application_page_shows_formulir_links(): void
    {
        [$operator, $application] = $this->applicationWithFormulir();

        $response = $this->actingAs($operator)->get(route('operator.applications.show', $application));

        $response->assertOk()
            ->assertSee('Formulir Unggahan Mahasiswa')
            ->assertSee('Surat Permohonan')
            ->assertSee('Pakta Integritas')
            ->assertSee('Sudah diunggah (PDF)')
            ->assertSee(route('operator.applications.formulir.download', [$application, 'surat']), false)
            ->assertSee(route('operator.applications.formulir.download', [$application, 'pakta']), false);
    }

    public function test_operator_can_preview_formulir(): void
    {
        [$operator, $application] = $this->applicationWithFormulir();

        $this->actingAs($operator)
            ->get(route('operator.applications.formulir.lihat', [$application, 'surat']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('x-frame-options', 'SAMEORIGIN');
    }

    public function test_operator_can_download_formulir(): void
    {
        [$operator, $application] = $this->applicationWithFormulir();

        $downloadName = 'Pakta-Integritas-'.Str::slug($application->nomor_pengajuan).'.pdf';

        $this->actingAs($operator)
            ->get(route('operator.applications.formulir.download', [$application, 'pakta']))
            ->assertOk()
            ->assertDownload($downloadName);
    }

    public function test_application_page_shows_not_uploaded_state(): void
    {
        [$operator, $application] = $this->applicationWithFormulir(withFiles: false);

        $this->actingAs($operator)
            ->get(route('operator.applications.show', $application))
            ->assertOk()
            ->assertSee('Belum diunggah')
            ->assertDontSee(route('operator.applications.formulir.download', [$application, 'surat']), false);
    }

    public function test_missing_formulir_is_not_found(): void
    {
        [$operator, $application] = $this->applicationWithFormulir(withFiles: false);

        $this->actingAs($operator)
            ->get(route('operator.applications.formulir.lihat', [$application, 'surat']))
            ->assertNotFound();
    }

    public function test_operator_outside_region_cannot_access_formulir(): void
    {
        [$operator, $application] = $this->applicationWithFormulir();

        $otherVillage = Village::query()
            ->where('id', '!=', $application->mahasiswa->profile->village_id)
            ->firstOrFail();

        $outsider = $this->operator(UserRole::OPERATOR_DESA, $otherVillage);

        $this->actingAs($outsider)
            ->get(route('operator.applications.formulir.lihat', [$application, 'surat']))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Application}
     */
    private function applicationWithFormulir(bool $withFiles = true): array
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
            'nik' => '6212010101010002',
            'nim' => 'KHM-TEST-002',
            'phone' => '081200000002',
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.50,
            'alamat' => 'Alamat pengujian',
            'village_id' => $village->id,
            'penghasilan_keluarga' => 2500000,
            'jumlah_tanggungan' => 4,
        ]);

        $periode = Periode::query()->firstOrCreate(
            ['tahun' => (int) config('kartu_hebat.current_period')],
            [
                'nama' => 'Periode 2026',
                'is_active' => true,
                'tanggal_mulai' => now()->subDay(),
                'tanggal_selesai' => now()->addDays(30),
            ],
        );

        $kategori = KategoriBeasiswa::query()->firstOrCreate(
            ['periode_id' => $periode->id, 'kode' => 'AKADEMIK'],
            ['nama' => 'Prestasi Akademik', 'aktif' => true, 'urutan' => 1],
        );

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'REG-'.$student->id,
            'status' => 'submitted',
        ]);

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'pendaftaran_id' => $pendaftaran->id,
            'nomor_pengajuan' => 'KHM-2026-'.$student->id,
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
            'application_type' => ApplicationType::AKADEMIK,
            'submitted_at' => now(),
        ]);

        $suratPath = 'pendaftarans/'.$pendaftaran->id.'/formulir/surat.pdf';
        $paktaPath = 'pendaftarans/'.$pendaftaran->id.'/formulir/pakta.pdf';
        $pdfContent = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";

        if ($withFiles) {
            Storage::disk('local')->put($suratPath, $pdfContent);
            Storage::disk('local')->put($paktaPath, $pdfContent);
        }

        FormulirPendaftaran::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'surat_permohonan' => $withFiles ? $suratPath : null,
            'pakta_integritas' => $withFiles ? $paktaPath : null,
        ]);

        $operator = $this->operator(UserRole::OPERATOR_KABUPATEN, $village);

        return [$operator, $application];
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
