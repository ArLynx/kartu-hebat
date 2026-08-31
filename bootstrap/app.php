<?php

use App\Http\Middleware\EnsureProfileIsComplete;
use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            '2fa.ensure' => EnsureTwoFactorIsEnabled::class,
            'profile.complete' => EnsureProfileIsComplete::class,
            'nocache' => PreventBackHistory::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        // Trusted proxies TIDAK dikonfigurasi di sini. TrustProxies middleware
        // bawaan Laravel otomatis membaca config('trustedproxy.proxies')
        // (variabel TRUSTED_PROXIES) saat request masuk. Memanggil
        // $middleware->trustProxies() di blok ini tidak aman karena blok ini
        // dieksekusi sebelum config dimuat (saat kernel di-resolve).
        // Lihat config/trustedproxy.php untuk daftar proxy yang dipercaya.

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
    })
    ->create();
