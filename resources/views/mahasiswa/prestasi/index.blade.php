@extends('layouts.portal')

@section('title', 'Prestasi Beasiswa')
@section('header', 'Prestasi')

@section('content')
<div class="max-w-6xl">
    @include('mahasiswa.partials.flow-header', [
        'title' => 'Prestasi',
        'description' => 'Tambahkan prestasi akademik atau nonakademik. Tahap ini tetap harus dikonfirmasi walaupun tidak memiliki prestasi.',
        'currentStep' => 3,
    ])

    @unless($canEdit)
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Pendaftaran telah dikirim. Data prestasi hanya dapat dilihat.</div>
    @endunless

    @if($canEdit)
        <section class="card mt-7 overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                <h2 class="text-xl font-bold">Tambah Prestasi</h2>
                <p class="mt-1 text-sm text-slate-500">Sertifikat dapat berupa PDF, JPG, JPEG, atau PNG maksimal 2 MB.</p>
            </div>
            <form method="POST" action="{{ route('mahasiswa.prestasi.store') }}" enctype="multipart/form-data" class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">
                @csrf
                <div>
                    <label class="form-label">Jenis Prestasi</label>
                    <select class="form-input" name="jenis" required>
                        <option value="">Pilih</option>
                        <option value="akademik" @selected(old('jenis') === 'akademik')>Akademik</option>
                        <option value="non_akademik" @selected(old('jenis') === 'non_akademik')>Nonakademik</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Tingkat</label>
                    <select class="form-input" name="tingkat" required>
                        <option value="">Pilih</option>
                        @foreach(['kampus' => 'Kampus', 'kabupaten' => 'Kabupaten/Kota', 'provinsi' => 'Provinsi', 'nasional' => 'Nasional', 'internasional' => 'Internasional'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('tingkat') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Nama Prestasi</label>
                    <input class="form-input" name="nama_prestasi" value="{{ old('nama_prestasi') }}" required>
                </div>
                <div>
                    <label class="form-label">Peringkat/Penghargaan</label>
                    <input class="form-input" name="peringkat" value="{{ old('peringkat') }}" placeholder="Contoh: Juara 1" required>
                </div>
                <div>
                    <label class="form-label">Tahun</label>
                    <input class="form-input" type="number" name="tahun" min="1990" max="{{ now()->year }}" value="{{ old('tahun') }}" required>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Penyelenggara</label>
                    <input class="form-input" name="penyelenggara" value="{{ old('penyelenggara') }}" required>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Dokumen Prestasi <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input class="form-input" type="file" name="dokumen" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Keterangan <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea class="form-input" name="keterangan" rows="3">{{ old('keterangan') }}</textarea>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="btn-primary" type="submit">Tambah Prestasi</button>
                </div>
            </form>
        </section>
    @endif

    <section class="card mt-7 overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
            <h2 class="text-xl font-bold">Daftar Prestasi</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $pendaftaran->prestasis->count() }} prestasi tercatat.</p>
        </div>
        <div class="space-y-4 p-6 sm:p-8">
            @forelse($pendaftaran->prestasis as $prestasi)
                <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap gap-2">
                                <span class="status-chip status-info">{{ $prestasi->jenis === 'akademik' ? 'Akademik' : 'Nonakademik' }}</span>
                                <span class="status-chip status-neutral">{{ ucfirst($prestasi->tingkat) }}</span>
                            </div>
                            <h3 class="mt-3 text-lg font-bold">{{ $prestasi->nama_prestasi }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $prestasi->peringkat }} · {{ $prestasi->penyelenggara }} · {{ $prestasi->tahun }}</p>
                            @if($prestasi->keterangan)<p class="mt-2 text-sm leading-6 text-slate-500">{{ $prestasi->keterangan }}</p>@endif
                            @if($prestasi->dokumen_prestasi)
                                <a href="{{ route('mahasiswa.prestasi.download', $prestasi) }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:underline">Unduh dokumen prestasi</a>
                            @endif
                        </div>
                        @if($canEdit)
                            <form method="POST" action="{{ route('mahasiswa.prestasi.destroy', $prestasi) }}" onsubmit="return confirm('Hapus prestasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-semibold text-red-600 hover:underline">Hapus</button>
                            </form>
                        @endif
                    </div>

                    @if($canEdit)
                        <details class="mt-4 border-t border-slate-200 pt-4">
                            <summary class="cursor-pointer text-sm font-semibold text-brand-700">Ubah data prestasi</summary>
                            <form method="POST" action="{{ route('mahasiswa.prestasi.update', $prestasi) }}" enctype="multipart/form-data" class="mt-4 grid gap-4 md:grid-cols-2">
                                @csrf
                                @method('PUT')
                                <select class="form-input" name="jenis" required>
                                    <option value="akademik" @selected($prestasi->jenis === 'akademik')>Akademik</option>
                                    <option value="non_akademik" @selected($prestasi->jenis === 'non_akademik')>Nonakademik</option>
                                </select>
                                <select class="form-input" name="tingkat" required>
                                    @foreach(['kampus', 'kabupaten', 'provinsi', 'nasional', 'internasional'] as $value)
                                        <option value="{{ $value }}" @selected($prestasi->tingkat === $value)>{{ ucfirst($value) }}</option>
                                    @endforeach
                                </select>
                                <input class="form-input md:col-span-2" name="nama_prestasi" value="{{ $prestasi->nama_prestasi }}" required>
                                <input class="form-input" name="peringkat" value="{{ $prestasi->peringkat }}" required>
                                <input class="form-input" type="number" name="tahun" min="1990" max="{{ now()->year }}" value="{{ $prestasi->tahun }}" required>
                                <input class="form-input md:col-span-2" name="penyelenggara" value="{{ $prestasi->penyelenggara }}" required>
                                <input class="form-input md:col-span-2" type="file" name="dokumen" accept=".pdf,.jpg,.jpeg,.png">
                                <textarea class="form-input md:col-span-2" name="keterangan" rows="2">{{ $prestasi->keterangan }}</textarea>
                                <div class="md:col-span-2 flex justify-end"><button class="btn-secondary" type="submit">Simpan Perubahan</button></div>
                            </form>
                        </details>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Belum ada prestasi yang ditambahkan.</div>
            @endforelse
        </div>
    </section>

    <div class="mt-6 flex flex-wrap justify-between gap-3">
        <a href="{{ route('mahasiswa.pendidikan.index') }}" class="btn-secondary">Kembali ke Pendidikan</a>
        @if($canEdit)
            <form method="POST" action="{{ route('mahasiswa.prestasi.confirm') }}">
                @csrf
                <button class="btn-primary">Konfirmasi & Lanjut ke Orang Tua <x-icon name="arrow-right" class="h-4 w-4" /></button>
            </form>
        @else
            <a href="{{ route('mahasiswa.orang-tua.index') }}" class="btn-primary">Lanjut ke Orang Tua</a>
        @endif
    </div>
</div>
@endsection
