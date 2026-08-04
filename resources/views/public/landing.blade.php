@extends('layouts.public')

@section('title', 'Kartu Hebat Mahasiswa — Kabupaten Murung Raya')

@section('content')
@php
    $open = \Carbon\Carbon::parse(config('kartu_hebat.registration_open'));
    $close = \Carbon\Carbon::parse(config('kartu_hebat.registration_close'));
    $daysLeft = max(0, (int) now()->startOfDay()->diffInDays($close, false));
@endphp

<section class="relative overflow-hidden bg-gradient-to-br from-[#fbf8fc] via-[#f8f8ff] to-blue-50">
    <div class="absolute inset-0 opacity-50" style="background-image: radial-gradient(circle at 80% 15%, rgba(20,112,232,.15), transparent 28%);"></div>
    <div class="container-page relative grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-24">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white">
                <x-icon name="shield" class="h-4 w-4" />
                Pendaftaran Periode {{ config('kartu_hebat.current_period') }}
            </span>
            <h1 class="mt-7 max-w-2xl text-4xl font-extrabold leading-[1.14] tracking-tight sm:text-5xl lg:text-6xl">
                Bantuan Kartu Hebat Mahasiswa
            </h1>
            <p class="mt-6 max-w-xl text-base leading-7 text-slate-600 sm:text-lg">
                Program bantuan pendidikan bagi mahasiswa berprestasi dan kurang mampu asal Kabupaten Murung Raya untuk mewujudkan sumber daya manusia berkualitas.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="btn-primary">
                    Daftar Sekarang
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
                <a href="#persyaratan" class="btn-secondary">Lihat Persyaratan</a>
            </div>
        </div>

        <div class="relative mx-auto w-full max-w-xl">
            <div class="absolute -inset-6 rounded-[2rem] bg-brand-100/60 blur-2xl"></div>
            <div class="relative overflow-hidden rounded-[1.6rem] border-[10px] border-white bg-white shadow-lift">
                <img src="{{ asset('images/hero-students.png') }}" alt="Portal Kartu Hebat Mahasiswa" class="aspect-[1.08] w-full object-cover object-center">
            </div>
            <div class="absolute -bottom-5 -left-4 card flex items-center gap-3 px-5 py-4 sm:left-2">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-600 text-white">
                    <x-icon name="users" class="h-6 w-6" />
                </div>
                <div>
                    <p class="text-sm font-bold text-navy-900">Beasiswa Aktif</p>
                    <p class="text-xs text-slate-500">{{ number_format($registeredCount) }} mahasiswa terdaftar</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="jadwal" class="container-page -mt-1 pb-16">
    <div class="grid items-center gap-6 rounded-2xl bg-navy-800 px-7 py-7 text-white shadow-lift md:grid-cols-[1fr_auto_auto] lg:px-10">
        <div>
            <h2 class="text-xl font-bold text-white">Periode Pendaftaran Semester Ganjil</h2>
            <p class="mt-2 text-sm text-slate-300">
                {{ $open->translatedFormat('d F Y') }} sampai {{ $close->translatedFormat('d F Y') }}
            </p>
        </div>
        <div class="flex items-center gap-7">
            <div class="text-center">
                <p class="font-display text-3xl font-extrabold">{{ str_pad((string) $daysLeft, 2, '0', STR_PAD_LEFT) }}</p>
                <p class="text-[10px] uppercase tracking-widest text-slate-300">Hari tersisa</p>
            </div>
            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
            <div class="text-center">
                <p class="font-display text-3xl font-extrabold">{{ number_format(config('kartu_hebat.quota')) }}</p>
                <p class="text-[10px] uppercase tracking-widest text-slate-300">Kuota</p>
            </div>
        </div>
        <a href="{{ route('register') }}" class="btn-blue justify-center">Mulai Pendaftaran</a>
    </div>
</section>

