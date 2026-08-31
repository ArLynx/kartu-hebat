<?php

namespace App\Http\Controllers;

class TwoFactorSetupController extends Controller
{
    public function index()
    {
        $user = request()->user();

        abort_unless($user, 401);

        if (! $user->role->requiresTwoFactor() || $user->two_factor_confirmed_at) {
            return redirect()->route('dashboard');
        }

        return view('auth.2fa-setup');
    }
}
