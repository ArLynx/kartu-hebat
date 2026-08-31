@extends('layouts.portal')

@section('title', 'Review Pendaftaran Beasiswa')
@section('header', 'Review')

@section('content')

    @php
        $data = $pendaftaran->dataPribadi;
        $pendidikan = $pendaftaran->pendidikan;
        $orangTua = $pendaftaran->orangTua;

        /*
        |--------------------------------------------------------------------------
        | Penentuan Form
        |--------------------------------------------------------------------------
        | Untuk sementara:
        | IPK >= 2.75 = Form A
        | IPK < 2.75  = Form B
        |
        | Jika ketentuan berubah, logic ini nanti kita pindahkan
        | ke satu tempat di backend.
        |--------------------------------------------------------------------------
        */

        $jenisForm = null;

        if ($pendidikan?->ipk !== null) {
            $jenisForm = (float) $pendidikan->ipk >= 2.75 ? 'A' : 'B';
        }
    @endphp


    <div class="max-w-6xl">

        {{-- ============================================================= --}}
        {{-- FLOW HEADER --}}
        {{-- ============================================================= --}}

        @include('mahasiswa.partials.flow-header', [
            'title' => 'Review Pendaftaran',
            'description' =>
                'Periksa kembali seluruh data sebelum masuk ke tahap submit. Gunakan tombol Perbaiki pada bagian yang belum sesuai.',
            'currentStep' => 6,
        ])


        {{-- ============================================================= --}}
        {{-- STATUS KELENGKAPAN --}}
        {{-- ============================================================= --}}

        @if ($missingStages !== [])
            <div class="mt-7 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">

                <p class="font-bold">
                    Pendaftaran belum siap disubmit.
                </p>

                <p class="mt-1 leading-6">
                    Tahap yang belum lengkap:
                    {{ implode(', ', $missingStages) }}.
                </p>

            </div>
        @else
            <div class="mt-7 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800">

                <p class="font-bold">
                    Seluruh data pendaftaran telah lengkap.
                </p>

                <p class="mt-1 leading-6">
                    Periksa kembali seluruh data dan formulir sebelum melanjutkan
                    ke tahap submit.
                </p>

            </div>
        @endif

        {{-- =========================================================
            PERINGATAN DATA FORMULIR
            ========================================================= --}}

        <div class="rounded-xl border border-red-200 bg-red-50 p-5">

            <div class="flex items-start gap-3">

                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <x-icon name="exclamation-triangle" class="h-5 w-5" />
                </div>

                <div>
                    <p class="font-bold text-red-800">
                        Periksa kembali data sebelum mengunduh formulir
                    </p>

                    <p class="mt-1 text-sm leading-6 text-red-700">
                        Surat Permohonan dan Pakta Integritas dibuat berdasarkan
                        data yang Anda isi pada sistem.
                    </p>

                    <p class="mt-2 text-sm font-semibold leading-6 text-red-800">
                        Jika terdapat kesalahan pada nama, NIK, alamat, perguruan
                        tinggi, NIM, data orang tua/wali, atau data lainnya pada
                        formulir, berarti terdapat kesalahan pada data pendaftaran
                        yang Anda isi di sistem.
                    </p>

                    <p class="mt-2 text-sm leading-6 text-red-700">
                        Silakan kembali ke bagian data yang bersangkutan melalui
                        tombol <strong>Perbaiki</strong>, kemudian periksa kembali
                        formulir sebelum dicetak dan ditandatangani.
                    </p>
                </div>

            </div>

        </div>


        <div class="mt-7 space-y-6">


            {{-- ========================================================= --}}
            {{-- DATA PRIBADI --}}
            {{-- ========================================================= --}}

            <section class="card overflow-hidden">

                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">

                    <div>
                        <h2 class="text-xl font-bold">
                            Data Pribadi
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Identitas dan alamat mahasiswa.
                        </p>
                    </div>

                    <a href="{{ route('mahasiswa.data-pribadi.index') }}"
                        class="text-sm font-semibold text-brand-700 hover:underline">
                        Perbaiki
                    </a>

                </div>


                <dl class="grid gap-x-8 gap-y-5 p-6 text-sm sm:p-8 md:grid-cols-2">

                    <div>
                        <dt class="text-slate-500">
                            Nama Lengkap
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $data?->nama_lengkap ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            NIK
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $data?->nik ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Tempat, Tanggal Lahir
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $data?->tempat_lahir ?: '-' }},
                            {{ $data?->tanggal_lahir?->format('d/m/Y') ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Jenis Kelamin / Agama
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $data?->jenis_kelamin === 'L' ? 'Laki-laki' : ($data?->jenis_kelamin === 'P' ? 'Perempuan' : '-') }}
                            /
                            {{ $data?->agama ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Nomor HP
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $data?->no_hp ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Nomor Rekening Bank Kalteng
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $data?->nomor_rekening ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Email
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ auth()->user()->email }}
                        </dd>
                    </div>


                    <div class="md:col-span-2">

                        <dt class="text-slate-500">
                            Alamat
                        </dt>

                        <dd class="mt-1 font-semibold leading-6">

                            {{ $data?->alamat ?: '-' }},
                            {{ $data?->desa ?: '-' }},
                            {{ $data?->kecamatan ?: '-' }},
                            {{ $data?->kabupaten ?: '-' }},
                            {{ $data?->provinsi ?: '-' }}

                            @if ($data?->kode_pos)
                                {{ $data->kode_pos }}
                            @endif

                        </dd>

                    </div>

                </dl>

            </section>



            {{-- ========================================================= --}}
            {{-- PENDIDIKAN --}}
            {{-- ========================================================= --}}

            <section class="card overflow-hidden">

                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">

                    <div>

                        <h2 class="text-xl font-bold">
                            Pendidikan
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Data perguruan tinggi dan akademik.
                        </p>

                    </div>

                    <a href="{{ route('mahasiswa.pendidikan.index') }}"
                        class="text-sm font-semibold text-brand-700 hover:underline">
                        Perbaiki
                    </a>

                </div>


                <dl class="grid gap-x-8 gap-y-5 p-6 text-sm sm:p-8 md:grid-cols-2">

                    <div>
                        <dt class="text-slate-500">
                            NIM
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->nim ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Perguruan Tinggi
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->universitas ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Fakultas
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->fakultas ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Jurusan
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->jurusan ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Program Studi
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->program_studi ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Jenjang / Semester
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->jenjang ?: '-' }}
                            /
                            {{ $pendidikan?->semester ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            IPK
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->ipk ?? '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Status Mahasiswa
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->status_mahasiswa ? ucfirst(str_replace('_', ' ', $pendidikan->status_mahasiswa)) : '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Tahun Masuk
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->tahun_masuk ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Tahun Lulus
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->tahun_lulus ?: '-' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-slate-500">
                            Status Perguruan Tinggi
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->status_perguruan_tinggi
                                ? ucfirst(strtolower($pendidikan->status_perguruan_tinggi))
                                : '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">
                            Akreditasi Perguruan Tinggi
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->akreditasi_perguruan_tinggi ?: '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-500">
                            Akreditasi Program Studi
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $pendidikan?->akreditasi_program_studi ?: '-' }}
                        </dd>
