<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        $allowed = array_values(array_filter(array_map('trim', $roles)));

        abort_unless(
            in_array($user->role->value, $allowed, true),
            403,
            'Anda tidak memiliki hak akses untuk halaman ini.',
        );

        return $next($request);
    }
}
