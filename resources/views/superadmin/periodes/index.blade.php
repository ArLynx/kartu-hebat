@extends('layouts.portal')

@section('title', 'Periode Beasiswa')
@section('header', 'Periode Beasiswa')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Master Data</p>
        <h1 class="mt-2 text-3xl font-extrabold">Periode Beasiswa</h1>
        <p class="mt-2 text-sm text-slate-600">Atur tahun anggaran, jadwal buka-tutup pendaftaran, dan status periode beasiswa.</p>
    </div>
    <a href="{{ route('superadmin.periodes.create') }}" class="btn-primary">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Periode
    </a>
</div>

<section class="table-shell mt-7">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tahun / Periode</th>
                    <th>Jadwal Pendaftaran</th>
                    <th>Kategori Terkait</th>
                    <th>Total Pendaftar</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periodes as $periode)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $periode->nama ?: 'Periode Tahun ' . $periode->tahun }}</p>
                            <p class="mt-1 text-xs text-slate-500">Tahun Anggaran {{ $periode->tahun }}</p>
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5 text-sm text-slate-700">
                                <x-icon name="calendar" class="h-4 w-4 text-slate-400" />
                                <span>{{ $periode->tanggal_mulai?->format('d M Y') ?? '-' }} — {{ $periode->tanggal_selesai?->format('d M Y') ?? '-' }}</span>
                            </div>
                            @if($periode->status === 'aktif')
                                @if(today()->lt($periode->tanggal_mulai))
                                    <p class="mt-1 text-xs text-amber-600">Belum dimulai (menunggu jadwal)</p>
                                @elseif(today()->gt($periode->tanggal_selesai))
                                    <p class="mt-1 text-xs text-rose-600">Telah melewati batas tanggal</p>
                                @else
                                    <p class="mt-1 text-xs text-emerald-600">Pendaftaran sedang berlangsung</p>
                                @endif
                            @endif
                        </td>
                        <td>
                            <span class="status-chip status-info">{{ $periode->kategori_beasiswas_count }} kategori</span>
                        </td>
                        <td>
                            <span class="font-medium text-slate-800">{{ number_format($periode->pendaftarans_count) }} mahasiswa</span>
                        </td>
                        <td>
                            @if($periode->status === 'aktif')
                                <span class="status-chip status-success">Aktif</span>
                            @elseif($periode->status === 'draft')
                                <span class="status-chip status-warning">Draft</span>
                            @else
                                <span class="status-chip status-neutral">Ditutup</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.periodes.edit', $periode) }}" class="rounded-lg p-2 text-brand-700 hover:bg-brand-50" title="Edit">
                                    <x-icon name="edit" class="h-5 w-5" />
                                </a>
                                <form method="POST" action="{{ route('superadmin.periodes.destroy', $periode) }}" onsubmit="return confirm('Hapus periode beasiswa tahun {{ $periode->tahun }}? Tindakan ini tidak dapat dibatalkan.')">
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
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500">
                            Belum ada data periode beasiswa. Klik tombol <strong>Tambah Periode</strong> untuk membuatnya.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($periodes->hasPages())
        <div class="border-t border-slate-200 px-5 py-4">{{ $periodes->links() }}</div>
    @endif
</section>
@endsection