</div>

                </dl>

            </section>



            {{-- ========================================================= --}}
            {{-- PRESTASI --}}
            {{-- ========================================================= --}}

            <section class="card overflow-hidden">

                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">

                    <div>

                        <h2 class="text-xl font-bold">
                            Prestasi
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $pendaftaran->prestasis->count() }}
                            prestasi tercatat.
                        </p>

                    </div>

                    <a href="{{ route('mahasiswa.prestasi.index') }}"
                        class="text-sm font-semibold text-brand-700 hover:underline">
                        Perbaiki
                    </a>

                </div>


                <div class="p-6 sm:p-8">

                    @forelse ($pendaftaran->prestasis as $prestasi)
                        <div class="border-b border-slate-100 py-4 first:pt-0 last:border-0 last:pb-0">

                            <p class="font-semibold">
                                {{ $prestasi->nama_prestasi }}
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                {{ ucfirst(str_replace('_', ' ', $prestasi->jenis)) }}
                                ·
                                {{ ucfirst($prestasi->tingkat) }}
                                ·
                                {{ $prestasi->peringkat }}
                                ·
                                {{ $prestasi->tahun }}
                            </p>

                        </div>

                    @empty

                        <p class="text-sm text-slate-500">
                            Tidak ada prestasi yang dicantumkan.
                        </p>
                    @endforelse

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- ORANG TUA / WALI --}}
            {{-- ========================================================= --}}

            <section class="card overflow-hidden">

                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">

                    <div>

                        <h2 class="text-xl font-bold">
                            Orang Tua / Wali
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Identitas dan penghasilan keluarga.
                        </p>

                    </div>

                    <a href="{{ route('mahasiswa.orang-tua.index') }}"
                        class="text-sm font-semibold text-brand-700 hover:underline">
                        Perbaiki
                    </a>

                </div>


                <dl class="grid gap-x-8 gap-y-5 p-6 text-sm sm:p-8 md:grid-cols-2">

                    <div>

                        <dt class="text-slate-500">
                            Ayah
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $orangTua?->nama_ayah ?: '-' }}
                            ·
                            {{ $orangTua?->pekerjaan_ayah ?: '-' }}
                        </dd>

                        <dd class="mt-1 text-slate-600">
                            Rp
                            {{ number_format((float) ($orangTua?->penghasilan_ayah ?? 0), 0, ',', '.') }}
                            /bulan
                        </dd>

                    </div>


                    <div>

                        <dt class="text-slate-500">
                            Ibu
                        </dt>

                        <dd class="mt-1 font-semibold">
                            {{ $orangTua?->nama_ibu ?: '-' }}
                            ·
                            {{ $orangTua?->pekerjaan_ibu ?: '-' }}
                        </dd>

                        <dd class="mt-1 text-slate-600">
                            Rp
                            {{ number_format((float) ($orangTua?->penghasilan_ibu ?? 0), 0, ',', '.') }}
                            /bulan
                        </dd>

                    </div>


                    @if ($orangTua?->memiliki_wali)
                        <div class="md:col-span-2">

                            <dt class="text-slate-500">
                                Wali
                            </dt>

                            <dd class="mt-1 font-semibold">
                                {{ $orangTua->nama_wali ?: '-' }}
                                ·
                                {{ $orangTua->pekerjaan_wali ?: '-' }}
                            </dd>

                            <dd class="mt-1 text-slate-600">
                                Rp
                                {{ number_format((float) ($orangTua->penghasilan_wali ?? 0), 0, ',', '.') }}
                                /bulan
                            </dd>

                        </div>
                    @endif

                </dl>

            </section>



            {{-- ========================================================= --}}
            {{-- DOKUMEN PERSYARATAN --}}
            {{-- ========================================================= --}}

            <section class="card overflow-hidden">

                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">

                    <div>

                        <h2 class="text-xl font-bold">
                            Dokumen Persyaratan
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Dokumen persyaratan sesuai kategori beasiswa.
                        </p>

                    </div>

                    <a href="{{ route('mahasiswa.dokumen.index') }}"
                        class="text-sm font-semibold text-brand-700 hover:underline">
                        Perbaiki
                    </a>

                </div>


                <div class="divide-y divide-slate-100 px-6 sm:px-8">

                    @forelse ($requiredTypes as $type)
                        @php
                            $document = $pendaftaran->dokumens->firstWhere('jenis_dokumen_id', $type->id);
                        @endphp


                        <div class="flex items-center justify-between gap-4 py-4">

                            <div>

                                <p class="text-sm font-semibold">
                                    {{ $type->nama }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $document?->nama_file_asli ?: 'Belum diunggah' }}
                                </p>

                            </div>


                            @if ($document)
                                <a href="{{ route('mahasiswa.dokumen.download', $document) }}"
                                    class="text-sm font-semibold text-brand-700 hover:underline">
                                    Unduh
                                </a>
                            @else
                                <span class="status-chip status-warning">
                                    Belum lengkap
                                </span>
                            @endif

                        </div>

                    @empty

                        <p class="py-6 text-sm text-amber-700">
                            Master persyaratan dokumen belum tersedia.
                        </p>
                    @endforelse

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- FORMULIR RESMI --}}
            {{-- ========================================================= --}}

            <section class="card overflow-hidden">


                {{-- HEADER --}}

                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                    <div class="flex flex-wrap items-center justify-between gap-3">

                        <div>

                            <h2 class="text-xl font-bold text-slate-900">
                                Formulir Resmi
                            </h2>

                            <p class="mt-1 text-sm leading-6 text-slate-500">
                                Dokumen yang harus dilengkapi sebelum pendaftaran dikirim.
                            </p>

                        </div>


                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                            Dokumen Wajib
                        </span>

                    </div>

                </div>



                {{-- FORMULIR --}}

                <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">


                    {{-- ================================================= --}}
                    {{-- SURAT PERMOHONAN --}}
                    {{-- ================================================= --}}

                    <div class="rounded-xl border border-slate-200 bg-white p-6">

                        <div class="flex items-start gap-4">

                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">

                                <x-icon name="document-text" class="h-6 w-6" />

                            </div>


                            <div class="min-w-0 flex-1">

                                <h3 class="text-lg font-bold text-slate-900">
                                    Surat Permohonan
                                </h3>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Surat permohonan yang telah disiapkan
                                    berdasarkan data pendaftaran Anda.
                                </p>

                            </div>

                        </div>


                        @if ($jenisForm)
                            <div class="mt-5 rounded-lg bg-slate-50 px-4 py-3">

                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Formulir
                                </p>

                                <p class="mt-1 text-base font-bold text-slate-900">
                                    Form {{ $jenisForm }}
                                </p>

                            </div>
                        @else
                            <div class="mt-5 rounded-lg bg-amber-50 px-4 py-3">

                                <p class="text-sm font-semibold text-amber-800">
                                    Formulir belum dapat ditentukan.
                                </p>

                                <p class="mt-1 text-xs leading-5 text-amber-700">
                                    Lengkapi data pendidikan terlebih dahulu.
                                </p>

                            </div>
                        @endif


                        <div class="mt-5">

                            <a href="{{ route('mahasiswa.formulir.surat-permohonan') }}"
                                class="btn-secondary inline-flex items-center gap-2">

                                <x-icon name="arrow-down-tray" class="h-4 w-4" />

                                Unduh Surat Permohonan
                            </a>

                        </div>

                    </div>



                    {{-- ================================================= --}}
                    {{-- PAKTA INTEGRITAS --}}
                    {{-- ================================================= --}}

                    <div class="rounded-xl border border-slate-200 bg-white p-6">

                        <div class="flex items-start gap-4">

                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">

                                <x-icon name="document-check" class="h-6 w-6" />

                            </div>


                            <div class="min-w-0 flex-1">

                                <h3 class="text-lg font-bold text-slate-900">
                                    Pakta Integritas
                                </h3>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Dokumen pakta integritas yang telah
                                    disiapkan berdasarkan data pendaftaran Anda.
                                </p>

                            </div>

                        </div>


                        <div class="mt-5 rounded-lg bg-slate-50 px-4 py-3">

                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Dokumen
                            </p>

                            <p class="mt-1 text-base font-bold text-slate-900">
                                Pakta Integritas
                            </p>

                        </div>


                        <div class="mt-5">

                            <a href="{{ route('mahasiswa.formulir.pakta-integritas') }}"
                                class="btn-secondary inline-flex items-center gap-2">

                                <x-icon name="arrow-down-tray" class="h-4 w-4" />

                                Unduh Pakta Integritas
                            </a>

                        </div>

                    </div>

                </div>



                {{-- ================================================= --}}
                {{-- PETUNJUK --}}
                {{-- ================================================= --}}

                <div class="border-t border-slate-200 bg-slate-50 px-6 py-5 sm:px-8">

                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">

                            <x-icon name="information-circle" class="h-4 w-4" />

                        </div>


                        <div class="text-sm leading-6 text-slate-600">

                            <p class="font-semibold text-slate-800">
                                Petunjuk pengisian formulir
                            </p>

                           <ol class="mt-2 list-decimal space-y-1 pl-5">

                                <li>
                                    Periksa seluruh data pendaftaran pada halaman Review.
                                </li>

                                <li>
                                    Jika terdapat kesalahan, klik tombol <strong>Perbaiki</strong>
                                    pada bagian yang bersangkutan.
                                </li>

                                <li>
                                    Unduh Surat Permohonan dan Pakta Integritas.
                                </li>

                                <li>
                                    Cetak kedua formulir.
                                </li>

                                <li>
                                    Tanda tangani formulir sesuai ketentuan.
                                </li>

                                <li>
                                    Bubuhkan <strong>materai Rp10.000 pada bagian tanda tangan "yang membuat pernyataan" 
                                    yaitu mahasiswa/pendaftar </strong> pada Pakta Integritas.
                                </li>

                                <li>
                                    Upload kembali formulir yang telah ditandatangani.
                                </li>

                            </ol>

                            <p class="mt-3 text-xs font-semibold leading-5 text-red-600">
                                Penting: Jika terdapat kesalahan data pada Surat Permohonan atau
                                Pakta Integritas, jangan langsung dicetak dan ditandatangani.
                                Perbaiki terlebih dahulu data pendaftaran pada sistem.
                            </p>

                        </div>

                    </div>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- UPLOAD FORMULIR --}}
            {{-- ========================================================= --}}

            <section class="card overflow-hidden">

                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                    <h2 class="text-xl font-bold">
                        Upload Formulir
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Upload kembali formulir yang telah dicetak dan ditandatangani.
                    </p>

                </div>


                <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">


                    {{-- =====================================================
             SURAT PERMOHONAN
             ===================================================== --}}

                    @php
                        $suratPermohonan = $pendaftaran->formulirPendaftaran?->surat_permohonan;
                    @endphp

                    <div class="rounded-xl border border-slate-200 bg-white p-6">

                        <div class="flex items-start gap-4">

                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">

                                <x-icon name="document-text" class="h-6 w-6" />

                            </div>


                            <div class="min-w-0 flex-1">

                                <div class="flex items-center gap-2">

                                    <h3 class="text-lg font-bold text-slate-900">
                                        Surat Permohonan
                                    </h3>

                                    <span class="rounded bg-red-50 px-2 py-1 text-xs font-bold text-red-600">
                                        WAJIB
                                    </span>

                                </div>

                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    Form {{ $jenisForm ?: '-' }}
                                </p>

                            </div>

                        </div>


                        {{-- =================================================
                            SUDAH ADA FILE
                        ================================================= --}}

                        @if ($suratPermohonan)
                            {{-- =========================================================
         SUDAH ADA FILE SURAT
         ========================================================= --}}
                            <div class="mt-5 rounded-xl border border-emerald-300 bg-emerald-50/40 p-4">

                                <p class="break-all text-sm font-semibold text-slate-900">
                                    {{ basename($suratPermohonan) }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Status Uploaded
                                </p>

                                {{-- LIHAT & UNDUH --}}
                                <div class="mt-3 flex items-center gap-4">

                                    <a href="{{ route('mahasiswa.formulir.lihat', ['jenis' => 'surat']) }}"
                                        target="_blank" class="text-sm font-semibold text-brand-700 hover:underline">
                                        Lihat
                                    </a>

                                    <a href="{{ route('mahasiswa.formulir.download', ['jenis' => 'surat']) }}"
                                        class="text-sm font-semibold text-brand-700 hover:underline">
                                        Unduh
                                    </a>

                                </div>

                            </div>


                            {{-- =========================================================
         GANTI FILE
         ========================================================= --}}
                            <form method="POST" action="{{ route('mahasiswa.formulir.upload') }}"
                                enctype="multipart/form-data" class="mt-4">

                                @csrf

                                <input type="hidden" name="jenis" value="surat">

                                <div class="flex flex-wrap items-center gap-3">

                                    <label for="surat_permohonan_ganti"
                                        class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">

                                        Pilih File

                                    </label>

                                    <input id="surat_permohonan_ganti" type="file" name="file"
                                        accept=".pdf,application/pdf" class="hidden"
                                        onchange="document.getElementById('nama_surat_ganti').textContent = this.files[0]?.name || 'Belum ada file dipilih';">

                                    <span id="nama_surat_ganti" class="text-sm text-slate-500">
                                        Belum ada file dipilih
                                    </span>

                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">

                                        Unggah

                                    </button>

                                </div>

                            </form>


                            {{-- =========================================================
         HAPUS
         ========================================================= --}}
                            <form method="POST" action="{{ route('mahasiswa.formulir.delete') }}" class="mt-3">

                                @csrf
                                @method('DELETE')

                                <input type="hidden" name="jenis" value="surat">

                                <button type="submit"
                                    onclick="return confirm('Hapus Surat Permohonan yang sudah diupload?')"
                                    class="text-sm font-semibold text-red-600 hover:underline">

                                    Hapus dokumen

                                </button>

                            </form>
                        @else
                            {{-- =========================================================
         BELUM ADA FILE SURAT
         ========================================================= --}}
                            <form method="POST" action="{{ route('mahasiswa.formulir.upload') }}"
                                enctype="multipart/form-data" class="mt-5">

                                @csrf

                                <input type="hidden" name="jenis" value="surat">

                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5">

                                    <div class="text-center">

                                        <p class="text-sm font-semibold text-slate-700">
                                            Belum ada file yang diunggah
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            PDF · Maksimal 2 MB
                                        </p>

                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center justify-center gap-3">

                                        <label for="surat_permohonan"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">

                                            Pilih File

                                        </label>

                                        <input id="surat_permohonan" type="file" name="file"
                                            accept=".pdf,application/pdf" class="hidden"
                                            onchange="document.getElementById('nama_surat').textContent = this.files[0]?.name || 'Belum ada file dipilih';">

                                        <span id="nama_surat" class="max-w-xs truncate text-sm text-slate-500">
                                            Belum ada file dipilih
                                        </span>

                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">

                                            Unggah

                                        </button>

                                    </div>

                                </div>

                            </form>
                        @endif

                    </div> {{-- END CARD SURAT PERMOHONAN --}}


                    {{-- =====================================================
                        PAKTA INTEGRITAS
                    ===================================================== --}}

                        @php
                            $paktaIntegritas = $pendaftaran->formulirPendaftaran?->pakta_integritas;
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-white p-6">

                            <div class="flex items-start gap-4">

                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">

                                    <x-icon name="document-check" class="h-6 w-6" />

                                </div>


                                <div class="min-w-0 flex-1">

                                    <div class="flex items-center gap-2">

                                        <h3 class="text-lg font-bold text-slate-900">
                                            Pakta Integritas
                                        </h3>

                                        <span class="rounded bg-red-50 px-2 py-1 text-xs font-bold text-red-600">
                                            WAJIB
                                        </span>

                                    </div>

                                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                                        <p class="text-sm font-bold text-amber-800">
                                            ⚠️ Perhatian
                                        </p>

                                        <p class="mt-1 text-sm leading-6 text-amber-700">
                                            Pakta Integritas wajib ditandatangani oleh Orang Tua/Wali dan mahasiswa/pendaftar.
                                            Pendaftar sebagai pihak yang membuat pernyataan, tanda tangan
                                            <strong>ber-materai Rp10.000</strong> atau pada bagian tanda tangan "yang membuat pernyataan"
                                            sebelum diunggah kembali. Orang Tua/Wali tidak perlu pakai materai.
                                        </p>
                                    </div>

                                </div>

                            </div>


                            {{-- =================================================
                 SUDAH ADA FILE
                 ================================================= --}}

                            @if ($paktaIntegritas)
                                {{-- =========================================================
         SUDAH ADA FILE PAKTA
         ========================================================= --}}
                                <div class="mt-5 rounded-xl border border-emerald-300 bg-emerald-50/40 p-4">

                                    <p class="break-all text-sm font-semibold text-slate-900">
                                        {{ basename($paktaIntegritas) }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Status Uploaded
                                    </p>

                                    {{-- LIHAT & UNDUH --}}
                                    <div class="mt-3 flex items-center gap-4">

                                        <a href="{{ route('mahasiswa.formulir.lihat', ['jenis' => 'pakta']) }}"
                                            target="_blank" class="text-sm font-semibold text-brand-700 hover:underline">

                                            Lihat

                                        </a>

                                        <a href="{{ route('mahasiswa.formulir.download', ['jenis' => 'pakta']) }}"
                                            class="text-sm font-semibold text-brand-700 hover:underline">

                                            Unduh

                                        </a>

                                    </div>

                                </div>


                                {{-- =========================================================
         GANTI FILE
         ========================================================= --}}
                                <form method="POST" action="{{ route('mahasiswa.formulir.upload') }}"
                                    enctype="multipart/form-data" class="mt-4">

                                    @csrf

                                    <input type="hidden" name="jenis" value="pakta">

                                    <div class="flex flex-wrap items-center gap-3">

                                        <label for="pakta_integritas_ganti"
                                            class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">

                                            Pilih File

                                        </label>

                                        <input id="pakta_integritas_ganti" type="file" name="file"
                                            accept=".pdf,application/pdf" class="hidden"
                                            onchange="document.getElementById('nama_pakta_ganti').textContent = this.files[0]?.name || 'Belum ada file dipilih';">

                                        <span id="nama_pakta_ganti" class="text-sm text-slate-500">
                                            Belum ada file dipilih
                                        </span>

                                        <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">

                                            Unggah

                                        </button>

                                    </div>

                                </form>


                                {{-- =========================================================
         HAPUS
         ========================================================= --}}
                                <form method="POST" action="{{ route('mahasiswa.formulir.delete') }}" class="mt-3">

                                    @csrf
                                    @method('DELETE')

                                    <input type="hidden" name="jenis" value="pakta">

                                    <button type="submit"
                                        onclick="return confirm('Hapus Pakta Integritas yang sudah diupload?')"
                                        class="text-sm font-semibold text-red-600 hover:underline">

                                        Hapus dokumen

                                    </button>

                                </form>
                            @else
                                {{-- =========================================================
         BELUM ADA FILE PAKTA
         ========================================================= --}}
                                <form method="POST" action="{{ route('mahasiswa.formulir.upload') }}"
                                    enctype="multipart/form-data" class="mt-5">

                                    @csrf

                                    <input type="hidden" name="jenis" value="pakta">

                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5">

                                        <div class="text-center">

                                            <p class="text-sm font-semibold text-slate-700">
                                                Belum ada file yang diunggah
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                PDF · Maksimal 2 MB
                                            </p>

                                        </div>

                                        <div class="mt-4 flex flex-wrap items-center justify-center gap-3">

                                            <label for="pakta_integritas"
                                                class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">

                                                Pilih File

                                            </label>

                                            <input id="pakta_integritas" type="file" name="file"
                                                accept=".pdf,application/pdf" class="hidden"
                                                onchange="document.getElementById('nama_pakta').textContent = this.files[0]?.name || 'Belum ada file dipilih';">

                                            <span id="nama_pakta" class="max-w-xs truncate text-sm text-slate-500">
                                                Belum ada file dipilih
                                            </span>

                                            <button type="submit"
                                                class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">

                                                Unggah

                                            </button>

                                        </div>

                                    </div>

                                </form>
                            @endif

                        </div>

            </section>

        </div>



        {{-- ============================================================= --}}
        {{-- FOOTER BUTTON --}}
        {{-- ============================================================= --}}

        <div class="mt-6 flex flex-wrap justify-between gap-3">

            <a href="{{ route('mahasiswa.dokumen.index') }}" class="btn-secondary">
                Kembali ke Dokumen
            </a>


            @if ($canEdit)
                <form method="POST" action="{{ route('mahasiswa.review.confirm') }}">

                    @csrf

                    <button class="btn-primary" type="submit" @disabled($missingStages !== [] || !$formulirLengkap)>
                        Konfirmasi Review & Lanjut ke Submit

                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>

                </form>
            @else
                <a href="{{ route('mahasiswa.submit.index') }}" class="btn-primary">
                    Lihat Status Submit
                </a>
            @endif

        </div>

    </div>

@endsection
