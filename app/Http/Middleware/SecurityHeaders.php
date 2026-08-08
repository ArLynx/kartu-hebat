<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Anti-MIME-sniffing, anti-clickjacking, dan kebijakan referrer.
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content-Security-Policy.
        // Aplikasi memakai Livewire 3 (yang meng-bundle Alpine.js). Alpine
        // mengevaluasi ekspresi via Function constructor dan Livewire memakai
        // skrip inline, sehingga butuh 'unsafe-eval' + 'unsafe-inline'.
        // Nilai ini tetap memblokir muatan dari origin eksternal yang tidak
        // diizinkan, objek plugin, clickjacking, dan pengubahan base-uri.
        $connectSrc = 'connect-src \'self\'';
        if (! app()->isProduction()) {
            // Izinkan websocket HMR Vite saat development.
            $connectSrc .= ' ws://localhost:* wss://localhost:* http://localhost:*';
        }

        $csp = "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
            ."style-src 'self' 'unsafe-inline'; "
            ."img-src 'self' data: https:; "
            ."font-src 'self' data:; "
            .$connectSrc.'; '
            ."object-src 'none'; "
            ."base-uri 'self'; "
            ."form-action 'self'; "
            ."frame-ancestors 'none'; "
            ."frame-src 'self'; "
            .(app()->isProduction() ? 'upgrade-insecure-requests' : '');

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
