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
<body class="min-h-screen bg-[#fbf8fc]">
    <header class="sticky top-0 z-40 border-b border-slate-200/90 bg-white/95 backdrop-blur">
        <div class="container-page flex h-18 items-center justify-between py-3">
            <a href="{{ route('home') }}" aria-label="Beranda Kartu Hebat Mahasiswa">
                <x-brand />
            </a>

            <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 lg:flex">
                <a href="{{ route('home') }}" class="hover:text-brand-600">Beranda</a>
                <a href="{{ route('home') }}#informasi" class="hover:text-brand-600">Informasi</a>
                <a href="{{ route('home') }}#persyaratan" class="hover:text-brand-600">Persyaratan</a>
                <a href="{{ route('home') }}#jadwal" class="hover:text-brand-600">Jadwal</a>
                <a href="{{ route('public.results') }}" class="hover:text-brand-600">Pengumuman</a>
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary !px-4 !py-2">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary !px-4 !py-2">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-blue !px-4 !py-2">Daftar</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-slate-100">
        <div class="container-page grid gap-10 py-12 md:grid-cols-3">
            <div class="md:col-span-1">
                <x-brand />
                <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600">
                    Program bantuan pendidikan untuk mendukung putra-putri daerah memperoleh pendidikan tinggi yang berkualitas.
                </p>
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider">Tautan Layanan</h3>
                <div class="mt-4 space-y-2 text-sm text-slate-600">
                    <a href="{{ route('public.results') }}" class="block hover:text-brand-600">Cek hasil seleksi</a>
                    <a href="{{ route('login') }}" class="block hover:text-brand-600">Masuk ke portal</a>
                    <a href="{{ route('register') }}" class="block hover:text-brand-600">Pendaftaran mahasiswa</a>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider">Pemerintah Daerah</h3>
                <p class="mt-4 text-sm leading-6 text-slate-600">{{ config('kartu_hebat.government') }}</p>
                <p class="text-sm text-slate-500">Layanan Kartu Hebat Mahasiswa</p>
            </div>
        </div>
        <div class="border-t border-slate-200">
            <div class="container-page py-5 text-xs text-slate-500">
                © {{ now()->year }} {{ config('kartu_hebat.government') }}. Hak cipta dilindungi.
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
