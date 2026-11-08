<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use App\Models\LaporanPertanggungjawaban;
use App\Models\Pendaftaran;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LpjController extends Controller
{
    public function __construct(
        private readonly MahasiswaPendaftaranService $flow
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Cari pendaftaran yang berstatus DITERIMA (Penerima Beasiswa)
        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', 'approved')
                    ->orWhereHas('application', fn ($aq) => $aq->where('status', ApplicationStatus::DITERIMA));
            })
            ->with([
                'periode',
                'kategoriBeasiswa',
                'dataPribadi',
                'laporanPertanggungjawaban',
                'blacklists' => fn ($bq) => $bq->latest('id'),
            ])
            ->latest('id')
            ->first();

        // 2. Jika belum ada yang berstatus Diterima, cari pendaftaran terakhir untuk konteks status
        $latestPendaftaran = $pendaftaran ?: Pendaftaran::query()
            ->where('user_id', $user->id)
            ->with(['periode', 'kategoriBeasiswa', 'application'])
            ->latest('id')
            ->first();

        $nik = $pendaftaran?->dataPribadi?->nik;
        $isBlacklisted = false;
        $activeBlacklist = null;

        if ($nik) {
            $activeBlacklist = Blacklist::query()
                ->where('nik', $nik)
                ->where('aktif', true)
                ->first();
            $isBlacklisted = (bool) $activeBlacklist;
        }

        $periode = $pendaftaran?->periode ?? $latestPendaftaran?->periode;
        $isLpjOpen = $periode?->isLpjOpen() ?? false;
        $isPastDeadline = $periode?->isLpjPastDeadline() ?? false;
        $lpj = $pendaftaran?->laporanPertanggungjawaban;

        // Mahasiswa dapat mengunggah LPJ jika diterima, belum disetujui, dan:
        // - Jadwal LPJ sedang dibuka, ATAU
        // - Sudah lewat batas waktu tapi belum kumpul / sedang diblokir
        $canUpload = $pendaftaran !== null
            && (! $lpj || $lpj->status !== 'disetujui')
            && ($isLpjOpen || $isPastDeadline || $isBlacklisted);

        $stepStatuses = $pendaftaran
            ? $this->flow->completion($pendaftaran)
            : array_fill(1, 7, false);

        $riwayatPendaftarans = Pendaftaran::query()
            ->where('user_id', $user->id)
            ->with(['periode', 'kategoriBeasiswa', 'jalurBeasiswa', 'application', 'laporanPertanggungjawaban'])
            ->orderByDesc('id')
            ->get();

        return view('mahasiswa.lpj.index', [
            'pendaftaran' => $pendaftaran,
            'latestPendaftaran' => $latestPendaftaran,
            'lpj' => $pendaftaran?->laporanPertanggungjawaban,
            'periode' => $periode,
            'isLpjOpen' => $isLpjOpen,
            'isPastDeadline' => $isPastDeadline,
            'isBlacklisted' => $isBlacklisted,
            'activeBlacklist' => $activeBlacklist,
            'canUpload' => $canUpload,
            'stepStatuses' => $stepStatuses,
            'riwayatPendaftarans' => $riwayatPendaftarans,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('status', 'approved')
                    ->orWhereHas('application', fn ($aq) => $aq->where('status', ApplicationStatus::DITERIMA));
            })
            ->with(['laporanPertanggungjawaban', 'periode', 'dataPribadi'])
            ->latest('id')
            ->first();

        if (! $pendaftaran) {
            return back()->with('error', 'Hanya mahasiswa yang telah dinyatakan lulus/diterima beasiswa yang dapat mengunggah LPJ.');
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048', // 2 MB (2.048 KB)
            ],
        ], [
            'file.required' => 'Pilih berkas bukti pembayaran UKT/LPJ terlebih dahulu.',
            'file.mimes' => 'Format berkas harus berupa PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran berkas melebihi batas maksimal 2 MB (2.048 KB).',
        ]);

        $uploadedFile = $request->file('file');
        $lpj = $pendaftaran->laporanPertanggungjawaban;

        // Hapus file lama jika ada
        if ($lpj && $lpj->file_path && Storage::disk('local')->exists($lpj->file_path)) {
            Storage::disk('local')->delete($lpj->file_path);
        }

        $storedPath = $uploadedFile->store('lpj', 'local');

        $pendaftaran->laporanPertanggungjawaban()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'file_path' => $storedPath,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
                'status' => 'menunggu_verifikasi',
                'uploaded_at' => now(),
            ]
        );

        $isPastDeadline = $pendaftaran->periode?->isLpjPastDeadline() ?? false;
        $userNik = $user->profile?->nik ?? $pendaftaran->dataPribadi?->nik;
        $isBlacklisted = false;
        if ($userNik) {
            $isBlacklisted = Blacklist::query()
                ->where('nik', $userNik)
                ->where('aktif', true)
                ->exists();
        }

        $successMessage = ($isPastDeadline || $isBlacklisted)
            ? 'Laporan Pertanggungjawaban (LPJ) susulan berhasil diunggah. Mohon segera melapor diri ke Bagian Kesejahteraan Rakyat (Kesra) Sekretariat Daerah Kabupaten Murung Raya untuk proses verifikasi dokumen dan pembukaan blokir akun Anda.'
            : 'Laporan Pertanggungjawaban (LPJ) berhasil diunggah dan sedang menunggu verifikasi Admin Kabupaten.';

        return back()->with('success', $successMessage);
    }

    public function file(Request $request): StreamedResponse|RedirectResponse
    {
        $user = $request->user();
        $pendaftaranId = $request->integer('pendaftaran_id');

        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $user->id)
            ->when($pendaftaranId, fn ($q) => $q->where('id', $pendaftaranId))
            ->with('laporanPertanggungjawaban')
            ->latest('id')
            ->first();

        $lpj = $pendaftaran?->laporanPertanggungjawaban;

        if (! $lpj || ! $lpj->file_path || ! Storage::disk('local')->exists($lpj->file_path)) {
            return back()->with('error', 'Berkas LPJ tidak ditemukan.');
        }

        $mimeType = $lpj->mime_type ?: Storage::disk('local')->mimeType($lpj->file_path);
        $ext = pathinfo($lpj->original_name ?: $lpj->file_path, PATHINFO_EXTENSION) ?: 'pdf';
        $nomor = $pendaftaran->nomor_pendaftaran ?: 'KH';
        $fileName = 'Berkas-LPJ-'.$nomor.'.'.$ext;

        if ($request->boolean('download')) {
            return Storage::disk('local')->download($lpj->file_path, $fileName, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-cache',
            ]);
        }

        return Storage::disk('local')->response(
            $lpj->file_path,
            $fileName,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ]
        );
    }
}
