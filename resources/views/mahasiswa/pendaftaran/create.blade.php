@extends('layouts.portal')

@section('title', 'Buat Pendaftaran Beasiswa')
@section('header', 'Buat Pendaftaran')

@section('content')
    <div class="max-w-5xl">
        <div>
            <h1 class="text-3xl font-extrabold">Buat Pendaftaran Beasiswa</h1>
            <p class="mt-2 leading-7 text-slate-600">
                Pilih kategori dan jenis beasiswa yang sesuai sebelum membuat
                draft pendaftaran. Pilihan kategori dan jenis beasiswa tidak dapat
                diubah setelah draft dibuat.
            </p>
        </div>

        <form action="{{ route('mahasiswa.pendaftaran.store') }}" method="POST" class="mt-7 space-y-6">
            @csrf

            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Periode Aktif</p>
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-extrabold">{{ $periode->nama }}</h2>
                            <p class="mt-1 text-sm text-slate-500">Tahun {{ $periode->tahun }} ·
                                {{ $periode->tanggal_mulai->format('d/m/Y') }}–{{ $periode->tanggal_selesai->format('d/m/Y') }}
                            </p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-4 py-2 text-xs font-bold text-emerald-800">AKTIF</span>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    {{-- ========================================================= --}}
                    {{-- KATEGORI BEASISWA --}}
                    {{-- REGULER / NON REGULER --}}
                    {{-- ========================================================= --}}

                    <div>

                        <h2 class="text-lg font-bold">
                            Pilih Kategori Beasiswa
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Pilih kategori berdasarkan metode perkuliahan yang sedang
                            Anda jalani.
                        </p>


                        @if ($jalurBeasiswas->isEmpty())

                            <div
                                class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
                                Belum ada kategori beasiswa yang aktif.
                            </div>
                        @else
                            <div class="mt-5 grid gap-5 md:grid-cols-2">

                                @foreach ($jalurBeasiswas as $jalur)
                                    <label class="block cursor-pointer">

                                        <input type="radio" name="jalur_beasiswa_id" value="{{ $jalur->id }}"
                                            class="peer sr-only" @checked(old('jalur_beasiswa_id') == $jalur->id)>

                                        <div
                                            class="h-full rounded-xl border-2 border-slate-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-md peer-checked:border-brand-600 peer-checked:ring-2 peer-checked:ring-brand-100">

                                            <div class="flex items-start justify-between gap-4">

                                                <div
                                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 text-brand-700">

                                                    <x-icon name="form" class="h-6 w-6" />

                                                </div>

                                                <span
                                                    class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                                    Kategori
                                                </span>

                                            </div>


                                            <h3 class="mt-5 text-xl font-extrabold">
                                                {{ $jalur->nama }}
                                            </h3>


                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                {{ $jalur->deskripsi }}
                                            </p>


                                            <p class="mt-5 text-sm font-bold text-brand-700">
                                                Pilih kategori ini
                                            </p>

                                        </div>

                                    </label>
                                @endforeach

                            </div>

                        @endif

                    </div>


                    {{-- ========================================================= --}}
                    {{-- JENIS BEASISWA --}}
                    {{-- 4 JENIS --}}
                    {{-- ========================================================= --}}

                    <div class="mt-10">

                        <h2 class="text-lg font-bold">
                            Pilih Jenis Beasiswa
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Pilih jenis beasiswa yang sesuai dengan kriteria Anda.
                        </p>


                        @if ($kategoriBeasiswas->isEmpty())

                            <div
                                class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
                                Belum ada jenis beasiswa yang aktif pada periode ini.
                            </div>
                        @else
                            <div class="mt-5 grid gap-5 md:grid-cols-2">

                                @foreach ($kategoriBeasiswas as $kategori)
                                    <label class="block cursor-pointer">

                                        <input type="radio" name="kategori_beasiswa_id" value="{{ $kategori->id }}"
                                            class="peer sr-only" @checked(old('kategori_beasiswa_id') == $kategori->id)>

                                        <div
                                            class="h-full rounded-xl border-2 border-slate-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-md peer-checked:border-brand-600 peer-checked:ring-2 peer-checked:ring-brand-100">

                                            <div class="flex items-start justify-between gap-4">

                                                <div
                                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100 text-brand-700">

                                                    <x-icon name="form" class="h-6 w-6" />

                                                </div>

                                                @if (isset($kategori->kuota))
                                                    <span
                                                        class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                                        Kuota {{ $kategori->kuota }}
                                                    </span>
                                                @endif

                                            </div>


                                            <h3 class="mt-5 text-xl font-extrabold">
                                                {{ $kategori->nama }}
                                            </h3>


                                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                                {{ $kategori->deskripsi }}
                                            </p>


                                            <p class="mt-5 text-sm font-bold text-brand-700">
                                                Pilih jenis ini
                                            </p>

                                        </div>

                                    </label>
                                @endforeach

                            </div>

                        @endif

                    </div>

                    <label
                        class="mt-8 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-5">
                        <input type="checkbox" id="persetujuan" name="persetujuan" value="1"
                            class="mt-1 h-5 w-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                            @checked(old('persetujuan'))>

                        <div>

                            <span class="text-sm font-semibold leading-6 text-slate-800">
                                Saya telah membaca dan menyetujui pilihan kategori dan jenis
                                beasiswa yang dipilih.
                            </span>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Pastikan pilihan Anda sudah sesuai sebelum membuat draft
                                pendaftaran.
                            </p>

                            <div
                                class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                                <span class="font-bold">Perhatian:</span>
                                Setelah draft pendaftaran dibuat, pilihan
                                <strong>Reguler/Non Reguler</strong> dan
                                <strong>Jenis Beasiswa</strong> tidak dapat diubah.
                                Pastikan pilihan sudah benar sebelum melanjutkan ke pengisian
                                data pendaftaran.
                            </div>

                        </div>
                    </label>
                </div>
            </section>

            <div class="flex flex-wrap justify-between gap-3">

                <a href="{{ route('mahasiswa.dashboard') }}" class="btn-secondary">
                    Kembali
                </a>

                <button type="submit" id="btn-buat-draft"
                    class="btn-primary disabled:cursor-not-allowed disabled:opacity-50" disabled>
                    Buat Draft Pendaftaran
                </button>

            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const button = document.getElementById('btn-buat-draft');
        const persetujuan = document.getElementById('persetujuan');

        function updateButton() {

            const jalurDipilih = document.querySelector(
                'input[name="jalur_beasiswa_id"]:checked'
            );

            const jenisDipilih = document.querySelector(
                'input[name="kategori_beasiswa_id"]:checked'
            );

            const lengkap =
                jalurDipilih &&
                jenisDipilih &&
                persetujuan.checked;

            button.disabled = !lengkap;
        }


        /*
        |--------------------------------------------------------------------------
        | Pilihan Kategori
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('input[name="jalur_beasiswa_id"]')
            .forEach(function (input) {

                input.addEventListener('change', updateButton);

            });


        /*
        |--------------------------------------------------------------------------
        | Pilihan Jenis Beasiswa
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('input[name="kategori_beasiswa_id"]')
            .forEach(function (input) {

                input.addEventListener('change', updateButton);

            });


        /*
        |--------------------------------------------------------------------------
        | Checkbox Persetujuan
        |--------------------------------------------------------------------------
        */

        persetujuan.addEventListener('change', updateButton);


        /*
        |--------------------------------------------------------------------------
        | Cek kondisi awal
        |--------------------------------------------------------------------------
        */

        updateButton();

    });
</script>
@endpush
