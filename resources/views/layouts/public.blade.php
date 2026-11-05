<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('kartu_hebat.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="min-h-screen bg-white antialiased">

    {{-- ========================================================= --}}
    {{-- NAVBAR --}}
    {{-- ========================================================= --}}
    <header data-animate-navbar
        class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/85 shadow-[0_4px_24px_rgba(15,23,42,0.035)] backdrop-blur-xl">

        {{-- Subtle Top Accent --}}
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-400/50 to-transparent">
        </div>

        <div class="container-page relative flex h-[76px] items-center justify-between py-3">

            {{-- BRAND --}}
            <a href="{{ route('home') }}" aria-label="Beranda Kartu Hebat Mahasiswa"
                class="relative z-10 shrink-0 transition-opacity duration-300 hover:opacity-80">

                <x-brand />

            </a>


            {{-- DESKTOP NAVIGATION --}}
            <nav class="hidden items-center gap-8 text-[13px] font-semibold tracking-wide text-slate-600 lg:flex">

                <a href="{{ route('home') }}"
                    class="group relative py-3 transition-colors duration-300 hover:text-blue-700">

                    Beranda

                    <span
                        class="absolute inset-x-0 bottom-0 h-[2px] origin-center scale-x-0 rounded-full bg-blue-600 transition-transform duration-300 group-hover:scale-x-100">
                    </span>

                </a>

                <a href="{{ route('home') }}#informasi"
                    class="group relative py-3 transition-colors duration-300 hover:text-blue-700">

                    Informasi

                    <span
                        class="absolute inset-x-0 bottom-0 h-[2px] origin-center scale-x-0 rounded-full bg-blue-600 transition-transform duration-300 group-hover:scale-x-100">
                    </span>

                </a>

                <a href="{{ route('home') }}#persyaratan"
                    class="group relative py-3 transition-colors duration-300 hover:text-blue-700">

                    Persyaratan

                    <span
                        class="absolute inset-x-0 bottom-0 h-[2px] origin-center scale-x-0 rounded-full bg-blue-600 transition-transform duration-300 group-hover:scale-x-100">
                    </span>

                </a>

                <a href="{{ route('home') }}#jadwal"
                    class="group relative py-3 transition-colors duration-300 hover:text-blue-700">

                    Jadwal

                    <span
                        class="absolute inset-x-0 bottom-0 h-[2px] origin-center scale-x-0 rounded-full bg-blue-600 transition-transform duration-300 group-hover:scale-x-100">
                    </span>

                </a>

                <a href="{{ route('public.announcements.index') }}"
                    class="group relative py-3 transition-colors duration-300 hover:text-blue-700 {{ request()->routeIs('public.announcements.*') ? 'text-blue-700 font-bold' : '' }}">

                    Pengumuman

                    <span
                        class="absolute inset-x-0 bottom-0 h-[2px] origin-center rounded-full bg-blue-600 transition-transform duration-300 {{ request()->routeIs('public.announcements.*') ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}">
                    </span>

                </a>

            </nav>


            {{-- ACTION & AUTHENTICATION BUTTONS --}}
            <div class="relative z-10 flex items-center gap-2">

                {{-- TOMBOL CEPAT CEK HASIL SELEKSI --}}
                <a href="{{ route('public.results') }}"
                    title="Periksa Pengumuman Hasil Seleksi & Nomor Tiket"
                    class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-blue-200/80 bg-blue-50/70 px-3.5 py-2.5 text-xs font-bold tracking-wide text-blue-700 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:border-blue-300 hover:bg-blue-600 hover:text-white hover:shadow-[0_4px_12px_rgba(37,99,235,0.2)] {{ request()->routeIs('public.results') ? 'border-blue-600 bg-blue-600 !text-white shadow-sm' : '' }}">

                    <x-icon name="search" class="h-3.5 w-3.5" />

                    <span>Cek Hasil</span>

                </a>

                @auth

                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-5 py-2.5 text-xs font-bold tracking-wide text-white shadow-[0_5px_15px_rgba(37,99,235,0.16)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-[0_9px_22px_rgba(37,99,235,0.23)]">

                        Dashboard

                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold tracking-wide text-slate-700 shadow-sm transition-all duration-300 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">

                        Masuk

                    </a>

                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-5 py-2.5 text-xs font-bold tracking-wide text-white shadow-[0_5px_15px_rgba(37,99,235,0.16)] transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-[0_9px_22px_rgba(37,99,235,0.23)]">

                        Daftar

                    </a>

                @endauth

            </div>

        </div>

    </header>


    {{-- ========================================================= --}}
    {{-- MAIN CONTENT --}}
    {{-- ========================================================= --}}
    <main class="relative">

        @yield('content')

    </main>


    {{-- ========================================================= --}}
    {{-- FOOTER --}}
    {{-- ========================================================= --}}
    <footer data-animate-footer
        class="relative isolate overflow-hidden border-t border-slate-100 bg-gradient-to-b from-white via-white to-slate-50/80">

        {{-- ===================================================== --}}
        {{-- ARCHITECTURAL DECORATIONS --}}
        {{-- ===================================================== --}}

        {{-- Background Grid --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(to_right,#f1f5f9_1px,transparent_1px),linear-gradient(to_bottom,#f1f5f9_1px,transparent_1px)] bg-[size:4rem_4rem] opacity-30 [mask-image:linear-gradient(to_bottom,transparent,black_25%,black_80%,transparent)]">
        </div>

        {{-- Blue Ambient Glow --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute -right-32 -top-40 -z-10 h-[420px] w-[420px] rounded-full bg-blue-100/50 blur-[110px]">
        </div>

        <div aria-hidden="true"
            class="pointer-events-none absolute -bottom-40 -left-32 -z-10 h-[380px] w-[380px] rounded-full bg-indigo-100/35 blur-[110px]">
        </div>

        {{-- Decorative Top Line --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-300/60 to-transparent">
        </div>

        {{-- ===================================================== --}}
        {{-- FOOTER MAIN CONTENT --}}
        {{-- ===================================================== --}}
        <div data-footer-content class="container-page relative grid gap-12 py-16 md:grid-cols-3 md:gap-10 lg:py-20">

            {{-- BRAND INFORMATION --}}
            <div data-footer-item class="md:col-span-1">

                <div class="inline-flex items-center">

                    <x-brand />

                </div>

                {{-- Decorative Accent --}}
                <div class="mt-6 h-[3px] w-12 rounded-full bg-gradient-to-r from-blue-600 to-sky-300">
                </div>

                <p class="mt-5 max-w-sm text-sm leading-7 text-slate-500">

                    Program bantuan pendidikan untuk mendukung putra-putri daerah memperoleh pendidikan tinggi yang
                    berkualitas.

                </p>

            </div>

            {{-- SERVICE LINKS --}}
            <div data-footer-item>

                <div class="flex items-center gap-3">

                    <span class="h-5 w-1 rounded-full bg-blue-600"></span>

                    <h3 class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-900">

                        Tautan Layanan

                    </h3>

                </div>

                <div class="mt-6 space-y-4 text-sm font-medium text-slate-500">

                    <a href="{{ route('public.announcements.index') }}"
                        class="group flex w-fit items-center gap-2 transition-colors duration-300 hover:text-blue-700">

                        <span
                            class="h-1 w-1 rounded-full bg-slate-300 transition-all duration-300 group-hover:w-2 group-hover:bg-blue-600">
                        </span>

                        Pengumuman & Informasi

                    </a>

                    <a href="{{ route('public.results') }}"
                        class="group flex w-fit items-center gap-2 transition-colors duration-300 hover:text-blue-700">

                        <span
                            class="h-1 w-1 rounded-full bg-slate-300 transition-all duration-300 group-hover:w-2 group-hover:bg-blue-600">
                        </span>

                        Cek hasil seleksi

                    </a>

                    <a href="{{ route('login') }}"
                        class="group flex w-fit items-center gap-2 transition-colors duration-300 hover:text-blue-700">

                        <span
                            class="h-1 w-1 rounded-full bg-slate-300 transition-all duration-300 group-hover:w-2 group-hover:bg-blue-600">
                        </span>

                        Masuk ke portal

                    </a>

                    <a href="{{ route('register') }}"
                        class="group flex w-fit items-center gap-2 transition-colors duration-300 hover:text-blue-700">

                        <span
                            class="h-1 w-1 rounded-full bg-slate-300 transition-all duration-300 group-hover:w-2 group-hover:bg-blue-600">
                        </span>

                        Pendaftaran mahasiswa

                    </a>

                </div>

            </div>

            {{-- GOVERNMENT INFORMATION --}}
            <div data-footer-item>

                <div class="flex items-center gap-3">

                    <span class="h-5 w-1 rounded-full bg-blue-600"></span>

                    <h3 class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-900">

                        Pemerintah Daerah

                    </h3>

                </div>

                <p class="mt-6 text-sm font-semibold leading-7 text-slate-700">

                    {{ config('kartu_hebat.government') }}

                </p>

                <p class="mt-2 text-sm leading-6 text-slate-500">

                    Layanan Kartu Hebat Mahasiswa

                </p>

                {{-- Decorative Identity --}}
                <div
                    class="mt-6 inline-flex items-center gap-2 rounded-full border border-blue-100/80 bg-white/80 px-3 py-2 shadow-[0_4px_15px_rgba(37,99,235,0.04)]">

                    <span class="h-2 w-2 rounded-full bg-blue-600 shadow-[0_0_10px_rgba(37,99,235,0.35)]"></span>

                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">

                        Portal Layanan Resmi

                    </span>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- FOOTER COPYRIGHT --}}
        {{-- ===================================================== --}}
        <div class="relative border-t border-slate-200/70 bg-white/50 backdrop-blur-sm">

            {{-- Top Decorative Glow --}}
            <div aria-hidden="true"
                class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-200 to-transparent">
            </div>

            <div data-footer-bottom
                class="container-page flex flex-col gap-3 py-6 sm:flex-row sm:items-center sm:justify-between">

                <p data-footer-bottom-item
                    class="text-center text-[11px] font-medium leading-6 tracking-wide text-slate-400 sm:text-left">

                    © {{ now()->year }} {{ config('kartu_hebat.government') }}. Hak cipta dilindungi.

                </p>

                <div data-footer-bottom-item class="flex items-center justify-center gap-2">

                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>

                    <span class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-400">

                        Kartu Hebat Mahasiswa

                    </span>

                </div>

            </div>

        </div>

    </footer>


    @stack('scripts')

</body>

</html>
