@extends('layouts.portal')

@section('title', 'Buat Pendaftaran Beasiswa')
@section('header', 'Buat Pendaftaran')

@section('content')
<div class="max-w-5xl">
    <div>
        <h1 class="text-3xl font-extrabold">Buat Pendaftaran Beasiswa</h1>
        <p class="mt-2 leading-7 text-slate-600">Pilih kategori dari periode aktif. Kategori tidak dapat diubah setelah draft dibuat.</p>
    </div>

    <form action="{{ route('mahasiswa.pendaftaran.store') }}" method="POST" class="mt-7 space-y-6">
        @csrf

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Periode Aktif</p>
                <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold">{{ $periode->nama }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Tahun {{ $periode->tahun }} · {{ $periode->tanggal_mulai->format('d/m/Y') }}–{{ $periode->tanggal_selesai->format('d/m/Y') }}</p>
                    </div>
                    <span class="rounded-full bg-emerald-100 px-4 py-2 text-xs font-bold text-emerald-800">AKTIF</span>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <h2 class="text-lg font-bold">Pilih Kategori Beasiswa</h2>

                @if($kategoriBeasiswas->isEmpty())
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
                        Belum ada kategori aktif pada periode ini. Administrator perlu menambahkan data pada tabel <code>kategori_beasiswas</code>.
                    </div>
                @else
                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        @foreach($kategoriBeasiswas as $kategori)
                            <label class="block cursor-pointer">
                                <input type="radio" name="kategori_beasiswa_id" value="{{ $kategori->id }}" class="peer sr-only" @checked(old('kategori_beasiswa_id') == $kategori->id)>
                                <div class="h-full rounded-xl border-2 border-slate-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-md peer-checked:border-brand-600 peer-checked:ring-2 peer-checked:ring-brand-100">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                                            <x-icon name="form" class="h-6 w-6" />
                                        </div>
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">Kuota {{ $kategori->kuota }}</span>
                                    </div>
                                    <h3 class="mt-5 text-xl font-extrabold">{{ $kategori->nama }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $kategori->deskripsi }}</p>
                                    <p class="mt-5 text-sm font-bold text-brand-700">Pilih kategori ini</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif

                <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <input type="checkbox" name="persetujuan" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('persetujuan'))>
                    <span class="text-sm leading-6 text-slate-700">Saya telah membaca dan menyetujui persyaratan kategori beasiswa yang dipilih.</span>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap justify-between gap-3">
            <a href="{{ route('mahasiswa.dashboard') }}" class="btn-secondary">Kembali</a>
            <button type="submit" class="btn-primary" @disabled($kategoriBeasiswas->isEmpty())>Buat Draft Pendaftaran</button>
        </div>
    </form>
</div>
@endsection
