@extends('layouts.public')

@section('title', $announcement->title . ' — Pengumuman Kartu Hebat Mahasiswa')

@section('content')
<article class="relative isolate overflow-hidden bg-white py-10 sm:py-14">
    {{-- Architectural Grid --}}
    <div aria-hidden="true"
        class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#f1f5f9_1px,transparent_1px),linear-gradient(to_bottom,#f1f5f9_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_65%_55%_at_50%_0%,#000_70%,transparent_100%)]">
    </div>

    {{-- Ambient Glow --}}
    <div aria-hidden="true"
        class="pointer-events-none absolute left-1/4 top-0 h-96 w-96 rounded-full bg-blue-200/25 blur-[110px]">
    </div>

    <div class="container-page relative max-w-4xl">
        {{-- BREADCRUMB --}}
        <nav class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-blue-700 transition">Beranda</a>
            <span>&rsaquo;</span>
            <a href="{{ route('public.announcements.index') }}" class="hover:text-blue-700 transition">Pengumuman</a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 truncate max-w-xs sm:max-w-md">{{ $announcement->title }}</span>
        </nav>

        {{-- ARTIKEL HEADER --}}
        <header class="mt-8 border-b border-slate-100 pb-8">
            <div class="flex flex-wrap items-center gap-2.5">
                @if($announcement->type === 'informasi')
                    <span class="rounded-md border border-blue-100 bg-blue-50 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-blue-700">
                        Informasi Resmi
                    </span>
                @elseif($announcement->type === 'panduan')
                    <span class="rounded-md border border-emerald-100 bg-emerald-50 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-emerald-700">
                        Panduan Teknis
                    </span>
                @elseif($announcement->type === 'hasil')
                    <span class="rounded-md border border-purple-100 bg-purple-50 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-purple-700">
                        Hasil Seleksi
                    </span>
                @else
                    <span class="rounded-md border border-slate-100 bg-slate-50 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-700">
                        {{ strtoupper($announcement->type) }}
                    </span>
                @endif

                <span class="text-xs text-slate-400">
                    Diterbitkan pada {{ $announcement->published_at ? $announcement->published_at->translatedFormat('l, d F Y') : '-' }}
                </span>
            </div>

            <h1 class="mt-4 text-2xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                {{ $announcement->title }}
            </h1>

            <div class="mt-4 flex items-center gap-3 text-xs text-slate-500">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 font-bold text-xs">
                    KH
                </div>
                <div>
                    <span class="font-bold text-slate-700">Pemerintah Kabupaten Murung Raya</span>
                    <span class="block text-[11px] text-slate-400">Program Bantuan Kartu Hebat Mahasiswa</span>
                </div>
            </div>
        </header>

        {{-- RINGKASAN / EXCERPT (JIKA ADA) --}}
        @if($announcement->excerpt)
            <div class="mt-6 rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50/70 to-indigo-50/50 p-5 text-sm font-medium leading-relaxed text-blue-950 shadow-sm">
                {{ $announcement->excerpt }}
            </div>
        @endif

        {{-- ISI LENGKAP PENGUMUMAN (BODY) --}}
        <div class="mt-8 text-base leading-relaxed text-slate-700 whitespace-pre-line font-normal space-y-4">
{{ $announcement->body }}
        </div>

        {{-- LAMPIRAN BERKAS PDF (JIKA ADA) --}}
        @if($announcement->attachment_path)
            <div class="mt-10 rounded-2xl border-2 border-slate-200 bg-slate-50/80 p-5 sm:p-6 transition hover:border-blue-300 hover:bg-white hover:shadow-md">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                            <x-icon name="document" class="h-6 w-6" />
                        </div>
                        <div>
                            <span class="rounded bg-red-100 px-2 py-0.5 text-[10px] font-extrabold text-red-800 uppercase">
                                Dokumen Lampiran Resmi (PDF)
                            </span>
                            <h3 class="mt-1 text-sm font-bold text-slate-900">
                                Berkas Pengumuman / Surat Edaran Terkait
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Silakan unduh dokumen lengkap untuk membaca rincian lampiran atau mencetak pengumuman ini.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('public.announcements.download', $announcement->slug) }}" 
                       class="btn-primary shrink-0 !py-2.5 !px-5 text-xs bg-brand-700 hover:bg-brand-800 justify-center">
                        <x-icon name="download" class="h-4 w-4 mr-1 inline" />
                        Unduh Berkas PDF
                    </a>
                </div>
            </div>
        @endif

        {{-- FOOTER ARTIKEL & TOMBOL KEMBALI --}}
        <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-6">
            <a href="{{ route('public.announcements.index') }}" 
               class="btn-secondary !py-2 !px-4 text-xs">
                &larr; Lihat Semua Pengumuman
            </a>

            <a href="{{ route('home') }}" class="text-xs font-bold text-blue-700 hover:underline">
                Ke Halaman Utama Portal &rarr;
            </a>
        </div>

        {{-- REKOMENDASI PENGUMUMAN LAINNYA --}}
        @if($recentAnnouncements->isNotEmpty())
            <div class="mt-16 border-t border-slate-100 pt-10">
                <h3 class="text-lg font-extrabold text-navy-950">Pengumuman Lainnya</h3>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach($recentAnnouncements as $recent)
                        <a href="{{ route('public.announcements.show', $recent->slug) }}" 
                           class="group block rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                            <span class="text-[10px] font-bold uppercase text-blue-600">
                                {{ $recent->published_at ? $recent->published_at->translatedFormat('d M Y') : '' }}
                            </span>
                            <h4 class="mt-1 line-clamp-2 text-xs font-bold text-slate-900 group-hover:text-blue-700">
                                {{ $recent->title }}
                            </h4>
                            <p class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-slate-500">
                                {{ $recent->excerpt ?: Str::limit(strip_tags($recent->body), 60) }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</article>
@endsection
