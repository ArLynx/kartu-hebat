@extends('layouts.portal')

@section('title', 'Edit Kategori Beasiswa')
@section('header', 'Edit Kategori Beasiswa')

@section('content')
<div class="mb-6">
    <p class="section-kicker">Master Data</p>
    <h1 class="mt-2 text-3xl font-extrabold">Edit {{ $category->nama }}</h1>
    <p class="mt-2 text-sm text-slate-600">Perubahan persyaratan akan berlaku pada alur pendaftaran kategori ini.</p>
</div>

<form method="POST" action="{{ route('superadmin.kategori-beasiswa.update', $category) }}" class="card p-6 lg:p-8">
    @csrf
    @method('PUT')
    @include('superadmin.kategori-beasiswa._form')
</form>
@endsection
