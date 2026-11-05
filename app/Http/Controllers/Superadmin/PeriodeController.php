<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodeController extends Controller
{
    public function index(): View
    {
        $periodes = Periode::query()
            ->withCount(['kategoriBeasiswas', 'pendaftarans'])
            ->orderByDesc('tahun')
            ->orderByDesc('tanggal_mulai')
            ->paginate(15);

        return view('superadmin.periodes.index', compact('periodes'));
    }

    public function create(): View
    {
        return view('superadmin.periodes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePeriode($request);

        $periode = Periode::query()->create($validated);

        return redirect()
            ->route('superadmin.periodes.index')
            ->with('success', 'Periode beasiswa berhasil ditambahkan.');
    }

    public function edit(Periode $periode): View
    {
        return view('superadmin.periodes.edit', compact('periode'));
    }

    public function update(Request $request, Periode $periode): RedirectResponse
    {
        $validated = $this->validatePeriode($request);

        $periode->update($validated);

        return redirect()
            ->route('superadmin.periodes.edit', $periode)
            ->with('success', 'Periode beasiswa berhasil diperbarui.');
    }

    public function destroy(Periode $periode): RedirectResponse
    {
        if ($periode->kategoriBeasiswas()->exists() || $periode->pendaftarans()->exists()) {
            return back()->with(
                'error',
                'Periode tidak dapat dihapus karena sudah memiliki kategori beasiswa atau pendaftaran terkait. Ubah status menjadi ditutup sebagai gantinya.',
            );
        }

        $periode->delete();

        return redirect()
            ->route('superadmin.periodes.index')
            ->with('success', 'Periode beasiswa berhasil dihapus.');
    }

    private function validatePeriode(Request $request): array
    {
        return $request->validate([
            'tahun' => ['required', 'integer', 'digits:4', 'min:2000', 'max:2099'],
            'nama' => ['nullable', 'string', 'max:255'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tanggal_mulai_lpj' => ['nullable', 'date'],
            'tanggal_selesai_lpj' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_lpj'],
            'tanggal_mulai_sanggah' => ['nullable', 'date', 'required_with:tanggal_selesai_sanggah'],
            'tanggal_selesai_sanggah' => ['nullable', 'date', 'required_with:tanggal_mulai_sanggah', 'after_or_equal:tanggal_mulai_sanggah'],
            'status' => ['required', 'string', Rule::in(['draft', 'aktif', 'ditutup'])],
        ], [
            'tahun.required' => 'Tahun periode wajib diisi.',
            'tahun.digits' => 'Tahun harus berupa 4 digit angka (contoh: 2026).',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'tanggal_selesai_lpj.after_or_equal' => 'Tanggal batas akhir LPJ harus sama atau setelah tanggal mulai LPJ.',
            'tanggal_mulai_sanggah.required_with' => 'Tanggal mulai masa sanggah wajib diisi bila tanggal selesai masa sanggah diisi.',
            'tanggal_selesai_sanggah.required_with' => 'Tanggal selesai masa sanggah wajib diisi bila tanggal mulai masa sanggah diisi.',
            'tanggal_selesai_sanggah.after_or_equal' => 'Tanggal selesai masa sanggah harus sama atau setelah tanggal mulai masa sanggah.',
            'status.required' => 'Status periode wajib dipilih.',
            'status.in' => 'Status periode yang dipilih tidak valid.',
        ]);
    }
}
