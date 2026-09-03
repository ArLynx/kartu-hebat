<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $result = null;
        $searched = false;

        if ($request->filled('nomor')) {
            $data = $request->validate([
                'nomor' => ['required', 'string', 'max:100'],
            ]);
            $searched = true;

            $result = Application::query()
                ->with(['mahasiswa.profile', 'selection', 'pendaftaran.jalurBeasiswa'])
                ->where('periode', config('kartu_hebat.current_period'))
                ->where(function ($query) use ($data): void {
                    $query
                        ->where('nomor_pengajuan', $data['nomor'])
                        ->orWhereHas('mahasiswa.profile', fn ($profile) => $profile->where('nik', $data['nomor']));
                })
                ->whereIn('status', ['DITERIMA', 'DITOLAK'])
                ->whereHas('selection', fn ($selection) => $selection->whereNotNull('published_at'))
                ->first();
        }

        return view('public.results', compact('result', 'searched'));
    }
}
