<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use App\Models\LaporanPertanggungjawaban;
use App\Models\Pendaftaran;
use App\Models\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LpjController extends Controller
{
    public function index(Request $request): View
    {
        $periodes = Periode::query()
            ->orderByDesc('tahun')
            ->orderByDesc('tanggal_mulai')
            ->get();

        // Default menampilkan Semua Periode agar penerima periode lama yang belum selesai LPJ tetap muncul
        $selectedPeriodeId = $request->has('periode_id')
            ? ($request->filled('periode_id') ? $request->integer('periode_id') : null)
            : null;
        $selectedPeriode = $selectedPeriodeId ? $periodes->firstWhere('id', $selectedPeriodeId) : null;

        // Auto-sinkronisasi penerima beasiswa yang lewat batas waktu LPJ namun belum disetujui
        $overdueRecipients = Pendaftaran::query()
            ->where(function ($q) {
                $q->where('status', 'approved')
                    ->orWhereHas('application', fn ($aq) => $aq->where('status', ApplicationStatus::DITERIMA));
            })
            ->whereHas('periode', function ($pq) {
                $pq->whereNotNull('tanggal_selesai_lpj')
                    ->where('tanggal_selesai_lpj', '<', now()->toDateString());
            })
            ->where(function ($q) {
                $q->doesntHave('laporanPertanggungjawaban')
                    ->orWhereHas('laporanPertanggungjawaban', fn ($lq) => $lq->where('status', '!=', 'disetujui'));
            })
            ->with(['dataPribadi', 'periode'])
            ->get();

        foreach ($overdueRecipients as $overdue) {
            $nik = $overdue->dataPribadi?->nik;
            if ($nik) {
                Blacklist::query()->firstOrCreate(
                    [
                        'nik' => $nik,
                        'tahun_berlaku' => $overdue->periode?->tahun ?? (int) now()->year,
                    ],
                    [
                        'pendaftaran_id' => $overdue->id,
                        'alasan' => 'Menunggu penyelesaian berkas LPJ periode sebelumnya.',
                        'aktif' => true,
                        'created_by' => null,
                    ]
                );
            }
        }

        $applicationTypes = ApplicationType::cases();
        $selectedType = $request->string('application_type')->trim()->toString();
        $selectedTypeEnum = ApplicationType::tryFrom($selectedType);
        if (! $selectedTypeEnum) {
            $selectedType = null;
        }

        $baseQuery = Pendaftaran::query()
            ->when($selectedPeriodeId, fn ($q) => $q->where('periode_id', $selectedPeriodeId))
            ->where(function ($q) {
                $q->where('status', 'approved')
                    ->orWhereHas('application', fn ($aq) => $aq->where('status', ApplicationStatus::DITERIMA));
            });

        // Total semua penerima tanpa filter jenis beasiswa
        $totalPenerimaSemuaJalur = (clone $baseQuery)->count();

        // Hitung total penerima & sudah LPJ per ApplicationType
        $typeCounts = [];
        $typeSudahLpjCounts = [];

        foreach ($applicationTypes as $type) {
            $typeQuery = (clone $baseQuery)->where(function ($q) use ($type) {
                $q->whereHas('application', fn ($aq) => $aq->where('application_type', $type->value))
                    ->orWhereHas('kategoriBeasiswa', fn ($kq) => $kq->where('kode', 'like', "%{$type->value}%"));
            });

            $typeCounts[$type->value] = (clone $typeQuery)->count();
            $typeSudahLpjCounts[$type->value] = (clone $typeQuery)->whereHas('laporanPertanggungjawaban', function ($lq) {
                $lq->whereIn('status', ['menunggu_verifikasi', 'disetujui']);
            })->count();
        }

        // Query dengan filter jenis beasiswa jika dipilih
        $filteredBaseQuery = (clone $baseQuery);
        if ($selectedTypeEnum) {
            $filteredBaseQuery->where(function ($q) use ($selectedTypeEnum) {
                $q->whereHas('application', fn ($aq) => $aq->where('application_type', $selectedTypeEnum->value))
                    ->orWhereHas('kategoriBeasiswa', fn ($kq) => $kq->where('kode', 'like', "%{$selectedTypeEnum->value}%"));
            });
        }

        // Summary counts for cards
        $totalPenerima = (clone $filteredBaseQuery)->count();
        $totalSudahLpj = (clone $filteredBaseQuery)->whereHas('laporanPertanggungjawaban', function ($lq) {
            $lq->whereIn('status', ['menunggu_verifikasi', 'disetujui']);
        })->count();
        $totalBelumLpj = $totalPenerima - $totalSudahLpj;
        $totalDiblokir = (clone $filteredBaseQuery)->whereHas('blacklists', function ($bq) {
            $bq->where('aktif', true);
        })->count();

        $query = (clone $filteredBaseQuery)->with([
            'user',
            'periode',
            'kategoriBeasiswa',
            'dataPribadi',
            'pendidikan',
            'application',
            'laporanPertanggungjawaban.verifier',
            'blacklists' => fn ($bq) => $bq->latest('id'),
        ]);

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_pendaftaran', 'like', "%{$search}%")
                    ->orWhereHas('dataPribadi', function ($dq) use ($search) {
                        $dq->where('nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($statusLpj = $request->string('status_lpj')->trim()->toString()) {
            if ($statusLpj === 'belum_upload') {
                $query->where(function ($q) {
                    $q->doesntHave('laporanPertanggungjawaban')
                        ->orWhereHas('laporanPertanggungjawaban', fn ($lq) => $lq->where('status', 'belum_upload'));
                });
            } elseif (in_array($statusLpj, ['menunggu_verifikasi', 'disetujui', 'ditolak'], true)) {
                $query->whereHas('laporanPertanggungjawaban', fn ($lq) => $lq->where('status', $statusLpj));
            }
        }

        if ($statusBlacklist = $request->string('status_blacklist')->trim()->toString()) {
            if ($statusBlacklist === 'diblokir') {
                $query->whereHas('blacklists', fn ($bq) => $bq->where('aktif', true));
            } elseif ($statusBlacklist === 'tidak_diblokir') {
                $query->whereDoesntHave('blacklists', fn ($bq) => $bq->where('aktif', true));
            }
        }

        $pendaftarans = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('operator.lpj.index', [
            'periodes' => $periodes,
            'selectedPeriode' => $selectedPeriode,
            'selectedPeriodeId' => $selectedPeriodeId,
            'applicationTypes' => $applicationTypes,
            'selectedType' => $selectedType,
            'selectedTypeEnum' => $selectedTypeEnum,
            'typeCounts' => $typeCounts,
            'typeSudahLpjCounts' => $typeSudahLpjCounts,
            'totalPenerimaSemuaJalur' => $totalPenerimaSemuaJalur,
            'pendaftarans' => $pendaftarans,
            'totalPenerima' => $totalPenerima,
            'totalSudahLpj' => $totalSudahLpj,
            'totalBelumLpj' => $totalBelumLpj,
            'totalDiblokir' => $totalDiblokir,
        ]);
    }

    public function toggleBlacklist(Request $request, Pendaftaran $pendaftaran): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['block', 'unblock'])],
            'alasan' => ['nullable', 'string', 'max:500'],
        ]);

        $nik = $pendaftaran->dataPribadi?->nik;

        if (! $nik) {
            return back()->with('error', 'Data NIK mahasiswa tidak ditemukan.');
        }

        $tahunBerlaku = $pendaftaran->periode?->tahun ?? (int) now()->year;
        $lpj = $pendaftaran->laporanPertanggungjawaban;

        if ($validated['action'] === 'block') {
            Blacklist::query()->updateOrCreate(
                [
                    'nik' => $nik,
                    'tahun_berlaku' => $tahunBerlaku,
                ],
                [
                    'pendaftaran_id' => $pendaftaran->id,
                    'alasan' => $validated['alasan'] ?: 'Menunggu penyelesaian berkas LPJ periode sebelumnya.',
                    'aktif' => true,
                    'created_by' => $request->user()->id,
                ]
            );

            if ($lpj && $lpj->file_path) {
                $lpj->update([
                    'status' => 'ditolak',
                    'catatan' => $validated['alasan'] ?? null,
                    'verified_at' => now(),
                    'verified_by' => $request->user()->id,
                ]);
            }

            return back()->with('success', "Keputusan disimpan: LPJ ditolak / kewajiban LPJ untuk NIK {$nik} ({$pendaftaran->dataPribadi?->nama_lengkap}) belum disetujui.");
        }

        // Action unblock (Diterima / Selesai)
        Blacklist::query()
            ->where('nik', $nik)
            ->where('aktif', true)
            ->update([
                'aktif' => false,
            ]);

        if ($lpj && $lpj->file_path) {
            $lpj->update([
                'status' => 'disetujui',
                'catatan' => $validated['alasan'] ?? null,
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
            ]);
        }

        return back()->with('success', "Keputusan disimpan: LPJ disetujui / kewajiban LPJ untuk NIK {$nik} ({$pendaftaran->dataPribadi?->nama_lengkap}) telah diselesaikan.");
    }

    public function verify(Request $request, Pendaftaran $pendaftaran): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan' => ['nullable', 'string', 'max:500'],
            'auto_unblock' => ['nullable', 'boolean'],
        ]);

        $lpj = $pendaftaran->laporanPertanggungjawaban;

        if (! $lpj || ! $lpj->file_path) {
            return back()->with('error', 'Mahasiswa belum mengunggah berkas LPJ.');
        }

        $lpj->update([
            'status' => $validated['status'],
            'catatan' => $validated['catatan'] ?? null,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        // Jika disetujui dan admin memilih untuk otomatis buka blokir NIK
        if ($validated['status'] === 'disetujui' && $request->boolean('auto_unblock')) {
            $nik = $pendaftaran->dataPribadi?->nik;
            if ($nik) {
                Blacklist::query()
                    ->where('nik', $nik)
                    ->where('aktif', true)
                    ->update(['aktif' => false]);
            }
        }

        $label = $validated['status'] === 'disetujui' ? 'disetujui' : 'diminta perbaikan';

        return back()->with('success', "Laporan Pertanggungjawaban berhasil diperiksa ({$label}).");
    }

    public function file(Pendaftaran $pendaftaran): StreamedResponse|RedirectResponse
    {
        $lpj = $pendaftaran->laporanPertanggungjawaban;

        if (! $lpj || ! $lpj->file_path || ! Storage::disk('local')->exists($lpj->file_path)) {
            return back()->with('error', 'Berkas LPJ tidak ditemukan di penyimpanan server.');
        }

        $mimeType = $lpj->mime_type ?: Storage::disk('local')->mimeType($lpj->file_path);
        $fileName = $lpj->original_name ?: basename($lpj->file_path);

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
