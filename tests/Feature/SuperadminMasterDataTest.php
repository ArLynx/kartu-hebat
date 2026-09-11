<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\DocumentType;
use App\Models\DocumentVerification;
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

    public function test_operator_with_document_assessment_history_cannot_be_deleted(): void
    {
        $superadmin = $this->superadmin();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_DESA,
            'two_factor_confirmed_at' => now(),
        ]);

        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $application = Application::query()->create([
            'nomor_pengajuan' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => null,
            'status' => ApplicationStatus::DRAFT,
        ]);
        $type = DocumentType::query()->create(['code' => 'TEST-TYPE', 'name' => 'Tes Tipe Dokumen']);
        $document = $application->documents()->create([
            'document_type_id' => $type->id,
            'uploaded_by' => $student->id,
            'path' => 'applications/'.$application->id.'/TEST-TYPE/test.pdf',
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size' => 4,
            'checksum' => hash('sha256', 'test'),
        ]);
        DocumentVerification::query()->create([
            'application_id' => $application->id,
            'document_id' => $document->id,
            'verifier_id' => $operator->id,
            'stage' => 'desa',
            'result' => 'memenuhi',
            'verified_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->delete(route('superadmin.operators.destroy', $operator))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $operator->id]);
    }

    public function test_operator_without_history_can_be_deleted(): void
    {
        $superadmin = $this->superadmin();
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KECAMATAN,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->delete(route('superadmin.operators.destroy', $operator))
            ->assertRedirect(route('superadmin.operators.index'));

        $this->assertDatabaseMissing('users', ['id' => $operator->id]);
    }

    public function test_superadmin_can_view_periode_index_and_create_page(): void
    {
        $superadmin = $this->superadmin();
        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Periode Beasiswa 2026',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.periodes.index'))
            ->assertOk()
            ->assertSee('Periode Beasiswa 2026');

        $this->actingAs($superadmin)
            ->get(route('superadmin.periodes.create'))
            ->assertOk()
            ->assertSee('Periode Beasiswa Baru');
    }

    public function test_superadmin_can_create_and_update_periode(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post(route('superadmin.periodes.store'), [
            'tahun' => 2027,
            'nama' => 'Beasiswa Hebat 2027',
            'tanggal_mulai' => '2027-02-01',
            'tanggal_selesai' => '2027-04-30',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('superadmin.periodes.index'));
        $this->assertDatabaseHas('periodes', [
            'tahun' => 2027,
            'nama' => 'Beasiswa Hebat 2027',
            'status' => 'draft',
        ]);

        $periode = Periode::query()->where('tahun', 2027)->firstOrFail();

        $updateResponse = $this->actingAs($superadmin)->put(route('superadmin.periodes.update', $periode), [
            'tahun' => 2027,
            'nama' => 'Beasiswa Hebat 2027 Final',
            'tanggal_mulai' => '2027-02-01',
            'tanggal_selesai' => '2027-05-15',
            'status' => 'aktif',
        ]);

        $updateResponse->assertRedirect(route('superadmin.periodes.edit', $periode));
        $this->assertDatabaseHas('periodes', [
            'id' => $periode->id,
            'nama' => 'Beasiswa Hebat 2027 Final',
            'status' => 'aktif',
        ]);
    }

    public function test_periode_with_categories_cannot_be_deleted(): void
    {
        $superadmin = $this->superadmin();
        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => 'Periode Terikat',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'CAT-TEST',
            'application_type' => ApplicationType::AKADEMIK,
            'nama' => 'Kategori Terikat',
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $this->actingAs($superadmin)
            ->delete(route('superadmin.periodes.destroy', $periode))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('periodes', ['id' => $periode->id]);
    }

    public function test_empty_periode_can_be_deleted(): void
    {
        $superadmin = $this->superadmin();
        $periode = Periode::query()->create([
            'tahun' => 2028,
            'nama' => 'Periode Kosong',
            'tanggal_mulai' => '2028-01-01',
            'tanggal_selesai' => '2028-12-31',
            'status' => 'draft',
        ]);

        $this->actingAs($superadmin)
            ->delete(route('superadmin.periodes.destroy', $periode))
            ->assertRedirect(route('superadmin.periodes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('periodes', ['id' => $periode->id]);
    }

    private function superadmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
