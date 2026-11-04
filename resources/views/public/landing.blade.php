@extends('layouts.public')

@section('title', 'Kartu Hebat Mahasiswa — Kabupaten Murung Raya')

@section('content')
    @php
        $open = \Carbon\Carbon::parse(config('kartu_hebat.registration_open'));
        $close = \Carbon\Carbon::parse(config('kartu_hebat.registration_close'));
        $daysLeft = max(0, (int) now()->startOfDay()->diffInDays($close, false));
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
                    class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white/80 px-4 py-2 text-xs font-bold tracking-wide text-blue-700 shadow-[0_4px_20px_rgba(37,99,235,0.06)] backdrop-blur-md">

                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-50"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-600"></span>
                    </span>

                    Pendaftaran Periode {{ config('kartu_hebat.current_period') }}
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

                    <a href="{{ route('register') }}"
                        class="group inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.20)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.25)]">

                        Daftar Sekarang

                        <x-icon name="arrow-right"
                            class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />

                    </a>

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
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>

                        <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-blue-700">
                            Informasi Pendaftaran
                        </p>
                    </div>

                    <h2 class="mt-3 text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                        Periode Pendaftaran Semester Ganjil
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ $open->translatedFormat('d F Y') }} sampai {{ $close->translatedFormat('d F Y') }}
                    </p>

                </div>

                {{-- Countdown and Quota --}}
                <div class="relative mt-8 flex items-center gap-7 md:mt-0">

                    <div class="min-w-[85px] text-center">

                        <p class="font-display text-3xl font-extrabold tracking-tight text-blue-700">
                            {{ str_pad((string) $daysLeft, 2, '0', STR_PAD_LEFT) }}
                        </p>

                        <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">
                            Hari tersisa
                        </p>

                    </div>

                    <div class="h-12 w-px bg-slate-200"></div>

                    <div class="min-w-[85px] text-center">

                        <p class="font-display text-3xl font-extrabold tracking-tight text-slate-900">
                            {{ number_format(config('kartu_hebat.quota')) }}
                        </p>

                        <p class="mt-1 text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400">
                            Kuota
                        </p>

                    </div>

                </div>

                {{-- Registration Button --}}
                <a href="{{ route('register') }}"
                    class="relative mt-8 inline-flex items-center justify-center gap-3 rounded-xl bg-blue-700 px-7 py-4 text-sm font-bold text-white shadow-[0_8px_25px_rgba(29,78,216,0.18)] transition-all duration-300 hover:-translate-y-1 hover:bg-blue-800 hover:shadow-[0_14px_30px_rgba(29,78,216,0.22)] md:mt-0">

                    Mulai Pendaftaran

                    <x-icon name="arrow-right" class="h-4 w-4" />

                </a>

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
                        Format yang diterima PDF, JPG, JPEG, atau PNG. Batas ukuran mengikuti ketentuan pada setiap jenis
                        dokumen.
                    </p>

                </div>

                <a href="{{ route('register') }}"
                    class="inline-flex shrink-0 items-center gap-2 text-sm font-bold text-blue-700 transition-colors duration-300 hover:text-blue-900">

                    Buat akun untuk mulai

                    <x-icon name="arrow-right" class="h-4 w-4" />

                </a>

            </div>


            {{-- DOCUMENT CARDS --}}
            <div data-stagger class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                @forelse($documentTypes->where('is_required', true) as $type)
                    <div data-stagger-card
                        class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] transition-all duration-500 hover:border-blue-400/50 hover:shadow-[0_20px_40px_rgba(37,99,235,0.06)]">

                        {{-- Hover Accent --}}
                        <div
                            class="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-gradient-to-r from-blue-600 to-sky-300 transition-transform duration-500 group-hover:scale-x-100">
                        </div>

                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700 transition-all duration-300 group-hover:bg-blue-600 group-hover:text-white group-hover:shadow-lg group-hover:shadow-blue-600/20">

                            <x-icon name="document" class="h-5 w-5" />

                        </div>

                        <h3 class="mt-6 text-base font-extrabold leading-snug text-slate-900">
                            {{ $type->name }}
                        </h3>

                        <p class="mt-3 text-xs leading-6 text-slate-500">
                            {{ $type->description }}
                        </p>

                        <div class="mt-6 border-t border-slate-100 pt-4">

                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                Ukuran maksimal
                            </p>

                            <p class="mt-1 text-sm font-extrabold text-blue-700">
                                {{ number_format($type->max_size_kb / 1024, 0) }} MB
                            </p>

                        </div>

                    </div>

                @empty

                    <p class="text-sm text-slate-500">
                        Data persyaratan belum tersedia.
                    </p>
                @endforelse

            </div>

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
                    <article data-stagger-card
                        class="group flex items-center gap-5 rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_15px_35px_rgba(37,99,235,0.06)] sm:p-6">

                        <div
                            class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl border border-blue-100 bg-blue-50/70 transition-colors duration-300 group-hover:bg-blue-100">

                            <span class="font-display text-lg font-extrabold text-blue-800">
                                {{ $announcement->published_at->format('d') }}
                            </span>

                            <span class="text-[9px] font-bold uppercase tracking-wider text-blue-500">
                                {{ $announcement->published_at->translatedFormat('M') }}
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

                    </article>

                @empty

                    <div data-stagger-card
                        class="rounded-2xl border border-slate-100 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
                        Belum ada pengumuman aktif.
                    </div>
                @endforelse

            </div>


            {{-- BUTTON --}}
            <div data-reveal class="mt-10 text-center">

                <a href="{{ route('public.results') }}"
                    class="group inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md">

                    Cek pengumuman hasil seleksi

                    <span class="transition-transform duration-300 group-hover:translate-y-1">
                        ↓
                    </span>

                </a>

            </div>

        </div>

    </section>

@endsection
