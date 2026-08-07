@extends('layouts.portal')

@section('title', 'Operator')
@section('header', 'Operator')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Manajemen Pengguna</p>
        <h1 class="mt-2 text-3xl font-extrabold">Operator</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Kelola akun operator desa, kecamatan, dinas, dan kabupaten. Password baru akan ditampilkan satu kali di halaman edit setelah disimpan.
        </p>
    </div>
    <a href="{{ route('superadmin.operators.create') }}" class="btn-primary">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Operator
    </a>
</div>

<form method="GET" class="mt-6 grid gap-3 sm:grid-cols-3">
    <div>
        <label class="form-label" for="q">Cari</label>
        <input id="q" name="q" type="text" class="form-input" value="{{ $filters['q'] }}" placeholder="Nama atau email">
    </div>
    <div>
        <label class="form-label" for="role">Role</label>
        <select id="role" name="role" class="form-input">
            <option value="">Semua role</option>
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="btn-primary">Filter</button>
        @if($filters['q'] || $filters['role'])
            <a href="{{ route('superadmin.operators.index') }}" class="btn-secondary">Reset</a>
        @endif
    </div>
</form>

<section class="table-shell mt-6">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Operator</th>
                    <th>Role</th>
                    <th>Wilayah</th>
                    <th>Status</th>
                    <th>2FA</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($operators as $operator)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $operator->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $operator->email }}</p>
                        </td>
                        <td>
                            <span class="status-chip status-info">{{ $roles[$operator->role->value] ?? $operator->role->value }}</span>
                        </td>
                        <td class="text-sm text-slate-600">
                            @if($operator->village)
                                Desa {{ $operator->village->name }}
                            @elseif($operator->kecamatan)
                                Kec. {{ $operator->kecamatan->name }}
                            @elseif($operator->kabupaten)
                                Kab. {{ $operator->kabupaten->name }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-chip {{ $operator->status === 'active' ? 'status-success' : 'status-neutral' }}">{{ $operator->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td>
                            @if($operator->two_factor_confirmed_at)
                                <span class="status-chip status-success" title="2FA aktif sejak {{ $operator->two_factor_confirmed_at->format('d M Y') }}">
                                    <x-icon name="shield" class="h-3 w-3" />
                                    Aktif
                                </span>
                            @else
                                <span class="status-chip status-warning" title="Operator wajib setup 2FA saat login pertama">
                                    <x-icon name="warning" class="h-3 w-3" />
                                    Belum
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.operators.edit', $operator) }}" class="rounded-lg p-2 text-brand-700 hover:bg-brand-50" title="Edit">
                                    <x-icon name="edit" class="h-5 w-5" />
                                </a>
                                <form method="POST" action="{{ route('superadmin.operators.destroy', $operator) }}" onsubmit="return confirm('Hapus operator {{ $operator->name }}? Tindakan ini tidak dapat dibatalkan.')">
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
                    <tr><td colspan="6" class="py-12 text-center text-slate-500">Belum ada operator.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($operators->hasPages())
        <div class="border-t border-slate-200 px-5 py-4">{{ $operators->links() }}</div>
    @endif
</section>
@endsection