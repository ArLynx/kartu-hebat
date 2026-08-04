<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly MahasiswaPendaftaranService $flow)
    {
    }

    public function index(Request $request): View
    {
        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $request->user()->getKey())
            ->with([
                'periode',
                'kategoriBeasiswa',
                'dataPribadi',
                'pendidikan',
                'prestasis',
                'orangTua',
                'dokumens',
                'application.verificationLogs.actor',
            ])
            ->latest('id')
            ->first();

        $stepStatuses = $pendaftaran
            ? $this->flow->completion($pendaftaran)
            : array_fill(1, 7, false);

        $currentStep = 1;
        foreach (range(1, 7) as $step) {
            if (! $stepStatuses[$step]) {
                $currentStep = $step;
                break;
            }
            $currentStep = $step;
        }

        return view('mahasiswa.dashboard', compact('pendaftaran', 'stepStatuses', 'currentStep'));
    }
}
