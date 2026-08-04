@extends('layouts.portal')

@section('title', 'Document Types')
@section('header', 'Document Types')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Master Data</p>
        <h1 class="mt-2 text-3xl font-extrabold">Document Types</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Data pada tabel <code>document_types</code> otomatis disinkronkan ke master dokumen alur pendaftaran terintegrasi.
        </p>
    </div>
    <a href="{{ route('superadmin.document-types.create') }}" class="btn-primary">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Document Type
    </a>
</div>

<section class="table-shell mt-7">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Document Type</th>
                    <th>Jalur</th>
                    <th>Format</th>
                    <th>Ukuran Maks.</th>
                    <th>Ketentuan</th>
                    <th>Sinkronisasi</th>
                    <th>Dipakai</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $type)
                    @php $mirror = $mirrorByCode->get($type->code); @endphp
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $type->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $type->code }} · Urutan {{ $type->sort_order }}</p>
                        </td>
                        <td>{{ $type->application_type?->label() ?? 'Semua jalur' }}</td>
                        <td>{{ implode(', ', $type->allowed_mimes ?? []) }}</td>
                        <td>{{ number_format($type->max_size_kb) }} KB</td>
                        <td>
                            <div class="flex flex-col items-start gap-1">
                                <span class="status-chip {{ $type->is_active ? 'status-success' : 'status-neutral' }}">{{ $type->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                <span class="text-xs text-slate-500">{{ $type->is_required ? 'Wajib' : 'Opsional' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-chip {{ $mirror ? 'status-success' : 'status-warning' }}">
                                {{ $mirror ? 'Terintegrasi' : 'Belum sinkron' }}
                            </span>
                        </td>
                        <td>{{ number_format($type->documents_count) }} dokumen</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.document-types.edit', $type) }}" class="rounded-lg p-2 text-brand-700 hover:bg-brand-50" title="Edit">
                                    <x-icon name="edit" class="h-5 w-5" />
                                </a>
                                <form method="POST" action="{{ route('superadmin.document-types.destroy', $type) }}" onsubmit="return confirm('Hapus document type ini?')">
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
                    <tr><td colspan="8" class="py-12 text-center text-slate-500">Belum ada document type.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($types->hasPages())
        <div class="border-t border-slate-200 px-5 py-4">{{ $types->links() }}</div>
    @endif
</section>
@endsection
