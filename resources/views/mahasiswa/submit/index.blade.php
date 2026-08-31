@extends('layouts.portal')

@section('title', 'Submit Pendaftaran Beasiswa')
@section('header', 'Submit')

@section('content')
    <div class="max-w-6xl">

        @include('mahasiswa.partials.flow-header', [
            'title' => 'Submit Pendaftaran',
            'description' =>
                'Tahap terakhir sebelum pendaftaran dikirim dan dikunci untuk proses verifikasi.',
            'currentStep' => 7,
        ])


        {{-- =========================================================
             SUDAH SUBMIT
             ========================================================= --}}
        @if ($stepStatuses[7])

            <section class="card mt-7 p-7 sm:p-10">

                <div class="mx-auto max-w-2xl text-center">

                    {{-- Icon --}}
                    <div
                        class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">

                        <x-icon name="check" class="h-8 w-8" />

                    </div>


                    {{-- Judul --}}
                    <h2 class="mt-5 text-2xl font-extrabold text-slate-900">
                        Pendaftaran Sudah Dikirim
                    </h2>


                    {{-- Informasi --}}
                    <p class="mt-3 leading-7 text-slate-600">

                        Pendaftaran
                        <strong class="text-slate-900">
                            {{ $pendaftaran->nomor_pendaftaran }}
                        </strong>

                        telah berhasil dikirim pada

                        <strong class="text-slate-900">
                            {{ $pendaftaran->submitted_at?->format('d/m/Y H:i') ?: 'waktu yang tidak tercatat' }}
                        </strong>

                        dan tidak dapat diubah selama proses verifikasi.

                    </p>


                    {{-- Status --}}
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-left">

                        <p class="text-sm font-bold text-emerald-800">
                            Status Pendaftaran
                        </p>

                        <p class="mt-1 text-sm leading-6 text-emerald-700">
                            Pendaftaran Anda telah diterima oleh sistem.
                            Silakan simpan Bukti Pendaftaran sebagai bukti
                            bahwa pengajuan telah berhasil dikirim.
                        </p>

                    </div>


                    {{-- Tombol --}}
                    <div class="mt-7 flex flex-wrap justify-center gap-3">

                        <a
                            href="{{ route('mahasiswa.dashboard') }}"
                            class="btn-secondary"
                        >
                            Kembali ke Dashboard
                        </a>


                        <a
                            href="{{ route('mahasiswa.bukti-pendaftaran.index') }}"
                            class="btn-primary inline-flex items-center gap-2"
                        >

                            <x-icon name="document" class="h-5 w-5" />

                            Lihat Bukti Pendaftaran

                        </a>


                        <a
                            href="{{ route('mahasiswa.bukti-pendaftaran.pdf') }}"
                            class="btn-primary inline-flex items-center gap-2"
                        >

                            <x-icon name="download" class="h-5 w-5" />

                            Unduh Bukti Pendaftaran

                        </a>

                    </div>

                </div>

            </section>


        @else

            {{-- =====================================================
                 RINGKASAN FINAL
                 ===================================================== --}}
            <section class="card mt-7 overflow-hidden">

                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                    <p class="text-xs font-bold uppercase tracking-wider text-brand-700">
                        Ringkasan Final
                    </p>

                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900">
                        {{ $pendaftaran->nomor_pendaftaran }}
                    </h2>

                    <p class="mt-2 text-sm text-slate-600">
                        {{ $pendaftaran->periode?->nama }}
                        ·
                        {{ $pendaftaran->kategoriBeasiswa?->nama }}
                    </p>

                </div>


                {{-- =================================================
                     CHECKLIST SEBELUM SUBMIT
                     ================================================= --}}
                <div class="border-b border-slate-200 px-6 py-6 sm:px-8">

                    <h3 class="text-lg font-extrabold text-slate-900">
                        Checklist Sebelum Submit
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Pastikan seluruh tahapan pendaftaran telah
                        dilengkapi sebelum pendaftaran dikunci.
                    </p>


                    <div class="mt-5 grid gap-3 sm:grid-cols-2">

                        {{-- Data Pribadi --}}
                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <div class="flex items-center gap-3">

                                @if ($stepStatuses[1] ?? false)

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">

                                        <x-icon name="check" class="h-4 w-4" />

                                    </div>

                                @else

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">

                                        <x-icon name="x-mark" class="h-4 w-4" />

                                    </div>

                                @endif

                                <span class="text-sm font-semibold text-slate-800">
                                    Data Pribadi
                                </span>

                            </div>

                            <span
                                class="text-xs font-semibold {{ ($stepStatuses[1] ?? false) ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ ($stepStatuses[1] ?? false) ? 'Lengkap' : 'Belum Lengkap' }}
                            </span>

                        </div>


                        {{-- Pendidikan --}}
                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <div class="flex items-center gap-3">

                                @if ($stepStatuses[2] ?? false)

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">

                                        <x-icon name="check" class="h-4 w-4" />

                                    </div>

                                @else

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">

                                        <x-icon name="x-mark" class="h-4 w-4" />

                                    </div>

                                @endif

                                <span class="text-sm font-semibold text-slate-800">
                                    Pendidikan
                                </span>

                            </div>

                            <span
                                class="text-xs font-semibold {{ ($stepStatuses[2] ?? false) ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ ($stepStatuses[2] ?? false) ? 'Lengkap' : 'Belum Lengkap' }}
                            </span>

                        </div>


                        {{-- Prestasi --}}
                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <div class="flex items-center gap-3">

                                @if ($stepStatuses[3] ?? false)

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">

                                        <x-icon name="check" class="h-4 w-4" />

                                    </div>

                                @else

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">

                                        <x-icon name="x-mark" class="h-4 w-4" />

                                    </div>

                                @endif

                                <span class="text-sm font-semibold text-slate-800">
                                    Prestasi
                                </span>

                            </div>

                            <span
                                class="text-xs font-semibold {{ ($stepStatuses[3] ?? false) ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ ($stepStatuses[3] ?? false) ? 'Lengkap' : 'Belum Lengkap' }}
                            </span>

                        </div>


                        {{-- Orang Tua --}}
                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <div class="flex items-center gap-3">

                                @if ($stepStatuses[4] ?? false)

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">

                                        <x-icon name="check" class="h-4 w-4" />

                                    </div>

                                @else

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">

                                        <x-icon name="x-mark" class="h-4 w-4" />

                                    </div>

                                @endif

                                <span class="text-sm font-semibold text-slate-800">
                                    Orang Tua / Wali
                                </span>

                            </div>

                            <span
                                class="text-xs font-semibold {{ ($stepStatuses[4] ?? false) ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ ($stepStatuses[4] ?? false) ? 'Lengkap' : 'Belum Lengkap' }}
                            </span>

                        </div>


                        {{-- Dokumen --}}
                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <div class="flex items-center gap-3">

                                @if ($stepStatuses[5] ?? false)

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">

                                        <x-icon name="check" class="h-4 w-4" />

                                    </div>

                                @else

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">

                                        <x-icon name="x-mark" class="h-4 w-4" />

                                    </div>

                                @endif

                                <span class="text-sm font-semibold text-slate-800">
                                    Dokumen
                                </span>

                            </div>

                            <span
                                class="text-xs font-semibold {{ ($stepStatuses[5] ?? false) ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ ($stepStatuses[5] ?? false) ? 'Lengkap' : 'Belum Lengkap' }}
                            </span>

                        </div>


                        {{-- Review --}}
                        <div
                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <div class="flex items-center gap-3">

                                @if ($stepStatuses[6] ?? false)

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">

                                        <x-icon name="check" class="h-4 w-4" />

                                    </div>

                                @else

                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600">

                                        <x-icon name="x-mark" class="h-4 w-4" />

                                    </div>

                                @endif

                                <span class="text-sm font-semibold text-slate-800">
                                    Review
                                </span>

                            </div>

                            <span
                                class="text-xs font-semibold {{ ($stepStatuses[6] ?? false) ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ ($stepStatuses[6] ?? false) ? 'Selesai' : 'Belum Selesai' }}
                            </span>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     FORM SUBMIT
                     ================================================= --}}
                <form
                    method="POST"
                    action="{{ route('mahasiswa.submit.store') }}"
                    class="space-y-5 p-6 sm:p-8"
                    onsubmit="return confirm('Kirim pendaftaran sekarang? Data akan dikunci setelah dikirim.')"
                >

                    @csrf


                    {{-- Pernyataan Kebenaran --}}
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <input
                            type="checkbox"
                            name="pernyataan_kebenaran"
                            value="1"
                            class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                            required
                        >

                        <span class="text-sm leading-6 text-slate-700">

                            Saya menyatakan bahwa seluruh data dan dokumen
                            yang saya sampaikan dalam pendaftaran ini benar,
                            sah, dan dapat dipertanggungjawabkan.

                        </span>

                    </label>


                    {{-- Pernyataan Penguncian --}}
                    <label
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <input
                            type="checkbox"
                            name="pernyataan_final"
                            value="1"
                            class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                            required
                        >

                        <span class="text-sm leading-6 text-slate-700">

                            Saya memahami bahwa setelah pendaftaran dikirim,
                            seluruh data dan dokumen akan dikunci dan tidak
                            dapat diubah oleh saya selama proses verifikasi,
                            kecuali pendaftaran dikembalikan untuk perbaikan
                            oleh petugas.

                        </span>

                    </label>


                    {{-- =================================================
                         PERINGATAN
                         ================================================= --}}
                    <div class="rounded-xl border border-red-200 bg-red-50 p-5">

                        <div class="flex items-start gap-3">

                            <x-icon
                                name="warning"
                                class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                            />

                            <div>

                                <p class="font-bold text-red-800">
                                    Periksa Sebelum Submit
                                </p>

                                <p class="mt-1 text-sm leading-6 text-red-700">
                                    Pastikan seluruh data dan dokumen sudah
                                    benar sebelum menekan tombol
                                    <strong>Submit Pendaftaran</strong>.
                                </p>

                                <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-6 text-red-700">

                                    <li>
                                        Nama, NIK, tempat dan tanggal lahir
                                        harus sesuai dengan dokumen resmi.
                                    </li>

                                    <li>
                                        Data perguruan tinggi, jurusan,
                                        program studi, NIM, dan data pendidikan
                                        harus benar.
                                    </li>

                                    <li>
                                        Data orang tua/wali harus sesuai dengan
                                        kondisi sebenarnya.
                                    </li>

                                    <li>
                                        Pastikan seluruh dokumen yang diunggah
                                        merupakan dokumen yang benar dan dapat
                                        dibaca.
                                    </li>

                                    <li>
                                        Periksa kembali Surat Permohonan dan
                                        Pakta Integritas sebelum dicetak dan
                                        ditandatangani.
                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         INFO PENGUNCIAN
                         ================================================= --}}
                    <div class="rounded-xl bg-navy-900 p-5 text-white">

                        <div class="flex items-start gap-3">

                            <x-icon
                                name="warning"
                                class="mt-0.5 h-5 w-5 shrink-0 text-amber-300"
                            />

                            <div>

                                <p class="font-bold text-white">
                                    Data Akan Dikunci
                                </p>

                                <p class="mt-1 text-sm leading-6 text-slate-200">

                                    Setelah pendaftaran berhasil disubmit,
                                    data dan dokumen akan dikunci untuk proses
                                    verifikasi. Mahasiswa tidak dapat melakukan
                                    perubahan secara langsung.

                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         TOMBOL
                         ================================================= --}}
                    <div class="flex flex-wrap justify-between gap-3 pt-2">

                        <a
                            href="{{ route('mahasiswa.review.index') }}"
                            class="btn-secondary"
                        >
                            Kembali ke Review
                        </a>


                        <button
                            class="btn-primary inline-flex items-center gap-2"
                            type="submit"
                            @disabled($missingStages !== [] || ! $canEdit)
                        >

                            Submit Pendaftaran

                            <x-icon
                                name="arrow-right"
                                class="h-4 w-4"
                            />

                        </button>

                    </div>

                </form>

            </section>

        @endif

    </div>
@endsection