<section id="informasi" class="container-page py-16">
    <div class="text-center">
        <p class="section-kicker">Proses yang terukur</p>
        <h2 class="mt-3 text-3xl font-bold">Tahapan Pendaftaran</h2>
        <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-600">
            Empat langkah utama untuk mengajukan bantuan dan memantau hasil verifikasi.
        </p>
    </div>

    <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['user', 'Buat Akun', 'Registrasi menggunakan nama, email aktif, dan kata sandi yang aman.'],
            ['form', 'Isi Formulir', 'Lengkapi data kependudukan, pendidikan, dan sosial ekonomi.'],
            ['upload', 'Unggah Dokumen', 'Unggah berkas persyaratan sesuai format dan batas ukuran.'],
            ['shield', 'Pantau Hasil', 'Ikuti status verifikasi desa, kecamatan, dinas, dan kabupaten.'],
        ] as $index => [$icon, $title, $description])
            <div class="relative text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 text-navy-900">
                    <x-icon :name="$icon" class="h-6 w-6" />
                </div>
                <span class="mt-5 inline-block text-xs font-bold text-brand-600">LANGKAH {{ $index + 1 }}</span>
                <h3 class="mt-2 text-lg font-bold">{{ $title }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
            </div>
        @endforeach
    </div>
</section>

<section id="persyaratan" class="border-y border-slate-200 bg-slate-100/70">
    <div class="container-page py-16">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-kicker">Berkas wajib</p>
                <h2 class="mt-3 text-3xl font-bold">Persyaratan Dokumen</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    Format yang diterima PDF, JPG, JPEG, atau PNG. Batas ukuran mengikuti ketentuan pada setiap jenis dokumen.
                </p>
            </div>
            <a href="{{ route('register') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Buat akun untuk mulai →</a>
        </div>

        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @forelse($documentTypes->where('is_required', true) as $type)
                <div class="card-hover border-b-4 border-b-brand-500 p-6">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-600 text-white">
                        <x-icon name="document" class="h-5 w-5" />
                    </div>
                    <h3 class="mt-5 text-base font-bold">{{ $type->name }}</h3>
                    <p class="mt-2 text-xs leading-5 text-slate-600">{{ $type->description }}</p>
                    <p class="mt-4 text-[11px] font-semibold text-slate-500">Maks. {{ number_format($type->max_size_kb / 1024, 0) }} MB</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Data persyaratan belum tersedia.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="container-page py-20">
    <div class="text-center">
        <p class="section-kicker">Informasi resmi</p>
        <h2 class="mt-3 text-3xl font-bold">Pengumuman Terbaru</h2>
        <p class="mt-3 text-sm text-slate-600">Informasi seleksi dan jadwal teknis dipublikasikan melalui portal ini.</p>
    </div>

    <div class="mx-auto mt-10 max-w-4xl space-y-4">
        @forelse($announcements as $announcement)
            <article class="card flex items-center gap-5 p-5">
                <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-slate-100">
                    <span class="font-display text-lg font-extrabold text-navy-900">{{ $announcement->published_at->format('d') }}</span>
                    <span class="text-[9px] uppercase text-slate-500">{{ $announcement->published_at->translatedFormat('M') }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="rounded bg-brand-50 px-2 py-0.5 text-[9px] font-bold uppercase text-brand-700">{{ $announcement->type }}</span>
                        <span class="text-xs text-slate-500">Informasi</span>
                    </div>
                    <h3 class="mt-1 truncate text-sm font-bold">{{ $announcement->title }}</h3>
                    <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ $announcement->excerpt }}</p>
                </div>
                <x-icon name="chevron-right" class="h-5 w-5 text-slate-400" />
            </article>
        @empty
            <div class="card p-8 text-center text-sm text-slate-500">Belum ada pengumuman aktif.</div>
        @endforelse
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('public.results') }}" class="text-sm font-semibold text-navy-900 hover:text-brand-600">Cek pengumuman hasil seleksi ↓</a>
    </div>
</section>
@endsection
