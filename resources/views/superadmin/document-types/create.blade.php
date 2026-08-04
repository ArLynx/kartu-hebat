@extends('layouts.portal')

@section('title', 'Tambah Document Type')
@section('header', 'Tambah Document Type')

@section('content')
<div class="mb-6">
    <p class="section-kicker">Master Data</p>
    <h1 class="mt-2 text-3xl font-extrabold">Document Type Baru</h1>
    <p class="mt-2 text-sm text-slate-600">Tambahkan definisi dokumen dan sinkronkan ke alur pendaftaran beasiswa.</p>
</div>

<form method="POST" action="{{ route('superadmin.document-types.store') }}" class="card p-6 lg:p-8">
    @csrf
    @include('superadmin.document-types._form')
</form>
@endsection
