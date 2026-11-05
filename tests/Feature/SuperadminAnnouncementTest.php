<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperadminAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegionSeeder::class);

        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'two_factor_confirmed_at' => now(),
        ]);

        Storage::fake('public');
    }

    public function test_superadmin_can_view_announcements_list(): void
    {
        Announcement::create([
            'title' => 'Pengumuman Uji Coba',
            'slug' => 'pengumuman-uji-coba',
            'type' => 'informasi',
            'excerpt' => 'Ringkasan pengumuman uji coba.',
            'body' => 'Isi teks pengumuman uji coba.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('superadmin.announcements.index'));

        $response->assertOk();
        $response->assertSee('Kelola Pengumuman');
        $response->assertSee('Pengumuman Uji Coba');
    }

    public function test_non_superadmin_cannot_access_announcements(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('superadmin.announcements.index'));
        $response->assertForbidden();
    }

    public function test_superadmin_can_create_announcement_with_pdf_attachment(): void
    {
        $pdf = UploadedFile::fake()->create('surat-edaran.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->superadmin)->post(route('superadmin.announcements.store'), [
            'title' => 'Jadwal Seleksi Periode 2027 Dibuka',
            'type' => 'informasi',
            'excerpt' => 'Pendaftaran dibuka mulai 1 Juli 2027.',
            'body' => 'Rincian jadwal dan tata cara pendaftaran beasiswa.',
            'is_active' => '1',
            'published_at' => now()->format('Y-m-d\TH:i'),
            'attachment' => $pdf,
        ]);

        $response->assertRedirect(route('superadmin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'Jadwal Seleksi Periode 2027 Dibuka',
            'slug' => 'jadwal-seleksi-periode-2027-dibuka',
            'type' => 'informasi',
            'is_active' => true,
        ]);

        $announcement = Announcement::where('slug', 'jadwal-seleksi-periode-2027-dibuka')->first();
        $this->assertNotNull($announcement->attachment_path);
        Storage::disk('public')->assertExists($announcement->attachment_path);
    }

    public function test_superadmin_can_toggle_announcement_status(): void
    {
        $announcement = Announcement::create([
            'title' => 'Status Toggle Test',
            'slug' => 'status-toggle-test',
            'type' => 'panduan',
            'excerpt' => 'Ringkasan singkat.',
            'body' => 'Isi teks pengumuman.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->patch(route('superadmin.announcements.toggle', $announcement));
        $response->assertSessionHas('success');

        $this->assertFalse($announcement->fresh()->is_active);

        // Toggle back
        $this->actingAs($this->superadmin)->patch(route('superadmin.announcements.toggle', $announcement));
        $this->assertTrue($announcement->fresh()->is_active);
    }

    public function test_superadmin_can_update_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Judul Lama',
            'slug' => 'judul-lama',
            'type' => 'informasi',
            'excerpt' => 'Ringkasan lama.',
            'body' => 'Isi teks lama.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->put(route('superadmin.announcements.update', $announcement), [
            'title' => 'Judul Baru Diperbarui',
            'type' => 'panduan',
            'excerpt' => 'Ringkasan baru.',
            'body' => 'Isi teks baru.',
            'is_active' => '1',
            'published_at' => now()->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect(route('superadmin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Judul Baru Diperbarui',
            'slug' => 'judul-baru-diperbarui',
            'type' => 'panduan',
        ]);
    }

    public function test_superadmin_can_delete_announcement(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pengumuman Dihapus',
            'slug' => 'pengumuman-dihapus',
            'type' => 'informasi',
            'excerpt' => 'Akan segera dihapus.',
            'body' => 'Teks isi.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->delete(route('superadmin.announcements.destroy', $announcement));

        $response->assertRedirect(route('superadmin.announcements.index'));
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_superadmin_can_view_announcement_file_inline(): void
    {
        $fakePdfPath = 'announcements/test-file.pdf';
        Storage::disk('public')->put($fakePdfPath, '%PDF-1.4 fake content');

        $announcement = Announcement::create([
            'title' => 'Pengumuman Lampiran',
            'slug' => 'pengumuman-lampiran',
            'type' => 'informasi',
            'excerpt' => 'Ringkasan.',
            'body' => 'Teks pengumuman.',
            'attachment_path' => $fakePdfPath,
            'is_active' => false, // Bahkan draf tetap bisa dilihat superadmin
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('superadmin.announcements.file', $announcement));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_announcement_file_returns_404_when_missing(): void
    {
        $announcement = Announcement::create([
            'title' => 'Tanpa Lampiran',
            'slug' => 'tanpa-lampiran',
            'type' => 'informasi',
            'excerpt' => 'Ringkasan.',
            'body' => 'Teks pengumuman.',
            'attachment_path' => null,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)->get(route('superadmin.announcements.file', $announcement));
        $response->assertNotFound();
    }
}
