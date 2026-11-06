<?php

namespace App\Http\Controllers\Public;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Periode;
use App\Models\Selection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $result = null;
        $searched = false;

        if ($request->filled('nomor')) {
            $validated = $request->validate([
                'nomor' => ['required', 'string', 'max:100'],
            ]);
            $searched = true;
            $nomor = trim($validated['nomor']);

            $result = Application::query()
                ->with(['mahasiswa.profile', 'selection', 'pendaftaran.periode', 'pendaftaran.jalurBeasiswa'])
                ->where(function (Builder $query) use ($nomor): void {
                    $query
                        ->where('nomor_pengajuan', $nomor)
                        ->orWhereHas('mahasiswa.profile', fn (Builder $profile) => $profile->where('nik', $nomor));
                })
                ->whereIn('status', [ApplicationStatus::DITERIMA->value, ApplicationStatus::DITOLAK->value])
                ->whereHas('selection', fn (Builder $selection) => $selection->whereNotNull('published_at'))
                ->orderByDesc(Selection::query()
                    ->select('published_at')
                    ->whereColumn('selections.application_id', 'applications.id'))
                ->orderByDesc('id')
                ->first();
        }

        $periode = $result
            ? $result->resolvedPeriode()
            : Periode::latestAktif();

        return view('public.results', compact('result', 'searched', 'periode'));
    }
}
