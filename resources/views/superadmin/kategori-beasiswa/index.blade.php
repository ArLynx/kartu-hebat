@extends('layouts.portal')

@section('title', 'Kategori Beasiswa')
@section('header', 'Kategori Beasiswa')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Master Data</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-3xl font-extrabold text-navy-950">Kategori Beasiswa</h1>
            @if($activePeriod && (string)$selectedPeriodeId === (string)$activePeriod->id)
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Periode Aktif: {{ $activePeriod->nama }}
                </span>
            @endif
        </div>
        <p class="mt-2 text-sm text-slate-600">Atur kategori, kuota, periode, jalur, dan dokumen persyaratan.</p>
    </div>
    <a href="{{ route('superadmin.kategori-beasiswa.create') }}" class="btn-primary">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Kategori
    </a>
</div>

{{-- BILAH FILTER & PENCARIAN --}}
<div class="mt-7 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="GET" action="{{ route('superadmin.kategori-beasiswa.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode kategori..." class="form-input text-xs">
        </div>
        <div class="lg:col-span-3">
            <select name="periode_id" class="form-input text-xs">
                <option value="all" @selected($selectedPeriodeId === 'all')>Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" @selected((string)$selectedPeriodeId === (string)$period->id)>
                        {{ $period->nama }} ({{ $period->tahun }}) {{ $period->status === 'aktif' ? '• Aktif' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <select name="application_type" class="form-input text-xs">
                <option value="">Semua Jalur</option>
                @foreach(\App\Enums\ApplicationType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(request('application_type') === $type->value)>
                        {{ $type->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <select name="status" class="form-input text-xs">
                <option value="">Semua Status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div class="flex gap-2 lg:col-span-2">
            <button type="submit" class="btn-primary !py-2 flex-1 text-xs justify-center">
                <x-icon name="search" class="h-3.5 w-3.5" />
                Filter
            </button>
            @if(request()->hasAny(['search', 'periode_id', 'application_type', 'status']))
                <a href="{{ route('superadmin.kategori-beasiswa.index') }}" class="btn-secondary !py-2 text-xs" title="Reset filter">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

<section class="table-shell mt-5">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Periode</th>
                    <th>Jalur</th>
                    <th>Kuota</th>
                    <th>Dokumen</th>
                    <th>Pendaftar</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $category->nama }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $category->kode }} · Urutan {{ $category->urutan }}</p>
                        </td>
                        <td>{{ $category->periode?->nama ?? $category->periode?->tahun ?? '-' }}</td>
                        <td>{{ $category->application_type?->label() ?? '-' }}</td>
                        <td>{{ number_format($category->kuota) }}</td>
                        <td>
                            <span class="status-chip status-info">{{ $category->jenisDokumens->count() }} jenis</span>
                        </td>
                        <td>{{ number_format($category->pendaftarans_count) }}</td>
                        <td>
                            <span class="status-chip {{ $category->aktif ? 'status-success' : 'status-neutral' }}">
                                {{ $category->aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.kategori-beasiswa.edit', $category) }}" class="rounded-lg p-2 text-brand-700 hover:bg-brand-50" title="Edit">
                                    <x-icon name="edit" class="h-5 w-5" />
                                </a>
                                <form method="POST" action="{{ route('superadmin.kategori-beasiswa.destroy', $category) }}" onsubmit="return confirm('Hapus kategori beasiswa ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-red-600 hover:bg-red-50" title="Hapus">
                                        <x-icon name="trash" class="h-5 w-5" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-12 text-center text-slate-500">Belum ada data kategori beasiswa yang sesuai dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($categories->hasPages())
        <div class="border-t border-slate-200 px-5 py-4">{{ $categories->links() }}</div>
    @endif
</section>
@endsection
