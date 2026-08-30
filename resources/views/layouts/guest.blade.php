<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Autentikasi' }} — {{ config('kartu_hebat.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden overflow-hidden bg-navy-950 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 20%, #1470e8 0, transparent 30%), radial-gradient(circle at 80% 70%, #4f46e5 0, transparent 28%);"></div>
            <div class="relative">
                <a href="{{ route('home') }}"><x-brand light /></a>
            </div>
            <div class="relative max-w-xl">
                <p class="section-kicker !text-blue-300">Portal bantuan pendidikan</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight text-white">Akses layanan beasiswa daerah secara aman dan transparan.</h1>
                <p class="mt-5 text-base leading-7 text-slate-300">
                    Lengkapi data, unggah dokumen, dan pantau proses verifikasi lintas dinas melalui satu portal.
                </p>
            </div>
            <p class="relative text-xs text-slate-400">{{ config('kartu_hebat.government') }}</p>
        </section>

        <section class="flex items-center justify-center bg-slate-50 px-4 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-8 block lg:hidden"><x-brand /></a>
                {{ $slot }}
            </div>
        </section>
    </div>
    @livewireScripts
</body>
</html>
