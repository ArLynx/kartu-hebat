@extends('layouts.portal')

@section('title', 'Dashboard Mahasiswa')
@section('header', 'Dashboard Mahasiswa')

@section('content')
<div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-3xl font-extrabold">Halo, {{ auth()->user()->name }}!</h1>
        <p class="mt-2 text-sm text-slate-600">Berikut status terbaru pendaftaran bantuan pendidikan Anda.</p>
    </div>
    <div class="text-left sm:text-right">
        <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-brand-700">
            {{ $application->nomor_pengajuan }}
        </span>
        <p class="mt-2 text-sm font-medium text-slate-700">Jalur {{ $application->application_type?->label() ?? 'belum dipilih' }} · {{ $application->status->label() }}</p>
    </div>
</div>

<div class="card mt-7 p-5 sm:p-7">
    @php
        $steps = [
            ['Data Diri', 15],
            ['Data Pendidikan', 30],
            ['Data Sosial Ekonomi', 45],
            ['Dokumen', 60],
            ['Verifikasi & Hasil', 80],
        ];
        $currentProgress = $application->status->progress();
    @endphp
    <div class="grid gap-4 sm:grid-cols-5">
        @foreach($steps as $index => [$label, $threshold])
            <div class="relative text-center">
                @if(!$loop->last)
                    <div class="absolute left-1/2 top-5 hidden h-0.5 w-full sm:block {{ $currentProgress >= $steps[$index + 1][1] ? 'bg-brand-600' : 'bg-slate-200' }}"></div>
                @endif
                <div class="relative mx-auto flex h-10 w-10 items-center justify-center rounded-full border-2 font-bold
                    {{ $currentProgress >= $threshold ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-slate-50 text-slate-400' }}">
                    @if($currentProgress > $threshold)
                        <x-icon name="check" class="h-5 w-5" />
                    @else
                        {{ $index + 1 }}
                    @endif
                </div>
                <p class="relative mt-3 text-xs font-semibold {{ $currentProgress >= $threshold ? 'text-navy-900' : 'text-slate-400' }}">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-7 grid gap-6 xl:grid-cols-[1fr_300px]">
    <div class="card relative overflow-hidden p-6 sm:p-8">
        <div class="absolute -bottom-16 -right-12 h-56 w-56 rounded-full bg-brand-50"></div>
        <div class="relative flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                <x-icon name="form" class="h-7 w-7" />
            </div>
            <div>
                <h2 class="text-xl font-bold">Status Pendaftaran Terkini</h2>
                <div class="mt-3"><x-status-badge :status="$application->status" /></div>
            </div>
        </div>

        <p class="relative mt-6 max-w-2xl text-base leading-7 text-slate-700">
            Pengajuan <strong class="text-brand-700">jalur {{ $application->application_type?->label() ?? 'belum dipilih' }}</strong>
            saat ini berada pada tahap <strong class="text-brand-700">{{ $application->status->label() }}</strong>.
        </p>

        @if($application->catatan)
            <div class="relative mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex items-start gap-3">
                    <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />
                    <div>
                        <p class="text-sm font-bold text-amber-900">Catatan Verifikator</p>
                        <p class="mt-1 text-sm leading-6 text-amber-800">{{ $application->catatan }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="relative mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-800">
                Pastikan nomor telepon dan alamat domisili tetap aktif selama proses verifikasi berlangsung.
            </div>
        @endif
    </div>

    <div class="space-y-5">
        <div class="card flex items-center gap-4 p-5">
            <div class="relative flex h-16 w-16 items-center justify-center rounded-full" style="background: conic-gradient(#0058bd {{ $profileCompletion }}%, #e2e8f0 0);">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-sm font-bold">{{ $profileCompletion }}%</div>
            </div>
            <div>
                <p class="font-semibold text-slate-900">Kelengkapan Profil</p>
                <p class="mt-1 text-sm text-slate-500">{{ $profileCompletion === 100 ? 'Lengkap' : 'Perlu dilengkapi' }}</p>
            </div>
        </div>

        <div class="card flex items-center gap-4 p-5">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-orange-100 text-orange-700">
                <x-icon name="document" class="h-6 w-6" />
            </div>
            <div>
                <p class="font-semibold text-slate-900">Dokumen Terunggah</p>
                <p class="mt-1 text-sm text-slate-500">{{ $application->documents->count() }} berkas</p>
            </div>
        </div>

        <a href="{{ route('student.application') }}" class="btn-primary w-full justify-center">
            <x-icon name="upload" class="h-5 w-5" />
            {{ $application->canBeEditedByStudent() ? 'Lengkapi Pendaftaran' : 'Lihat Pendaftaran' }}
        </a>
    </div>
</div>

<div class="mt-7 grid gap-6 lg:grid-cols-2">
    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div class="flex items-center gap-3">
                <x-icon name="bell" class="h-5 w-5 text-brand-600" />
                <h2 class="text-base font-bold">Notifikasi Terbaru</h2>
            </div>
            <a href="{{ route('notifications.index') }}" class="text-sm font-semibold text-brand-600">Lihat Semua</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
                <a href="{{ route('notifications.read', $notification->id) }}" class="block px-6 py-5 hover:bg-slate-50">
                    <p class="text-sm font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Informasi' }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->data['message'] ?? '' }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-500">Belum ada notifikasi.</div>
            @endforelse
        </div>
    </section>

    <section class="relative min-h-72 overflow-hidden rounded-xl bg-navy-900 p-8 text-white shadow-card">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 80% 20%, #60a5fa 0, transparent 35%), radial-gradient(circle at 15% 90%, #8b5cf6 0, transparent 30%);"></div>
        <div class="relative flex h-full flex-col justify-end">
            <span class="text-xs font-bold uppercase tracking-widest text-blue-200">Tips Beasiswa</span>
            <h2 class="mt-3 text-2xl font-bold text-white">Siapkan dokumen asli</h2>
            <p class="mt-3 max-w-lg text-sm leading-6 text-slate-200">
                Dokumen asli dapat diminta saat verifikasi faktual. Pastikan seluruh data yang diunggah sama dengan dokumen sumber.
            </p>
            <a href="{{ route('student.application') }}" class="mt-5 inline-flex w-fit rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-navy-900">Periksa Dokumen</a>
        </div>
    </section>
</div>
@endsection
