<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LpjController extends Controller
{
    public function __construct(
        private readonly MahasiswaPendaftaranService $flow
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with(
                    'error',
                    'Buat pendaftaran beasiswa terlebih dahulu.'
                );
        }

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
        ]);

        return view('mahasiswa.lpj.index', [
            'pendaftaran' => $pendaftaran,
            'stepStatuses' => $this->flow->completion($pendaftaran),
        ]);
    }
}