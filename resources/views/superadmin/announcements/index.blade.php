@extends('layouts.portal')

@section('title', 'Kelola Pengumuman')
@section('header', 'Kelola Pengumuman')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Portal Publik</p>
        <h1 class="mt-2 text-3xl font-extrabold text-navy-950">Kelola Pengumuman</h1>
        <p class="mt-2 text-sm text-slate-600">Publikasikan informasi resmi, panduan teknis, dan surat edaran beasiswa ke halaman depan.</p>
    </div>
    <a href="{{ route('superadmin.announcements.create') }}" class="btn-primary shrink-0">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Pengumuman
    </a>
</div>

{{-- FILTER & PENCARIAN --}}
<div class="mt-7 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="GET" action="{{ route('superadmin.announcements.index') }}" class="grid gap-3 sm:grid-cols-12">
        <div class="sm:col-span-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul atau ringkasan pengumuman..." class="form-input text-xs">
        </div>
        <div class="sm:col-span-3">
            <select name="type" class="form-input text-xs">
                <option value="">Semua Kategori</option>
                <option value="informasi" @selected(request('type') === 'informasi')>Informasi</option>
                <option value="panduan" @selected(request('type') === 'panduan')>Panduan Teknis</option>
                <option value="hasil" @selected(request('type') === 'hasil')>Pengumuman Hasil</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <select name="status" class="form-input text-xs">
                <option value="">Semua Status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif (Tayang)</option>
                <option value="draft" @selected(request('status') === 'draft')>Draf (Sembunyi)</option>
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-2">
            <button type="submit" class="btn-primary !py-2 flex-1 text-xs justify-center">
                <x-icon name="search" class="h-3.5 w-3.5" />
                Filter
            </button>
            @if(request()->hasAny(['search', 'type', 'status']))
                <a href="{{ route('superadmin.announcements.index') }}" class="btn-secondary !py-2 text-xs" title="Reset filter">
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
                    <th class="w-32">Tanggal</th>
                    <th>Judul & Ringkasan</th>
                    <th>Kategori</th>
                    <th>Lampiran</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $announcement)
                    <tr>
                        <td class="whitespace-nowrap font-mono text-xs text-slate-600">
                            {{ $announcement->published_at ? $announcement->published_at->translatedFormat('d M Y') : '-' }}
                            <span class="block text-[11px] text-slate-400">
                                {{ $announcement->published_at ? $announcement->published_at->format('H:i') . ' WIB' : '' }}
                            </span>
                        </td>
                        <td>
                            <p class="font-bold text-sm text-slate-900">{{ $announcement->title }}</p>
                            <p class="mt-1 line-clamp-1 text-xs text-slate-500 font-normal">
                                {{ $announcement->excerpt ?: Str::limit(strip_tags($announcement->body), 80) }}
                            </p>
                            @if($announcement->creator)
                                <span class="mt-1 inline-block text-[10px] text-slate-400">
                                    Oleh: {{ $announcement->creator->name }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($announcement->type === 'informasi')
                                <span class="rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-700">
                                    Informasi
                                </span>
                            @elseif($announcement->type === 'panduan')
                                <span class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700">
                                    Panduan
                                </span>
                            @elseif($announcement->type === 'hasil')
                                <span class="rounded-md border border-purple-200 bg-purple-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-purple-700">
                                    Hasil Seleksi
                                </span>
                            @else
                                <span class="status-chip status-neutral text-[10px]">
                                    {{ strtoupper($announcement->type) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($announcement->attachment_path)
                                <a href="{{ route('superadmin.announcements.file', $announcement) }}" target="_blank" class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                    <x-icon name="document" class="h-3.5 w-3.5 text-red-600" />
                                    <span>PDF</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('superadmin.announcements.toggle', $announcement) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center gap-1 cursor-pointer transition hover:opacity-80" title="Klik untuk ubah status tampil">
                                    @if($announcement->is_active)
                                        <span class="status-chip status-success text-xs">● Tayang</span>
                                    @else
                                        <span class="status-chip status-neutral text-xs">○ Draf</span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <a href="{{ route('superadmin.announcements.edit', $announcement) }}" class="rounded-lg p-2 text-brand-700 hover:bg-brand-50" title="Edit Pengumuman">
                                    <x-icon name="edit" class="h-4 w-4" />
                                </a>
                                <form method="POST" action="{{ route('superadmin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Hapus pengumuman ini? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-rose-600 hover:bg-rose-50" title="Hapus Pengumuman">
                                        <x-icon name="x" class="h-4 w-4" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-sm text-slate-500">
                            Belum ada data pengumuman yang sesuai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($announcements->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">
            {{ $announcements->links() }}
        </div>
    @endif
</section>
@endsection
