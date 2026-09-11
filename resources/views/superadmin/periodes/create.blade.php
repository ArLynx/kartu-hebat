@extends('layouts.portal')

@section('title', 'Tambah Periode Beasiswa')
@section('header', 'Tambah Periode Beasiswa')

@section('content')
<div class="mb-6">
    <p class="section-kicker">Master Data</p>
    <h1 class="mt-2 text-3xl font-extrabold">Periode Beasiswa Baru</h1>
    <p class="mt-2 text-sm text-slate-600">Tentukan tahun anggaran, rentang tanggal pendaftaran, dan status periode.</p>
</div>

<form method="POST" action="{{ route('superadmin.periodes.store') }}" class="card p-6 lg:p-8">
    @csrf
    @include('superadmin.periodes._form')
</form>
@endsection
