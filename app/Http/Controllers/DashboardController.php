<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user()->isSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        if ($request->user()->isOperator()) {
            return redirect()->route('operator.dashboard');
        }

        return redirect()->route('mahasiswa.dashboard');
    }
}
