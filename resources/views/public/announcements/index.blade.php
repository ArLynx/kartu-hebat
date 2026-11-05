@extends('layouts.public')

@section('title', 'Pengumuman & Informasi Beasiswa — Kartu Hebat Mahasiswa')

@section('content')
<section class="relative isolate overflow-hidden bg-white py-12 sm:py-16">
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

    <div class="container-page relative">
        {{-- HEADER --}}
        <div data-reveal class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white/80 px-4 py-2 shadow-[0_4px_20px_rgba(37,99,235,0.06)] backdrop-blur-md">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-50"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-blue-600"></span>
                </span>
                <span class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-blue-700">
                    Pusat Informasi Resmi
                </span>
            </span>

            <h1 class="mt-5 bg-gradient-to-r from-slate-900 via-blue-900 to-slate-800 bg-clip-text text-3xl font-extrabold leading-tight tracking-tight text-transparent sm:text-4xl lg:text-5xl">
                Pengumuman & Surat Edaran
            </h1>

            <p class="mt-4 text-sm leading-relaxed text-slate-500 sm:text-base">
                Temukan informasi resmi, petunjuk teknis pengunggahan berkas, dan jadwal kegiatan program Kartu Hebat Mahasiswa Kabupaten Murung Raya.
            </p>

            <div class="mx-auto mt-6 h-1 w-16 rounded-full bg-gradient-to-r from-blue-600 to-sky-300"></div>
        </div>

        {{-- CALLOUT CEK HASIL SELEKSI --}}
        <div data-reveal class="mx-auto mt-8 max-w-3xl rounded-2xl border border-blue-200/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-white p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3.5 text-center sm:text-left">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm">
                    <x-icon name="search" class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-900">Ingin Memeriksa Hasil Seleksi Beasiswa?</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Cek status penerimaan dengan memasukkan nomor pendaftaran tiket atau NIK Anda.</p>
                </div>
            </div>
            <a href="{{ route('public.results') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 shrink-0">
                <span>Cek Hasil Seleksi</span>
                <span>&rarr;</span>
            </a>
        </div>

        {{-- FILTER & SEARCH --}}
        <div data-reveal class="mx-auto mt-8 max-w-3xl">
            <form method="GET" action="{{ route('public.announcements.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <x-icon name="search" class="h-4 w-4" />
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari judul pengumuman, panduan, atau informasi..." 
                           class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-xs font-medium text-slate-800 placeholder-slate-400 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="flex gap-2">
                    <select name="type" class="rounded-xl border border-slate-200 bg-white py-3 px-3.5 text-xs font-semibold text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua Kategori</option>
                        <option value="informasi" @selected(request('type') === 'informasi')>Informasi</option>
                        <option value="panduan" @selected(request('type') === 'panduan')>Panduan Teknis</option>
                        <option value="hasil" @selected(request('type') === 'hasil')>Hasil Seleksi</option>
                    </select>

                    <button type="submit" class="btn-primary !py-2.5 !px-5 text-xs">
                        Cari
                    </button>

                    @if(request()->hasAny(['search', 'type']))
                        <a href="{{ route('public.announcements.index') }}" class="btn-secondary !py-2.5 !px-4 text-xs" title="Reset filter">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- ANNOUNCEMENTS LIST --}}
        <div data-stagger="announcements" class="mx-auto mt-10 max-w-4xl space-y-4">
            @forelse($announcements as $announcement)
                <a href="{{ route('public.announcements.show', $announcement->slug) }}" data-stagger-card
                   class="group flex items-center gap-5 rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-[0_15px_35px_rgba(37,99,235,0.06)] sm:p-6 block">
                    {{-- KOTAK KALENDER TANGGAL --}}
                    <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl border border-blue-100 bg-blue-50/70 transition-colors duration-300 group-hover:bg-blue-100">
                        <span class="font-display text-lg font-extrabold text-blue-800">
                            {{ $announcement->published_at ? $announcement->published_at->format('d') : '-' }}
                        </span>
                        <span class="text-[9px] font-bold uppercase tracking-wider text-blue-500">
                            {{ $announcement->published_at ? $announcement->published_at->translatedFormat('M') : '' }}
                        </span>
                    </div>

                    {{-- INFORMASI KONTEN --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            @if($announcement->type === 'informasi')
                                <span class="rounded-md border border-blue-100 bg-blue-50 px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-blue-700">
                                    Informasi
                                </span>
                            @elseif($announcement->type === 'panduan')
                                <span class="rounded-md border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-emerald-700">
                                    Panduan
                                </span>
                            @elseif($announcement->type === 'hasil')
                                <span class="rounded-md border border-purple-100 bg-purple-50 px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-purple-700">
                                    Hasil Seleksi
                                </span>
                            @else
                                <span class="rounded-md border border-slate-100 bg-slate-50 px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-slate-700">
                                    {{ strtoupper($announcement->type) }}
                                </span>
                            @endif

                            @if($announcement->attachment_path)
                                <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">
                                    <x-icon name="document" class="h-3 w-3 text-red-600" />
                                    PDF Lampiran
                                </span>
                            @endif

                            <span class="text-xs text-slate-400">
                                &bull; {{ $announcement->published_at ? $announcement->published_at->translatedFormat('d F Y') : '' }}
                            </span>
                        </div>

                        <h2 class="mt-2 text-base font-extrabold text-slate-900 transition-colors duration-300 group-hover:text-blue-700">
                            {{ $announcement->title }}
                        </h2>

                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                            {{ $announcement->excerpt ?: Str::limit(strip_tags($announcement->body), 120) }}
                        </p>
                    </div>

                    {{-- PANAH CHEVRON --}}
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition-all duration-300 group-hover:bg-blue-600 group-hover:text-white">
                        <x-icon name="chevron-right" class="h-5 w-5" />
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-slate-100 bg-white p-12 text-center text-sm text-slate-500 shadow-sm">
                    <p class="font-bold text-slate-700">Tidak ada pengumuman yang sesuai.</p>
                    <p class="mt-1 text-xs text-slate-400">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
                </div>
            @endforelse
        </div>

        {{-- PAGINASI --}}
        @if($announcements->hasPages())
            <div class="mx-auto mt-10 max-w-4xl">
                {{ $announcements->links() }}
            </div>
        @endif

        {{-- TOMBOL KEMBALI KE BERANDA --}}
        <div data-reveal class="mt-12 text-center">
            <a href="{{ route('home') }}" class="group inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-blue-700">
                <span>&larr;</span> Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</section>
@endsection
