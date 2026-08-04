<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isStudent() && !$user->isProfileComplete()) {
            return redirect()
                ->route('student.application')
                ->with('warning', 'Lengkapi profil mahasiswa sebelum melanjutkan.');
        }

        return $next($request);
    }
}
