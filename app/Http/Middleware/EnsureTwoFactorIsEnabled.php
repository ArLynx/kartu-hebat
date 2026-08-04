<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->role->requiresTwoFactor()
            && !$user->two_factor_confirmed_at
            && !$request->routeIs('2fa.setup', 'profile.show', 'logout')
        ) {
            return redirect()
                ->route('2fa.setup')
                ->with('warning', 'Aktifkan autentikasi dua faktor sebelum mengakses modul internal.');
        }

        return $next($request);
    }
}
