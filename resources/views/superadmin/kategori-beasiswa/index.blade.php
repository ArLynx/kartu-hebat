@extends('layouts.portal')

@section('title', 'Kategori Beasiswa')
@section('header', 'Kategori Beasiswa')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Master Data</p>
        <h1 class="mt-2 text-3xl font-extrabold">Kategori Beasiswa</h1>
        <p class="mt-2 text-sm text-slate-600">Atur kategori, kuota, periode, jalur, dan dokumen persyaratan.</p>
    </div>
    <a href="{{ route('superadmin.kategori-beasiswa.create') }}" class="btn-primary">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Kategori
    </a>
</div>

<section class="table-shell mt-7">
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
                    <tr><td colspan="8" class="py-12 text-center text-slate-500">Belum ada kategori beasiswa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($categories->hasPages())
        <div class="border-t border-slate-200 px-5 py-4">{{ $categories->links() }}</div>
    @endif
</section>
@endsection
