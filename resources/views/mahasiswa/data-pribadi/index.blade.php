@extends('layouts.portal')

@section('title', 'Data Pribadi Beasiswa')
@section('header', 'Data Pribadi')

@section('content')
    @php($data = $pendaftaran->dataPribadi)

    <div class="max-w-6xl">

        {{-- ============================================================= --}}
        {{-- FLOW HEADER --}}
        {{-- ============================================================= --}}

        @include('mahasiswa.partials.flow-header', [
            'title' => 'Data Pribadi',
            'description' =>
                'Lengkapi identitas sesuai dokumen resmi untuk pendaftaran ' .
                $pendaftaran->nomor_pendaftaran .
                '.',
            'currentStep' => 1,
        ])


        {{-- ============================================================= --}}
        {{-- STATUS JIKA SUDAH DIKIRIM --}}
        {{-- ============================================================= --}}

        @unless ($canEdit)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                Pendaftaran telah dikirim. Data hanya dapat dilihat dan tidak dapat diubah.
            </div>
        @endunless


        {{-- ============================================================= --}}
        {{-- INFORMASI PENGISIAN --}}
        {{-- ============================================================= --}}

        @if ($canEdit)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="flex items-start gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                        <x-icon name="information-circle" class="h-5 w-5" />
                    </div>

                    <div>
                        <h3 class="font-bold text-blue-900">
                            Informasi Pengisian
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-blue-800">
                            Anda dapat membuka tab tahapan pendaftaran atau menu pada sidebar
                            untuk melihat dan mempelajari setiap bagian yang harus dilengkapi.
                            Data wajib ditandai dengan tanda
                            <span class="font-bold text-red-600">*</span>
                            dan harus diisi sebelum dapat disimpan dan dilanjutkan ke tahap berikutnya.
                        </p>
                    </div>

                </div>
            </div>
        @endif


        {{-- ============================================================= --}}
        {{-- FORM --}}
        {{-- ============================================================= --}}

        <form
            method="POST"
            action="{{ route('mahasiswa.data-pribadi.update') }}"
            class="mt-7 space-y-6"
        >
            @csrf
            @method('PUT')


            <fieldset
                @disabled(!$canEdit)
                class="space-y-6"
            >

                {{-- ===================================================== --}}
                {{-- INFORMASI MAHASISWA --}}
                {{-- ===================================================== --}}

                <section class="card overflow-hidden">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                        <h2 class="text-xl font-bold">
                            Informasi Mahasiswa
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Isi sesuai dengan identitas resmi mahasiswa.
                        </p>
                    </div>


                    <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">

                        {{-- ================================================= --}}
                        {{-- NIK --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                NIK
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nik"
                                maxlength="16"
                                inputmode="numeric"
                                value="{{ old('nik', $data?->nik) }}"
                                placeholder="Masukkan NIK"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                NIK harus terdiri dari 16 digit.
                            </p>
                        </div>


                        {{-- ================================================= --}}
                        {{-- REKENING BANK KALTENG --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Nomor Rekening Bank Kalteng
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nomor_rekening"
                                inputmode="numeric"
                                value="{{ old('nomor_rekening', $data?->nomor_rekening) }}"
                                placeholder="Masukkan nomor rekening Bank Kalteng"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Wajib menggunakan rekening atas nama mahasiswa pada
                                PT Bank Pembangunan Daerah Kalimantan Tengah (Bank Kalteng).
                            </p>
                        </div>


                        {{-- ================================================= --}}
                        {{-- NAMA LENGKAP --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Nama Lengkap
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_lengkap"
                                value="{{ old('nama_lengkap', $data?->nama_lengkap ?: auth()->user()->name) }}"
                                placeholder="Masukkan nama lengkap"
                                required
                            >
                        </div>


                        {{-- ================================================= --}}
                        {{-- NO HP --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Nomor HP/WhatsApp
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="no_hp"
                                inputmode="tel"
                                value="{{ old('no_hp', $data?->no_hp) }}"
                                placeholder="Contoh: 081234567890"
                                required
                            >
                        </div>


                        {{-- ================================================= --}}
                        {{-- TEMPAT LAHIR --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Tempat Lahir
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="tempat_lahir"
                                value="{{ old('tempat_lahir', $data?->tempat_lahir) }}"
                                placeholder="Masukkan tempat lahir"
                                required
                            >
                        </div>


                        {{-- ================================================= --}}
                        {{-- TANGGAL LAHIR --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Tanggal Lahir
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="date"
                                name="tanggal_lahir"
                                max="{{ today()->subDay()->format('Y-m-d') }}"
                                value="{{ old('tanggal_lahir', $data?->tanggal_lahir?->format('Y-m-d')) }}"
                                required
                            >
                        </div>


                        {{-- ================================================= --}}
                        {{-- JENIS KELAMIN --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Jenis Kelamin
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                class="form-input"
                                name="jenis_kelamin"
                                required
                            >
                                <option value="">
                                    Pilih jenis kelamin
                                </option>

                                <option
                                    value="L"
                                    @selected(old('jenis_kelamin', $data?->jenis_kelamin) === 'L')
                                >
                                    Laki-laki
                                </option>

                                <option
                                    value="P"
                                    @selected(old('jenis_kelamin', $data?->jenis_kelamin) === 'P')
                                >
                                    Perempuan
                                </option>
                            </select>
                        </div>


                        {{-- ================================================= --}}
                        {{-- AGAMA --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Agama
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                class="form-input"
                                name="agama"
                                required
                            >
                                <option value="">
                                    Pilih agama
                                </option>

                                @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $agama)
                                    <option
                                        value="{{ $agama }}"
                                        @selected(old('agama', $data?->agama) === $agama)
                                    >
                                        {{ $agama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        {{-- ================================================= --}}
                        {{-- EMAIL --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label class="form-label">
                                Email Akun
                            </label>

                            <input
                                class="form-input bg-slate-100"
                                type="email"
                                value="{{ auth()->user()->email }}"
                                readonly
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Email mengikuti akun mahasiswa dan tidak dapat diubah
                                pada halaman ini.
                            </p>

                        </div>

                    </div>

                </section>


                {{-- ===================================================== --}}
                {{-- ALAMAT --}}
                {{-- ===================================================== --}}

                <section class="card overflow-hidden">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                        <h2 class="text-xl font-bold">
                            Alamat
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Isi alamat sesuai dengan dokumen identitas mahasiswa.
                        </p>

                    </div>


                    <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">

                        {{-- ================================================= --}}
                        {{-- ALAMAT LENGKAP --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label class="form-label">
                                Alamat Lengkap (Sesuai KTP)
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                class="form-input"
                                name="alamat"
                                rows="3"
                                placeholder="Masukkan alamat lengkap sesuai KTP"
                                required
                            >{{ old('alamat', $data?->alamat) }}</textarea>

                        </div>


                        {{-- ================================================= --}}
                        {{-- PROVINSI --}}
                        {{-- ================================================= --}}

                        <div>

                            <label class="form-label">
                                Provinsi
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="provinsi"
                                value="{{ old('provinsi', $data?->provinsi) }}"
                                placeholder="Masukkan provinsi"
                                required
                            >

                        </div>


                        {{-- ================================================= --}}
                        {{-- DESA --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label class="form-label">
                                Desa/Kelurahan sesuai Master Wilayah
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                class="form-input"
                                name="village_id"
                                required
                            >

                                <option value="">
                                    Pilih desa/kelurahan
                                </option>

                                @foreach ($villages as $village)

                                    <option
                                        value="{{ $village->id }}"
                                        @selected(
                                            (int) old('village_id', $data?->village_id)
                                            === (int) $village->id
                                        )
                                    >
                                        {{ $village->display_name }}
                                        — Kecamatan {{ $village->kecamatan->name }},
                                        Kabupaten {{ $village->kabupaten->name }}
                                    </option>

                                @endforeach

                            </select>

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Pilihan ini menentukan antrean verifikator
                                desa, kecamatan, lintas dinas, dan kabupaten.
                            </p>

                        </div>


                        {{-- ================================================= --}}
                        {{-- KODE POS --}}
                        {{-- ================================================= --}}

                        <div>

                            <label class="form-label">
                                Kode Pos
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="kode_pos"
                                inputmode="numeric"
                                maxlength="10"
                                value="{{ old('kode_pos', $data?->kode_pos) }}"
                                placeholder="Masukkan kode pos"
                                required
                            >

                        </div>

                    </div>

                </section>

            </fieldset>


            {{-- ============================================================= --}}
            {{-- BUTTON --}}
            {{-- ============================================================= --}}

            <div class="flex flex-wrap justify-between gap-3">

                <a
                    href="{{ route('mahasiswa.dashboard') }}"
                    class="btn-secondary"
                >
                    Kembali
                </a>


                @if ($canEdit)

                    <button
                        class="btn-primary"
                        type="submit"
                    >
                        Simpan & Lanjut ke Pendidikan

                        <x-icon
                            name="arrow-right"
                            class="h-4 w-4"
                        />
                    </button>

                @else

                    <a
                        href="{{ route('mahasiswa.pendidikan.index') }}"
                        class="btn-primary"
                    >
                        Lanjut ke Pendidikan

                        <x-icon
                            name="arrow-right"
                            class="h-4 w-4"
                        />
                    </a>

                @endif

            </div>

        </form>

    </div>
@endsection