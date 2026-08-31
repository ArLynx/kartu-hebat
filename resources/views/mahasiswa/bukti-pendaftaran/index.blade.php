@extends('layouts.portal')

@section('title', 'Bukti Pendaftaran')
@section('header', 'Bukti Pendaftaran')

@section('content')

<div class="mx-auto max-w-5xl">

    {{-- ====================================================== --}}
    {{-- HEADER HALAMAN --}}
    {{-- ====================================================== --}}

    <div class="mb-7">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <p class="text-sm font-semibold text-brand-700">
                    Kartu Hebat Mahasiswa
                </p>

                <h1 class="mt-1 text-2xl font-extrabold text-navy-950 sm:text-3xl">
                    Bukti Pendaftaran
                </h1>

                <p class="mt-2 text-sm text-slate-600">
                    Bukti bahwa pendaftaran telah berhasil dikirim dan tercatat
                    dalam sistem Kartu Hebat Mahasiswa.
                </p>

            </div>


            {{-- TOMBOL CETAK --}}

            <a
                href="{{ route('mahasiswa.bukti-pendaftaran.pdf') }}"
                class="btn-primary inline-flex items-center justify-center gap-2"
            >

                <x-icon
                    name="download"
                    class="h-5 w-5"
                />

                Cetak Bukti Pendaftaran

            </a>

        </div>

    </div>



    {{-- ====================================================== --}}
    {{-- KARTU BUKTI PENDAFTARAN --}}
    {{-- ====================================================== --}}

    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >


        {{-- ================================================== --}}
        {{-- KOP --}}
        {{-- ================================================== --}}

        <div class="border-b-4 border-brand-700 px-6 py-7 sm:px-10">

            <div class="flex items-center gap-5">

                @if(file_exists(public_path('images/logo-murung-raya.png')))

                    <img
                        src="{{ asset('images/logo-murung-raya.png') }}"
                        alt="Logo Kabupaten Murung Raya"
                        class="h-20 w-20 object-contain"
                    >

                @endif


                <div class="flex-1">

                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-700">
                        Pemerintah Kabupaten Murung Raya
                    </p>

                    <h2 class="mt-1 text-xl font-extrabold uppercase text-navy-950 sm:text-2xl">
                        Kartu Hebat Mahasiswa
                    </h2>

                    <p class="mt-1 text-sm text-slate-600">
                        Kartu Bukti Pendaftaran
                    </p>

                </div>

            </div>

        </div>



        {{-- ================================================== --}}
        {{-- NOMOR PENDAFTARAN --}}
        {{-- ================================================== --}}

    
        <div class="px-6 py-7 sm:px-10">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">


                {{-- NOMOR --}}

                <div>

                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-700">
                        Bukti Pendaftaran
                    </p>


                    <h3 class="mt-2 text-2xl font-extrabold tracking-wide text-navy-950">

                        {{ $pendaftaran->nomor_pendaftaran }}

                    </h3>


                    <p class="mt-2 text-sm text-slate-600">

                        {{ $pendaftaran->periode?->nama ?? '-' }}

                    </p>

                </div>

            </div>

        </div>

        {{-- ================================================== --}}
        {{-- DATA PENDAFTAR --}}
        {{-- ================================================== --}}

        <div class="border-t border-slate-200 px-6 py-7 sm:px-10">

            <h4
                class="mb-5 text-sm font-extrabold uppercase tracking-wider text-navy-950"
            >

                Data Pendaftar

            </h4>


            <div class="grid gap-x-10 gap-y-5 sm:grid-cols-2">


                {{-- NAMA --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Nama Lengkap
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{
                            $pendaftaran->dataPribadi?->nama_lengkap
                            ?? $pendaftaran->user?->name
                            ?? '-'
                        }}

                    </p>

                </div>



                {{-- NIK --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        NIK
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->dataPribadi?->nik ?? '-' }}

                    </p>

                </div>



                {{-- NIM --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        NIM
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->pendidikan?->nim ?? '-' }}

                    </p>

                </div>



                {{-- PERGURUAN TINGGI --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Perguruan Tinggi
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->pendidikan?->universitas ?? '-' }}

                    </p>

                </div>



                {{-- PROGRAM STUDI --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Program Studi
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->pendidikan?->program_studi ?? '-' }}

                    </p>

                </div>

                {{-- JALUR BEASISWA --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Kategori Beasiswa
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">
                        {{ $pendaftaran->jalurBeasiswa?->nama ?? '-' }}
                    </p>

                </div>


                {{-- KATEGORI --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Jenis Beasiswa
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->kategoriBeasiswa?->nama ?? '-' }}

                    </p>

                </div>



                {{-- PERIODE --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Periode
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->periode?->nama ?? '-' }}

                    </p>

                </div>



                {{-- TANGGAL --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Tanggal Pendaftaran
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{
                            (
                                $pendaftaran->application?->submitted_at
                                ?? $pendaftaran->submitted_at
                            )?->format('d F Y H:i')
                            ?? '-'
                        }}

                    </p>

                </div>

            </div>

        </div>



        {{-- ================================================== --}}
        {{-- ALAMAT LENGKAP --}}
        {{-- ================================================== --}}

        <div class="border-t border-slate-200 bg-slate-50 px-6 py-7 sm:px-10">

            <h4
                class="mb-5 text-sm font-extrabold uppercase tracking-wider text-navy-950"
            >

                Alamat Pendaftar

            </h4>


            <div class="grid gap-x-10 gap-y-5 sm:grid-cols-2">


                {{-- ALAMAT / JALAN --}}

                <div class="sm:col-span-2">

                    <p class="text-xs font-medium text-slate-500">
                        Alamat / Jalan
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->dataPribadi?->alamat ?? '-' }}

                    </p>

                </div>



                {{-- PROVINSI --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Provinsi
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->dataPribadi?->provinsi ?? '-' }}

                    </p>

                </div>



                {{-- KABUPATEN --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Kabupaten
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->dataPribadi?->kabupaten ?? '-' }}

                    </p>

                </div>



                {{-- KECAMATAN --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Kecamatan
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->dataPribadi?->kecamatan ?? '-' }}

                    </p>

                </div>



                {{-- DESA / KELURAHAN --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Desa / Kelurahan
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->dataPribadi?->desa ?? '-' }}

                    </p>

                </div>



                {{-- KODE POS --}}

                <div>

                    <p class="text-xs font-medium text-slate-500">
                        Kode Pos
                    </p>

                    <p class="mt-1 font-semibold text-slate-900">

                        {{ $pendaftaran->dataPribadi?->kode_pos ?? '-' }}

                    </p>

                </div>

            </div>

        </div>



        {{-- ================================================== --}}
        {{-- CATATAN VERIFIKASI --}}
        {{-- ================================================== --}}

        @if($pendaftaran->application?->catatan)

            <div
                class="border-t border-amber-200 bg-amber-50 px-6 py-6 sm:px-10"
            >

                <div class="flex items-start gap-4">


                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"
                    >

                        ⚠

                    </div>


                    <div>

                        <h4 class="font-bold text-amber-900">

                            Catatan Verifikasi

                        </h4>


                        <p class="mt-2 text-sm leading-6 text-amber-800">

                            {{ $pendaftaran->application->catatan }}

                        </p>

                    </div>

                </div>

            </div>

        @endif



        {{-- ================================================== --}}
        {{-- KETERANGAN BUKTI --}}
        {{-- ================================================== --}}

        <div class="border-t border-slate-200 px-6 py-7 sm:px-10">

            <div class="rounded-xl bg-slate-50 p-5">

                <p class="text-sm leading-6 text-slate-600">

                    Bukti ini merupakan tanda bahwa mahasiswa telah
                    berhasil melakukan pendaftaran Kartu Hebat Mahasiswa
                    melalui sistem resmi Pemerintah Kabupaten Murung Raya.

                </p>


                <p class="mt-2 text-sm font-semibold text-slate-800">

                    Simpan bukti pendaftaran ini untuk keperluan administrasi.

                </p>

            </div>

        </div>



        {{-- ================================================== --}}
        {{-- FOOTER --}}
        {{-- ================================================== --}}

        <div class="border-t border-slate-200 px-6 py-7 sm:px-10">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">


                <div>

                    <p class="text-sm text-slate-500">
                        Puruk Cahu
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">

                        {{ now()->format('d F Y') }}

                    </p>

                </div>


            </div>

        </div>


    </section>



    {{-- ====================================================== --}}
    {{-- TOMBOL BAWAH --}}
    {{-- ====================================================== --}}

    <div class="mt-6 flex flex-wrap justify-between gap-3">


        <a
            href="{{ route('mahasiswa.dashboard') }}"
            class="btn-secondary"
        >

            Kembali ke Dashboard

        </a>


        <a
            href="{{ route('mahasiswa.bukti-pendaftaran.pdf') }}"
            class="btn-primary inline-flex items-center gap-2"
        >

            <x-icon
                name="download"
                class="h-5 w-5"
            />

            Cetak Bukti Pendaftaran

        </a>

    </div>

</div>

@endsection