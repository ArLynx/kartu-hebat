<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\KategoriBeasiswa;
use App\Models\Pendaftaran;
use App\Models\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PendaftaranController extends Controller
{
    /**
     * Menampilkan formulir pembuatan draft pendaftaran.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $periode = $this->periodeAktif();

        if (! $periode) {
            return redirect()
                ->route('mahasiswa.dashboard')
                ->with('error', 'Belum ada periode pendaftaran beasiswa yang aktif.');
        }

        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $request->user()->getKey())
            ->where('periode_id', $periode->getKey())
            ->latest('id')
            ->first();

        if ($pendaftaran) {
            return redirect()
                ->route('mahasiswa.dashboard')
                ->with('warning', 'Anda sudah memiliki pendaftaran pada periode aktif.');
        }

        $kategoriBeasiswas = KategoriBeasiswa::query()
            ->where('periode_id', $periode->getKey())
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        return view('mahasiswa.pendaftaran.create', compact('periode', 'kategoriBeasiswas'));
    }

    /**
     * Menyimpan draft menggunakan ID pengguna yang sedang terautentikasi.
     * Tidak menggunakan user dummy dari proyek beasiswa.zip.
     */
    public function store(Request $request): RedirectResponse
    {
        $periode = $this->periodeAktif();

        if (! $periode) {
            return redirect()
                ->route('mahasiswa.dashboard')
                ->with('error', 'Periode pendaftaran beasiswa belum aktif.');
        }

        $validated = $request->validate([
            'kategori_beasiswa_id' => [
                'required',
                Rule::exists('kategori_beasiswas', 'id')->where(
                    fn ($query) => $query
                        ->where('periode_id', $periode->getKey())
                        ->where('aktif', true)
                ),
            ],
            'persetujuan' => ['accepted'],
        ], [
            'kategori_beasiswa_id.required' => 'Pilih salah satu kategori beasiswa.',
            'kategori_beasiswa_id.exists' => 'Kategori beasiswa tidak tersedia pada periode aktif.',
            'persetujuan.accepted' => 'Pernyataan persetujuan harus dicentang.',
        ]);

        $userId = $request->user()->getKey();

        $pendaftaran = DB::transaction(function () use ($userId, $periode, $validated): Pendaftaran {
            $pendaftaran = Pendaftaran::query()
                ->where('user_id', $userId)
                ->where('periode_id', $periode->getKey())
                ->lockForUpdate()
                ->first();

            if (! $pendaftaran) {
                $pendaftaran = Pendaftaran::query()->create([
                    'user_id' => $userId,
                    'periode_id' => $periode->getKey(),
                    'kategori_beasiswa_id' => $validated['kategori_beasiswa_id'],
                    'status' => 'draft',
                ]);
            }

            if (! $pendaftaran->nomor_pendaftaran) {
                $pendaftaran->forceFill([
                    'nomor_pendaftaran' => sprintf(
                        'KHM-%s-%06d',
                        $periode->tahun,
                        $pendaftaran->getKey(),
                    ),
                ])->save();
            }

            return $pendaftaran->fresh(['periode', 'kategoriBeasiswa']);
        });

        return redirect()
            ->route('mahasiswa.data-pribadi.index')
            ->with(
                'success',
                'Draft pendaftaran berhasil dibuat dengan nomor '.$pendaftaran->nomor_pendaftaran.'. Lengkapi data pribadi untuk memulai.'
            );
    }

    private function periodeAktif(): ?Periode
    {
        return Periode::query()
            ->where('status', 'aktif')
            ->whereDate('tanggal_mulai', '<=', today())
            ->whereDate('tanggal_selesai', '>=', today())
            ->orderByDesc('tanggal_mulai')
            ->first();
    }
}
