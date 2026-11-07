<?php

namespace Tests\Feature\Mahasiswa;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\JalurBeasiswa;
use App\Models\KategoriBeasiswa;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Selection;
use App\Models\User;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeasiswaRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_membuat_pendaftaran_di_tabel_pendaftarans_bukan_applications(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'email_verified_at' => now(),
        ]);

        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => '2026/2027 Ganjil',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'REGULER',
            'nama' => 'Beasiswa Reguler',
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $jalur = $this->createJalur();

        $response = $this->actingAs($user)->post(route('mahasiswa.pendaftaran.store'), [
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'persetujuan' => '1',
        ]);

        $response->assertRedirect(route('mahasiswa.data-pribadi.index'));

        $this->assertDatabaseHas('pendaftarans', [
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $kategori->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_mahasiswa_hanya_memiliki_satu_pendaftaran_pada_periode_yang_sama(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'email_verified_at' => now(),
        ]);
        $periode = $this->createPeriode();
        $jalur = $this->createJalur();
        $first = $this->createCategory($periode);
        $second = $this->createCategory($periode, 'ALTERNATIF');

        $this->actingAs($user)->post(route('mahasiswa.pendaftaran.store'), [
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $first->id,
            'persetujuan' => '1',
        ])->assertRedirect(route('mahasiswa.data-pribadi.index'));

        $this->actingAs($user)->get(route('mahasiswa.pendaftaran.create'))
            ->assertRedirect(route('mahasiswa.dashboard'))
            ->assertSessionHas('warning');

        $this->actingAs($user)->post(route('mahasiswa.pendaftaran.store'), [
            'jalur_beasiswa_id' => $jalur->id,
            'kategori_beasiswa_id' => $second->id,
            'persetujuan' => '1',
        ])->assertRedirect(route('mahasiswa.data-pribadi.index'));

        $this->assertDatabaseCount('pendaftarans', 1);
    }

    public function test_pendaftaran_periode_lama_tidak_dipakai_sebagai_pendaftaran_saat_ini(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'email_verified_at' => now(),
        ]);
        $oldPeriod = $this->createPeriode('ditutup');
        $activePeriod = $this->createPeriode();
        $oldRegistration = Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $oldPeriod->id,
            'kategori_beasiswa_id' => $this->createCategory($oldPeriod, 'LAMA')->id,
            'status' => 'draft',
        ]);
        $currentRegistration = Pendaftaran::query()->create([
            'user_id' => $user->id,
            'periode_id' => $activePeriod->id,
            'kategori_beasiswa_id' => $this->createCategory($activePeriod, 'AKTIF')->id,
            'status' => 'draft',
        ]);

        $this->assertSame($currentRegistration->id, app(MahasiswaPendaftaranService::class)->currentFor($user)->id);
        $this->assertNotSame($oldRegistration->id, app(MahasiswaPendaftaranService::class)->currentFor($user)->id);
    }

    public function test_nomor_pendaftaran_dibuat_acak_kombinasi_huruf_dan_angka_tanpa_karakter_ambigu(): void
    {
        $periode = $this->createPeriode();
        $jalur = $this->createJalur();
        $kategori = $this->createCategory($periode);

        $generatedNumbers = [];

        for ($i = 0; $i < 5; $i++) {
            $student = User::factory()->create([
                'role' => UserRole::MAHASISWA,
                'email_verified_at' => now(),
            ]);

            $this->actingAs($student)->post(route('mahasiswa.pendaftaran.store'), [
                'jalur_beasiswa_id' => $jalur->id,
                'kategori_beasiswa_id' => $kategori->id,
                'persetujuan' => '1',
            ])->assertRedirect(route('mahasiswa.data-pribadi.index'));

            $pendaftaran = Pendaftaran::query()->where('user_id', $student->id)->firstOrFail();
            $nomor = (string) $pendaftaran->nomor_pendaftaran;

            // Format KHM-{TAHUN}-{6 karakter huruf kapital & angka tanpa O/0/I/1}
            $this->assertMatchesRegularExpression('/^KHM-2026-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $nomor);

            $suffix = substr($nomor, strlen('KHM-2026-'));
            $this->assertMatchesRegularExpression('/[A-Z]/', $suffix);
            $this->assertMatchesRegularExpression('/[2-9]/', $suffix);

            $generatedNumbers[] = $nomor;
        }

        $this->assertCount(5, array_unique($generatedNumbers));
    }

    public function test_pencarian_pengumuman_publik_mengenali_nomor_tiket_acak_huruf_kecil(): void
    {
        $periode = $this->createPeriode('ditutup');
        $kategori = $this->createCategory($periode);
        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'email_verified_at' => now(),
        ]);

        $nomorAcak = Pendaftaran::generateNomorPendaftaran($periode->tahun);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => $nomorAcak,
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $application = Application::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $nomorAcak,
            'periode' => $periode->nama,
            'status' => ApplicationStatus::DITERIMA,
            'application_type' => ApplicationType::AKADEMIK,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);

        Selection::query()->create([
            'application_id' => $application->id,
            'final_score' => 92.5,
            'rank' => 1,
            'published_at' => now(),
        ]);

        $this->get(route('public.results', ['nomor' => strtolower($nomorAcak)]))
            ->assertOk()
            ->assertSee($nomorAcak)
            ->assertSee('Diterima');
    }

    private function createPeriode(string $status = 'aktif'): Periode
    {
        return Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Periode Pengujian',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => $status,
        ]);
    }

    private function createCategory(Periode $periode, string $code = 'REGULER'): KategoriBeasiswa
    {
        return KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => $code,
            'nama' => 'Beasiswa '.$code,
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);
    }

    private function createJalur(string $code = 'REGULER'): JalurBeasiswa
    {
        return JalurBeasiswa::query()->create([
            'kode' => $code,
            'nama' => 'Jalur '.$code,
            'aktif' => true,
            'urutan' => 1,
        ]);
    }
}
