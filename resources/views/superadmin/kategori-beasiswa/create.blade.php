@extends('layouts.portal')

@section('title', 'Tambah Kategori Beasiswa')
@section('header', 'Tambah Kategori Beasiswa')

@section('content')
<div class="mb-6">
    <p class="section-kicker">Master Data</p>
    <h1 class="mt-2 text-3xl font-extrabold">Kategori Beasiswa Baru</h1>
    <p class="mt-2 text-sm text-slate-600">Lengkapi informasi kategori dan pilih dokumen persyaratannya.</p>
</div>

<form method="POST" action="{{ route('superadmin.kategori-beasiswa.store') }}" class="card p-6 lg:p-8">
    @csrf
    @include('superadmin.kategori-beasiswa._form')
</form>
@endsection
