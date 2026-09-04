<!DOCTYPE html>
<html lang="id" x-data="{ sidebarOpen: false }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal') — {{ config('kartu_hebat.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>

<body class="min-h-screen bg-[#f8f7fb]">
    @php
        $user = auth()->user();
        $isStudent = $user->isStudent();
    @endphp

    <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false">
    </div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-navy-950 px-4 py-5 text-white transition-transform duration-200 lg:translate-x-0"
        :class="{ 'translate-x-0': sidebarOpen }">
        <div class="flex items-center justify-between px-1">
            <x-brand light />
            <button class="rounded-lg p-2 text-slate-300 hover:bg-white/10 lg:hidden" @click="sidebarOpen = false">
                <x-icon name="x" />
            </button>
        </div>

        <nav class="mt-10 flex-1 space-y-1.5 overflow-y-auto">
            @if ($isStudent)

                {{-- Dashboard --}}
                <x-sidebar-link :href="route('mahasiswa.dashboard')" :active="request()->routeIs('mahasiswa.dashboard')" icon="home">
                    Beranda Beasiswa
                </x-sidebar-link>


                {{-- Buat Pendaftaran --}}
                <x-sidebar-link :href="route('mahasiswa.pendaftaran.create')" :active="request()->routeIs('mahasiswa.pendaftaran.*')" icon="form">
                    Pendaftaran
                </x-sidebar-link>


                {{-- Submenu Pendaftaran --}}
                <div class="ml-4 border-l border-slate-200 pl-3">

                    <x-sidebar-link :href="route('mahasiswa.data-pribadi.index')" :active="request()->routeIs('mahasiswa.data-pribadi.*')" icon="user">
                        Data Pribadi
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('mahasiswa.pendidikan.index')" :active="request()->routeIs('mahasiswa.pendidikan.*')" icon="building">
                        Pendidikan
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('mahasiswa.prestasi.index')" :active="request()->routeIs('mahasiswa.prestasi.*')" icon="chart">
                        Prestasi
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('mahasiswa.orang-tua.index')" :active="request()->routeIs('mahasiswa.orang-tua.*')" icon="users">
                        Orang Tua
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('mahasiswa.dokumen.index')" :active="request()->routeIs('mahasiswa.dokumen.*')" icon="folder">
                        Dokumen
                    </x-sidebar-link>

                    {{-- Review --}}
                    <x-sidebar-link :href="route('mahasiswa.review.index')" :active="request()->routeIs('mahasiswa.review.*')" icon="check">
                        Review
                    </x-sidebar-link>

                    {{-- Submit --}}
                    <x-sidebar-link :href="route('mahasiswa.submit.index')" :active="request()->routeIs('mahasiswa.submit.*')" icon="send">
                        Submit
                    </x-sidebar-link>

                </div>

                {{-- Laporan Pertanggungjawaban --}}
                <x-sidebar-link :href="route('mahasiswa.lpj.index')" :active="request()->routeIs('mahasiswa.lpj.*')" icon="folder">
                    Laporan Pertanggungjawaban
                </x-sidebar-link>

                {{-- Pengumuman --}}
                <x-sidebar-link :href="route('public.results')" :active="request()->routeIs('public.results')" icon="announcement">
                    Pengumuman
                </x-sidebar-link>
            @elseif($user->isSuperadmin())
                <x-sidebar-link :href="route('superadmin.dashboard')" :active="request()->routeIs('superadmin.dashboard')" icon="home">Dashboard</x-sidebar-link>
                <x-sidebar-link :href="route('superadmin.kategori-beasiswa.index')" :active="request()->routeIs('superadmin.kategori-beasiswa.*')" icon="tag">Kategori Beasiswa</x-sidebar-link>
                <x-sidebar-link :href="route('superadmin.document-types.index')" :active="request()->routeIs('superadmin.document-types.*')" icon="document">Document Types</x-sidebar-link>
                <x-sidebar-link :href="route('superadmin.operators.index')" :active="request()->routeIs('superadmin.operators.*')" icon="users">Operator</x-sidebar-link>
            @else
                <x-sidebar-link :href="route('operator.dashboard')" :active="request()->routeIs('operator.dashboard')" icon="home">Dashboard</x-sidebar-link>
                <x-sidebar-link :href="route('operator.applications.index')" :active="request()->routeIs('operator.applications.*')" icon="form">Antrean Pengajuan</x-sidebar-link>
                @if ($user->hasRole('operator_kecamatan', 'operator_kabupaten'))
                    <a href="{{ route('operator.reports.recap') }}" class="sidebar-link">
                        <x-icon name="download" class="h-5 w-5" />
                        <span>Rekap Pengajuan</span>
                    </a>
                @endif
                @if ($user->hasRole('operator_kabupaten'))
                    <x-sidebar-link :href="route('operator.reconciliation')" :active="request()->routeIs('operator.reconciliation')" icon="shield">Rekonsiliasi
                        Dinas</x-sidebar-link>
                    <x-sidebar-link :href="route('operator.selection')" :active="request()->routeIs('operator.selection*')" icon="users">Penetapan
                        Penerima</x-sidebar-link>
                    <a href="{{ route('operator.reports.excel') }}" class="sidebar-link">
                        <x-icon name="download" class="h-5 w-5" />
                        <span>Rekap Seleksi</span>
                    </a>
                @endif
            @endif

            <div class="my-5 border-t border-white/10"></div>
            <x-sidebar-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')" icon="bell">
                Notifikasi
                @if ($unreadNotifications > 0)
                    <span
                        class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] text-white">{{ $unreadNotifications }}</span>
                @endif
            </x-sidebar-link>
            <x-sidebar-link :href="route('profile.show')" :active="request()->routeIs('profile.show')" icon="user">Profil & Keamanan</x-sidebar-link>
        </nav>

        <div class="border-t border-white/10 pt-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="sidebar-link w-full">
                    <x-icon name="logout" class="h-5 w-5" />
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-64">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
                <button class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true">
                    <x-icon name="menu" />
                </button>

                <div>
                    <p class="font-display text-lg font-bold text-navy-900">@yield('header', 'Dashboard')</p>
                    <p class="hidden text-xs text-slate-500 sm:block">{{ $user->role->label() }}</p>
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <a href="{{ route('notifications.index') }}"
                        class="relative rounded-full p-2 text-slate-600 hover:bg-slate-100">
                        <x-icon name="bell" />
                        @if ($unreadNotifications > 0)
                            <span
                                class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-red-500"></span>
                        @endif
                    </a>
                    <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-700">
                            {{ mb_substr($user->name, 0, 1) }}
                        </div>
                        <div class="hidden leading-tight sm:block">
                            <p class="max-w-44 truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->role->label() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-7 sm:px-6 lg:px-8">
            <x-flash />
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white px-4 py-7 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ now()->year }} {{ config('kartu_hebat.government') }}</p>
                <p>Sistem Kartu Hebat Mahasiswa</p>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>
