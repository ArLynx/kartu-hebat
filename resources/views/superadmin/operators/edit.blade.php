@extends('layouts.portal')

@section('title', 'Edit Operator')
@section('header', 'Edit Operator')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">Manajemen Pengguna</p>
        <h1 class="mt-2 text-3xl font-extrabold">{{ $operator->name }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ $operator->email }} · {{ $roles[$operator->role->value] ?? $operator->role->value }}</p>
    </div>
    <form method="POST" action="{{ route('superadmin.operators.reset-password', $operator) }}" data-confirm="Reset password untuk {{ $operator->name }}? Password baru akan dibuat otomatis.">
        @csrf
        <button type="submit" class="btn-secondary">
            <x-icon name="key" class="h-4 w-4" />
            Reset Password
        </button>
    </form>
</div>

@if(session('generated_password'))
    <div class="card mb-6 border-amber-300 bg-amber-50 p-5">
        <h2 class="font-bold text-amber-900">Password Baru (tampilkan sekali)</h2>
        <p class="mt-1 text-sm text-amber-800">Salin dan kirim ke operator melalui kanal aman. Halaman ini tidak menampilkan password lagi.</p>
        <code class="mt-3 block break-all rounded bg-white px-3 py-2 text-base font-mono text-slate-900">{{ session('generated_password') }}</code>
    </div>
@endif

<div class="card mb-6 p-5">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Autentikasi Dua Faktor</h2>
            <p class="mt-1 text-xs text-slate-600">
                @if($operator->two_factor_confirmed_at)
                    Aktif sejak <span class="font-semibold">{{ $operator->two_factor_confirmed_at->format('d M Y H:i') }}</span>.
                @else
                    Operator belum mengaktifkan 2FA. Login pertama akan mengarahkan ke halaman setup.
                @endif
            </p>
        </div>
        @if($operator->two_factor_confirmed_at)
            <span class="status-chip status-success">
                <x-icon name="shield" class="h-3 w-3" />
                Aktif
            </span>
        @else
            <span class="status-chip status-warning">
                <x-icon name="warning" class="h-3 w-3" />
                Belum
            </span>
        @endif
    </div>
</div>

<form method="POST" action="{{ route('superadmin.operators.update', $operator) }}" class="card p-6 lg:p-8">
    @csrf
    @method('PUT')
    @include('superadmin.operators._form', ['operator' => $operator])
</form>
@endsection