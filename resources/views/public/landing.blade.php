@extends('layouts.public')

@section('title', 'Kartu Hebat Mahasiswa — Kabupaten Murung Raya')

@section('content')
    @php
        $periode ??= null;
        $categories ??= collect();
        $today = today();

        $displayYear = (int) ($periode?->tahun ?: $today->year);
        $registrationState = 'upcoming_draft';
        $daysLeft = 0;
        $daysUntilOpen = 0;

        if ($periode) {
            if ($periode->status === 'aktif' && $periode->tanggal_mulai && $periode->tanggal_selesai) {
                if ($today->betweenIncluded($periode->tanggal_mulai, $periode->tanggal_selesai)) {
                    $registrationState = 'open';
                    $daysLeft = max(0, (int) $today->diffInDays($periode->tanggal_selesai, false));
                } elseif ($today->lt($periode->tanggal_mulai)) {
                    $registrationState = 'upcoming_scheduled';
                    $daysUntilOpen = max(1, (int) $today->diffInDays($periode->tanggal_mulai, false));
                } else {
                    $registrationState = 'closed';
                }
            } elseif ($periode->status === 'draft') {
                $registrationState = 'upcoming_draft';
            } else {
                $registrationState = 'closed';
            }
        }

        $isUpcoming = in_array($registrationState, ['upcoming_scheduled', 'upcoming_draft'], true);
    @endphp

    {{-- ========================================================= --}}
    {{-- HERO SECTION --}}
    {{-- ========================================================= --}}
    <section id="hero"
        class="relative isolate flex min-h-0 items-center overflow-hidden bg-white lg:min-h-[calc(100svh-5.625rem)]">

        {{-- Architectural Grid --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#f1f5f9_1px,transparent_1px),linear-gradient(to_bottom,#f1f5f9_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]">
        </div>

        {{-- Ambient Glow --}}
        <div aria-hidden="true" data-parallax="glow"
            class="pointer-events-none absolute left-1/4 top-0 h-96 w-96 rounded-full bg-blue-200/20 blur-[100px]">
        </div>

        <div aria-hidden="true"
            class="pointer-events-none absolute -right-32 top-1/3 h-96 w-96 rounded-full bg-sky-100/40 blur-[100px]">
        </div>

        <div aria-hidden="true"
            class="pointer-events-none absolute bottom-0 left-0 h-72 w-72 rounded-full bg-indigo-100/20 blur-[100px]">
        </div>

        <div
            class="container-page relative grid w-full items-center gap-10 py-12 sm:py-14 lg:grid-cols-[0.95fr_1.05fr] lg:gap-8 lg:py-10 lg:-translate-x-8">

            {{-- HERO CONTENT --}}
            <div class="relative z-10 min-w-0" data-parallax="text">

                <span data-hero-badge
                    class="inline-flex items-center gap-2 rounded-full border bg-white/80 px-4 py-2 text-xs font-bold tracking-wide shadow-[0_4px_20px_rgba(37,99,235,0.06)] backdrop-blur-md {{ $registrationState === 'open' ? 'border-blue-100 text-blue-700' : ($isUpcoming ? 'border-amber-200/80 text-amber-700' : 'border-slate-200 text-slate-600') }}">

                    @if ($registrationState === 'open')
                        <span class="relative flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-50"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-600"></span>
                        </span>
                        Pendaftaran Tahun {{ $displayYear }} Dibuka
                    @elseif ($isUpcoming)
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                        Akan Datang &bull; Pendaftaran Tahun {{ $displayYear }}
                    @else
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-slate-400"></span>
                        Pendaftaran Tahun {{ $displayYear }} Telah Ditutup
                    @endif
                </span>

                <h1 data-hero-title
                    class="mt-7 max-w-xl bg-gradient-to-r from-slate-900 via-blue-900 to-slate-800 bg-clip-text text-4xl font-extrabold leading-[1.12] tracking-tight text-transparent sm:text-5xl lg:text-6xl">
                    Bantuan Kartu Hebat Mahasiswa
                </h1>

                <p data-hero-description class="mt-7 max-w-xl text-base leading-8 text-slate-600 sm:text-lg">
                    Program bantuan pendidikan bagi mahasiswa berprestasi dan kurang mampu asal Kabupaten Murung Raya untuk
                    mewujudkan sumber daya manusia berkualitas.
                </p>

                <div data-hero-actions class="mt-9 flex flex-wrap gap-4">

                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="group inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.20)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.25)]">

                            Buka Dashboard

                            <x-icon name="arrow-right"
                                class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />

                        </a>
                    @elseif ($registrationState === 'open')
                        <a href="{{ route('register') }}"
                            class="group inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.20)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.25)]">

                            Daftar Sekarang

                            <x-icon name="arrow-right"
                                class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />

                        </a>
                    @elseif ($registrationState === 'upcoming_scheduled')
                        <a href="{{ route('register') }}"
                            class="group inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.20)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.25)]">

                            Buat Akun Lebih Awal

                            <x-icon name="arrow-right"
                                class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />

                        </a>
                    @elseif ($registrationState === 'closed')
                        <a href="{{ route('public.results') }}"
                            class="group inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.20)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.25)]">

                            Cek Hasil Seleksi

                            <x-icon name="arrow-right"
                                class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />

                        </a>
                    @else
                        <a href="{{ route('public.announcements.index') }}"
                            class="group inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.20)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.25)]">

                            Lihat Pengumuman

                            <x-icon name="arrow-right"
                                class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />

                        </a>
                    @endauth

                    <a href="#persyaratan"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white/80 px-7 py-4 text-sm font-bold text-slate-700 shadow-sm backdrop-blur-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">

                        Lihat Persyaratan

                    </a>

                </div>

                {{-- Subtle Trust Indicator --}}
                <div data-hero-trust class="mt-10 flex items-center gap-4">

                    <div class="flex -space-x-2">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-blue-100 text-blue-700">
                            <x-icon name="graduation-cap" class="h-4 w-4" />
                        </div>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-indigo-100 text-indigo-700">
                            <x-icon name="users" class="h-4 w-4" />
                        </div>

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-sky-100 text-sky-700">
                            <x-icon name="shield-check" class="h-4 w-4" />
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-800">
                            Program Bantuan Pendidikan
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            Kabupaten Murung Raya
                        </p>
                    </div>

                </div>

            </div>

            {{-- HERO IMAGE --}}
            <div data-parallax="visual"
                class="relative mx-auto w-full max-w-xl lg:-mr-8 lg:w-[125%] lg:max-w-[780px] lg:-translate-x-9">

                {{-- BACKGROUND VISUAL COMPOSITION --}}
                <div aria-hidden="true" class="pointer-events-none absolute inset-0">

                    {{-- Soft Ambient Light --}}
                    <div
                        class="absolute left-1/2 top-1/2 h-[75%] w-[75%] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-200/30 blur-[90px]">
                    </div>

                    <div class="absolute left-[18%] top-[18%] h-40 w-40 rounded-full bg-indigo-200/20 blur-[65px]">
                    </div>

                    {{-- Orbital Rings --}}
                    <div
                        class="absolute left-1/2 top-1/2 h-[78%] w-[78%] -translate-x-1/2 -translate-y-1/2 rotate-[-18deg] rounded-[50%] border border-blue-200/50">
                    </div>

                    <div
                        class="absolute left-1/2 top-1/2 h-[88%] w-[88%] -translate-x-1/2 -translate-y-1/2 rotate-[18deg] rounded-[50%] border border-dashed border-slate-200/80">
                    </div>

                    {{-- Decorative Orbit Nodes --}}
                    <span
                        class="absolute right-[15%] top-[13%] h-3 w-3 rounded-full border-[3px] border-white bg-blue-500 shadow-[0_0_0_5px_rgba(59,130,246,0.12)]">
                    </span>

                    <span
                        class="absolute bottom-[20%] left-[12%] h-2.5 w-2.5 rounded-full border-2 border-white bg-indigo-400 shadow-[0_0_0_5px_rgba(99,102,241,0.12)]">
                    </span>

                    {{-- Small Decorative Crosses --}}
                    <span class="absolute left-[22%] top-[12%] text-xl font-light text-blue-300/80">+</span>
                    <span class="absolute bottom-[16%] right-[18%] text-2xl font-light text-indigo-300/70">+</span>

                </div>

                {{-- Main Image --}}
                <img data-hero-visual src="{{ asset('images/Perayaan Wisuda Tiga Sahabat.png') }}"
                    alt="Portal Kartu Hebat Mahasiswa" class="relative z-10 block h-auto w-full object-contain">

                {{-- Bottom Accent --}}
                <div aria-hidden="true"
                    class="pointer-events-none absolute bottom-[5%] left-1/2 z-0 h-8 w-[55%] -translate-x-1/2 rounded-[50%] bg-blue-900/10 blur-2xl">
                </div>

            </div>

        </div>

        {{-- Bottom Transition --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-50/70 to-transparent">
        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- PERIODE PENDAFTARAN --}}
    {{-- ========================================================= --}}
    <section id="jadwal" class="relative z-10 bg-slate-50/70 pb-16 pt-12">

        <div class="container-page">

            <div data-reveal
                class="relative overflow-hidden rounded-[2rem] border border-white bg-white/85 px-7 py-8 shadow-[0_12px_45px_rgba(15,23,42,0.06)] backdrop-blur-xl md:grid md:grid-cols-[1fr_auto_auto] md:items-center md:gap-8 lg:px-10">

                {{-- Decorative Background --}}
                <div aria-hidden="true"
                    class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-blue-100/60 blur-[80px]">
                </div>

                <div aria-hidden="true"
                    class="pointer-events-none absolute bottom-0 left-1/3 h-32 w-32 rounded-full bg-indigo-100/40 blur-[60px]">
                </div>

                {{-- Period Information --}}
                <div class="relative">

                    <div class="flex items-center gap-2">
                        <span
                            class="h-1.5 w-1.5 rounded-full {{ $registrationState === 'open' ? 'bg-blue-600' : ($isUpcoming ? 'bg-amber-500' : 'bg-slate-400') }}"></span>

                        <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-blue-700">
                            Informasi Pendaftaran
                        </p>
                    </div>

                    @if ($registrationState === 'open')
                        <h2 class="mt-3 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                            Pendaftaran Beasiswa Tahun {{ $displayYear }}
                        </h2>

                        <p class="mt-2 text-sm text-slate-500">
                            @if (filled($periode->nama))
                                <span class="font-semibold text-slate-700">{{ $periode->nama }}</span> &bull;
                            @endif
                            {{ $periode->tanggal_mulai->translatedFormat('d F Y') }} sampai
                            {{ $periode->tanggal_selesai->translatedFormat('d F Y') }}
                        </p>
                    @elseif ($registrationState === 'upcoming_scheduled')
                        <h2 class="mt-3 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                            Pendaftaran Beasiswa Tahun {{ $displayYear }} Akan Datang
                        </h2>

                        <p class="mt-2 text-sm text-slate-500">
                            Dibuka mulai {{ $periode->tanggal_mulai->translatedFormat('d F Y') }} sampai
                            {{ $periode->tanggal_selesai->translatedFormat('d F Y') }}
                        </p>
                    @elseif ($registrationState === 'closed')
                        <h2 class="mt-3 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                            Pendaftaran Beasiswa Tahun {{ $displayYear }} Telah Ditutup
                        </h2>

                        <p class="mt-2 text-sm text-slate-500">
                            @if ($periode?->tanggal_mulai && $periode?->tanggal_selesai)
                                Masa pendaftaran ({{ $periode->tanggal_mulai->translatedFormat('d F Y') }} sampai
                                {{ $periode->tanggal_selesai->translatedFormat('d F Y') }}) telah berakhir.
                            @else
                                Masa penerimaan pendaftaran tahun {{ $displayYear }} telah berakhir.
                            @endif
                        </p>
                    @else
                        <h2 class="mt-3 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                            Pendaftaran Beasiswa Tahun {{ $displayYear }} Akan Datang
                        </h2>

                        <p class="mt-2 text-sm text-slate-500">
                            @if ($periode?->tanggal_mulai && $periode?->tanggal_selesai && $today->lte($periode->tanggal_selesai))
                                Rencana jadwal: {{ $periode->tanggal_mulai->translatedFormat('d F Y') }} sampai
                                {{ $periode->tanggal_selesai->translatedFormat('d F Y') }} (menunggu pembukaan resmi).
                            @else
                                Jadwal pembukaan pendaftaran tahun {{ $displayYear }} sedang dipersiapkan dan akan
                                diumumkan melalui portal ini.
                            @endif
                        </p>
                    @endif

                </div>

                {{-- Countdown / Schedule Status (Tanpa Informasi Kuota) --}}
                <div class="relative mt-8 flex items-center md:mt-0">

                    <div class="min-w-[110px] text-left md:text-center">

                        @if ($registrationState === 'open')
                            @if ($daysLeft > 0)
                                <p class="font-display text-3xl font-extrabold tracking-tight text-blue-700">
                                    {{ str_pad((string) $daysLeft, 2, '0', STR_PAD_LEFT) }}
                                </p>

                                <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">
                                    Hari tersisa
                                </p>
                            @else
                                <p class="font-display text-2xl font-extrabold tracking-tight text-amber-600">
                                    Hari Ini
                                </p>

                                <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">
                                    Hari terakhir
                                </p>
                            @endif
                        @elseif ($registrationState === 'upcoming_scheduled')
                            <p class="font-display text-3xl font-extrabold tracking-tight text-amber-600">
                                {{ str_pad((string) $daysUntilOpen, 2, '0', STR_PAD_LEFT) }}
                            </p>

                            <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">
                                Hari menuju buka
                            </p>
                        @elseif ($registrationState === 'closed')
                            <p class="font-display text-2xl font-extrabold tracking-tight text-slate-600">
                                Ditutup
                            </p>

                            <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">
                                Status pendaftaran
                            </p>
                        @else
                            <p class="font-display text-xl font-extrabold tracking-tight text-amber-600 sm:text-2xl">
                                Akan Datang
                            </p>

                            <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">
                                Belum dibuka
                            </p>
                        @endif

                    </div>

                </div>

                {{-- Registration / Action Button --}}
                @if ($registrationState === 'open' || $registrationState === 'upcoming_scheduled')
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="relative mt-8 inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.18)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.22)] md:mt-0">

                            {{ $registrationState === 'open' ? 'Lanjutkan Pendaftaran' : 'Buka Dashboard' }}

                            <x-icon name="arrow-right" class="h-4 w-4" />

                        </a>
                    @else
                        <a href="{{ route('register') }}"
                            class="relative mt-8 inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.18)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.22)] md:mt-0">

                            {{ $registrationState === 'open' ? 'Mulai Pendaftaran' : 'Buat Akun Lebih Awal' }}

                            <x-icon name="arrow-right" class="h-4 w-4" />

                        </a>
                    @endauth
                @elseif ($registrationState === 'closed')
                    <a href="{{ route('public.results') }}"
                        class="relative mt-8 inline-flex items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-7 py-4 text-sm font-bold text-slate-700 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 md:mt-0">

                        Cek Hasil Seleksi

                        <x-icon name="arrow-right" class="h-4 w-4" />

                    </a>
                @else
                    <a href="{{ route('public.announcements.index') }}"
                        class="relative mt-8 inline-flex items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-7 py-4 text-sm font-bold text-slate-700 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 md:mt-0">

                        Lihat Pengumuman

                        <x-icon name="arrow-right" class="h-4 w-4" />

                    </a>
                @endif

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- ALUR PENDAFTARAN --}}
    {{-- ========================================================= --}}
    <section id="informasi" class="relative overflow-hidden bg-white py-16 sm:py-20">

        <div aria-hidden="true"
            class="pointer-events-none absolute -left-40 top-1/4 h-96 w-96 rounded-full bg-blue-100/30 blur-[100px]">
        </div>

        <div class="container-page relative">

            <img data-reveal src="{{ asset('images/alur.jpg') }}" alt="Alur Pendaftaran Beasiswa"
                class="mx-auto h-auto w-full max-w-[1700px] rounded-3xl border border-slate-100 bg-white p-2 shadow-[0_15px_50px_rgba(15,23,42,0.06)]">

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- VIDEO TUTORIAL --}}
    {{-- ========================================================= --}}
    <section id="tutorial" class="relative overflow-hidden rounded-t-[3rem] bg-slate-50/70">

        {{-- Background Decoration --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute -right-32 top-0 h-96 w-96 rounded-full bg-blue-100/40 blur-[100px]">
        </div>

        <div aria-hidden="true"
            class="pointer-events-none absolute -left-32 bottom-0 h-80 w-80 rounded-full bg-indigo-100/30 blur-[100px]">
        </div>

        <div class="container-page relative py-16 lg:py-24">

            {{-- HEADER --}}
            <div data-reveal class="mx-auto max-w-3xl text-center">

                <p class="section-kicker">
                    Video Tutorial
                </p>

                <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Panduan Penggunaan Layanan
                </h2>

                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-500 sm:text-base">
                    Simak video tutorial berikut untuk mengetahui cara melakukan pendaftaran
                    Kartu Hebat Mahasiswa secara online.
                </p>

                <div class="mx-auto mt-6 h-1 w-16 rounded-full bg-gradient-to-r from-blue-600 to-sky-300">
                </div>

            </div>


            {{-- VIDEO --}}
            <div data-reveal class="mx-auto mt-12 max-w-5xl">

                <div
                    class="relative overflow-hidden rounded-[2rem] border-[6px] border-white bg-white shadow-[0_25px_70px_rgba(15,23,42,0.10)]">

                    <div class="aspect-video w-full overflow-hidden rounded-[1.5rem] bg-slate-100">

                        <iframe class="h-full w-full" src="https://www.youtube.com/embed/TNiGf6ZxFe4?si"
                            title="Tutorial Pendaftaran Kartu Hebat Mahasiswa" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen>
                        </iframe>

                    </div>

                </div>

            </div>


            {{-- INFORMASI SINGKAT --}}
            <div data-reveal
                class="mx-auto mt-9 flex max-w-5xl items-start gap-4 rounded-2xl border border-blue-100/80 bg-white/80 px-5 py-5 shadow-[0_8px_30px_rgba(37,99,235,0.04)] backdrop-blur-md sm:items-center sm:px-7">

                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">

                    <x-icon name="info" class="h-5 w-5" />

                </div>

                <div>

                    <p class="text-sm font-extrabold text-slate-900">
                        Tonton video sampai selesai
                    </p>

                    <p class="mt-1 text-xs leading-6 text-slate-500">
                        Pastikan Anda memahami setiap tahapan sebelum memulai proses pendaftaran.
                    </p>

                </div>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- PERSYARATAN DOKUMEN --}}
    {{-- ========================================================= --}}
    <section id="persyaratan" class="relative overflow-hidden rounded-t-[3rem] bg-white">

        <div aria-hidden="true"
            class="pointer-events-none absolute right-0 top-0 h-96 w-96 rounded-full bg-sky-100/30 blur-[100px]">
        </div>

        <div class="container-page relative py-16 lg:py-24">

            <div data-reveal class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">

                <div>

                    <p class="section-kicker">
                        Berkas wajib
                    </p>

                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                        Persyaratan Dokumen
                    </h2>

                    <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-500">
                        Pilih jenis beasiswa di bawah ini untuk melihat daftar dokumen wajib, format berkas yang diterima,
                        serta batas ukuran maksimal unggahan.
                    </p>

                </div>

                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-blue-700 transition-colors duration-300 hover:text-blue-900">

                        Buka dashboard pendaftaran

                        <x-icon name="arrow-right" class="h-4 w-4" />

                    </a>
                @elseif ($registrationState === 'open' || $registrationState === 'upcoming_scheduled')
                    <a href="{{ route('register') }}"
                        class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-blue-700 transition-colors duration-300 hover:text-blue-900">

                        Buat akun untuk mulai

                        <x-icon name="arrow-right" class="h-4 w-4" />

                    </a>
                @elseif ($registrationState === 'closed')
                    <a href="{{ route('public.results') }}"
                        class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-blue-700 transition-colors duration-300 hover:text-blue-900">

                        Cek hasil seleksi

                        <x-icon name="arrow-right" class="h-4 w-4" />

                    </a>
                @else
                    <a href="{{ route('public.announcements.index') }}"
                        class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-blue-700 transition-colors duration-300 hover:text-blue-900">

                        Pantau pengumuman resmi

                        <x-icon name="arrow-right" class="h-4 w-4" />

                    </a>
                @endauth

            </div>

            @if ($registrationState !== 'open')
                <div data-reveal
                    class="mt-8 flex items-start gap-4 rounded-2xl border px-5 py-4 sm:items-center sm:px-6 {{ $isUpcoming ? 'border-amber-200/80 bg-amber-50/70' : 'border-slate-200 bg-slate-50/90' }}">

                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $isUpcoming ? 'bg-amber-100 text-amber-700' : 'bg-slate-200/80 text-slate-600' }}">
                        <x-icon name="{{ $isUpcoming ? 'clock' : 'info' }}" class="h-5 w-5" />
                    </div>

                    <div class="min-w-0 flex-1">
                        @if ($registrationState === 'upcoming_scheduled')
                            <p class="text-sm font-extrabold text-slate-900">
                                Pendaftaran Tahun {{ $displayYear }} Akan Dibuka pada
                                {{ $periode->tanggal_mulai->translatedFormat('d F Y') }}
                            </p>
                            <p class="mt-0.5 text-xs leading-6 text-slate-600">
                                Anda sudah dapat mempelajari dan menyiapkan berkas persyaratan per jenis beasiswa di bawah
                                ini lebih awal sebelum pendaftaran dibuka.
                            </p>
                        @elseif ($registrationState === 'closed')
                            <p class="text-sm font-extrabold text-slate-900">
                                Pendaftaran Tahun {{ $displayYear }} Telah Ditutup
                            </p>
                            <p class="mt-0.5 text-xs leading-6 text-slate-600">
                                Daftar dokumen di bawah ini ditampilkan sebagai acuan persyaratan periode tahun
                                {{ $displayYear }} untuk gambaran persiapan periode berikutnya.
                            </p>
                        @else
                            <p class="text-sm font-extrabold text-slate-900">
                                Pendaftaran Tahun {{ $displayYear }} Belum Dibuka (Akan Datang)
                            </p>
                            <p class="mt-0.5 text-xs leading-6 text-slate-600">
                                Daftar dokumen di bawah ini ditampilkan sebagai acuan persiapan awal bagi calon pendaftar
                                dan dapat menyesuaikan ketentuan resmi saat pendaftaran dibuka.
                            </p>
                        @endif
                    </div>

                </div>
            @endif

            @if ($categories->isNotEmpty())
                @php
                    $tabGridCols = match (true) {
                        $categories->count() >= 4 => 'sm:grid-cols-2 xl:grid-cols-4',
                        $categories->count() === 3 => 'sm:grid-cols-3',
                        $categories->count() === 2 => 'sm:grid-cols-2',
                        default => 'grid-cols-1 max-w-md',
                    };
                @endphp

                <div data-reveal data-requirement-tabs class="mt-10">

                    {{-- CATEGORY TABS --}}
                    <div role="tablist" aria-label="Jenis Beasiswa"
                        class="grid gap-2.5 rounded-2xl border border-slate-200/80 bg-slate-50/90 p-2 {{ $tabGridCols }}">

                        @foreach ($categories as $category)
                            @php
                                $isActiveTab = $loop->first;
                                $appTypeValue = $category->application_type?->value;
                                $docCount = $category->jenisDokumens->count();
                            @endphp

                            <button type="button" role="tab"
                                id="persyaratan-tab-{{ $category->id }}"
                                aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                                aria-controls="persyaratan-panel-{{ $category->id }}"
                                tabindex="{{ $isActiveTab ? '0' : '-1' }}"
                                data-requirement-tab="{{ $category->id }}"
                                class="group flex items-center gap-3.5 rounded-xl border px-4 py-3.5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40 {{ $isActiveTab ? 'border-blue-200 bg-white text-blue-800 shadow-[0_8px_24px_rgba(37,99,235,0.09)]' : 'border-transparent bg-transparent text-slate-600 hover:bg-white/70 hover:text-slate-900' }}">

                                <span data-tab-icon
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors duration-200 {{ $isActiveTab ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/25' : 'border border-slate-200/80 bg-white text-slate-500 group-hover:border-blue-200 group-hover:text-blue-600' }}">
                                    @if ($appTypeValue === 'AKADEMIK')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                            stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                        </svg>
                                    @elseif ($appTypeValue === 'TIDAK_MAMPU')
                                        <x-icon name="users" class="h-5 w-5" />
                                    @elseif ($appTypeValue === 'DISABILITAS')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                            stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                        </svg>
                                    @elseif ($appTypeValue === 'NON_AKADEMIK')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                            stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                                        </svg>
                                    @else
                                        <x-icon name="document" class="h-5 w-5" />
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span data-tab-title
                                        class="block truncate text-xs font-extrabold leading-snug sm:text-sm {{ $isActiveTab ? 'text-slate-900' : 'text-slate-700 group-hover:text-slate-900' }}">
                                        {{ $category->nama }}
                                    </span>

                                    <span data-tab-badge
                                        class="mt-1 inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold {{ $isActiveTab ? 'bg-blue-50 text-blue-700' : 'bg-slate-200/70 text-slate-600' }}">
                                        {{ $docCount }} berkas wajib
                                    </span>
                                </span>

                            </button>
                        @endforeach

                    </div>

                    {{-- CATEGORY PANELS --}}
                    @foreach ($categories as $category)
                        <div role="tabpanel"
                            id="persyaratan-panel-{{ $category->id }}"
                            aria-labelledby="persyaratan-tab-{{ $category->id }}"
                            data-requirement-panel="{{ $category->id }}"
                            class="mt-7 {{ $loop->first ? '' : 'hidden' }}">

                            {{-- Category Description Banner --}}
                            <div
                                class="flex flex-col gap-3 rounded-2xl border border-blue-100/80 bg-blue-50/40 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">

                                <div class="min-w-0">
                                    <h3 class="text-sm font-extrabold text-slate-900 sm:text-base">
                                        {{ $category->nama }}
                                    </h3>

                                    <p class="mt-1 text-xs leading-6 text-slate-600">
                                        {{ filled($category->deskripsi) ? $category->deskripsi : 'Siapkan seluruh dokumen wajib berikut sebelum mengirim pengajuan beasiswa.' }}
                                    </p>
                                </div>

                                <span
                                    class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full border border-blue-200/80 bg-white px-3.5 py-1.5 text-xs font-extrabold text-blue-700 shadow-sm">
                                    <x-icon name="check-circle" class="h-4 w-4 text-blue-600" />
                                    {{ $category->jenisDokumens->count() }} Dokumen Wajib
                                </span>

                            </div>

                            {{-- Document Cards Grid --}}
                            @if ($category->jenisDokumens->isNotEmpty())
                                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                                    @foreach ($category->jenisDokumens as $dokumen)
                                        @php
                                            $formattedFormats = collect(explode(',', (string) $dokumen->format_file))
                                                ->map(fn (string $ext): string => strtoupper(trim($ext)))
                                                ->filter()
                                                ->implode(', ') ?: 'PDF';

                                            $maxKb = (int) $dokumen->maksimal_ukuran;
                                            $maxSizeLabel = $maxKb >= 1024
                                                ? rtrim(rtrim(number_format($maxKb / 1024, 1, ',', '.'), '0'), ',').' MB'
                                                : $maxKb.' KB';
                                        @endphp

                                        <div
                                            class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] transition-all duration-300 hover:-translate-y-0.5 hover:border-blue-400/50 hover:shadow-[0_20px_40px_rgba(37,99,235,0.06)]">

                                            {{-- Hover Accent --}}
                                            <div
                                                class="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-gradient-to-r from-blue-600 to-sky-300 transition-transform duration-500 group-hover:scale-x-100">
                                            </div>

                                            <div class="flex items-center justify-between gap-3">
                                                <div
                                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700 transition-all duration-300 group-hover:bg-blue-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-blue-600/20">

                                                    <x-icon name="document" class="h-5 w-5" />

                                                </div>

                                                <span
                                                    class="rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                    Berkas {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                                </span>
                                            </div>

                                            <h4 class="mt-5 text-base font-extrabold leading-snug text-slate-900">
                                                {{ $dokumen->nama }}
                                            </h4>

                                            <p class="mt-2.5 text-xs leading-6 text-slate-500">
                                                {{ filled($dokumen->deskripsi) ? $dokumen->deskripsi : 'Dokumen persyaratan wajib sesuai ketentuan pendaftaran.' }}
                                            </p>

                                            <div class="mt-auto pt-6">
                                                <div
                                                    class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 text-left">

                                                    <div>
                                                        <p
                                                            class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                            Format file
                                                        </p>

                                                        <p class="mt-1 text-xs font-extrabold text-slate-700">
                                                            {{ $formattedFormats }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <p
                                                            class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                            Ukuran maks.
                                                        </p>

                                                        <p class="mt-1 text-sm font-extrabold text-blue-700">
                                                            {{ $maxSizeLabel }}
                                                        </p>
                                                    </div>

                                                </div>
                                            </div>

                                        </div>
                                    @endforeach

                                </div>
                            @else
                                <div
                                    class="mt-6 rounded-2xl border border-slate-100 bg-slate-50/70 p-8 text-center text-sm text-slate-500">
                                    Belum ada rincian dokumen wajib pada jenis beasiswa ini.
                                </div>
                            @endif

                        </div>
                    @endforeach

                </div>
            @else
                <div data-reveal
                    class="mt-12 rounded-2xl border border-slate-100 bg-slate-50/70 p-8 text-center text-sm text-slate-500">
                    Data persyaratan belum tersedia.
                </div>
            @endif

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- PENGUMUMAN TERBARU --}}
    {{-- ========================================================= --}}
    <section class="relative overflow-hidden rounded-t-[3rem] bg-slate-50/70">

        {{-- Background Decoration --}}
        <div aria-hidden="true" data-reveal
            class="pointer-events-none absolute -right-32 top-0 h-96 w-96 rounded-full bg-blue-100/40 blur-[100px]">
        </div>

        <div class="container-page relative py-16 lg:py-24">

            {{-- HEADER --}}
            <div data-reveal class="text-center">

                <p class="section-kicker">
                    Informasi resmi
                </p>

                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">
                    Pengumuman Terbaru
                </h2>

                <p class="mx-auto mt-4 max-w-xl text-sm leading-7 text-slate-500">
                    Informasi seleksi dan jadwal teknis dipublikasikan melalui portal ini.
                </p>

                <div class="mx-auto mt-6 h-1 w-16 rounded-full bg-gradient-to-r from-blue-600 to-sky-300">
                </div>

            </div>


            {{-- ANNOUNCEMENTS --}}
            <div data-stagger="announcements" class="mx-auto mt-12 max-w-4xl space-y-4">

                @forelse($announcements as $announcement)
                    <a href="{{ route('public.announcements.show', $announcement->slug) }}" data-stagger-card
                        class="group flex items-center gap-5 rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_15px_35px_rgba(37,99,235,0.06)] sm:p-6 block">

                        <div
                            class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl border border-blue-100 bg-blue-50/70 transition-colors duration-300 group-hover:bg-blue-100">

                            <span class="font-display text-lg font-extrabold text-blue-800">
                                {{ $announcement->published_at ? $announcement->published_at->format('d') : '-' }}
                            </span>

                            <span class="text-[9px] font-bold uppercase tracking-wider text-blue-500">
                                {{ $announcement->published_at ? $announcement->published_at->translatedFormat('M') : '' }}
                            </span>

                        </div>

                        <div class="min-w-0 flex-1">

                            <div class="flex items-center gap-2">

                                <span
                                    class="rounded-md border border-blue-100 bg-blue-50 px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-wider text-blue-700">
                                    {{ $announcement->type }}
                                </span>

                                <span class="text-xs text-slate-400">
                                    Informasi
                                </span>

                            </div>

                            <h3
                                class="mt-2 truncate text-sm font-extrabold text-slate-900 transition-colors duration-300 group-hover:text-blue-700">
                                {{ $announcement->title }}
                            </h3>

                            <p class="mt-1 line-clamp-1 text-xs leading-5 text-slate-500">
                                {{ $announcement->excerpt }}
                            </p>

                        </div>

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition-all duration-300 group-hover:bg-blue-600 group-hover:text-white">

                            <x-icon name="chevron-right" class="h-5 w-5" />

                        </div>

                    </a>

                @empty

                    <div data-stagger-card
                        class="rounded-2xl border border-slate-100 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
                        Belum ada pengumuman aktif.
                    </div>
                @endforelse

            </div>


            {{-- BUTTON --}}
            <div data-reveal class="mt-10 flex flex-wrap items-center justify-center gap-3">

                <a href="{{ route('public.announcements.index') }}"
                    class="group inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50/70 px-6 py-3.5 text-sm font-bold text-blue-700 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:bg-blue-600 hover:text-white hover:shadow-md">

                    Lihat Semua Pengumuman

                    <span class="transition-transform duration-300 group-hover:translate-x-1">
                        &rarr;
                    </span>

                </a>

                <a href="{{ route('public.results') }}"
                    class="group inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">

                    Cek Pengumuman Hasil Seleksi

                    <span class="transition-transform duration-300 group-hover:translate-y-1">
                        &darr;
                    </span>

                </a>

            </div>

        </div>

    </section>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('[data-requirement-tabs]');
            if (!container) return;

            const tabs = Array.from(container.querySelectorAll('[data-requirement-tab]'));
            const panels = Array.from(container.querySelectorAll('[data-requirement-panel]'));
            if (!tabs.length || !panels.length) return;

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const activateTab = (nextTab, shouldFocus = false) => {
                const targetId = nextTab.getAttribute('data-requirement-tab');

                tabs.forEach((tab) => {
                    const isActive = tab === nextTab;
                    const iconBox = tab.querySelector('[data-tab-icon]');
                    const title = tab.querySelector('[data-tab-title]');
                    const badge = tab.querySelector('[data-tab-badge]');

                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    tab.setAttribute('tabindex', isActive ? '0' : '-1');

                    tab.className = [
                        'group flex items-center gap-3.5 rounded-xl border px-4 py-3.5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600/40',
                        isActive
                            ? 'border-blue-200 bg-white text-blue-800 shadow-[0_8px_24px_rgba(37,99,235,0.09)]'
                            : 'border-transparent bg-transparent text-slate-600 hover:bg-white/70 hover:text-slate-900',
                    ].join(' ');

                    if (iconBox) {
                        iconBox.className = [
                            'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors duration-200',
                            isActive
                                ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/25'
                                : 'border border-slate-200/80 bg-white text-slate-500 group-hover:border-blue-200 group-hover:text-blue-600',
                        ].join(' ');
                    }

                    if (title) {
                        title.className = [
                            'block truncate text-xs font-extrabold leading-snug sm:text-sm',
                            isActive ? 'text-slate-900' : 'text-slate-700 group-hover:text-slate-900',
                        ].join(' ');
                    }

                    if (badge) {
                        badge.className = [
                            'mt-1 inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold',
                            isActive ? 'bg-blue-50 text-blue-700' : 'bg-slate-200/70 text-slate-600',
                        ].join(' ');
                    }
                });

                panels.forEach((panel) => {
                    const isTarget = panel.getAttribute('data-requirement-panel') === targetId;
                    panel.classList.toggle('hidden', !isTarget);

                    if (isTarget && !prefersReducedMotion && typeof panel.animate === 'function') {
                        panel.animate(
                            [
                                { opacity: 0, transform: 'translateY(6px)' },
                                { opacity: 1, transform: 'translateY(0)' },
                            ],
                            { duration: 220, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' },
                        );
                    }
                });

                if (shouldFocus) {
                    nextTab.focus();
                }
            };

            tabs.forEach((tab, index) => {
                tab.addEventListener('click', () => activateTab(tab));

                tab.addEventListener('keydown', (event) => {
                    let nextIndex = null;

                    if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                        nextIndex = (index + 1) % tabs.length;
                    } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                        nextIndex = (index - 1 + tabs.length) % tabs.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = tabs.length - 1;
                    }

                    if (nextIndex !== null) {
                        event.preventDefault();
                        activateTab(tabs[nextIndex], true);
                    }
                });
            });
        });
    </script>
@endpush
