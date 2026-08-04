<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\Application;
use App\Models\KategoriBeasiswa;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use App\Services\ApplicationWorkflowService;
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
                'fakultas' => 'Teknik',
                'program_studi' => 'Teknik Informatika',
                'jenjang' => 'S1',
                'semester' => 5,
                'ipk' => 3.75,
                'tahun_masuk' => 2024,
                'tahun_lulus' => null,
                'status_mahasiswa' => 'aktif',
            ])
            ->assertRedirect(route('mahasiswa.prestasi.index'));

        $this->actingAs($user)
            ->post(route('mahasiswa.prestasi.confirm'))
            ->assertRedirect(route('mahasiswa.orang-tua.index'));

        $this->actingAs($user)
            ->put(route('mahasiswa.orang-tua.update'), [
                'nama_ayah' => 'Ayah Pengujian',
                'nik_ayah' => '6212123456789013',
                'pekerjaan_ayah' => 'Petani',
                'penghasilan_ayah' => 2000000,
                'nama_ibu' => 'Ibu Pengujian',
                'nik_ibu' => '6212123456789014',
                'pekerjaan_ibu' => 'Pedagang',
                'penghasilan_ibu' => 1500000,
                'memiliki_wali' => false,
            ])
            ->assertRedirect(route('mahasiswa.dokumen.index'));

        $requiredTypes = app(\App\Services\MahasiswaPendaftaranService::class)
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
            ->assertRedirect(route('mahasiswa.dashboard'));

        $pendaftaran->refresh();
        $this->assertSame('verification', $pendaftaran->status);
        $this->assertNotNull($pendaftaran->submitted_at);

        $application = Application::query()->where('pendaftaran_id', $pendaftaran->id)->firstOrFail();
        $this->assertSame(ApplicationStatus::VERIFIKASI_DESA, $application->status);
        $this->assertSame($pendaftaran->nomor_pendaftaran, $application->nomor_pengajuan);
        $this->assertSame($village->id, $user->fresh()->profile?->village_id);
        $this->assertSame($requiredTypes->count(), $application->documents()->count());

        $villageOperator = User::factory()->create([
            'role' => UserRole::OPERATOR_DESA,
            'village_id' => $village->id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        $application = app(ApplicationWorkflowService::class)->verify(
            $application,
            $villageOperator,
            VerificationDecision::BTL,
            'Perbaiki dokumen identitas.',
        );

        $this->assertSame(ApplicationStatus::BTL_DESA, $application->status);
        $this->assertSame('revision', $pendaftaran->fresh()->status);
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

        return Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-2026-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);
    }
}
