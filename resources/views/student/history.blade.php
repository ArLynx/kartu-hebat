@extends('layouts.portal')

@section('title', 'Riwayat Verifikasi')
@section('header', 'Riwayat Verifikasi')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-3xl font-extrabold">Riwayat Verifikasi</h1>
        <p class="mt-2 text-sm text-slate-600">Pantau setiap keputusan dan catatan petugas secara kronologis.</p>
    </div>
    <div class="text-left sm:text-right">
        <x-status-badge :status="$application->status" />
        <p class="mt-2 text-xs text-slate-500">{{ $application->nomor_pengajuan }} · Jalur {{ $application->application_type?->label() ?? 'belum dipilih' }}</p>
    </div>
</div>

@if(in_array($application->status, [\App\Enums\ApplicationStatus::BTL_DESA, \App\Enums\ApplicationStatus::BTL_KECAMATAN], true))
    <div class="mt-7 rounded-xl border border-amber-200 bg-amber-50 p-6">
        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <x-icon name="warning" class="h-6 w-6" />
            </div>
            <div>
                <h2 class="text-xl font-bold text-amber-950">Butuh Perbaikan Dokumen (BTL)</h2>
                <p class="mt-2 text-sm leading-6 text-amber-900">{{ $application->catatan }}</p>
                <a href="{{ route('student.application') }}" class="mt-4 inline-flex text-sm font-bold text-amber-900 underline">Perbaiki pendaftaran sekarang</a>
            </div>
        </div>
    </div>
@endif

<div class="mt-7 grid gap-6 xl:grid-cols-[1fr_360px]">
    <section class="card p-6 sm:p-8">
        <h2 class="text-xl font-bold">Linimasa Status</h2>
        <div class="mt-7 space-y-0">
            @forelse($application->verificationLogs->sortBy('created_at') as $log)
                <div class="relative flex gap-4 pb-7 last:pb-0">
                    @if(!$loop->last)
                        <div class="absolute left-5 top-10 h-full w-px bg-slate-200"></div>
                    @endif
                    <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 ring-4 ring-white">
                        <x-icon name="check" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 pt-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold text-slate-900">{{ $log->to_status->label() }}</p>
                            <span class="text-xs text-slate-400">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $log->actor?->role?->label() ?? 'Sistem' }}</p>
                        @if($log->notes)
                            <p class="mt-2 rounded-lg bg-slate-50 p-3 text-sm leading-6 text-slate-600">{{ $log->notes }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-slate-500">Belum ada aktivitas verifikasi.</div>
            @endforelse
        </div>
    </section>

    <aside class="space-y-6">
        <section class="card p-6">
            <h2 class="text-base font-bold">Alur Verifikasi Instansi</h2>
            <div class="mt-5 space-y-4">
                @php
                    $stages = [
                        ['Desa/Kelurahan', $application->villageVerification?->decision],
                        ['Kecamatan', $application->districtVerification?->decision],
                        ['Dukcapil', $application->agencyVerifications->firstWhere('agency', 'dukcapil')?->decision],
                        ['Dinas Sosial', $application->agencyVerifications->firstWhere('agency', 'sosial')?->decision],
                        ['Dinas Pendidikan', $application->agencyVerifications->firstWhere('agency', 'pendidikan')?->decision],
                    ];
                @endphp
                @foreach($stages as [$label, $decision])
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full {{ $decision?->value === 'MS' ? 'bg-emerald-100 text-emerald-700' : ($decision ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400') }}">
                            <x-icon :name="$decision?->value === 'MS' ? 'check' : 'clock'" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
                            <p class="text-xs text-slate-500">{{ $decision?->label() ?? 'Menunggu' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @if($application->selection?->published_at)
            <section class="rounded-xl {{ $application->status === \App\Enums\ApplicationStatus::DITERIMA ? 'bg-emerald-600' : 'bg-red-600' }} p-6 text-white">
                <p class="text-xs font-bold uppercase tracking-wider text-white/80">Hasil Akhir</p>
                <h2 class="mt-2 text-2xl font-extrabold text-white">{{ $application->status->label() }}</h2>
                <p class="mt-2 text-sm text-white/90">Peringkat {{ $application->selection->rank ?? '-' }} pada jalur {{ $application->application_type?->label() }} dengan skor {{ number_format((float) $application->selection->final_score, 2) }}.</p>
            </section>
        @endif
    </aside>
</div>
@endsection
