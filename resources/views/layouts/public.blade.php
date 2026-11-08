<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('kartu_hebat.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        /* =========================================================
   MOBILE NAVIGATION — SMOOTH OVERLAY ANIMATION
   Desktop navigation remains untouched.
========================================================= */

        @media (max-width: 1023px) {
            .mobile-nav-panel {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                z-index: 40;

                display: grid;
                grid-template-rows: 0fr;

                width: 100%;
                overflow: hidden;

                opacity: 0;
                visibility: hidden;
                pointer-events: none;

                transform: translateY(-8px);
                transform-origin: top;

                border-top: 1px solid transparent;

                transition:
                    grid-template-rows 380ms cubic-bezier(0.22, 1, 0.36, 1),
                    opacity 260ms ease,
                    transform 380ms cubic-bezier(0.22, 1, 0.36, 1),
                    border-color 260ms ease,
                    visibility 0s linear 380ms;
            }

            .mobile-nav-panel>nav {
                min-height: 0;
                overflow: hidden;
            }

            .mobile-nav-panel.is-open {
                grid-template-rows: 1fr;

                opacity: 1;
                visibility: visible;
                pointer-events: auto;

                transform: translateY(0);

                border-top-color: rgb(241 245 249);

                transition:
                    grid-template-rows 380ms cubic-bezier(0.22, 1, 0.36, 1),
                    opacity 260ms ease,
                    transform 380ms cubic-bezier(0.22, 1, 0.36, 1),
                    border-color 260ms ease,
                    visibility 0s;
            }

            /* Stagger animation untuk setiap menu */
            .mobile-nav-panel nav>div:first-child>a,
            .mobile-nav-panel nav>div:last-child {
                opacity: 0;
                transform: translateY(-7px);

                transition:
                    opacity 240ms ease,
                    transform 320ms cubic-bezier(0.22, 1, 0.36, 1);
            }

            .mobile-nav-panel.is-open nav>div:first-child>a,
            .mobile-nav-panel.is-open nav>div:last-child {
                opacity: 1;
                transform: translateY(0);
            }

            .mobile-nav-panel.is-open nav>div:first-child>a:nth-child(1) {
                transition-delay: 45ms;
            }

            .mobile-nav-panel.is-open nav>div:first-child>a:nth-child(2) {
                transition-delay: 80ms;
            }

            .mobile-nav-panel.is-open nav>div:first-child>a:nth-child(3) {
                transition-delay: 115ms;
            }

            .mobile-nav-panel.is-open nav>div:first-child>a:nth-child(4) {
                transition-delay: 150ms;
            }

            .mobile-nav-panel.is-open nav>div:first-child>a:nth-child(5) {
                transition-delay: 185ms;
            }

            .mobile-nav-panel.is-open nav>div:last-child {
                transition-delay: 220ms;
            }
        }

        /* Pastikan panel tidak pernah tampil di desktop */
        @media (min-width: 1024px) {
            #mobile-navigation {
                display: none !important;
            }
        }

        /* Hormati preferensi reduced motion */
        @media (prefers-reduced-motion: reduce) {

            .mobile-nav-panel,
            .mobile-nav-panel *,
            .mobile-nav-panel::before {
                transition: none !important;
                transform: none !important;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-white antialiased">


    {{-- ========================================================= --}}
    {{-- NAVBAR --}}
    {{-- ========================================================= --}}
    <header data-animate-navbar
        class="sticky top-0 z-50 border-b border-slate-200/60 bg-white/90 shadow-[0_4px_24px_rgba(15,23,42,0.035)] backdrop-blur-xl">

        {{-- Subtle Top Accent --}}
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-400/50 to-transparent">
        </div>

        {{-- NAVBAR MAIN --}}
        <div class="container-page relative flex h-[68px] items-center justify-between py-2.5 lg:h-[76px] lg:py-3">

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
                    class="group relative py-3 transition-colors duration-300 hover:text-blue-700 {{ request()->routeIs('public.announcements.*') ? 'font-bold text-blue-700' : '' }}">
                    Pengumuman
                    <span
                        class="absolute inset-x-0 bottom-0 h-[2px] origin-center rounded-full bg-blue-600 transition-transform duration-300 {{ request()->routeIs('public.announcements.*') ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100' }}">
                    </span>
                </a>

            </nav>

            {{-- DESKTOP ACTION BUTTONS --}}
            <div class="relative z-10 hidden items-center gap-2 lg:flex">

                <a href="{{ route('public.results') }}" title="Periksa Pengumuman Hasil Seleksi & Nomor Tiket"
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

            {{-- MOBILE ACTIONS --}}
            <div class="relative z-10 flex items-center gap-2 lg:hidden">

                {{-- Cek Hasil --}}
                <a href="{{ route('public.results') }}" aria-label="Cek hasil seleksi"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 text-xs font-bold text-blue-700 transition-colors duration-200 {{ request()->routeIs('public.results') ? 'border-blue-600 bg-blue-600 text-white' : '' }}">

                    <x-icon name="search" class="h-4 w-4" />
                    <span>Cek Hasil</span>

                </a>

                {{-- Menu Toggle --}}
                <button type="button" id="mobile-menu-button" aria-label="Buka menu navigasi" aria-expanded="false"
                    aria-controls="mobile-navigation"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition-colors duration-200 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40">

                    <x-icon name="menu" class="h-5 w-5" />

                </button>

            </div>

        </div>

        {{-- MOBILE NAVIGATION PANEL --}}
        <div id="mobile-navigation" aria-hidden="true" inert
            class="mobile-nav-panel bg-white shadow-[0_12px_24px_rgba(15,23,42,0.06)] lg:hidden">

            <nav aria-label="Navigasi mobile" class="container-page py-3">

                {{-- NAVIGATION LINKS --}}
                <div class="space-y-1">

                    <a href="{{ route('home') }}"
                        class="flex items-center rounded-xl px-4 py-3 text-sm font-semibold transition-colors duration-200 {{ request()->routeIs('home') && !request()->routeIs('public.announcements.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-700' }}">
                        Beranda
                    </a>

                    <a href="{{ route('home') }}#informasi"
                        class="flex items-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-colors duration-200 hover:bg-slate-50 hover:text-blue-700">
                        Informasi
                    </a>

                    <a href="{{ route('home') }}#persyaratan"
                        class="flex items-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-colors duration-200 hover:bg-slate-50 hover:text-blue-700">
                        Persyaratan
                    </a>

                    <a href="{{ route('home') }}#jadwal"
                        class="flex items-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition-colors duration-200 hover:bg-slate-50 hover:text-blue-700">
                        Jadwal
                    </a>

                    <a href="{{ route('public.announcements.index') }}"
                        class="flex items-center rounded-xl px-4 py-3 text-sm font-semibold transition-colors duration-200 {{ request()->routeIs('public.announcements.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-700' }}">
                        Pengumuman
                    </a>

                </div>

                {{-- AUTHENTICATION LINKS --}}
                <div class="mt-3 border-t border-slate-100 pt-3">

                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="flex w-full items-center justify-center rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition-colors duration-200 hover:bg-blue-800">
                            Dashboard
                        </a>
                    @else
                        <div class="grid grid-cols-2 gap-2">

                            <a href="{{ route('login') }}"
                                class="flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition-colors duration-200 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                Masuk
                            </a>

                            <a href="{{ route('register') }}"
                                class="flex items-center justify-center rounded-xl bg-blue-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition-colors duration-200 hover:bg-blue-800">
                                Daftar
                            </a>

                        </div>
                    @endauth

                </div>

            </nav>

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

    <script>
        (() => {
            const menuButton = document.getElementById('mobile-menu-button');
            const mobileNavigation = document.getElementById('mobile-navigation');

            if (!menuButton || !mobileNavigation) return;

            function setMenuState(isOpen) {
                mobileNavigation.classList.toggle('is-open', isOpen);

                menuButton.setAttribute('aria-expanded', String(isOpen));
                menuButton.setAttribute(
                    'aria-label',
                    isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi'
                );

                mobileNavigation.setAttribute('aria-hidden', String(!isOpen));
                mobileNavigation.inert = !isOpen;

                menuButton.innerHTML = isOpen ?
                    `<svg xmlns="http://www.w3.org/2000/svg"
                   class="h-5 w-5"
                   viewBox="0 0 24 24"
                   fill="none"
                   stroke="currentColor"
                   stroke-width="2"
                   stroke-linecap="round"
                   stroke-linejoin="round">
                   <path d="M18 6 6 18M6 6l12 12"/>
               </svg>` :
                    `<svg xmlns="http://www.w3.org/2000/svg"
                   class="h-5 w-5"
                   viewBox="0 0 24 24"
                   fill="none"
                   stroke="currentColor"
                   stroke-width="2"
                   stroke-linecap="round"
                   stroke-linejoin="round">
                   <path d="M4 6h16M4 12h16M4 18h16"/>
               </svg>`;
            }

            menuButton.addEventListener('click', () => {
                const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
                setMenuState(!isOpen);
            });

            mobileNavigation.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    setMenuState(false);
                });
            });

            document.addEventListener('keydown', (event) => {
                if (
                    event.key === 'Escape' &&
                    menuButton.getAttribute('aria-expanded') === 'true'
                ) {
                    setMenuState(false);
                    menuButton.focus();
                }
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth >= 1024) {
                    setMenuState(false);
                }
            });
        })();
    </script>

</body>

</html>
