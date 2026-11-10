@extends('layouts.public')

@section('title', 'Pengumuman Hasil Seleksi')

<style>
    /* =========================================
   SEARCH RESULT TRANSITION
========================================= */

    .search-result-transition {
        opacity: 0;
        transform: translateY(14px);
        filter: blur(3px);
        transition:
            opacity 420ms cubic-bezier(0.22, 1, 0.36, 1),
            transform 420ms cubic-bezier(0.22, 1, 0.36, 1),
            filter 420ms cubic-bezier(0.22, 1, 0.36, 1);
        will-change: opacity, transform, filter;
    }

    .search-result-transition.is-visible {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
    }

    .search-result-transition.is-leaving {
        opacity: 0;
        transform: translateY(-8px);
        filter: blur(2px);
        transition-duration: 180ms;
    }

    @media (prefers-reduced-motion: reduce) {

        .search-result-transition,
        .search-result-transition.is-visible,
        .search-result-transition.is-leaving {
            transition: none;
            transform: none;
            filter: none;
        }
    }
</style>

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
        <div class="container-page relative py-8 sm:py-10 lg:flex lg:min-h-[calc(100svh-5.75rem)] lg:items-start lg:py-6">

            <div class="grid w-full grid-cols-1 items-start gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-12 xl:gap-16">

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
                    <div data-reveal class="mt-11 w-full">

                        <div
                            class="relative rounded-[1.5rem] border border-slate-100 bg-white/90 p-3 shadow-[0_20px_60px_rgba(15,23,42,0.07),0_5px_20px_rgba(37,99,235,0.04)] backdrop-blur-xl sm:p-4">

                            {{-- Decorative Glow --}}
                            <div aria-hidden="true"
                                class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-blue-100/40 blur-[60px]">
                            </div>

                            <form id="results-search-form" method="GET" action="{{ route('public.results') }}"
                                class="relative !mb-0 flex flex-col gap-3 sm:flex-row">

                                <div class="group relative flex-1">

                                    <x-icon name="search"
                                        class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors duration-300 group-focus-within:text-blue-600" />

                                    <input name="nomor" value="{{ request('nomor') }}"
                                        class="form-input !h-[54px] !rounded-xl !border-slate-200 !bg-slate-50/70 !pl-12 !pr-4 !text-sm !font-medium !text-slate-800 placeholder:!text-slate-400 focus:!border-blue-400 focus:!bg-white focus:!ring-4 focus:!ring-blue-500/10"
                                        placeholder="Contoh: KHM-2026-000128 atau 16 digit NIK" required>

                                </div>

                                <button type="submit" id="results-search-button"
                                    class="inline-flex min-h-[54px] items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 text-sm font-bold text-white shadow-[0_7px_20px_rgba(37,99,235,0.18)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-[0_12px_28px_rgba(37,99,235,0.25)] disabled:cursor-wait disabled:opacity-70">

                                    <span>Periksa Hasil</span>

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
                    <div id="search-result-container" aria-live="polite" aria-atomic="true">

                        @if ($searched)

                            <div data-reveal class="mt-5 w-full">

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

                                                <p
                                                    class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-blue-600">
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
                                            <div
                                                class="my-7 h-px bg-gradient-to-r from-slate-100 via-slate-200 to-transparent">
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
                                        class="relative isolate overflow-hidden rounded-2xl
        border border-blue-200/80 border-l-[3px] border-l-amber-400
        bg-gradient-to-br from-white via-white to-[#edf4ff]
        p-4
        shadow-[0_18px_42px_rgba(30,64,175,0.12),0_5px_14px_rgba(15,23,42,0.055),inset_0_1px_0_rgba(255,255,255,0.95)]
        ring-1 ring-blue-100/70
        sm:p-5">

                                        {{-- TOP ACCENT --}}
                                        <div aria-hidden="true"
                                            class="pointer-events-none absolute inset-x-6 top-0 h-px
            bg-gradient-to-r from-transparent via-blue-300/90 to-transparent">
                                        </div>

                                        {{-- AMBIENT DECORATION --}}
                                        <div aria-hidden="true"
                                            class="pointer-events-none absolute -right-12 -top-16
            h-44 w-44 rounded-full
            bg-blue-200/45 blur-[60px]">
                                        </div>

                                        <div aria-hidden="true"
                                            class="pointer-events-none absolute -bottom-16 -left-12
            h-32 w-32 rounded-full
            bg-sky-100/50 blur-[55px]">
                                        </div>

                                        <div class="relative flex items-start gap-3.5">

                                            {{-- WARNING ICON --}}
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center
                rounded-xl border border-amber-200
                bg-gradient-to-br from-white via-amber-50 to-amber-100/80
                text-amber-600
                shadow-[0_4px_12px_rgba(217,119,6,0.12),inset_0_1px_2px_rgba(255,255,255,0.9)]">

                                                <x-icon name="warning" class="h-5 w-5" />

                                            </div>

                                            {{-- STATUS INFORMATION --}}
                                            <div class="min-w-0 flex-1">

                                                <p
                                                    class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-amber-600">
                                                    Status pencarian
                                                </p>

                                                <h2
                                                    class="mt-1 text-base font-extrabold tracking-tight text-slate-900 sm:text-lg">
                                                    Hasil belum ditemukan
                                                </h2>

                                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                                    Data dengan nomor pengajuan atau NIK tersebut belum ditemukan.
                                                    Periksa kembali data Anda atau tunggu hingga hasil seleksi resmi
                                                    dipublikasikan.
                                                </p>

                                                {{-- SUPPORTING INFORMATION --}}
                                                <div
                                                    class="mt-3 flex items-start gap-2
                    border-t border-blue-200/60 pt-3">

                                                    <x-icon name="info"
                                                        class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-600" />

                                                    <p class="text-xs leading-5 text-slate-600">
                                                        Pastikan nomor pengajuan atau NIK yang dimasukkan sudah sesuai.
                                                    </p>

                                                </div>

                                            </div>

                                        </div>

                                    </div>
                                @endif

                            </div>

                        @endif
                    </div>
                </div>

                {{-- ========================================================= --}}
                {{-- RIGHT COLUMN: INFORMASI WAKTU SANGGAH --}}
                {{-- ========================================================= --}}
                <div id="sanggahan-schedule-container" class="min-w-0 lg:pt-8">

                    <x-sanggahan.public-schedule :periode="$periode" :periode-label="$result ? 'Periode hasil tidak ditemukan' : 'Belum ada periode aktif'" />

                </div>

            </div>

        </div>

        {{-- Bottom Transition --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-50/50 to-transparent">
        </div>

    </section>

@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('results-search-form');
            const button = document.getElementById('results-search-button');
            const resultContainer = document.getElementById('search-result-container');
            const scheduleContainer = document.getElementById('sanggahan-schedule-container');

            if (!form || !button || !resultContainer || !scheduleContainer) return;

            const originalButtonContent = button.innerHTML;
            const prefersReducedMotion = window.matchMedia(
                '(prefers-reduced-motion: reduce)'
            ).matches;

            let isSearching = false;

            function wait(duration) {
                return new Promise(resolve => setTimeout(resolve, duration));
            }

            function animateResult(element) {
                if (!element) return;

                element.classList.add('search-result-transition');

                // Memastikan browser mengenali kondisi awal animasi.
                void element.offsetWidth;

                requestAnimationFrame(() => {
                    element.classList.add('is-visible');
                });
            }

            async function replaceResultContent(newContent) {
                const currentResult = resultContainer.firstElementChild;

                // Hilangkan hasil sebelumnya secara perlahan.
                if (currentResult && !prefersReducedMotion) {
                    currentResult.classList.add('search-result-transition');

                    void currentResult.offsetWidth;

                    currentResult.classList.add('is-leaving');

                    await wait(180);
                }

                // Masukkan hasil pencarian terbaru.
                resultContainer.innerHTML = newContent;

                const newResult = resultContainer.firstElementChild;

                if (!newResult) return;

                if (prefersReducedMotion) {
                    return;
                }

                // Berikan jeda satu frame sebelum animasi masuk.
                animateResult(newResult);
            }

            async function performSearch(url) {
                if (isSearching) return;

                isSearching = true;
                button.disabled = true;

                button.innerHTML = `
                <svg class="h-4 w-4 animate-spin"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg">

                    <circle cx="12" cy="12" r="9"
                        stroke="currentColor"
                        stroke-width="3"
                        opacity=".25" />

                    <path d="M21 12a9 9 0 0 0-9-9"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round" />
                </svg>

                <span>Memeriksa...</span>
            `;

                form.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        throw new Error(`Permintaan gagal: ${response.status}`);
                    }

                    const html = await response.text();

                    const parser = new DOMParser();
                    const documentResponse = parser.parseFromString(
                        html,
                        'text/html'
                    );

                    const newResult = documentResponse.querySelector(
                        '#search-result-container'
                    );

                    const newSchedule = documentResponse.querySelector(
                        '#sanggahan-schedule-container'
                    );

                    if (!newResult || !newSchedule) {
                        throw new Error('Bagian hasil pencarian tidak ditemukan.');
                    }

                    // Animasi pergantian hasil pencarian.
                    await replaceResultContent(newResult.innerHTML);

                    // Sinkronisasi informasi jadwal sanggahan.
                    scheduleContainer.innerHTML = newSchedule.innerHTML;

                    // Perbarui URL tanpa memuat ulang halaman.
                    window.history.pushState({}, '', url);

                } catch (error) {
                    console.error('Pencarian hasil seleksi gagal:', error);

                    // Fallback jika AJAX mengalami kegagalan.
                    window.location.assign(url.toString());

                } finally {
                    isSearching = false;
                    button.disabled = false;
                    button.innerHTML = originalButtonContent;
                    form.removeAttribute('aria-busy');
                }
            }

            form.addEventListener('submit', event => {
                event.preventDefault();

                if (!form.reportValidity()) return;

                const url = new URL(form.action, window.location.origin);
                const formData = new FormData(form);

                url.search = new URLSearchParams(formData).toString();

                performSearch(url);
            });
        })();
    </script>
@endpush
