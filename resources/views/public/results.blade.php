@extends('layouts.public')

@section('title', 'Pengumuman Hasil Seleksi')

@section('content')
<section class="bg-gradient-to-b from-blue-50 to-[#fbf8fc]">
    <div class="container-page py-16 sm:py-20">
        <div class="mx-auto max-w-3xl text-center">
            <span class="inline-flex rounded-full bg-brand-100 px-4 py-2 text-xs font-bold text-brand-700">HASIL PENETAPAN</span>
            <h1 class="mt-5 text-4xl font-extrabold">Cek Hasil Seleksi</h1>
            <p class="mt-4 text-base leading-7 text-slate-600">
                Masukkan nomor pengajuan atau NIK untuk melihat hasil yang telah dipublikasikan.
            </p>
        </div>

        <div class="mx-auto mt-10 max-w-2xl card p-6 sm:p-8">
            <form method="GET" action="{{ route('public.results') }}" class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-3.5 h-5 w-5 text-slate-400" />
                    <input name="nomor" value="{{ request('nomor') }}" class="form-input !pl-11" placeholder="Contoh: KHM-2026-000128 atau 16 digit NIK" required>
                </div>
                <button class="btn-primary justify-center">Periksa Hasil</button>
            </form>
        </div>

        @if($searched)
            <div class="mx-auto mt-8 max-w-2xl">
                @if($result)
                    @php
                        $nameParts = preg_split('/\s+/', $result->mahasiswa->name);
                        $maskedName = collect($nameParts)->map(fn($part) => mb_substr($part, 0, 2).str_repeat('*', max(2, mb_strlen($part)-2)))->implode(' ');
                        $nik = $result->mahasiswa->profile?->nik ?? '';
                        $maskedNik = mb_substr($nik, 0, 4).str_repeat('*', max(0, mb_strlen($nik)-8)).mb_substr($nik, -4);
                    @endphp
                    <div class="card overflow-hidden">
                        <div class="bg-navy-900 px-6 py-5 text-white">
                            <p class="text-xs font-semibold uppercase tracking-widest text-blue-200">Nomor Pengajuan</p>
                            <p class="mt-1 font-display text-xl font-bold">{{ $result->nomor_pengajuan }}</p>
                        </div>
                        <div class="p-6 sm:p-8">
                            <div class="flex items-start gap-4">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full {{ $result->status->tone() === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    <x-icon :name="$result->status->tone() === 'success' ? 'check' : 'x'" class="h-7 w-7" />
                                </div>
                                <div>
                                    <x-status-badge :status="$result->status" />
                                    <h2 class="mt-3 text-2xl font-bold">{{ $maskedName }}</h2>
                                    <p class="mt-1 text-sm text-slate-500">NIK {{ $maskedNik }}</p>
                                </div>
                            </div>

                            <dl class="mt-7 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-semibold uppercase text-slate-500">Jalur Pengajuan</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $result->application_type?->label() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase text-slate-500">Kategori Mahasiswa</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $result->pendaftaran?->jalurBeasiswa?->nama ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase text-slate-500">Peringkat Jalur</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $result->selection?->rank ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase text-slate-500">Perguruan Tinggi</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $result->mahasiswa->profile?->universitas }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-semibold uppercase text-slate-500">Program Studi</dt>
                                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $result->mahasiswa->profile?->program_studi }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                        <x-icon name="warning" class="mx-auto h-8 w-8 text-amber-600" />
                        <h2 class="mt-3 text-lg font-bold">Hasil belum ditemukan</h2>
                        <p class="mt-2 text-sm leading-6 text-amber-800">Periksa kembali nomor pengajuan/NIK atau tunggu sampai keputusan resmi dipublikasikan.</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
@endsection
