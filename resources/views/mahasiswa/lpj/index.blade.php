@extends('layouts.portal')

@section('title', 'LPJ')
@section('header', 'Laporan Pertanggungjawaban')

@section('content')

<div class="mx-auto max-w-5xl">

    {{-- HEADER HALAMAN --}}
    <div class="mb-7">
        <div>
            <p class="text-sm font-semibold text-brand-700">
                Kartu Hebat Mahasiswa
            </p>

            <h1 class="mt-1 text-2xl font-extrabold text-navy-950 sm:text-3xl">
                Laporan Pertanggungjawaban
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                Kelola dan sampaikan laporan pertanggungjawaban
                penggunaan bantuan pendidikan.
            </p>
        </div>
    </div>


    {{-- STATUS LPJ --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-7 sm:px-10">

            <h2 class="text-xl font-extrabold text-navy-950">
                Status LPJ
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Informasi mengenai status laporan pertanggungjawaban Anda.
            </p>

        </div>


        <div class="px-6 py-7 sm:px-10">

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">

                <div class="flex items-start gap-4">

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                        ℹ
                    </div>

                    <div>

                        <h3 class="font-bold text-blue-900">
                            Belum Dibuka
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-blue-800">
                            Pengumpulan Laporan Pertanggungjawaban belum
                            dibuka oleh pengelola Program Kartu Hebat Mahasiswa.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>


    {{-- INFORMASI LPJ --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-7 sm:px-10">

            <h2 class="text-xl font-extrabold text-navy-950">
                Informasi Pengumpulan
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Informasi periode dan ketentuan pengumpulan LPJ.
            </p>

        </div>


        <div class="grid gap-x-10 gap-y-6 px-6 py-7 sm:grid-cols-2 sm:px-10">

            <div>
                <p class="text-xs font-medium text-slate-500">
                    Periode Beasiswa
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $pendaftaran->periode?->nama ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs font-medium text-slate-500">
                    Kategori Beasiswa
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    {{ $pendaftaran->kategoriBeasiswa?->nama ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs font-medium text-slate-500">
                    Batas Pengumpulan
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    Belum ditentukan
                </p>
            </div>


            <div>
                <p class="text-xs font-medium text-slate-500">
                    Status
                </p>

                <span class="mt-1 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    Belum Dibuka
                </span>
            </div>

        </div>

    </section>


    {{-- DOKUMEN LPJ --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-7 sm:px-10">

            <h2 class="text-xl font-extrabold text-navy-950">
                Dokumen LPJ
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Dokumen yang harus disiapkan untuk laporan pertanggungjawaban.
            </p>

        </div>


        <div class="px-6 py-7 sm:px-10">

            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">

                <h3 class="font-semibold text-slate-900">
                    Pengumpulan LPJ Belum Dibuka
                </h3>

                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                    Pengunggahan dokumen LPJ dapat dilakukan setelah
                    periode pengumpulan dibuka oleh pengelola program.
                </p>

            </div>

        </div>

    </section>


    {{-- PERHATIAN --}}
    <section class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">

        <div class="flex items-start gap-3">

            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                ⚠
            </div>

            <div>

                <h3 class="font-bold text-amber-900">
                    Perhatian
                </h3>

                <p class="mt-2 text-sm leading-6 text-amber-800">
                    Laporan Pertanggungjawaban wajib disampaikan sesuai
                    dengan ketentuan dan batas waktu yang ditetapkan oleh
                    pengelola Program Kartu Hebat Mahasiswa.
                </p>

                <p class="mt-2 text-sm leading-6 text-amber-800">
                    Pastikan seluruh dokumen yang disampaikan benar,
                    lengkap, dan dapat dipertanggungjawabkan.
                </p>

            </div>

        </div>

    </section>

</div>

@endsection