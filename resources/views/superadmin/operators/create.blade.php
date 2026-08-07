@extends('layouts.portal')

@section('title', 'Tambah Operator')
@section('header', 'Tambah Operator')

@section('content')
<div class="mb-6">
    <p class="section-kicker">Manajemen Pengguna</p>
    <h1 class="mt-2 text-3xl font-extrabold">Operator Baru</h1>
    <p class="mt-2 text-sm text-slate-600">Password akan dibuat otomatis dan ditampilkan satu kali di halaman edit setelah disimpan.</p>
</div>

<form method="POST" action="{{ route('superadmin.operators.store') }}" class="card p-6 lg:p-8">
    @csrf
    @include('superadmin.operators._form', ['operator' => null])
</form>
@endsection