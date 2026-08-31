<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\JalurBeasiswa;
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
                ->with(
                    'error',
                    'Belum ada periode pendaftaran beasiswa yang aktif.'
                );
        }

        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $request->user()->getKey())
            ->where('periode_id', $periode->getKey())
            ->latest('id')
            ->first();

        if ($pendaftaran) {
            return redirect()
                ->route('mahasiswa.dashboard')
                ->with(
                    'warning',
                    'Anda sudah memiliki pendaftaran pada periode aktif.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Kategori Beasiswa
        |--------------------------------------------------------------------------
        | Reguler / Non Reguler
        |--------------------------------------------------------------------------
        */
        $jalurBeasiswas = JalurBeasiswa::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Jenis Beasiswa
        |--------------------------------------------------------------------------
        | Tidak Mampu
        | Prestasi Akademik
        | Prestasi Non Akademik
        | Penyandang Disabilitas
        |--------------------------------------------------------------------------
        */
        $kategoriBeasiswas = KategoriBeasiswa::query()
            ->where('periode_id', $periode->getKey())
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        return view('mahasiswa.pendaftaran.create', [
            'periode' => $periode,
            'jalurBeasiswas' => $jalurBeasiswas,
            'kategoriBeasiswas' => $kategoriBeasiswas,
        ]);
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
                ->with(
                    'error',
                    'Periode pendaftaran beasiswa belum aktif.'
                );
        }

        $validated = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | KATEGORI BEASISWA
            | Reguler / Non Reguler
            |--------------------------------------------------------------------------
            */
            'jalur_beasiswa_id' => [
                'required',
                Rule::exists('jalur_beasiswas', 'id')
                    ->where(
                        fn ($query) => $query->where('aktif', true)
                    ),
            ],

            /*
            |--------------------------------------------------------------------------
            | JENIS BEASISWA
            | Tidak Mampu / Prestasi Akademik /
            | Prestasi Non Akademik / Disabilitas
            |--------------------------------------------------------------------------
            */
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
            'jalur_beasiswa_id.required' =>
                'Pilih kategori beasiswa terlebih dahulu.',

            'jalur_beasiswa_id.exists' =>
                'Kategori beasiswa tidak tersedia.',

            'kategori_beasiswa_id.required' =>
                'Pilih jenis beasiswa.',

            'kategori_beasiswa_id.exists' =>
                'Jenis beasiswa tidak tersedia pada periode aktif.',

            'persetujuan.accepted' =>
                'Pernyataan persetujuan harus dicentang.',
        ]);

        $userId = $request->user()->getKey();

        $pendaftaran = DB::transaction(
            function () use (
                $userId,
                $periode,
                $validated
            ): Pendaftaran {

                $pendaftaran = Pendaftaran::query()
                    ->where('user_id', $userId)
                    ->where('periode_id', $periode->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $pendaftaran) {

                    $pendaftaran = Pendaftaran::query()->create([
                        'user_id' => $userId,
                        'periode_id' => $periode->getKey(),

                        // Kategori: Reguler / Non Reguler
                        'jalur_beasiswa_id' =>
                            $validated['jalur_beasiswa_id'],

                        // Jenis: Tidak Mampu / Prestasi / Disabilitas
                        'kategori_beasiswa_id' =>
                            $validated['kategori_beasiswa_id'],

                        'status' => 'draft',
                    ]);
                }

                if (! $pendaftaran->nomor_pendaftaran) {

                    $pendaftaran->forceFill([
                        'nomor_pendaftaran' => sprintf(
                            'KHM-%s-%06d',
                            $periode->tahun,
                            $pendaftaran->getKey()
                        ),
                    ])->save();
                }

                return $pendaftaran->fresh([
                    'periode',
                    'jalurBeasiswa',
                    'kategoriBeasiswa',
                ]);
            }
        );

        return redirect()
            ->route('mahasiswa.data-pribadi.index')
            ->with(
                'success',
                'Draft pendaftaran berhasil dibuat dengan nomor '
                . $pendaftaran->nomor_pendaftaran
                . '. Lengkapi data pribadi untuk memulai.'
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
