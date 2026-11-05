@extends('layouts.portal')

@section('title', 'Edit Pengumuman')
@section('header', 'Edit Pengumuman')

@section('content')
<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('superadmin.announcements.index') }}" class="hover:text-brand-700">Kelola Pengumuman</a>
            <span>&rsaquo;</span>
            <span class="text-slate-800 font-semibold">Edit Pengumuman</span>
        </div>
        <p class="section-kicker">Portal Publik</p>
        <h1 class="mt-2 text-3xl font-extrabold text-navy-950">Edit Pengumuman</h1>
        <p class="mt-2 text-sm text-slate-600">Perbarui informasi, ringkasan, atau lampiran dokumen pengumuman ini.</p>
    </div>
    <a href="{{ route('superadmin.announcements.index') }}" class="btn-secondary">
        &larr; Kembali
    </a>
</div>

<form method="POST" action="{{ route('superadmin.announcements.update', $announcement) }}" enctype="multipart/form-data" class="card p-6 lg:p-8">
    @csrf
    @method('PUT')
    @include('superadmin.announcements._form', ['announcement' => $announcement])
</form>
@endsection
