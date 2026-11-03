@extends('layouts.portal')

@section('title', 'Dashboard Pendaftaran Beasiswa')
@section('header', 'Pendaftaran Beasiswa')

@section('content')
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold">Halo, {{ auth()->user()->name }}!</h1>
            <p class="mt-2 text-sm text-slate-600">Pendaftaran terhubung langsung dengan verifikasi lintas dinas dan seleksi
                kabupaten.</p>
        </div>
        @if ($pendaftaran)
            <div class="text-left sm:text-right">
                <span
                    class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-brand-700">{{ $pendaftaran->nomor_pendaftaran }}</span>
                <p class="mt-2 text-sm font-medium text-slate-700">
                    {{ $pendaftaran->application?->status?->label() ?? ucfirst($pendaftaran->status) }}</p>
            </div>
        @endif
    </div>

    {{-- ALERT STATUS KEWAJIBAN LPJ / BLOKIR NIK --}}
    @if ($activeBlacklist || session('error_blacklist') || session('error_lpj'))
        <div class="mt-6 rounded-2xl border-2 border-rose-300 bg-rose-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-200 text-rose-900">
                        <x-icon name="exclamation-triangle" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded bg-rose-200 px-2.5 py-0.5 text-[11px] font-extrabold text-rose-900 uppercase tracking-wide">
                                🚫 Akun Terkunci
                            </span>
                            <h3 class="text-base font-bold text-rose-950">
                                Pendaftaran Periode Selanjutnya Ditangguhkan (Kewajiban Laporan Pertanggungjawaban (LPJ) Belum Selesai)
                            </h3>
                        </div>
                        <p class="mt-2 text-xs text-rose-900 leading-relaxed font-medium">
                            <strong>Perhatian:</strong> Anda <strong>TIDAK DAPAT melakukan pendaftaran beasiswa pada periode baru / tahun selanjutnya</strong> karena kewajiban Laporan Pertanggungjawaban (LPJ) periode sebelumnya belum selesai atau ditolak oleh Admin.
                        </p>
                        @if($activeBlacklist?->alasan || session('error_blacklist') || session('error_lpj'))
                            <div class="mt-2.5 rounded-lg bg-white/90 border border-rose-200 p-2.5 text-xs text-rose-950">
                                <span class="font-bold text-rose-900">Catatan Admin:</span>
                                <span class="italic text-slate-700">{{ session('error_lpj') ?? (session('error_blacklist') ?? $activeBlacklist->alasan) }}</span>
                            </div>
                        @endif
                        <p class="mt-2 text-xs text-rose-900">
                            Silakan perbaiki dan kumpulkan bukti pembayaran UKT yang sah di menu LPJ Beasiswa agar hak pendaftaran Anda dipulihkan oleh Admin Kabupaten.
                        </p>
                    </div>
                </div>
                <a href="{{ route('mahasiswa.lpj.index') }}" class="btn-primary shrink-0 text-xs bg-rose-600 hover:bg-rose-700 border-none whitespace-nowrap">
                    Ke Menu LPJ Beasiswa →
                </a>
            </div>
        </div>
    @endif

    {{-- BANNER JIKA ADA PERIODE BARU DIBUKA --}}
    @if ($activePeriode && $pendaftaran && $pendaftaran->periode_id !== $activePeriode->id)
        <div class="mt-6 rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-brand-800">
                        Pendaftaran Periode {{ $activePeriode->tahun }} Dibuka
                    </span>
                    <h3 class="mt-2 text-lg font-extrabold text-navy-950">
                        Daftar Beasiswa Periode {{ $activePeriode->tahun }} ({{ $activePeriode->nama ?: 'Beasiswa ' . $activePeriode->tahun }})
                    </h3>
                    <p class="mt-1 text-xs text-slate-600">
                        Jadwal: {{ $activePeriode->tanggal_mulai?->format('d M Y') }} s/d {{ $activePeriode->tanggal_selesai?->format('d M Y') }}. Data profil Anda dari periode sebelumnya akan otomatis disalin ke draf baru.
                    </p>
                </div>
                <div class="flex flex-col items-start sm:items-end">
                    @if ($activeBlacklist)
                        <button type="button" disabled class="btn-secondary !bg-slate-200 !text-slate-600 !border-slate-300 opacity-80 cursor-not-allowed text-xs font-bold" title="Selesaikan kewajiban LPJ terlebih dahulu">
                            🔒 Pendaftaran Terkunci (Selesaikan LPJ)
                        </button>
                        <p class="mt-1.5 text-[11px] text-rose-600 font-semibold max-w-xs text-left sm:text-right">
                            Selesaikan kewajiban LPJ periode sebelumnya untuk membuka pendaftaran periode ini.
                        </p>
                    @else
                        <a href="{{ route('mahasiswa.pendaftaran.create') }}" class="btn-primary text-xs">
                            Daftar Periode {{ $activePeriode->tahun }} →
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if (!$pendaftaran)
        <section class="card mt-7 p-7 sm:p-10">
            <div class="flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-100 text-brand-700"><x-icon
                            name="form" class="h-7 w-7" /></div>
                    <h2 class="mt-5 text-2xl font-extrabold">Belum Ada Pendaftaran Beasiswa</h2>
                    <p class="mt-3 leading-7 text-slate-600">Buat draft pendaftaran, pilih kategori beasiswa, lalu lengkapi
                        data sesuai tujuh tahapan pendaftaran.</p>
                </div>
                <a href="{{ route('mahasiswa.pendaftaran.create') }}" class="btn-primary justify-center"><x-icon
                        name="form" class="h-5 w-5" />Buat Pendaftaran</a>
            </div>
        </section>
    @else
        @php
            $steps = [
                1 => ['label' => 'Data Pribadi', 'route' => 'mahasiswa.data-pribadi.index'],
                2 => ['label' => 'Pendidikan', 'route' => 'mahasiswa.pendidikan.index'],
                3 => ['label' => 'Prestasi', 'route' => 'mahasiswa.prestasi.index'],
                4 => ['label' => 'Orang Tua', 'route' => 'mahasiswa.orang-tua.index'],
                5 => ['label' => 'Dokumen', 'route' => 'mahasiswa.dokumen.index'],
                6 => ['label' => 'Review', 'route' => 'mahasiswa.review.index'],
                7 => ['label' => 'Submit', 'route' => 'mahasiswa.submit.index'],
            ];

            $isNonAkademik = $pendaftaran?->kategoriBeasiswa?->application_type === \App\Enums\ApplicationType::NON_AKADEMIK;
            if (! $isNonAkademik) {
                unset($steps[3]);
            }
        @endphp

        @if ($pendaftaran->application)
            <section class="card mt-7 overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Status Verifikasi</p>
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-extrabold">{{ $pendaftaran->application->status->label() }}</h2>
                            <p class="mt-1 text-sm text-slate-600">Nomor pengajuan:
                                {{ $pendaftaran->application->nomor_pengajuan }}</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-blue-50 px-4 py-2 text-sm font-bold text-brand-700">
                            {{ $pendaftaran->application->application_type?->label() }}
                        </span>
                    </div>
                </div>
                <div class="px-6 py-5 sm:px-8">
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-600"
                            style="width: {{ $pendaftaran->application->status->progress() }}%"></div>
                    </div>
                    @if ($pendaftaran->application->catatan)
                        <div
                            class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                            <strong>Catatan petugas:</strong> {{ $pendaftaran->application->catatan }}
                        </div>
                    @endif
                    @if (in_array($pendaftaran->status, ['revision'], true))
                        <p class="mt-4 text-sm text-slate-600">Perbaiki data yang diminta, konfirmasi ulang tahap review,
                            kemudian kirim kembali pendaftaran.</p>
                    @endif

                    @if ($pendaftaran->application->verificationLogs->isNotEmpty())
                        <div class="mt-5 border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-bold text-slate-900">Riwayat terbaru</h3>
                            <div class="mt-3 space-y-3">
                                @foreach ($pendaftaran->application->verificationLogs->take(3) as $log)
                                    <div class="flex items-start justify-between gap-4 text-sm">
                                        <div>
                                            <p class="font-semibold text-slate-800">{{ $log->to_status->label() }}</p>
                                            @if ($log->notes)
                                                <p class="mt-1 text-slate-600">{{ $log->notes }}</p>
                                            @endif
                                        </div>
                                        <p class="shrink-0 text-xs text-slate-500">
                                            {{ $log->created_at->translatedFormat('d M Y, H:i') }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @php
                        $documentAssessments = $pendaftaran->application->documents
                            ->flatMap(fn($document) => $document->verifications)
                            ->filter()
                            ->groupBy(
                                fn($verification) => \App\Services\AgencyVerificationService::stageLabel(
                                    $verification->stage,
                                ),
                            );
                    @endphp
                    @if ($documentAssessments->isNotEmpty())
                        <div class="mt-5 border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-bold text-slate-900">Penilaian Dokumen</h3>
                            <p class="mt-1 text-xs text-slate-500">Hasil pemeriksaan berkas oleh petugas verifikasi.</p>
                            <div class="mt-3 space-y-4">
                                @foreach ($documentAssessments as $stageLabel => $verifications)
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">
                                            {{ $stageLabel }}</p>
                                        <ul class="mt-2 space-y-2">
                                            @foreach ($verifications as $verification)
                                                <li
                                                    class="flex flex-col gap-1 rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-slate-800">
                                                            {{ $verification->document?->type?->name }}</p>
                                                        @if ($verification->notes)
                                                            <p class="mt-0.5 text-xs text-slate-600">
                                                                {{ $verification->notes }}</p>
                                                        @endif
                                                    </div>
                                                    <span @class([
                                                        'inline-flex w-fit shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold',
                                                        'bg-emerald-50 text-emerald-700' =>
                                                            $verification->result ===
                                                            \App\Enums\DocumentVerificationResult::MEMENUHI,
                                                        'bg-red-50 text-red-700' =>
                                                            $verification->result ===
                                                            \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI,
                                                        'bg-slate-100 text-slate-500' =>
                                                            $verification->result ===
                                                            \App\Enums\DocumentVerificationResult::BELUM_DINILAI,
                                                    ])>
                                                        {{ $verification->result->label() }}
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <section class="card mt-7 p-6 sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Periode</p>
                    <h2 class="mt-2 text-2xl font-extrabold">{{ $pendaftaran->periode?->nama }}</h2>
                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                        <p>
                            Kategori:
                            <strong>{{ $pendaftaran->jalurBeasiswa?->nama ?? '-' }}</strong>
                        </p>

                        <p>
                            Jenis:
                            <strong>
                                {{ $pendaftaran->kategoriBeasiswa?->nama }}
                            </strong>
                        </p>
                    </div>
                </div>
                <span
                    class="inline-flex w-fit rounded-full {{ $stepStatuses[7] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} px-4 py-2 text-sm font-bold">{{ ucfirst($pendaftaran->status) }}</span>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2 {{ $isNonAkademik ? 'lg:grid-cols-7' : 'lg:grid-cols-6' }}">
                @php $seq = 1; @endphp
                @foreach ($steps as $number => $step)
                    @php
                        $completed = $stepStatuses[$number];
                        $current = $number === $currentStep && !$stepStatuses[7];
                    @endphp
                    <a href="{{ route($step['route']) }}" @class([
                        'rounded-xl border p-4 text-center transition focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                        'border-brand-300 bg-brand-50 shadow-sm' => $current,
                        'border-emerald-200 bg-emerald-50 hover:border-emerald-300' => $completed,
                        'border-slate-200 bg-slate-50 hover:border-brand-200 hover:bg-white' =>
                            !$completed && !$current,
                    ])>
                        <div @class([
                            'mx-auto flex h-9 w-9 items-center justify-center rounded-full font-bold',
                            'bg-brand-700 text-white' => $current,
                            'bg-emerald-600 text-white' => $completed,
                            'bg-white text-slate-500' => !$completed && !$current,
                        ])>
                            @if ($completed)
                                <x-icon name="check" class="h-4 w-4" />@else{{ $seq }}
                            @endif
                        </div>
                        <p class="mt-3 text-xs font-semibold text-slate-700">{{ $step['label'] }}</p>
                    </a>
                    @php $seq++; @endphp
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                @php
                    $targetStep = isset($steps[$currentStep]) ? $currentStep : array_key_first($steps);
                    $stepKeys = array_keys($steps);
                    $displayCurrentStep = array_search($targetStep, $stepKeys) !== false ? array_search($targetStep, $stepKeys) + 1 : $targetStep;
                @endphp
                <a href="{{ route($steps[$targetStep]['route']) }}" class="btn-primary">
                    <x-icon name="form" class="h-5 w-5" />
                    {{ $stepStatuses[7] ? 'Lihat Pendaftaran' : 'Lanjutkan Tahap ' . $displayCurrentStep }}
                </a>
                <a href="{{ route('profile.show') }}" class="btn-secondary">Profil & Keamanan Akun</a>
            </div>
        </section>
    @endif

    {{-- RIWAYAT PENDAFTARAN & LPJ PERIODE SEBELUMNYA --}}
    @if(isset($riwayatPendaftarans) && $riwayatPendaftarans->count() > 0)
        <section class="card mt-8 p-6 sm:p-8" x-data="{ activeModalPendaftaranId: null }">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-lg font-bold text-navy-950">Riwayat Pendaftaran & Berkas LPJ Beasiswa</h3>
                <p class="mt-0.5 text-xs text-slate-500">Daftar periode beasiswa yang pernah Anda ikuti beserta status LPJ.</p>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>No. Pendaftaran</th>
                            <th>Jalur & Kategori</th>
                            <th>Status Pendaftaran</th>
                            <th>Status LPJ</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riwayatPendaftarans as $riwayat)
                            @php
                                $rLpj = $riwayat->laporanPertanggungjawaban;
                                $isApproved = $riwayat->status === 'approved' || ($riwayat->application && $riwayat->application->status === \App\Enums\ApplicationStatus::DITERIMA);
                            @endphp
                            <tr>
                                <td class="font-bold text-slate-800">
                                    {{ $riwayat->periode?->tahun ?? '-' }}
                                    <span class="block text-[11px] font-normal text-slate-500">{{ $riwayat->periode?->nama }}</span>
                                </td>
                                <td class="font-mono text-xs">{{ $riwayat->nomor_pendaftaran }}</td>
                                <td>
                                    <p class="text-xs font-semibold text-slate-800">{{ $riwayat->jalurBeasiswa?->nama ?? '-' }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $riwayat->kategoriBeasiswa?->nama ?? '-' }}</p>
                                </td>
                                <td>
                                    @if($isApproved)
                                        <span class="status-chip status-success text-xs">Lulus / Diterima</span>
                                    @elseif($riwayat->status === 'submitted')
                                        <span class="status-chip status-info text-xs">Sedang Diproses</span>
                                    @else
                                        <span class="status-chip status-neutral text-xs">{{ ucfirst($riwayat->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(! $isApproved)
                                        <span class="text-xs text-slate-400">-</span>
                                    @elseif(! $rLpj || $rLpj->status === 'belum_upload')
                                        <span class="status-chip status-warning text-xs">Belum Upload</span>
                                    @elseif($rLpj->status === 'menunggu_verifikasi')
                                        <span class="status-chip status-purple text-xs">Menunggu Verifikasi</span>
                                    @elseif($rLpj->status === 'disetujui')
                                        <span class="status-chip status-success text-xs">LPJ Disetujui</span>
                                    @elseif($rLpj->status === 'ditolak')
                                        <span class="status-chip status-danger text-xs">Perlu Perbaikan</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @php
                                            $hasAnyDocs = ($riwayat->dokumens && $riwayat->dokumens->isNotEmpty())
                                                || ($riwayat->formulirPendaftaran && ($riwayat->formulirPendaftaran->surat_permohonan || $riwayat->formulirPendaftaran->pakta_integritas))
                                                || ($rLpj && $rLpj->file_path);
                                        @endphp
                                        @if($hasAnyDocs)
                                            <button type="button" @click="activeModalPendaftaranId = {{ $riwayat->id }}" class="btn-secondary !py-1 !px-2.5 text-xs text-brand-800 border-brand-300 hover:bg-brand-50" title="Lihat Dokumen Arsip Persyaratan & Formulir">
                                                <x-icon name="folder" class="h-3.5 w-3.5 mr-1 inline" />
                                                Dokumen
                                            </button>
                                        @endif
                                        @if($rLpj && $rLpj->file_path)
                                            <a href="{{ route('mahasiswa.lpj.file', ['pendaftaran_id' => $riwayat->id, 'download' => 1]) }}" class="btn-secondary !py-1 !px-2.5 text-xs" title="Unduh Berkas LPJ">
                                                <x-icon name="document" class="h-3.5 w-3.5 mr-1 inline" />
                                                Berkas LPJ
                                            </a>
                                        @endif
                                        @if($riwayat->status === 'submitted' || $isApproved)
                                            <a href="{{ route('mahasiswa.bukti-pendaftaran.pdf', ['pendaftaran_id' => $riwayat->id]) }}" class="btn-secondary !py-1 !px-2.5 text-xs" title="Cetak Bukti Pendaftaran">
                                                <x-icon name="download" class="h-3.5 w-3.5 mr-1 inline" />
                                                Bukti
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MODAL LIHAT DOKUMEN ARSIP PENDAFTARAN --}}
            @foreach($riwayatPendaftarans as $riwayat)
                @php
                    $rLpj = $riwayat->laporanPertanggungjawaban;
                    $isApproved = $riwayat->status === 'approved' || ($riwayat->application && $riwayat->application->status === \App\Enums\ApplicationStatus::DITERIMA);
                    $hasAnyDocs = ($riwayat->dokumens && $riwayat->dokumens->isNotEmpty())
                        || ($riwayat->formulirPendaftaran && ($riwayat->formulirPendaftaran->surat_permohonan || $riwayat->formulirPendaftaran->pakta_integritas))
                        || ($rLpj && $rLpj->file_path);
                @endphp
                @if($hasAnyDocs)
                    <div x-show="activeModalPendaftaranId === {{ $riwayat->id }}"
                         x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
                         @keydown.escape.window="activeModalPendaftaranId = null">
                        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl transition-all"
                             @click.outside="activeModalPendaftaranId = null">
                            <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">
                                        Dokumen Arsip — Periode {{ $riwayat->periode?->nama ?? $riwayat->periode?->tahun }}
                                    </h3>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        No. Pendaftaran: <span class="font-mono font-semibold text-slate-700">{{ $riwayat->nomor_pendaftaran }}</span>
                                        &bull; {{ $riwayat->kategoriBeasiswa?->nama }} ({{ $riwayat->jalurBeasiswa?->nama }})
                                    </p>
                                </div>
                                <button type="button" @click="activeModalPendaftaranId = null" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                    <x-icon name="x" class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="mt-4 max-h-[60vh] space-y-3 overflow-y-auto pr-1">
                                {{-- 1. DOKUMEN PERSYARATAN --}}
                                @if($riwayat->dokumens)
                                    @foreach($riwayat->dokumens as $dokumen)
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3.5 transition hover:bg-white hover:shadow-sm">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-sm text-slate-800">{{ $dokumen->jenisDokumen?->nama ?? 'Dokumen' }}</span>
                                                    <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 uppercase">
                                                        {{ $dokumen->status ?: 'Tersimpan' }}
                                                    </span>
                                                </div>
                                                <p class="truncate text-xs text-slate-600 mt-1 font-mono">
                                                    {{ $dokumen->nama_file_asli ?: basename($dokumen->file_path) }}
                                                </p>
                                                <p class="text-[11px] text-slate-400 mt-0.5">
                                                    {{ $dokumen->ukuran_file ? number_format($dokumen->ukuran_file / 1024, 1, ',', '.').' KB' : 'File tersimpan' }}
                                                    @if($dokumen->verified_at)
                                                        &bull; Diverifikasi {{ $dokumen->verified_at->translatedFormat('d M Y') }}
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                @if(in_array($dokumen->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true))
                                                    <a href="{{ route('mahasiswa.dokumen.preview', $dokumen) }}" target="_blank" rel="noopener" class="btn-secondary !py-1.5 !px-3 text-xs bg-white hover:bg-slate-50">
                                                        <x-icon name="eye" class="h-3.5 w-3.5 mr-1 inline" />
                                                        Lihat
                                                    </a>
                                                @endif
                                                <a href="{{ route('mahasiswa.dokumen.download', $dokumen) }}" download="{{ $dokumen->nama_file_asli ?: 'dokumen.pdf' }}" class="btn-primary !py-1.5 !px-3 text-xs bg-brand-700 hover:bg-brand-800">
                                                    <x-icon name="download" class="h-3.5 w-3.5 mr-1 inline" />
                                                    Unduh
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif

                                {{-- 2. SURAT PERMOHONAN RESMI --}}
                                @if($riwayat->formulirPendaftaran && $riwayat->formulirPendaftaran->surat_permohonan)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 transition hover:bg-white hover:shadow-sm">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-slate-800">Surat Permohonan Beasiswa</span>
                                                <span class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800 uppercase">
                                                    Formulir Resmi
                                                </span>
                                            </div>
                                            <p class="truncate text-xs text-slate-600 mt-1 font-mono">
                                                Surat-Permohonan-{{ $riwayat->nomor_pendaftaran }}.pdf
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                Dokumen bertandatangan pemohon
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ route('mahasiswa.formulir.lihat', ['jenis' => 'surat', 'pendaftaran_id' => $riwayat->id]) }}" target="_blank" rel="noopener" class="btn-secondary !py-1.5 !px-3 text-xs bg-white hover:bg-slate-50">
                                                <x-icon name="eye" class="h-3.5 w-3.5 mr-1 inline" />
                                                Lihat
                                            </a>
                                            <a href="{{ route('mahasiswa.formulir.download', ['jenis' => 'surat', 'pendaftaran_id' => $riwayat->id]) }}" download="Surat-Permohonan-{{ $riwayat->nomor_pendaftaran }}.pdf" class="btn-primary !py-1.5 !px-3 text-xs bg-brand-700 hover:bg-brand-800">
                                                <x-icon name="download" class="h-3.5 w-3.5 mr-1 inline" />
                                                Unduh
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                {{-- 3. PAKTA INTEGRITAS RESMI --}}
                                @if($riwayat->formulirPendaftaran && $riwayat->formulirPendaftaran->pakta_integritas)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 transition hover:bg-white hover:shadow-sm">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-slate-800">Pakta Integritas Bermeterai</span>
                                                <span class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-800 uppercase">
                                                    Formulir Resmi
                                                </span>
                                            </div>
                                            <p class="truncate text-xs text-slate-600 mt-1 font-mono">
                                                Pakta-Integritas-{{ $riwayat->nomor_pendaftaran }}.pdf
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                Dokumen bertandatangan pemohon & orang tua/wali
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ route('mahasiswa.formulir.lihat', ['jenis' => 'pakta', 'pendaftaran_id' => $riwayat->id]) }}" target="_blank" rel="noopener" class="btn-secondary !py-1.5 !px-3 text-xs bg-white hover:bg-slate-50">
                                                <x-icon name="eye" class="h-3.5 w-3.5 mr-1 inline" />
                                                Lihat
                                            </a>
                                            <a href="{{ route('mahasiswa.formulir.download', ['jenis' => 'pakta', 'pendaftaran_id' => $riwayat->id]) }}" download="Pakta-Integritas-{{ $riwayat->nomor_pendaftaran }}.pdf" class="btn-primary !py-1.5 !px-3 text-xs bg-brand-700 hover:bg-brand-800">
                                                <x-icon name="download" class="h-3.5 w-3.5 mr-1 inline" />
                                                Unduh
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                {{-- 4. BERKAS LPJ (JIKA ADA) --}}
                                @if($rLpj && $rLpj->file_path)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-purple-200 bg-purple-50/50 p-3.5 transition hover:bg-white hover:shadow-sm">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-slate-800">Laporan Pertanggungjawaban (LPJ)</span>
                                                <span class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-800 uppercase">
                                                    {{ $rLpj->status === 'disetujui' ? 'LPJ Disetujui' : 'Berkas LPJ' }}
                                                </span>
                                            </div>
                                            <p class="truncate text-xs text-slate-600 mt-1 font-mono">
                                                Berkas-LPJ-{{ $riwayat->nomor_pendaftaran }}.pdf
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                Laporan penggunaan dana beasiswa mahasiswa
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ route('mahasiswa.lpj.file', ['pendaftaran_id' => $riwayat->id]) }}" target="_blank" rel="noopener" class="btn-secondary !py-1.5 !px-3 text-xs bg-white hover:bg-slate-50">
                                                <x-icon name="eye" class="h-3.5 w-3.5 mr-1 inline" />
                                                Lihat
                                            </a>
                                            <a href="{{ route('mahasiswa.lpj.file', ['pendaftaran_id' => $riwayat->id, 'download' => 1]) }}" download="Berkas-LPJ-{{ $riwayat->nomor_pendaftaran }}.pdf" class="btn-primary !py-1.5 !px-3 text-xs bg-brand-700 hover:bg-brand-800">
                                                <x-icon name="download" class="h-3.5 w-3.5 mr-1 inline" />
                                                Unduh
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                {{-- 5. BUKTI PENDAFTARAN RESMI --}}
                                @if($riwayat->status === 'submitted' || $isApproved)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-3.5 transition hover:bg-white hover:shadow-sm">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-slate-800">Tanda Bukti Pendaftaran</span>
                                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 uppercase">
                                                    Resmi Ber-QR
                                                </span>
                                            </div>
                                            <p class="truncate text-xs text-slate-600 mt-1 font-mono">
                                                bukti-pendaftaran-{{ $riwayat->nomor_pendaftaran }}.pdf
                                            </p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                Bukti tanda terima pendaftaran Kartu Hebat
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <a href="{{ route('mahasiswa.bukti-pendaftaran.pdf', ['pendaftaran_id' => $riwayat->id]) }}" download="bukti-pendaftaran-{{ $riwayat->nomor_pendaftaran }}.pdf" class="btn-primary !py-1.5 !px-3 text-xs bg-brand-700 hover:bg-brand-800">
                                                <x-icon name="download" class="h-3.5 w-3.5 mr-1 inline" />
                                                Unduh
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-5 flex justify-end border-t border-slate-100 pt-3">
                                <button type="button" @click="activeModalPendaftaranId = null" class="btn-secondary !py-2 !px-4 text-xs">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </section>
    @endif
@endsection
