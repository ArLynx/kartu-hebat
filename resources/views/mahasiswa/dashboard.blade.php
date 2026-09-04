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
                                fn($verification) => \App\Services\DocumentVerificationService::stageLabel(
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

            <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
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
                                <x-icon name="check" class="h-4 w-4" />@else{{ $number }}
                            @endif
                        </div>
                        <p class="mt-3 text-xs font-semibold text-slate-700">{{ $step['label'] }}</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route($steps[$currentStep]['route']) }}" class="btn-primary">
                    <x-icon name="form" class="h-5 w-5" />
                    {{ $stepStatuses[7] ? 'Lihat Pendaftaran' : 'Lanjutkan Tahap ' . $currentStep }}
                </a>
                <a href="{{ route('profile.show') }}" class="btn-secondary">Profil & Keamanan Akun</a>
            </div>
        </section>
    @endif
@endsection
