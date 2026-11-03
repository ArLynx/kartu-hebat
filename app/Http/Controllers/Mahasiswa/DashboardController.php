<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly MahasiswaPendaftaranService $flow) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $user->getKey())
            ->with([
                'periode',
                'kategoriBeasiswa',
                'jalurBeasiswa',
                'dataPribadi',
                'pendidikan',
                'prestasis',
                'orangTua',
                'dokumens',
                'laporanPertanggungjawaban',
                'application.verificationLogs.actor',
                'application.documents.type',
                'application.documents.verifications',
            ])
            ->latest('id')
            ->first();

        $activePeriode = Periode::query()->aktif()->first();

        // Cek status kewajiban LPJ / blokir NIK mahasiswa
        $userNik = $user->profile?->nik ?? $pendaftaran?->dataPribadi?->nik;
        $activeBlacklist = null;
        if ($userNik) {
            $activeBlacklist = Blacklist::query()
                ->where('nik', $userNik)
                ->where('aktif', true)
                ->first();
        }

        if (! $activeBlacklist && $activePeriode) {
            $previousApproved = Pendaftaran::query()
                ->where('user_id', $user->id)
                ->where('periode_id', '!=', $activePeriode->id)
                ->where(function ($q) {
                    $q->where('status', 'approved')
                        ->orWhereHas('application', fn ($aq) => $aq->where('status', ApplicationStatus::DITERIMA));
                })
                ->with(['periode', 'laporanPertanggungjawaban', 'dataPribadi'])
                ->latest('id')
                ->first();

            if ($previousApproved) {
                $lpj = $previousApproved->laporanPertanggungjawaban;
                if (! $lpj || $lpj->status !== 'disetujui') {
                    $nik = $previousApproved->dataPribadi?->nik ?? $userNik;
                    if ($nik) {
                        $activeBlacklist = Blacklist::query()->firstOrCreate(
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
                }
            }
        }

        // Cek apakah mahasiswa bisa daftar periode baru
        $canRegisterNewPeriod = $activePeriode !== null
            && ($pendaftaran === null || $pendaftaran->periode_id !== $activePeriode->id)
            && ! $activeBlacklist;

        $riwayatPendaftarans = Pendaftaran::query()
            ->where('user_id', $user->id)
            ->with([
                'periode',
                'kategoriBeasiswa',
                'jalurBeasiswa',
                'application',
                'laporanPertanggungjawaban',
                'dokumens.jenisDokumen',
                'formulirPendaftaran',
            ])
            ->orderByDesc('id')
            ->get();

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

        return view('mahasiswa.dashboard', compact(
            'pendaftaran',
            'stepStatuses',
            'currentStep',
            'activeBlacklist',
            'activePeriode',
            'canRegisterNewPeriod',
            'riwayatPendaftarans'
        ));
    }
}
