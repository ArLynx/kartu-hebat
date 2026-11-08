@extends('layouts.portal')

@section('title', 'Antrean Sanggahan')
@section('header', 'Antrean Sanggahan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="section-kicker">{{ $agencyLabel }}</p>
            <h1 class="mt-1 text-2xl font-extrabold text-navy-950 sm:text-3xl">Antrean Sanggahan</h1>
            <p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-600">
                Pengajuan yang dibuka kembali karena sanggahan dikabulkan dan menunggu penilaian ulang dinas Anda.
                Keputusan TMS sebelumnya sudah dianulir; nilai ulang dokumen lalu kirim keputusan baru.
            </p>
        </div>
        <a href="{{ route('operator.applications.index') }}" class="btn-secondary shrink-0">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Antrean Pengajuan
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="card border-l-4 border-l-amber-400 p-5">
            <p class="text-sm font-semibold text-slate-500">Menunggu Penilaian Ulang</p>
            <p class="metric-number mt-2">{{ number_format($summary['total']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Pengajuan pada wilayah kewenangan Anda.</p>
        </div>
        <div class="card border-l-4 border-l-brand-500 p-5">
            <p class="text-sm font-semibold text-slate-500">Periode Terdampak</p>
            <p class="metric-number mt-2">{{ number_format($summary['periodes']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Termasuk periode lama yang dibuka kembali lewat sanggahan.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('operator.sanggahan-queue.index') }}" class="grid gap-4 md:grid-cols-4">
            <div>
                <label for="periode_id" class="form-label text-xs">Periode</label>
                <select id="periode_id" name="periode_id" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="">Semua Periode</option>
                    @foreach ($periodes as $periode)
                        <option value="{{ $periode->id }}" @selected((string) request('periode_id') === (string) $periode->id)>
                            {{ $periode->nama ?: 'Periode '.$periode->tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label for="q" class="form-label text-xs">Cari Nomor Pengajuan / Nama / NIK / NIM</label>
                <div class="flex gap-2">
                    <input id="q" name="q" type="text" class="form-input text-sm" placeholder="Nomor pengajuan, nama, NIK, atau NIM" value="{{ request('q') }}">
                    <button type="submit" class="btn-primary shrink-0 px-3">
                        <x-icon name="search" class="h-4 w-4" />
                    </button>
                    @if (request()->hasAny(['periode_id', 'q']))
                        <a href="{{ route('operator.sanggahan-queue.index') }}" class="btn-secondary shrink-0 px-3" title="Reset filter">
                            ✕
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <section class="table-shell">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Pengajuan</th>
                        <th>Periode</th>
                        <th>Konteks Sanggahan</th>
                        <th>TMS yang Dianulir</th>
                        <th>Dikabulkan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        @php
                            $applicationSanggahan = $application->sanggahan;
                            $annulled = $annulledVerifications[$application->getKey()] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-900">{{ $application->mahasiswa?->name ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $application->mahasiswa?->profile?->village?->display_name }}
                                </p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $application->nomor_pengajuan }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $application->application_type?->label() ?? '-' }}
                                    @if ($application->pendaftaran?->jalurBeasiswa)
                                        &middot; {{ $application->pendaftaran->jalurBeasiswa->nama }}
                                    @endif
                                </p>
                            </td>
                            <td class="whitespace-nowrap">
                                {{ $applicationSanggahan?->periode?->nama ?? $application->pendaftaran?->periode?->nama ?? $application->periode }}
                            </td>
                            <td class="max-w-sm">
                                <p class="line-clamp-2 text-sm text-slate-700">{{ $applicationSanggahan?->uraian ?? '-' }}</p>
                                @if ($applicationSanggahan?->catatan_keputusan)
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500">
                                        Keputusan kabupaten: {{ $applicationSanggahan->catatan_keputusan }}
                                    </p>
                                @endif
                            </td>
                            <td class="max-w-sm">
                                @if ($annulled)
                                    <p class="line-clamp-2 text-sm text-red-700">{{ $annulled['notes'] ?: 'Verifikator tidak menuliskan catatan.' }}</p>
                                @else
                                    <span class="text-xs text-slate-500">Catatan TMS tidak ditemukan.</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <p class="text-sm font-semibold text-slate-800">{{ $applicationSanggahan?->decidedBy?->name ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $applicationSanggahan?->decided_at?->format('d M Y H:i') ?? '-' }}</p>
                            </td>
                            <td>
                                <a href="{{ route('operator.applications.show', $application) }}" class="btn-primary !px-3 !py-2 text-xs">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    Nilai Ulang
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center text-slate-500">
                                Tidak ada pengajuan hasil sanggahan yang menunggu penilaian ulang dinas Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>{{ $applications->links() }}</div>
</div>
@endsection
