@extends('layouts.portal')

@section('title', 'Pendidikan Beasiswa')
@section('header', 'Pendidikan')

@section('content')
    @php($pendidikan = $pendaftaran->pendidikan)

    <div class="max-w-6xl">

        @include('mahasiswa.partials.flow-header', [
            'title' => 'Data Pendidikan',
            'description' => 'Lengkapi data perguruan tinggi dan status akademik sesuai dokumen resmi.',
            'currentStep' => 2,
        ])

        @unless($canEdit)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Pendaftaran telah dikirim. Data pendidikan hanya dapat dilihat dan tidak dapat diubah.
            </div>
        @endunless

        <form
            method="POST"
            action="{{ route('mahasiswa.pendidikan.update') }}"
            class="mt-7 space-y-6"
        >
            @csrf
            @method('PUT')

            <fieldset @disabled(!$canEdit) class="space-y-6">

                {{-- ========================================================= --}}
                {{-- INFORMASI PENDIDIKAN & PERGURUAN TINGGI (1 CARD) --}}
                {{-- ========================================================= --}}

                <section class="card overflow-hidden">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                        <h2 class="text-xl font-bold text-slate-800">
                            Informasi Pendidikan & Perguruan Tinggi
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Gunakan data yang sesuai dengan dokumen resmi perguruan tinggi.
                        </p>
                    </div>

                    <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">

                        {{-- NIM --}}
                        <div>
                            <label class="form-label">
                                NIM <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nim"
                                maxlength="50"
                                value="{{ old('nim', $pendidikan?->nim) }}"
                                placeholder="Masukkan NIM"
                                required
                            >
                        </div>

                        {{-- PERGURUAN TINGGI --}}
                        <div>
                            <label class="form-label">
                                Perguruan Tinggi <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="universitas"
                                maxlength="150"
                                value="{{ old('universitas', $pendidikan?->universitas) }}"
                                placeholder="Masukkan nama perguruan tinggi"
                                required
                            >
                        </div>

                        {{-- STATUS PERGURUAN TINGGI --}}
                        <div>
                            <label class="form-label">
                                Status Perguruan Tinggi
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                class="form-input"
                                name="status_perguruan_tinggi"
                                required
                            >
                                <option value="">
                                    Pilih status perguruan tinggi
                                </option>

                                <option
                                    value="negeri"
                                    @selected(old('status_perguruan_tinggi', $pendidikan?->status_perguruan_tinggi) === 'negeri')
                                >
                                    Perguruan Tinggi Negeri (PTN)
                                </option>

                                <option
                                    value="swasta"
                                    @selected(old('status_perguruan_tinggi', $pendidikan?->status_perguruan_tinggi) === 'swasta')
                                >
                                    Perguruan Tinggi Swasta (PTS)
                                </option>
                            </select>
                        </div>

                        {{-- FAKULTAS --}}
                        <div>
                            <label class="form-label">
                                Fakultas <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="fakultas"
                                maxlength="150"
                                value="{{ old('fakultas', $pendidikan?->fakultas) }}"
                                placeholder="Masukkan nama fakultas"
                                required
                            >
                        </div>

                        {{-- JURUSAN --}}
                        <div>
                            <label class="form-label">
                                Jurusan <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="jurusan"
                                maxlength="150"
                                value="{{ old('jurusan', $pendidikan?->jurusan) }}"
                                placeholder="Masukkan jurusan atau - jika tidak ada"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi <strong>-</strong> apabila perguruan tinggi tidak memiliki jurusan.
                            </p>
                        </div>

                        {{-- PROGRAM STUDI --}}
                        <div>
                            <label class="form-label">
                                Program Studi <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="program_studi"
                                maxlength="150"
                                value="{{ old('program_studi', $pendidikan?->program_studi) }}"
                                placeholder="Masukkan program studi atau - jika tidak ada"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi <strong>-</strong> apabila perguruan tinggi tidak memiliki program studi.
                            </p>
                        </div>

                        {{-- JENJANG --}}
                        <div>
                            <label class="form-label">
                                Jenjang Program <span class="text-red-500">*</span>
                            </label>

                            <select
                                class="form-input"
                                name="jenjang"
                                required
                            >
                                <option value="">
                                    Pilih jenjang
                                </option>

                                @foreach([
                                    'D3' => 'Diploma III (D3)',
                                    'D4' => 'Diploma IV (D4)',
                                    'S1' => 'Strata 1 (S1)',
                                ] as $value => $label)
                                    <option
                                        value="{{ $value }}"
                                        @selected(old('jenjang', $pendidikan?->jenjang) === $value)
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- SEMESTER --}}
                        <div>
                            <label class="form-label">
                                Semester Saat Ini <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="number"
                                name="semester"
                                min="1"
                                max="14"
                                value="{{ old('semester', $pendidikan?->semester) }}"
                                placeholder="Contoh: 5"
                                required
                            >
                        </div>

                        {{-- INDEKS PRESTASI TOTAL --}}
                        <div>
                            <label class="form-label">
                                Indeks Prestasi Total <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="number"
                                name="ipk"
                                min="0"
                                max="4"
                                step="0.01"
                                value="{{ old('ipk', $pendidikan?->ipk) }}"
                                placeholder="Contoh: 3.25"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Masukkan Indeks Prestasi Total / Kumulatif terakhir sesuai transkrip nilai atau KHS resmi, bukan Indeks Prestasi Semester (IPS).
                            </p>
                        </div>

                        {{-- BESARAN NILAI UKT --}}
                        <div>
                            <label class="form-label">
                                Besaran Nilai UKT (per semester) <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">
                                    Rp
                                </span>
                                <input
                                    class="form-input pl-10"
                                    type="number"
                                    name="nominal_ukt"
                                    min="0"
                                    step="1000"
                                    value="{{ old('nominal_ukt', $pendidikan?->nominal_ukt !== null ? (int) $pendidikan->nominal_ukt : '') }}"
                                    placeholder="Contoh: 3500000"
                                    required
                                >
                            </div>

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Masukkan nominal Uang Kuliah Tunggal (UKT) per semester sesuai slip pembayaran atau SK penetapan UKT, isi tanpa tanda titik (1000000).
                            </p>
                        </div>

                        {{-- TAHUN MASUK --}}
                        <div>
                            <label class="form-label">
                                Tahun Masuk <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="number"
                                name="tahun_masuk"
                                min="1990"
                                max="{{ now()->year }}"
                                value="{{ old('tahun_masuk', $pendidikan?->tahun_masuk) }}"
                                placeholder="Contoh: 2023"
                                required
                            >
                        </div>

                        {{-- AKREDITASI PERGURUAN TINGGI --}}
                        <div>
                            <label class="form-label">
                                Akreditasi Perguruan Tinggi
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="akreditasi_perguruan_tinggi"
                                maxlength="50"
                                value="{{ old('akreditasi_perguruan_tinggi', $pendidikan?->akreditasi_perguruan_tinggi) }}"
                                placeholder="Contoh: Unggul / A / Baik Sekali"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi akreditasi institusi / perguruan tinggi sesuai dokumen resmi.
                            </p>
                        </div>

                        {{-- ALAMAT PERGURUAN TINGGI --}}
                        <div class="md:col-span-2">
                            <label class="form-label">
                                Alamat Perguruan Tinggi
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                class="form-input"
                                name="alamat_perguruan_tinggi"
                                rows="3"
                                maxlength="1000"
                                placeholder="Masukkan alamat lengkap kampus perguruan tinggi"
                                required
                            >{{ old('alamat_perguruan_tinggi', $pendidikan?->alamat_perguruan_tinggi) }}</textarea>
                        </div>

                    </div>

                </section>

                {{-- ========================================================= --}}
                {{-- STATUS VERIFIKASI PDDIKTI --}}
                {{-- ========================================================= --}}

                <section
                    class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
                >
                    Status verifikasi PDDikti:
                    <strong>
                        {{ $pendidikan?->pddikti_verified_at
                            ? 'Terverifikasi pada ' . $pendidikan->pddikti_verified_at->format('d/m/Y H:i')
                            : 'Belum diverifikasi' }}
                    </strong>
                </section>

            </fieldset>

            {{-- ========================================================= --}}
            {{-- NAVIGASI --}}
            {{-- ========================================================= --}}

            <div class="flex flex-wrap justify-between gap-3">

                <a
                    href="{{ route('mahasiswa.data-pribadi.index') }}"
                    class="btn-secondary"
                >
                    Kembali ke Data Pribadi
                </a>

                <?php
                    $isNonAkademik = $pendaftaran->kategoriBeasiswa?->application_type === \App\Enums\ApplicationType::NON_AKADEMIK;
                    $nextRoute = $isNonAkademik ? route('mahasiswa.prestasi.index') : route('mahasiswa.orang-tua.index');
                    $nextLabel = $isNonAkademik ? 'Prestasi' : 'Data Orang Tua';
                ?>

                @if($canEdit)
                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Simpan & Lanjut ke {{ $nextLabel }}

                        <x-icon
                            name="arrow-right"
                            class="h-4 w-4"
                        />
                    </button>
                @else
                    <a
                        href="{{ $nextRoute }}"
                        class="btn-primary"
                    >
                        Lanjut ke {{ $nextLabel }}
                    </a>
                @endif

            </div>

        </form>

    </div>
@endsection
