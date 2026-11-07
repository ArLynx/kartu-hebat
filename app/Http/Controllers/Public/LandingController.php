<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\KategoriBeasiswa;
use App\Models\Periode;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        $periode = $this->resolvePeriode();

        return view('public.landing', [
            'periode' => $periode,
            'categories' => $this->resolveCategories($periode),
            'announcements' => Announcement::query()
                ->published()
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }

    private function resolvePeriode(): ?Periode
    {
        $currentYear = (int) today()->year;

        $currentlyOpen = Periode::query()
            ->aktif()
            ->orderByDesc('tanggal_mulai')
            ->latest('id')
            ->first();

        if ($currentlyOpen) {
            return $currentlyOpen;
        }

        $currentYearPeriods = Periode::query()
            ->where('tahun', $currentYear)
            ->orderByDesc('tanggal_mulai')
            ->latest('id')
            ->get();

        if ($currentYearPeriods->isNotEmpty()) {
            return $currentYearPeriods->first(
                fn (Periode $periode): bool => $periode->status === 'aktif'
                    && $periode->tanggal_mulai !== null
                    && today()->lt($periode->tanggal_mulai),
            )
                ?? $currentYearPeriods->firstWhere('status', 'draft')
                ?? $currentYearPeriods->firstWhere('status', 'ditutup')
                ?? $currentYearPeriods->first();
        }

        return Periode::query()
            ->where('status', 'aktif')
            ->whereDate('tanggal_mulai', '>', today())
            ->orderBy('tanggal_mulai')
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, KategoriBeasiswa>
     */
    private function resolveCategories(?Periode $periode): Collection
    {
        $sourcePeriodeId = $periode?->id;

        if (
            ! $sourcePeriodeId
            || ! KategoriBeasiswa::query()->where('periode_id', $sourcePeriodeId)->where('aktif', true)->exists()
        ) {
            if ($sourcePeriodeId && KategoriBeasiswa::query()->where('periode_id', $sourcePeriodeId)->exists()) {
                return collect();
            }

            $sourcePeriodeId = Periode::query()
                ->whereHas('kategoriBeasiswas', fn ($query) => $query->where('aktif', true))
                ->orderByRaw("CASE status WHEN 'aktif' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")
                ->orderByDesc('tahun')
                ->orderByDesc('tanggal_mulai')
                ->latest('id')
                ->value('id');
        }

        if (! $sourcePeriodeId) {
            return collect();
        }

        return KategoriBeasiswa::query()
            ->where('periode_id', $sourcePeriodeId)
            ->where('aktif', true)
            ->with([
                'jenisDokumens' => fn ($query) => $query
                    ->where('jenis_dokumens.aktif', true)
                    ->orderBy('kategori_beasiswa_dokumens.urutan')
                    ->orderBy('jenis_dokumens.id'),
            ])
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }
}
