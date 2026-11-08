<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Blacklist;
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

        if ($lpjWarning = $this->checkLpjObligation($request->user(), $periode)) {
            return redirect()
                ->route('mahasiswa.dashboard')
                ->with('error_lpj', $lpjWarning);
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

        if ($kategoriBeasiswas->isEmpty()) {
            $kategoriBeasiswas = KategoriBeasiswa::query()
                ->where('aktif', true)
                ->orderBy('urutan')
                ->get();
        }

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
                    fn ($query) => $query->where('aktif', true)
                ),
            ],

            'persetujuan' => ['accepted'],
        ], [
            'jalur_beasiswa_id.required' => 'Pilih kategori beasiswa terlebih dahulu.',

            'jalur_beasiswa_id.exists' => 'Kategori beasiswa tidak tersedia.',

            'kategori_beasiswa_id.required' => 'Pilih jenis beasiswa.',

            'kategori_beasiswa_id.exists' => 'Jenis beasiswa tidak tersedia pada periode aktif.',

            'persetujuan.accepted' => 'Pernyataan persetujuan harus dicentang.',
        ]);

        $userId = $request->user()->getKey();
        if ($lpjWarning = $this->checkLpjObligation($request->user(), $periode)) {
            return redirect()
                ->route('mahasiswa.dashboard')
                ->with('error_lpj', $lpjWarning);
        }

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
                        'jalur_beasiswa_id' => $validated['jalur_beasiswa_id'],

                        // Jenis: Tidak Mampu / Prestasi / Disabilitas
                        'kategori_beasiswa_id' => $validated['kategori_beasiswa_id'],

                        'status' => 'draft',
                    ]);

                    // Salin data profil lama jika mahasiswa pernah mendaftar pada periode sebelumnya
                    $previousPendaftaran = Pendaftaran::query()
                        ->where('user_id', $userId)
                        ->where('id', '!=', $pendaftaran->id)
                        ->latest('id')
                        ->first();

                    if ($previousPendaftaran) {
                        if (! $pendaftaran->dataPribadi()->exists() && $previousPendaftaran->dataPribadi) {
                            $data = $previousPendaftaran->dataPribadi->replicate()->toArray();
                            unset($data['id'], $data['pendaftaran_id'], $data['created_at'], $data['updated_at']);
                            $pendaftaran->dataPribadi()->create($data);
                        }
                        if (! $pendaftaran->orangTua()->exists() && $previousPendaftaran->orangTua) {
                            $data = $previousPendaftaran->orangTua->replicate()->toArray();
                            unset($data['id'], $data['pendaftaran_id'], $data['created_at'], $data['updated_at']);
                            $pendaftaran->orangTua()->create($data);
                        }
                        if (! $pendaftaran->pendidikan()->exists() && $previousPendaftaran->pendidikan) {
                            $data = $previousPendaftaran->pendidikan->replicate()->toArray();
                            unset($data['id'], $data['pendaftaran_id'], $data['created_at'], $data['updated_at']);
                            $pendaftaran->pendidikan()->create($data);
                        }
                    }
                }

                if (! $pendaftaran->nomor_pendaftaran) {
                    $pendaftaran->forceFill([
                        'nomor_pendaftaran' => Pendaftaran::generateNomorPendaftaran($periode->tahun),
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
                .$pendaftaran->nomor_pendaftaran
                .'. Lengkapi data pribadi untuk memulai.'
            );
    }

    private function periodeAktif(): ?Periode
    {
        return Periode::query()
            ->aktif()
            ->orderByDesc('tanggal_mulai')
            ->first();
    }

    private function checkLpjObligation($user, $currentPeriode): ?string
    {
        $userNik = $user->profile?->nik
            ?? Pendaftaran::where('user_id', $user->id)->with('dataPribadi')->get()->pluck('dataPribadi.nik')->filter()->first();

        if ($userNik) {
            $activeBlacklist = Blacklist::query()->where('nik', $userNik)->where('aktif', true)->first();
            if ($activeBlacklist) {
                return 'Mohon maaf, Anda belum dapat melakukan pendaftaran beasiswa pada periode baru. Silakan lengkapi dan unggah berkas Laporan Pertanggungjawaban (LPJ) / bukti pembayaran UKT Anda pada periode sebelumnya melalui menu LPJ Beasiswa terlebih dahulu untuk diverifikasi oleh Admin.';
            }
        }

        // Cek otomatis jika pernah diterima pada periode sebelumnya dan LPJ belum disetujui
        $previousApproved = Pendaftaran::query()
            ->where('user_id', $user->id)
            ->where('periode_id', '!=', $currentPeriode->id)
            ->where(function ($q) {
                $q->where('status', 'approved')
                    ->orWhereHas('application', fn ($aq) => $aq->where('status', ApplicationStatus::DITERIMA));
            })
            ->with(['periode', 'laporanPertanggungjawaban', 'dataPribadi'])
            ->latest('id')
            ->first();

        if ($previousApproved) {
            $lpj = $previousApproved->laporanPertanggungjawaban;
            $isLpjApproved = $lpj && $lpj->status === 'disetujui';

            if (! $isLpjApproved) {
                $nik = $previousApproved->dataPribadi?->nik ?? $userNik;
                if ($nik) {
                    Blacklist::query()->firstOrCreate(
                        [
                            'nik' => $nik,
                            'tahun_berlaku' => $previousApproved->periode?->tahun ?? (int) now()->year,
                        ],
                        [
                            'pendaftaran_id' => $previousApproved->id,
                            'alasan' => 'Menunggu penyelesaian berkas LPJ periode sebelumnya.',
                            'aktif' => true,
                            'created_by' => null,
                        ]
                    );
                }

                return 'Mohon maaf, Anda belum dapat melakukan pendaftaran beasiswa pada periode baru. Silakan lengkapi dan unggah berkas Laporan Pertanggungjawaban (LPJ) / bukti pembayaran UKT Anda pada periode sebelumnya melalui menu LPJ Beasiswa terlebih dahulu untuk diverifikasi oleh Admin.';
            }
        }

        return null;
    }
}
