<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RegionSeeder::class);
        Storage::fake('public');
    }

    public function test_visitor_can_view_announcements_index(): void
    {
        Announcement::create([
            'title' => 'Pengumuman Penting Beasiswa',
            'slug' => 'pengumuman-penting-beasiswa',
            'type' => 'informasi',
            'excerpt' => 'Ringkasan informasi publik.',
            'body' => 'Isi penjelasan beasiswa.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('public.announcements.index'));

        $response->assertOk();
        $response->assertSee('Pengumuman');
        $response->assertSee('Pengumuman Penting Beasiswa');
        $response->assertSee(route('public.results'));
        $response->assertSee('Cek Hasil Seleksi');
    }

    public function test_visitor_can_search_and_filter_announcements(): void
    {
        Announcement::create([
            'title' => 'Panduan Khusus Prestasi',
            'slug' => 'panduan-khusus-prestasi',
            'type' => 'panduan',
            'excerpt' => 'Panduan lengkap.',
            'body' => 'Isi panduan.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        Announcement::create([
            'title' => 'Informasi Jadwal Wawancara',
            'slug' => 'informasi-jadwal-wawancara',
            'type' => 'informasi',
            'excerpt' => 'Informasi jadwal.',
            'body' => 'Isi jadwal.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $searchResponse = $this->get(route('public.announcements.index', ['search' => 'Prestasi']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Panduan Khusus Prestasi');
        $searchResponse->assertDontSee('Informasi Jadwal Wawancara');

        $typeResponse = $this->get(route('public.announcements.index', ['type' => 'informasi']));
        $typeResponse->assertOk();
        $typeResponse->assertSee('Informasi Jadwal Wawancara');
        $typeResponse->assertDontSee('Panduan Khusus Prestasi');
    }

    public function test_visitor_can_read_announcement_detail(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pembukaan Seleksi Tahap II',
            'slug' => 'pembukaan-seleksi-tahap-ii',
            'type' => 'informasi',
            'excerpt' => 'Ringkasan tahap dua.',
            'body' => 'Penjelasan detail mengenai tata cara pendaftaran tahap dua.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('public.announcements.show', $announcement->slug));

        $response->assertOk();
        $response->assertSee('Pembukaan Seleksi Tahap II');
        $response->assertSee('Penjelasan detail mengenai tata cara pendaftaran tahap dua.');
    }

    public function test_draft_announcement_returns_404(): void
    {
        $draft = Announcement::create([
            'title' => 'Pengumuman Draf Belum Terbit',
            'slug' => 'pengumuman-draf-belum-terbit',
            'type' => 'informasi',
            'excerpt' => 'Draf rahasia.',
            'body' => 'Belum boleh dilihat.',
            'is_active' => false,
            'published_at' => now(),
        ]);

        $response = $this->get(route('public.announcements.show', $draft->slug));
        $response->assertNotFound();
    }

    public function test_visitor_can_download_announcement_pdf(): void
    {
        $file = UploadedFile::fake()->create('juknis-beasiswa.pdf', 300, 'application/pdf');
        $path = $file->storePubliclyAs('announcements', 'juknis-beasiswa.pdf', 'public');

        $announcement = Announcement::create([
            'title' => 'Juknis Resmi Beasiswa',
            'slug' => 'juknis-resmi-beasiswa',
            'type' => 'panduan',
            'excerpt' => 'Silakan unduh juknis terlampir.',
            'body' => 'Tata cara lengkap ada di berkas PDF.',
            'attachment_path' => $path,
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('public.announcements.download', $announcement->slug));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_homepage_announcement_cards_link_to_detail(): void
    {
        $announcement = Announcement::create([
            'title' => 'Pengumuman Beranda',
            'slug' => 'pengumuman-beranda',
            'type' => 'informasi',
            'excerpt' => 'Muncul di beranda.',
            'body' => 'Isi lengkap beranda.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('public.announcements.show', $announcement->slug));
        $response->assertSee(route('public.announcements.index'));
    }
}
