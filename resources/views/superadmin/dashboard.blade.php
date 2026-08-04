@extends('layouts.portal')

@section('title', 'Dashboard Superadmin')
@section('header', 'Dashboard Superadmin')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Master Data</p>
        <h1 class="mt-2 text-3xl font-extrabold">Pengaturan Beasiswa</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Kelola kategori beasiswa dan jenis dokumen yang digunakan oleh sistem pendaftaran.
        </p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('superadmin.kategori-beasiswa.create') }}" class="btn-secondary">
            <x-icon name="plus" class="h-4 w-4" />
            Kategori Baru
        </a>
        <a href="{{ route('superadmin.document-types.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Document Type Baru
        </a>
    </div>
</div>

<div class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['tag', 'Kategori Beasiswa', $stats['categories'], $stats['activeCategories'].' kategori aktif'],
        ['document', 'Document Types', $stats['documentTypes'], $stats['activeDocumentTypes'].' jenis aktif'],
        ['folder', 'Jenis Terintegrasi', $stats['integratedDocumentTypes'], 'Dipakai alur pendaftaran'],
        ['clock', 'Periode', $stats['periods'], 'Seluruh periode beasiswa'],
    ] as [$icon, $label, $value, $description])
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
                    <p class="metric-number mt-2">{{ number_format($value) }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ $description }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                    <x-icon :name="$icon" class="h-5 w-5" />
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-7 grid gap-6 xl:grid-cols-2">
    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h2 class="text-lg font-bold">Kategori Terbaru</h2>
                <p class="mt-1 text-xs text-slate-500">Kategori yang terakhir diperbarui.</p>
            </div>
            <a href="{{ route('superadmin.kategori-beasiswa.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Lihat semua</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentCategories as $category)
                <a href="{{ route('superadmin.kategori-beasiswa.edit', $category) }}" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                        <x-icon name="tag" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-slate-900">{{ $category->nama }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $category->kode }} · {{ $category->periode?->nama ?? $category->periode?->tahun }}</p>
                    </div>
                    <span class="status-chip {{ $category->aktif ? 'status-success' : 'status-neutral' }}">
                        {{ $category->aktif ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-500">Belum ada kategori beasiswa.</p>
            @endforelse
        </div>
    </section>

    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h2 class="text-lg font-bold">Document Types Terbaru</h2>
                <p class="mt-1 text-xs text-slate-500">Master dokumen yang terakhir diperbarui.</p>
            </div>
            <a href="{{ route('superadmin.document-types.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Lihat semua</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentDocumentTypes as $type)
                <a href="{{ route('superadmin.document-types.edit', $type) }}" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                        <x-icon name="document" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-slate-900">{{ $type->name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $type->code }} · Maks. {{ number_format($type->max_size_kb) }} KB</p>
                    </div>
                    <span class="status-chip {{ $type->is_active ? 'status-success' : 'status-neutral' }}">
                        {{ $type->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-500">Belum ada document type.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
