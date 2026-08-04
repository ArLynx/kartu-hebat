@extends('layouts.portal')

@section('title', 'Pendidikan Beasiswa')
@section('header', 'Pendidikan')

@section('content')
@php($pendidikan = $pendaftaran->pendidikan)

<div class="max-w-6xl">
    @include('mahasiswa.partials.flow-header', [
        'title' => 'Data Pendidikan',
        'description' => 'Isi data perguruan tinggi dan status akademik yang masih berlaku.',
        'currentStep' => 2,
    ])

    @unless($canEdit)
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Pendaftaran telah dikirim. Data pendidikan hanya dapat dilihat.</div>
    @endunless

    <form method="POST" action="{{ route('mahasiswa.pendidikan.update') }}" class="mt-7 space-y-6">
        @csrf
        @method('PUT')
        <fieldset @disabled(!$canEdit)>
            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                    <h2 class="text-xl font-bold">Informasi Akademik</h2>
                    <p class="mt-1 text-sm text-slate-500">Gunakan data yang sama dengan PDDikti dan dokumen kampus.</p>
                </div>
                <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">
                    <div>
                        <label class="form-label">NIM</label>
                        <input class="form-input" name="nim" value="{{ old('nim', $pendidikan?->nim) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Perguruan Tinggi</label>
                        <input class="form-input" name="universitas" value="{{ old('universitas', $pendidikan?->universitas) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Fakultas</label>
                        <input class="form-input" name="fakultas" value="{{ old('fakultas', $pendidikan?->fakultas) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Program Studi</label>
                        <input class="form-input" name="program_studi" value="{{ old('program_studi', $pendidikan?->program_studi) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Jenjang</label>
                        <select class="form-input" name="jenjang" required>
                            <option value="">Pilih jenjang</option>
                            @foreach(['D3', 'D4', 'S1', 'S2', 'S3'] as $jenjang)
                                <option value="{{ $jenjang }}" @selected(old('jenjang', $pendidikan?->jenjang) === $jenjang)>{{ $jenjang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Status Mahasiswa</label>
                        <select class="form-input" name="status_mahasiswa" required>
                            <option value="">Pilih status</option>
                            @foreach(['aktif' => 'Aktif', 'cuti' => 'Cuti', 'lulus' => 'Lulus', 'drop_out' => 'Drop Out', 'nonaktif' => 'Nonaktif'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status_mahasiswa', $pendidikan?->status_mahasiswa) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Semester Saat Ini</label>
                        <input class="form-input" type="number" name="semester" min="1" max="14" value="{{ old('semester', $pendidikan?->semester) }}" required>
                    </div>
                    <div>
                        <label class="form-label">IPK</label>
                        <input class="form-input" type="number" name="ipk" min="0" max="4" step="0.01" value="{{ old('ipk', $pendidikan?->ipk) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Tahun Masuk</label>
                        <input class="form-input" type="number" name="tahun_masuk" min="1990" max="{{ now()->year }}" value="{{ old('tahun_masuk', $pendidikan?->tahun_masuk) }}" required>
                    </div>
                    <div>
                        <label class="form-label">Tahun Lulus <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input class="form-input" type="number" name="tahun_lulus" min="1990" max="{{ now()->year + 10 }}" value="{{ old('tahun_lulus', $pendidikan?->tahun_lulus) }}">
                    </div>
                    <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        Status verifikasi PDDikti: <strong>{{ $pendidikan?->pddikti_verified_at ? 'Terverifikasi pada '.$pendidikan->pddikti_verified_at->format('d/m/Y H:i') : 'Belum diverifikasi' }}</strong>
                    </div>
                </div>
            </section>
        </fieldset>

        <div class="flex flex-wrap justify-between gap-3">
            <a href="{{ route('mahasiswa.data-pribadi.index') }}" class="btn-secondary">Kembali ke Data Pribadi</a>
            @if($canEdit)
                <button type="submit" class="btn-primary">Simpan & Lanjut ke Prestasi <x-icon name="arrow-right" class="h-4 w-4" /></button>
            @else
                <a href="{{ route('mahasiswa.prestasi.index') }}" class="btn-primary">Lanjut ke Prestasi</a>
            @endif
        </div>
    </form>
</div>
@endsection
