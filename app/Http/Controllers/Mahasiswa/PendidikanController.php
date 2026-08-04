<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Pendidikan;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PendidikanController extends Controller
{
    public function __construct(private readonly MahasiswaPendaftaranService $flow)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Buat pendaftaran beasiswa terlebih dahulu.');
        }

        $pendaftaran->load(['pendidikan', 'periode', 'kategoriBeasiswa']);

        return view('mahasiswa.pendidikan.index', [
            'pendaftaran' => $pendaftaran,
            'stepStatuses' => $this->flow->completion($pendaftaran),
            'canEdit' => $this->flow->isEditable($pendaftaran),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);
        abort_unless($this->flow->isEditable($pendaftaran), 403, 'Pendaftaran tidak dapat diubah.');

        $pendidikanId = $pendaftaran->pendidikan?->id;
        $currentYear = (int) now()->year;

        $validated = $request->validate([
            'nim' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pendidikans', 'nim')->ignore($pendidikanId),
            ],
            'universitas' => ['required', 'string', 'max:150'],
            'fakultas' => ['required', 'string', 'max:150'],
            'program_studi' => ['required', 'string', 'max:150'],
            'jenjang' => ['required', Rule::in(['D3', 'D4', 'S1', 'S2', 'S3'])],
            'semester' => ['required', 'integer', 'between:1,14'],
            'ipk' => ['required', 'numeric', 'between:0,4'],
            'tahun_masuk' => ['required', 'integer', 'digits:4', 'between:1990,'.$currentYear],
            'tahun_lulus' => ['nullable', 'integer', 'digits:4', 'gte:tahun_masuk', 'max:'.($currentYear + 10)],
            'status_mahasiswa' => [
                'required',
                Rule::in(['aktif', 'cuti', 'lulus', 'drop_out', 'nonaktif']),
            ],
        ], [
            'ipk.between' => 'IPK harus berada pada rentang 0,00 sampai 4,00.',
            'tahun_lulus.gte' => 'Tahun lulus tidak boleh lebih awal dari tahun masuk.',
        ]);

        DB::transaction(function () use ($pendaftaran, $validated): void {
            Pendidikan::query()->updateOrCreate(
                ['pendaftaran_id' => $pendaftaran->id],
                $validated,
            );

            $pendaftaran->forceFill(['review_dikonfirmasi_at' => null])->save();
        });

        return redirect()
            ->route('mahasiswa.prestasi.index')
            ->with('success', 'Data pendidikan berhasil disimpan.');
    }
}
