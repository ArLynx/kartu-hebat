@extends('layouts.portal')

@section('title', 'Prestasi Beasiswa')
@section('header', 'Prestasi')

@section('content')
    <div class="max-w-6xl">

        @include('mahasiswa.partials.flow-header', [
            'title' => 'Prestasi',
            'description' => 'Tambahkan prestasi akademik atau nonakademik jika ada. Jika tidak memiliki prestasi, Anda tetap dapat melanjutkan ke tahap berikutnya.',
            'currentStep' => 3,
        ])

        @unless($canEdit)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Pendaftaran telah dikirim. Data prestasi hanya dapat dilihat dan tidak dapat diubah.
            </div>
        @endunless


        {{-- ========================================================= --}}
        {{-- TAMBAH PRESTASI --}}
        {{-- ========================================================= --}}

        @if($canEdit)

            <section class="card mt-7 overflow-hidden">

                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                    <h2 class="text-xl font-bold">
                        Tambah Prestasi
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Tambahkan prestasi yang pernah diperoleh. Anda dapat menambahkan lebih dari satu prestasi.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('mahasiswa.prestasi.store') }}"
                    enctype="multipart/form-data"
                    class="grid gap-5 p-6 sm:p-8 md:grid-cols-2"
                >

                    @csrf


                    {{-- JENIS --}}
                    <div>

                        <label class="form-label">
                            Jenis Prestasi
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            class="form-input"
                            name="jenis"
                            required
                        >
                            <option value="">
                                Pilih jenis prestasi
                            </option>

                            <option
                                value="akademik"
                                @selected(old('jenis') === 'akademik')
                            >
                                Akademik
                            </option>

                            <option
                                value="non_akademik"
                                @selected(old('jenis') === 'non_akademik')
                            >
                                Nonakademik
                            </option>
                        </select>

                    </div>


                    {{-- TINGKAT --}}
                    <div>

                        <label class="form-label">
                            Tingkat
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            class="form-input"
                            name="tingkat"
                            required
                        >
                            <option value="">
                                Pilih tingkat
                            </option>

                            @foreach([
                                'kampus' => 'Kampus',
                                'kabupaten' => 'Kabupaten/Kota',
                                'provinsi' => 'Provinsi',
                                'nasional' => 'Nasional',
                                'internasional' => 'Internasional',
                            ] as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(old('tingkat') === $value)
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- NAMA PRESTASI --}}
                    <div class="md:col-span-2">

                        <label class="form-label">
                            Nama Prestasi
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            class="form-input"
                            type="text"
                            name="nama_prestasi"
                            value="{{ old('nama_prestasi') }}"
                            placeholder="Contoh: Juara 1 Lomba Karya Tulis Ilmiah"
                            required
                        >

                    </div>


                    {{-- PERINGKAT --}}
                    <div>

                        <label class="form-label">
                            Peringkat/Penghargaan
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            class="form-input"
                            type="text"
                            name="peringkat"
                            value="{{ old('peringkat') }}"
                            placeholder="Contoh: Juara 1"
                            required
                        >

                    </div>


                    {{-- TAHUN --}}
                    <div>

                        <label class="form-label">
                            Tahun
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            class="form-input"
                            type="number"
                            name="tahun"
                            min="1990"
                            max="{{ now()->year }}"
                            value="{{ old('tahun') }}"
                            placeholder="Contoh: {{ now()->year }}"
                            required
                        >

                    </div>


                    {{-- PENYELENGGARA --}}
                    <div class="md:col-span-2">

                        <label class="form-label">
                            Penyelenggara
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            class="form-input"
                            type="text"
                            name="penyelenggara"
                            value="{{ old('penyelenggara') }}"
                            placeholder="Contoh: Universitas Palangka Raya"
                            required
                        >

                    </div>


                    {{-- DOKUMEN --}}
                    <div class="md:col-span-2">

                        <label class="form-label">
                            Dokumen Prestasi
                            <span class="font-normal text-slate-400">(opsional)</span>
                        </label>

                        <input
                            class="form-input"
                            type="file"
                            name="dokumen"
                            accept=".pdf,.jpg,.jpeg,.png"
                        >

                        <p class="mt-2 text-xs text-slate-500">
                            Format PDF, JPG, JPEG, atau PNG. Maksimal 2 MB.
                        </p>

                    </div>


                    {{-- KETERANGAN --}}
                    <div class="md:col-span-2">

                        <label class="form-label">
                            Keterangan
                            <span class="font-normal text-slate-400">(opsional)</span>
                        </label>

                        <textarea
                            class="form-input"
                            name="keterangan"
                            rows="3"
                            placeholder="Keterangan tambahan jika diperlukan"
                        >{{ old('keterangan') }}</textarea>

                    </div>


                    {{-- BUTTON --}}
                    <div class="md:col-span-2 flex justify-end">

                        <button
                            class="btn-primary"
                            type="submit"
                        >
                            Tambah Prestasi
                        </button>

                    </div>

                </form>

            </section>

        @endif


        {{-- ========================================================= --}}
        {{-- DAFTAR PRESTASI --}}
        {{-- ========================================================= --}}

        <section class="card mt-7 overflow-hidden">

            <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                <h2 class="text-xl font-bold">
                    Daftar Prestasi
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pendaftaran->prestasis->count() }} prestasi tercatat.
                </p>

            </div>


            <div class="space-y-4 p-6 sm:p-8">

                @forelse($pendaftaran->prestasis as $prestasi)

                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                            <div>

                                <div class="flex flex-wrap gap-2">

                                    <span class="status-chip status-info">
                                        {{ $prestasi->jenis === 'akademik' ? 'Akademik' : 'Nonakademik' }}
                                    </span>

                                    <span class="status-chip status-neutral">
                                        {{ ucfirst($prestasi->tingkat) }}
                                    </span>

                                </div>


                                <h3 class="mt-3 text-lg font-bold">
                                    {{ $prestasi->nama_prestasi }}
                                </h3>


                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $prestasi->peringkat }}
                                    ·
                                    {{ $prestasi->penyelenggara }}
                                    ·
                                    {{ $prestasi->tahun }}
                                </p>


                                @if($prestasi->keterangan)

                                    <p class="mt-2 text-sm leading-6 text-slate-500">
                                        {{ $prestasi->keterangan }}
                                    </p>

                                @endif


                                @if($prestasi->dokumen_prestasi)

                                    <a
                                        href="{{ route('mahasiswa.prestasi.download', $prestasi) }}"
                                        class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:underline"
                                    >
                                        Unduh dokumen prestasi
                                    </a>

                                @endif

                            </div>


                            @if($canEdit)

                                <form
                                    method="POST"
                                    action="{{ route('mahasiswa.prestasi.destroy', $prestasi) }}"
                                    onsubmit="return confirm('Hapus prestasi ini?')"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-sm font-semibold text-red-600 hover:underline"
                                    >
                                        Hapus
                                    </button>

                                </form>

                            @endif

                        </div>


                        {{-- EDIT --}}
                        @if($canEdit)

                            <details class="mt-4 border-t border-slate-200 pt-4">

                                <summary class="cursor-pointer text-sm font-semibold text-brand-700">
                                    Ubah data prestasi
                                </summary>


                                <form
                                    method="POST"
                                    action="{{ route('mahasiswa.prestasi.update', $prestasi) }}"
                                    enctype="multipart/form-data"
                                    class="mt-4 grid gap-4 md:grid-cols-2"
                                >

                                    @csrf
                                    @method('PUT')


                                    {{-- JENIS --}}
                                    <div>

                                        <label class="form-label">
                                            Jenis Prestasi
                                        </label>

                                        <select
                                            class="form-input"
                                            name="jenis"
                                            required
                                        >

                                            <option
                                                value="akademik"
                                                @selected($prestasi->jenis === 'akademik')
                                            >
                                                Akademik
                                            </option>

                                            <option
                                                value="non_akademik"
                                                @selected($prestasi->jenis === 'non_akademik')
                                            >
                                                Nonakademik
                                            </option>

                                        </select>

                                    </div>


                                    {{-- TINGKAT --}}
                                    <div>

                                        <label class="form-label">
                                            Tingkat
                                        </label>

                                        <select
                                            class="form-input"
                                            name="tingkat"
                                            required
                                        >

                                            @foreach([
                                                'kampus' => 'Kampus',
                                                'kabupaten' => 'Kabupaten/Kota',
                                                'provinsi' => 'Provinsi',
                                                'nasional' => 'Nasional',
                                                'internasional' => 'Internasional',
                                            ] as $value => $label)

                                                <option
                                                    value="{{ $value }}"
                                                    @selected($prestasi->tingkat === $value)
                                                >
                                                    {{ $label }}
                                                </option>

                                            @endforeach

                                        </select>

                                    </div>


                                    {{-- NAMA --}}
                                    <div class="md:col-span-2">

                                        <label class="form-label">
                                            Nama Prestasi
                                        </label>

                                        <input
                                            class="form-input"
                                            type="text"
                                            name="nama_prestasi"
                                            value="{{ $prestasi->nama_prestasi }}"
                                            required
                                        >

                                    </div>


                                    {{-- PERINGKAT --}}
                                    <div>

                                        <label class="form-label">
                                            Peringkat/Penghargaan
                                        </label>

                                        <input
                                            class="form-input"
                                            type="text"
                                            name="peringkat"
                                            value="{{ $prestasi->peringkat }}"
                                            required
                                        >

                                    </div>


                                    {{-- TAHUN --}}
                                    <div>

                                        <label class="form-label">
                                            Tahun
                                        </label>

                                        <input
                                            class="form-input"
                                            type="number"
                                            name="tahun"
                                            min="1990"
                                            max="{{ now()->year }}"
                                            value="{{ $prestasi->tahun }}"
                                            required
                                        >

                                    </div>


                                    {{-- PENYELENGGARA --}}
                                    <div class="md:col-span-2">

                                        <label class="form-label">
                                            Penyelenggara
                                        </label>

                                        <input
                                            class="form-input"
                                            type="text"
                                            name="penyelenggara"
                                            value="{{ $prestasi->penyelenggara }}"
                                            required
                                        >

                                    </div>


                                    {{-- DOKUMEN --}}
                                    <div class="md:col-span-2">

                                        <label class="form-label">
                                            Ganti Dokumen Prestasi
                                            <span class="font-normal text-slate-400">(opsional)</span>
                                        </label>

                                        <input
                                            class="form-input"
                                            type="file"
                                            name="dokumen"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                        >

                                    </div>


                                    {{-- KETERANGAN --}}
                                    <div class="md:col-span-2">

                                        <label class="form-label">
                                            Keterangan
                                            <span class="font-normal text-slate-400">(opsional)</span>
                                        </label>

                                        <textarea
                                            class="form-input"
                                            name="keterangan"
                                            rows="2"
                                        >{{ $prestasi->keterangan }}</textarea>

                                    </div>


                                    <div class="md:col-span-2 flex justify-end">

                                        <button
                                            class="btn-secondary"
                                            type="submit"
                                        >
                                            Simpan Perubahan
                                        </button>

                                    </div>

                                </form>

                            </details>

                        @endif

                    </article>

                @empty

                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">

                        <p class="text-sm font-semibold text-slate-600">
                            Belum ada prestasi yang ditambahkan.
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Jika Anda tidak memiliki prestasi, Anda dapat langsung melanjutkan ke tahap berikutnya.
                        </p>

                    </div>

                @endforelse

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- NAVIGASI --}}
        {{-- ========================================================= --}}

        <div class="mt-6 flex flex-wrap justify-between gap-3">

            <a
                href="{{ route('mahasiswa.pendidikan.index') }}"
                class="btn-secondary"
            >
                Kembali ke Pendidikan
            </a>


            @if($canEdit)

                <form
                    method="POST"
                    action="{{ route('mahasiswa.prestasi.confirm') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Konfirmasi & Lanjut ke Orang Tua

                        <x-icon
                            name="arrow-right"
                            class="h-4 w-4"
                        />
                    </button>

                </form>

            @else

                <a
                    href="{{ route('mahasiswa.orang-tua.index') }}"
                    class="btn-primary"
                >
                    Lanjut ke Orang Tua
                </a>

            @endif

        </div>

    </div>
@endsection