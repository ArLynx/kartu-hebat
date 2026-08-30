@extends('layouts.portal')

@section('title', 'Data Pribadi Beasiswa')
@section('header', 'Data Pribadi')

@section('content')
@php($data = $pendaftaran->dataPribadi)

<div class="max-w-6xl">
    @include('mahasiswa.partials.flow-header', [
        'title' => 'Data Pribadi',
        'description' => 'Lengkapi identitas sesuai dokumen resmi untuk pendaftaran '.$pendaftaran->nomor_pendaftaran.'.',
        'currentStep' => 1,
    ])

    @unless($canEdit)
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
            Pendaftaran telah dikirim. Data hanya dapat dilihat dan tidak dapat diubah.
        </div>
    @endunless

    <form method="POST" action="{{ route('mahasiswa.data-pribadi.update') }}" class="mt-7 space-y-6">
        @csrf
        @method('PUT')

        <fieldset @disabled(!$canEdit) class="space-y-6">
            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                    <h2 class="text-xl font-bold">Informasi Mahasiswa</h2>
                </div>
                <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">
                    <div>
                        <label class="form-label">NIK</label>
                        <input class="form-input" type="text" name="nik" maxlength="16" inputmode="numeric" value="{{ old('nik', $data?->nik) }}" required>
                    </div>
                    <div>
                        <label class="form-label">NISN <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input class="form-input" type="text" name="nisn" inputmode="numeric" value="{{ old('nisn', $data?->nisn) }}">
                    </div>
                    <div>
                        <label class="form-label">Nama Lengkap</label>
                        <input class="form-input" type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $data?->nama_lengkap ?: auth()->user()->name) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Nomor HP/WhatsApp</label>
                        <input class="form-input" type="text" name="no_hp" value="{{ old('no_hp', $data?->no_hp) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Tempat Lahir</label>
                        <input class="form-input" type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $data?->tempat_lahir) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Lahir</label>
                        <input class="form-input" type="date" name="tanggal_lahir" max="{{ today()->subDay()->format('Y-m-d') }}" value="{{ old('tanggal_lahir', $data?->tanggal_lahir?->format('Y-m-d')) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Jenis Kelamin</label>
                        <select class="form-input" name="jenis_kelamin" required>
                            <option value="">Pilih</option>
                            <option value="L" @selected(old('jenis_kelamin', $data?->jenis_kelamin) === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('jenis_kelamin', $data?->jenis_kelamin) === 'P')>Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Agama <span class="font-normal text-slate-400">(opsional)</span></label>
                        <select class="form-input" name="agama">
                            <option value="">Pilih</option>
                            @foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $agama)
                                <option value="{{ $agama }}" @selected(old('agama', $data?->agama) === $agama)>{{ $agama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Email Akun</label>
                        <input class="form-input bg-slate-100" type="email" value="{{ auth()->user()->email }}" readonly>
                    </div>
                </div>
            </section>

            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                    <h2 class="text-xl font-bold">Alamat Domisili</h2>
                </div>
                <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea class="form-input" name="alamat" rows="3" required>{{ old('alamat', $data?->alamat) }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Provinsi</label>
                        <input class="form-input" name="provinsi" value="{{ old('provinsi', $data?->provinsi) }}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Desa/Kelurahan sesuai Master Wilayah</label>
                        <select class="form-input" name="village_id" required>
                            <option value="">Pilih desa/kelurahan</option>
                            @foreach($villages as $village)
                                <option value="{{ $village->id }}" @selected((int) old('village_id', $data?->village_id) === (int) $village->id)>
                                    {{ $village->display_name }} — Kecamatan {{ $village->kecamatan->name }}, Kabupaten {{ $village->kabupaten->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Pilihan ini menentukan antrean verifikator lintas dinas dan seleksi kabupaten.</p>
                    </div>
                    <div>
                        <label class="form-label">Kode Pos <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input class="form-input" name="kode_pos" value="{{ old('kode_pos', $data?->kode_pos) }}">
                    </div>
                </div>
            </section>
        </fieldset>

        <div class="flex flex-wrap justify-between gap-3">
            <a href="{{ route('mahasiswa.dashboard') }}" class="btn-secondary">Kembali</a>
            @if($canEdit)
                <button class="btn-primary" type="submit">
                    Simpan & Lanjut ke Pendidikan
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </button>
            @else
                <a href="{{ route('mahasiswa.pendidikan.index') }}" class="btn-primary">Lanjut ke Pendidikan</a>
            @endif
        </div>
    </form>
</div>
@endsection
