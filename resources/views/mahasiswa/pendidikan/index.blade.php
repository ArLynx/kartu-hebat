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
                {{-- INFORMASI AKADEMIK --}}
                {{-- ========================================================= --}}

                <section class="card overflow-hidden">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                        <h2 class="text-xl font-bold">
                            Informasi Akademik
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
                                placeholder="Masukkan jurusan atau '-' jika tidak ada"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi <strong>'-'</strong> apabila perguruan tinggi tidak memiliki jurusan.
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
                                placeholder="Masukkan program studi atau '-' jika tidak ada"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi <strong>'-' </strong> apabila perguruan tinggi tidak memiliki program studi.
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
                                    'S2' => 'Strata 2 (S2)',
                                    'S3' => 'Strata 3 (S3)',
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


                        {{-- STATUS MAHASISWA --}}
                        <div>
                            <label class="form-label">
                                Status Mahasiswa <span class="text-red-500">*</span>
                            </label>

                            <select
                                class="form-input"
                                name="status_mahasiswa"
                                required
                            >
                                <option value="">
                                    Pilih status mahasiswa
                                </option>

                                @foreach([
                                    'aktif' => 'Aktif',
                                    'cuti' => 'Cuti',
                                    'lulus' => 'Lulus',
                                    'drop_out' => 'Drop Out',
                                    'nonaktif' => 'Nonaktif',
                                ] as $value => $label)

                                    <option
                                        value="{{ $value }}"
                                        @selected(old('status_mahasiswa', $pendidikan?->status_mahasiswa) === $value)
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


                        {{-- IPK --}}
                        <div>
                            <label class="form-label">
                                IPK <span class="text-red-500">*</span>
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
                                Masukkan IPK terakhir sesuai dokumen akademik.
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


                        {{-- TAHUN LULUS --}}
                        <div>
                            <label class="form-label">
                                Tahun Lulus
                                <span class="font-normal text-slate-400">
                                    (opsional)
                                </span>
                            </label>

                            <input
                                class="form-input"
                                type="number"
                                name="tahun_lulus"
                                min="1990"
                                max="{{ now()->year + 10 }}"
                                value="{{ old('tahun_lulus', $pendidikan?->tahun_lulus) }}"
                                placeholder="Kosongkan jika belum lulus"
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Kosongkan apabila mahasiswa masih menempuh pendidikan.
                            </p>
                        </div>

                    </div>

                </section>


                {{-- ========================================================= --}}
                {{-- INFORMASI PERGURUAN TINGGI --}}
                {{-- ========================================================= --}}

                <section class="card overflow-hidden">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                        <h2 class="text-xl font-bold">
                            Informasi Perguruan Tinggi
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Lengkapi informasi sesuai dokumen resmi perguruan tinggi.
                        </p>

                    </div>


                    <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">

                        {{-- AKREDITASI PT --}}
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
                                Isi akreditasi institusi/perguruan tinggi.
                            </p>
                        </div>


                        {{-- AKREDITASI JURUSAN / PRODI --}}
                        <div>
                            <label class="form-label">
                                Akreditasi Jurusan / Program Studi
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="akreditasi_program_studi"
                                maxlength="50"
                                value="{{ old('akreditasi_program_studi', $pendidikan?->akreditasi_program_studi) }}"
                                placeholder="Contoh: Unggul / A / Baik Sekali atau '-'"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi akreditasi jurusan/program studi sesuai data resmi.
                                Isi <strong>'-'</strong> apabila tidak tersedia.
                            </p>
                        </div>


                        {{-- KETUA PRODI --}}
                        <div>
                            <label class="form-label">
                                Nama Ketua Prodi
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_ketua_prodi"
                                maxlength="150"
                                value="{{ old('nama_ketua_prodi', $pendidikan?->nama_ketua_prodi) }}"
                                placeholder="Nama Ketua Prodi atau '-' "
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi <strong>'-'</strong> apabila tidak memiliki Ketua Prodi.
                            </p>
                        </div>


                        {{-- KETUA JURUSAN --}}
                        <div>
                            <label class="form-label">
                                Nama Ketua Jurusan
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_ketua_jurusan"
                                maxlength="150"
                                value="{{ old('nama_ketua_jurusan', $pendidikan?->nama_ketua_jurusan) }}"
                                placeholder="Nama Ketua Jurusan atau '-'"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi <strong>'-'</strong> apabila tidak memiliki Ketua Jurusan.
                            </p>
                        </div>


                        {{-- REKTOR --}}
                        <div>
                            <label class="form-label">
                                Nama Rektor
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_rektor"
                                maxlength="150"
                                value="{{ old('nama_rektor', $pendidikan?->nama_rektor) }}"
                                placeholder="Nama Rektor atau '-' "
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi <strong>'-'</strong> apabila perguruan tinggi tidak menggunakan jabatan Rektor.
                            </p>
                        </div>


                        {{-- DIREKTUR --}}
                        <div>
                            <label class="form-label">
                                Nama Direktur
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_direktur"
                                maxlength="150"
                                value="{{ old('nama_direktur', $pendidikan?->nama_direktur) }}"
                                placeholder="Nama Direktur atau '-'"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Isi <strong>'-'</strong> apabila perguruan tinggi tidak menggunakan jabatan Direktur.
                            </p>
                        </div>


                        {{-- ALAMAT PT --}}
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
                                placeholder="Masukkan alamat lengkap perguruan tinggi"
                                required
                            >{{ old('alamat_perguruan_tinggi', $pendidikan?->alamat_perguruan_tinggi) }}</textarea>

                        </div>


                        {{-- TELEPON PT --}}
                        <div>

                            <label class="form-label">
                                No. Telp/HP Perguruan Tinggi
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="no_telp_perguruan_tinggi"
                                maxlength="30"
                                inputmode="tel"
                                value="{{ old('no_telp_perguruan_tinggi', $pendidikan?->no_telp_perguruan_tinggi) }}"
                                placeholder="Masukkan nomor telepon perguruan tinggi atau isi '-'"
                                required
                            >

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


                @if($canEdit)

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Simpan & Lanjut ke Prestasi

                        <x-icon
                            name="arrow-right"
                            class="h-4 w-4"
                        />
                    </button>

                @else

                    <a
                        href="{{ route('mahasiswa.prestasi.index') }}"
                        class="btn-primary"
                    >
                        Lanjut ke Prestasi
                    </a>

                @endif

            </div>

        </form>

    </div>
@endsection