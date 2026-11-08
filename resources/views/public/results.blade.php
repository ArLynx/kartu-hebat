@extends('layouts.public')

@section('title', 'Pengumuman Hasil Seleksi')

@section('content')

    <section class="relative isolate overflow-hidden bg-white">

        {{-- Architectural Grid --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#f1f5f9_1px,transparent_1px),linear-gradient(to_bottom,#f1f5f9_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_65%_55%_at_50%_0%,#000_70%,transparent_100%)]">
        </div>

        {{-- Ambient Glow --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute left-1/4 top-0 h-96 w-96 rounded-full bg-blue-200/25 blur-[110px]">
        </div>

        <div aria-hidden="true"
            class="pointer-events-none absolute -right-32 top-1/3 h-96 w-96 rounded-full bg-indigo-100/30 blur-[110px]">
        </div>

        {{-- Main Container --}}
        <div class="container-page relative py-8 sm:py-10 lg:flex lg:min-h-[calc(100svh-5.75rem)] lg:items-center lg:py-6">

            <div class="grid w-full grid-cols-1 items-center gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-12 xl:gap-16">

                {{-- ========================================================= --}}
                {{-- LEFT COLUMN: HERO, SEARCH FORM, SEARCH RESULT --}}
                {{-- ========================================================= --}}
                <div class="min-w-0">

                    {{-- HERO CONTENT --}}
                    <div data-results-hero class="max-w-2xl text-center lg:text-left">

                        {{-- Eyebrow --}}
                        <div data-results-eyebrow
                            class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white/80 px-4 py-2 shadow-[0_4px_20px_rgba(37,99,235,0.06)] backdrop-blur-md">

                            <span class="relative flex h-2 w-2">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-50"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-600"></span>
                            </span>

                            <span class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-blue-700">
                                Hasil Penetapan
                            </span>

                        </div>

                        {{-- Heading --}}
                        <h1 data-results-title
                            class="mt-5 bg-gradient-to-r from-slate-900 via-blue-900 to-slate-800 bg-clip-text text-4xl font-extrabold leading-[1.12] tracking-tight text-transparent sm:text-5xl xl:text-[3.5rem]">
                            Cek Hasil Seleksi
                        </h1>

                        <p data-results-description
                            class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-500 sm:text-base sm:leading-8 lg:mx-0">
                            Masukkan nomor pengajuan atau NIK untuk melihat hasil yang telah dipublikasikan.
                        </p>

                        {{-- Decorative Accent --}}
                        <div data-results-accent
                            class="mx-auto mt-6 flex items-center justify-center gap-2 lg:mx-0 lg:justify-start">

                            <span class="h-1 w-8 rounded-full bg-blue-600"></span>
                            <span class="h-1 w-3 rounded-full bg-blue-200"></span>
                            <span class="h-1 w-1 rounded-full bg-blue-100"></span>

                        </div>

                    </div>

                    {{-- ========================================================= --}}
                    {{-- SEARCH FORM --}}
                    {{-- ========================================================= --}}
                    <div data-reveal class="mt-7 w-full">

                        <div
                            class="relative rounded-[1.5rem] border border-slate-100 bg-white/90 p-3 shadow-[0_20px_60px_rgba(15,23,42,0.07),0_5px_20px_rgba(37,99,235,0.04)] backdrop-blur-xl sm:p-4">

                            {{-- Decorative Glow --}}
                            <div aria-hidden="true"
                                class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-blue-100/40 blur-[60px]">
                            </div>

                            <form method="GET" action="{{ route('public.results') }}"
                                class="relative flex flex-col gap-3 sm:flex-row">

                                <div class="group relative flex-1">

                                    <x-icon name="search"
                                        class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors duration-300 group-focus-within:text-blue-600" />

                                    <input name="nomor" value="{{ request('nomor') }}"
                                        class="form-input !h-[54px] !rounded-xl !border-slate-200 !bg-slate-50/70 !pl-12 !pr-4 !text-sm !font-medium !text-slate-800 placeholder:!text-slate-400 focus:!border-blue-400 focus:!bg-white focus:!ring-4 focus:!ring-blue-500/10"
                                        placeholder="Contoh: KHM-2026-000128 atau 16 digit NIK" required>

                                </div>

                                <button
                                    class="inline-flex min-h-[54px] items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 text-sm font-bold text-white shadow-[0_7px_20px_rgba(37,99,235,0.18)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-[0_12px_28px_rgba(37,99,235,0.25)]">

                                    Periksa Hasil

                                    <x-icon name="arrow-right" class="h-4 w-4" />

                                </button>

                            </form>

                        </div>

                        {{-- Supporting Information --}}
                        <div data-reveal class="mt-4 flex items-center justify-center gap-2 lg:justify-start">

                            <x-icon name="shield-check" class="h-4 w-4 shrink-0 text-blue-600" />

                            <p class="text-[11px] font-medium tracking-wide text-slate-400">
                                Gunakan nomor pengajuan atau NIK yang terdaftar.
                            </p>

                        </div>

                    </div>

                    {{-- ========================================================= --}}
                    {{-- SEARCH RESULT --}}
                    {{-- ========================================================= --}}
                    @if ($searched)

                        <div data-reveal class="mt-10 w-full">

                            @if ($result)
                                @php
                                    $nameParts = preg_split('/\s+/', $result->mahasiswa->name);

                                    $maskedName = collect($nameParts)
                                        ->map(
                                            fn($part) => mb_substr($part, 0, 2) .
                                                str_repeat('*', max(2, mb_strlen($part) - 2)),
                                        )
                                        ->implode(' ');

                                    $nik = $result->mahasiswa->profile?->nik ?? '';

                                    $maskedNik =
                                        mb_substr($nik, 0, 4) .
                                        str_repeat('*', max(0, mb_strlen($nik) - 8)) .
                                        mb_substr($nik, -4);
                                @endphp

                                {{-- RESULT CARD --}}
                                <div
                                    class="group relative overflow-hidden rounded-[1.75rem] border border-slate-100 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.07)]">

                                    {{-- Top Accent --}}
                                    <div
                                        class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-400">
                                    </div>

                                    {{-- Application Number --}}
                                    <div
                                        class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-blue-50/60 px-6 py-6 sm:px-8">

                                        <div aria-hidden="true"
                                            class="pointer-events-none absolute -right-10 -top-20 h-48 w-48 rounded-full bg-blue-100/40 blur-[65px]">
                                        </div>

                                        <div class="relative">

                                            <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-blue-600">
                                                Nomor Pengajuan
                                            </p>

                                            <p
                                                class="mt-2 font-display text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                                                {{ $result->nomor_pengajuan }}
                                            </p>

                                        </div>

                                    </div>

                                    {{-- RESULT DETAILS --}}
                                    <div class="p-6 sm:p-8">

                                        {{-- Student Information --}}
                                        <div class="flex items-start gap-4">

                                            <div
                                                class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl {{ $result->status->tone() === 'success' ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100' : 'bg-red-50 text-red-600 ring-1 ring-red-100' }}">

                                                <x-icon :name="$result->status->tone() === 'success' ? 'check' : 'x'" class="h-7 w-7" />

                                            </div>

                                            <div class="min-w-0 flex-1">

                                                <x-status-badge :status="$result->status" />

                                                <h2
                                                    class="mt-3 break-words text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                                                    {{ $maskedName }}
                                                </h2>

                                                <p class="mt-2 text-xs font-medium tracking-wide text-slate-400">
                                                    NIK {{ $maskedNik }}
                                                </p>

                                            </div>

                                        </div>

                                        {{-- Information Divider --}}
                                        <div class="my-7 h-px bg-gradient-to-r from-slate-100 via-slate-200 to-transparent">
                                        </div>

                                        {{-- Academic Information --}}
                                        <dl class="grid gap-x-8 gap-y-7 sm:grid-cols-2">

                                            <div class="group/item">

                                                <dt
                                                    class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[0.15em] text-slate-400">

                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>

                                                    Jalur Pengajuan

                                                </dt>

                                                <dd class="mt-2 text-sm font-bold leading-6 text-slate-800">
                                                    {{ $result->application_type?->label() }}
                                                </dd>

                                            </div>

                                            <div class="group/item">

                                                <dt
                                                    class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[0.15em] text-slate-400">

                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>

                                                    Kategori Mahasiswa

                                                </dt>

                                                <dd class="mt-2 break-words text-sm font-bold leading-6 text-slate-800">
                                                    {{ $result->pendaftaran?->jalurBeasiswa?->nama ?? '-' }}
                                                </dd>

                                            </div>

                                            <div class="group/item">

                                                <dt
                                                    class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[0.15em] text-slate-400">

                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>

                                                    Peringkat Jalur

                                                </dt>

                                                <dd class="mt-2 text-sm font-bold leading-6 text-slate-800">
                                                    {{ $result->selection?->rank ?? '-' }}
                                                </dd>

                                            </div>

                                            <div class="group/item">

                                                <dt
                                                    class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[0.15em] text-slate-400">

                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>

                                                    Perguruan Tinggi

                                                </dt>

                                                <dd class="mt-2 break-words text-sm font-bold leading-6 text-slate-800">
                                                    {{ $result->mahasiswa->profile?->universitas }}
                                                </dd>

                                            </div>

                                            <div class="group/item">

                                                <dt
                                                    class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-[0.15em] text-slate-400">

                                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>

                                                    Program Studi

                                                </dt>

                                                <dd class="mt-2 break-words text-sm font-bold leading-6 text-slate-800">
                                                    {{ $result->mahasiswa->profile?->program_studi }}
                                                </dd>

                                            </div>

                                        </dl>

                                    </div>

                                </div>
                            @else
                                {{-- RESULT NOT FOUND --}}
                                <div
                                    class="relative overflow-hidden rounded-[1.75rem] border border-amber-100 bg-white p-8 text-center shadow-[0_15px_45px_rgba(15,23,42,0.05)] sm:p-10">

                                    {{-- Ambient Warning Glow --}}
                                    <div aria-hidden="true"
                                        class="pointer-events-none absolute -top-20 left-1/2 h-48 w-48 -translate-x-1/2 rounded-full bg-amber-100/60 blur-[70px]">
                                    </div>

                                    <div
                                        class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border border-amber-100 bg-amber-50 text-amber-600">

                                        <x-icon name="warning" class="h-8 w-8" />

                                    </div>

                                    <h2 class="relative mt-5 text-xl font-extrabold tracking-tight text-slate-900">
                                        Hasil belum ditemukan
                                    </h2>

                                    <p class="relative mx-auto mt-3 max-w-md text-sm leading-7 text-slate-500">
                                        Periksa kembali nomor pengajuan/NIK atau tunggu sampai keputusan resmi
                                        dipublikasikan.
                                    </p>

                                    <div
                                        class="relative mx-auto mt-6 inline-flex items-center gap-2 rounded-full border border-amber-100 bg-amber-50/70 px-4 py-2">

                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>

                                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700">
                                            Silakan periksa kembali data Anda
                                        </span>

                                    </div>

                                </div>
                            @endif

                        </div>

                    @endif

                </div>

                {{-- ========================================================= --}}
                {{-- RIGHT COLUMN: INFORMASI WAKTU SANGGAH --}}
                {{-- ========================================================= --}}
                <div class="min-w-0">

                    <x-sanggahan.public-schedule
                        :periode="$periode"
                        :periode-label="$result ? 'Periode hasil tidak ditemukan' : 'Belum ada periode aktif'"
                    />

                </div>

            </div>

        </div>

        {{-- Bottom Transition --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-50/50 to-transparent">
        </div>

    </section>

@endsection
