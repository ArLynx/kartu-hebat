<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_allows_youtube_frames_in_content_security_policy(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertSee('https://www.youtube.com/embed/', false);

        $this->assertStringContainsString(
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com;",
            (string) $response->headers->get('Content-Security-Policy'),
        );
    }

    public function test_non_landing_page_keeps_frame_src_limited_to_self(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');

        $contentSecurityPolicy = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-src 'self';", $contentSecurityPolicy);
        $this->assertStringNotContainsString('youtube.com', $contentSecurityPolicy);
    }
}
