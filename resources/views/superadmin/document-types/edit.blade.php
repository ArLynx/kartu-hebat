@extends('layouts.portal')

@section('title', 'Edit Document Type')
@section('header', 'Edit Document Type')

@section('content')
<div class="mb-6">
    <p class="section-kicker">Master Data</p>
    <h1 class="mt-2 text-3xl font-extrabold">Edit {{ $documentType->name }}</h1>
    <p class="mt-2 text-sm text-slate-600">Perubahan akan disinkronkan ke master dokumen pendaftaran.</p>
</div>

<form method="POST" action="{{ route('superadmin.document-types.update', $documentType) }}" class="card p-6 lg:p-8">
    @csrf
    @method('PUT')
    @include('superadmin.document-types._form')
</form>
@endsection
