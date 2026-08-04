<?php

namespace Tests\Feature\Mahasiswa;

use App\Enums\UserRole;
use App\Models\KategoriBeasiswa;
use App\Models\Periode;
use App\Models\User;
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

        $response = $this->actingAs($user)->post(route('mahasiswa.pendaftaran.store'), [
            'kategori_beasiswa_id' => $kategori->id,
            'persetujuan' => '1',
        ]);

        $response->assertRedirect(route('mahasiswa.data-pribadi.index'));

        $this->assertDatabaseHas('pendaftarans', [
            'user_id' => $user->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseCount('applications', 0);
    }
}
