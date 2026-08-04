<?php

namespace Tests\Feature;

use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\DocumentType;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_is_redirected_to_its_dashboard(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('superadmin.dashboard'));

        $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Pengaturan Beasiswa');
    }

    public function test_operator_cannot_access_superadmin_master_data(): void
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($operator)
            ->get(route('superadmin.kategori-beasiswa.index'))
            ->assertForbidden();
    }

    public function test_superadmin_can_create_document_type_and_integrated_mirror(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post(route('superadmin.document-types.store'), [
            'code' => 'SURAT-REKOMENDASI',
            'name' => 'Surat Rekomendasi',
            'description' => 'Surat rekomendasi dari perguruan tinggi.',
            'application_type' => ApplicationType::AKADEMIK->value,
            'allowed_mimes' => 'pdf, jpg, png',
            'max_size_kb' => 4096,
            'is_required' => '1',
            'is_active' => '1',
            'sort_order' => 70,
        ]);

        $type = DocumentType::query()->where('code', 'SURAT-REKOMENDASI')->firstOrFail();

        $response->assertRedirect(route('superadmin.document-types.edit', $type));

        $this->assertSame(['pdf', 'jpg', 'png'], $type->allowed_mimes);
        $this->assertDatabaseHas('jenis_dokumens', [
            'kode' => 'SURAT-REKOMENDASI',
            'nama' => 'Surat Rekomendasi',
            'format_file' => 'pdf,jpg,png',
            'maksimal_ukuran' => 4096,
            'aktif' => 1,
        ]);
    }

    public function test_superadmin_can_create_category_with_document_requirements(): void
    {
        $superadmin = $this->superadmin();
        $period = Periode::query()->create([
            'tahun' => 2026,
            'nama' => '2026/2027 Ganjil',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);
        $ktp = JenisDokumen::query()->create([
            'kode' => 'KTP',
            'nama' => 'Kartu Tanda Penduduk',
            'format_file' => 'pdf,jpg',
            'maksimal_ukuran' => 2048,
            'aktif' => true,
        ]);
        $khs = JenisDokumen::query()->create([
            'kode' => 'KHS',
            'nama' => 'Kartu Hasil Studi',
            'format_file' => 'pdf',
            'maksimal_ukuran' => 2048,
            'aktif' => true,
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.kategori-beasiswa.store'), [
            'periode_id' => $period->id,
            'kode' => 'BEASISWA-PRESTASI',
            'application_type' => ApplicationType::AKADEMIK->value,
            'nama' => 'Beasiswa Prestasi',
            'deskripsi' => 'Kategori untuk mahasiswa berprestasi.',
            'kuota' => 25,
            'aktif' => '1',
            'urutan' => 3,
            'icon' => 'school',
            'warna' => 'blue',
            'jenis_dokumen_ids' => [$ktp->id, $khs->id],
        ]);

        $category = KategoriBeasiswa::query()->where('kode', 'BEASISWA-PRESTASI')->firstOrFail();

        $response->assertRedirect(route('superadmin.kategori-beasiswa.edit', $category));
        $this->assertSame([$ktp->id, $khs->id], $category->jenisDokumens()->pluck('jenis_dokumens.id')->all());
        $this->assertDatabaseHas('kategori_beasiswa_dokumens', [
            'kategori_beasiswa_id' => $category->id,
            'jenis_dokumen_id' => $ktp->id,
            'urutan' => 1,
        ]);
        $this->assertDatabaseHas('kategori_beasiswa_dokumens', [
            'kategori_beasiswa_id' => $category->id,
            'jenis_dokumen_id' => $khs->id,
            'urutan' => 2,
        ]);
    }

    private function superadmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
