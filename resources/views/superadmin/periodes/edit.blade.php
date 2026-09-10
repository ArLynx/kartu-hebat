@extends('layouts.portal')

@section('title', 'Edit Periode Beasiswa')
@section('header', 'Edit Periode Beasiswa')

@section('content')
<div class="mb-6">
    <p class="section-kicker">Master Data</p>
    <h1 class="mt-2 text-3xl font-extrabold">Edit {{ $periode->nama ?: 'Periode Tahun ' . $periode->tahun }}</h1>
    <p class="mt-2 text-sm text-slate-600">Perubahan jadwal atau status akan mempengaruhi penerimaan pendaftaran kategori beasiswa.</p>
</div>

<form method="POST" action="{{ route('superadmin.periodes.update', $periode) }}" class="card p-6 lg:p-8">
    @csrf
    @method('PUT')
    @include('superadmin.periodes._form')
</form>
@endsection
