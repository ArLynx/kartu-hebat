<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        Announcement::query()->create([
            'title' => 'Pengumuman Seleksi Beasiswa 2026',
            'slug' => 'pengumuman-seleksi-beasiswa-2026',
            'type' => 'informasi',
            'excerpt' => 'Hasil seleksi telah diterbitkan.',
            'body' => 'Lihat hasil seleksi pada halaman pengumuman.',
            'is_active' => true,
            'published_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
