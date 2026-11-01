@extends('layouts.portal')

@section('title', 'Prestasi Beasiswa Non-Akademik')
@section('header', 'Prestasi')

@section('content')
    <div class="max-w-6xl">

        @include('mahasiswa.partials.flow-header', [
            'title' => 'Prestasi & Keunggulan Non-Akademik',
            'description' => 'Calon penerima Beasiswa Prestasi Non-Akademik wajib memiliki keunggulan berupa sertifikat kompetisi/lomba atau SK penunjukan kepengurusan organisasi mahasiswa (Ormawa) di perguruan tinggi.',
            'currentStep' => 3,
        ])

        @unless($canEdit)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Pendaftaran telah dikirim. Data prestasi hanya dapat dilihat dan tidak dapat diubah.
            </div>
        @endunless


        {{-- ========================================================= --}}
        {{-- TAMBAH PRESTASI / ORMAWA --}}
        {{-- ========================================================= --}}

        @if($canEdit)

            <section class="card mt-7 overflow-hidden" x-data="{ kategoriBukti: '{{ old('kategori_bukti', 'lomba') }}' }">

                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                    <h2 class="text-xl font-bold">
                        Tambah Bukti Prestasi / Keunggulan
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Tambahkan sertifikat kompetisi/lomba atau SK kepengurusan organisasi mahasiswa yang diperoleh selama masa perkuliahan. Anda dapat menambahkan lebih dari satu data.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('mahasiswa.prestasi.store') }}"
                    enctype="multipart/form-data"
                    class="grid gap-5 p-6 sm:p-8 md:grid-cols-2"
                >

                    @csrf

                    {{-- PILIHAN KATEGORI BUKTI --}}
                    <div class="md:col-span-2">
                        <label class="form-label mb-2">
                            Pilih Kategori Bukti Keunggulan
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-center gap-3.5 p-4 rounded-xl border-2 cursor-pointer transition"
                                :class="kategoriBukti === 'lomba' ? 'border-brand-600 bg-brand-50/60 text-brand-900 ring-2 ring-brand-100 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300 text-slate-700'">
                                <input type="radio" name="kategori_bukti" value="lomba" x-model="kategoriBukti" class="sr-only">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 text-xl font-bold shadow-inner">
                                    🏆
                                </span>
                                <div>
                                    <p class="text-sm font-bold">Kompetisi / Lomba Non-Akademik</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Sertifikat lomba di bidang olahraga, keagamaan, seni & budaya, kepemudaan</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3.5 p-4 rounded-xl border-2 cursor-pointer transition"
                                :class="kategoriBukti === 'organisasi' ? 'border-brand-600 bg-brand-50/60 text-brand-900 ring-2 ring-brand-100 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300 text-slate-700'">
                                <input type="radio" name="kategori_bukti" value="organisasi" x-model="kategoriBukti" class="sr-only">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 text-xl font-bold shadow-inner">
                                    🏛️
                                </span>
                                <div>
                                    <p class="text-sm font-bold">Pengurus Organisasi Mahasiswa</p>
                                    <p class="text-xs text-slate-500 mt-0.5">SK penunjukan pengurus Ormawa (BEM, DPM, HMJ, UKM, dll.) di kampus</p>
                                </div>
                            </label>
                        </div>
                    </div>


                    {{-- ========================================================= --}}
                    {{-- FORM KHUSUS KOMPETISI / LOMBA --}}
                    {{-- ========================================================= --}}
                    <template x-if="kategoriBukti === 'lomba'">
                        <div class="contents">

                            {{-- NAMA PRESTASI / LOMBA --}}
                            <div class="md:col-span-2">
                                <label class="form-label">
                                    Nama Kompetisi / Kegiatan Lomba
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="text"
                                    name="nama_prestasi"
                                    value="{{ old('nama_prestasi') }}"
                                    placeholder="Contoh: Juara 1 Pekan Olahraga Mahasiswa Cabang Bulutangkis / MTQ Mahasiswa"
                                    required
                                >
                                @error('nama_prestasi')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- TINGKAT --}}
                            <div>
                                <label class="form-label">
                                    Tingkat Perlombaan
                                    <span class="text-red-500">*</span>
                                </label>
                                <select
                                    class="form-input"
                                    name="tingkat"
                                    required
                                >
                                    <option value="">Pilih tingkat</option>
                                    @foreach(\App\Models\Prestasi::TINGKAT_OPTIONS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('tingkat') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tingkat')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- PERINGKAT (DROPDOWN OPSI A) --}}
                            <div>
                                <label class="form-label">
                                    Peringkat / Capaian
                                    <span class="text-red-500">*</span>
                                </label>
                                <select
                                    class="form-input"
                                    name="peringkat"
                                    required
                                >
                                    <option value="">Pilih peringkat / capaian</option>
                                    @foreach(\App\Models\Prestasi::PERINGKAT_OPTIONS as $value => $label)
                                        <option value="{{ $value }}" @selected(old('peringkat') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('peringkat')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- PENYELENGGARA --}}
                            <div>
                                <label class="form-label">
                                    Penyelenggara
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="text"
                                    name="penyelenggara"
                                    value="{{ old('penyelenggara') }}"
                                    placeholder="Contoh: BAPOMI / Dispora / Universitas X"
                                    required
                                >
                                @error('penyelenggara')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- TAHUN --}}
                            <div>
                                <label class="form-label">
                                    Tahun Perolehan
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="number"
                                    name="tahun"
                                    min="1990"
                                    max="{{ now()->year }}"
                                    value="{{ old('tahun', now()->year) }}"
                                    placeholder="Contoh: {{ now()->year }}"
                                    required
                                >
                                <p class="mt-1 text-xs text-slate-500">Diterbitkan pada saat sedang menempuh perkuliahan.</p>
                                @error('tahun')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- DOKUMEN SERTIFIKAT --}}
                            <div class="md:col-span-2">
                                <label class="form-label">
                                    Dokumen Sertifikat / Piagam Penghargaan
                                    <span class="font-normal text-slate-400">(opsional / PDF, JPG, PNG maks 2 MB)</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="file"
                                    name="dokumen"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                >
                                @error('dokumen')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- KETERANGAN --}}
                            <div class="md:col-span-2">
                                <label class="form-label">
                                    Keterangan Tambahan
                                    <span class="font-normal text-slate-400">(opsional)</span>
                                </label>
                                <textarea
                                    class="form-input"
                                    name="keterangan"
                                    rows="2"
                                    placeholder="Catatan tambahan mengenai prestasi lomba ini jika diperlukan"
                                >{{ old('keterangan') }}</textarea>
                            </div>

                        </div>
                    </template>


                    {{-- ========================================================= --}}
                    {{-- FORM KHUSUS PENGURUS ORGANISASI MAHASISWA --}}
                    {{-- ========================================================= --}}
                    <template x-if="kategoriBukti === 'organisasi'">
                        <div class="contents">

                            {{-- NAMA ORGANISASI --}}
                            <div class="md:col-span-2">
                                <label class="form-label">
                                    Nama Organisasi Mahasiswa
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="text"
                                    name="nama_organisasi"
                                    value="{{ old('nama_organisasi') }}"
                                    placeholder="Contoh: Badan Eksekutif Mahasiswa (BEM) / Dewan Perwakilan Mahasiswa (DPM) / HMJ"
                                    required
                                >
                                @error('nama_organisasi')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- JABATAN DALAM ORMAWA --}}
                            <div>
                                <label class="form-label">
                                    Jabatan dalam Kepengurusan
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="text"
                                    name="jabatan_ormawa"
                                    value="{{ old('jabatan_ormawa') }}"
                                    placeholder="Contoh: Ketua Umum / Sekretaris / Bendahara / Koordinator Divisi"
                                    required
                                >
                                @error('jabatan_ormawa')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- NAMA KAMPUS / PENYELENGGARA --}}
                            <div>
                                <label class="form-label">
                                    Nama Perguruan Tinggi / Kampus
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="text"
                                    name="penyelenggara"
                                    value="{{ old('penyelenggara', $pendaftaran->pendidikan?->universitas) }}"
                                    placeholder="Contoh: Universitas Palangka Raya"
                                    required
                                >
                                @error('penyelenggara')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- TAHUN SK / MASA BAKTI --}}
                            <div>
                                <label class="form-label">
                                    Tahun Masa Bakti / SK
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="number"
                                    name="tahun"
                                    min="1990"
                                    max="{{ now()->year }}"
                                    value="{{ old('tahun', now()->year) }}"
                                    placeholder="Contoh: {{ now()->year }}"
                                    required
                                >
                                @error('tahun')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- DOKUMEN SK --}}
                            <div>
                                <label class="form-label">
                                    Dokumen Surat Keputusan (SK)
                                    <span class="font-normal text-slate-400">(opsional / PDF, JPG, PNG maks 2 MB)</span>
                                </label>
                                <input
                                    class="form-input"
                                    type="file"
                                    name="dokumen"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                >
                                @error('dokumen')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- NOMOR SK / KETERANGAN --}}
                            <div class="md:col-span-2">
                                <label class="form-label">
                                    Nomor SK & Keterangan Tambahan
                                    <span class="font-normal text-slate-400">(opsional)</span>
                                </label>
                                <textarea
                                    class="form-input"
                                    name="keterangan"
                                    rows="2"
                                    placeholder="Contoh: SK Rektor No. 123/UN/2025 tentang Susunan Pengurus BEM"
                                >{{ old('keterangan') }}</textarea>
                            </div>

                        </div>
                    </template>


                    {{-- BUTTON SUBMIT --}}
                    <div class="md:col-span-2 flex justify-end">
                        <button
                            class="btn-primary"
                            type="submit"
                        >
                            Tambah ke Daftar
                        </button>
                    </div>

                </form>

            </section>

        @endif


        {{-- ========================================================= --}}
        {{-- DAFTAR PRESTASI & KEPENGURUSAN TERCATAT --}}
        {{-- ========================================================= --}}

        <section class="card mt-7 overflow-hidden">

            <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                <h2 class="text-xl font-bold">
                    Daftar Prestasi & Keunggulan
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pendaftaran->prestasis->count() }} data keunggulan non-akademik tercatat.
                </p>

            </div>


            <div class="space-y-4 p-6 sm:p-8">

                @forelse($pendaftaran->prestasis as $prestasi)

                    <article class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                            <div>

                                <div class="flex flex-wrap gap-2">

                                    @if($prestasi->is_pengurus_inti_ormawa)
                                        <span class="status-chip bg-emerald-100 text-emerald-800 border-emerald-300 font-semibold">
                                            🏛️ Pengurus Ormawa
                                        </span>
                                        @if($prestasi->jabatan_ormawa)
                                            <span class="status-chip bg-blue-100 text-blue-800 border-blue-300 font-semibold">
                                                {{ $prestasi->jabatan_ormawa }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="status-chip status-info">
                                            🏆 {{ $prestasi->peringkat_label }}
                                        </span>
                                        <span class="status-chip status-neutral">
                                            Tingkat {{ ucfirst($prestasi->tingkat) }}
                                        </span>
                                    @endif

                                </div>


                                <h3 class="mt-3 text-lg font-bold text-slate-900">
                                    {{ $prestasi->nama_prestasi }}
                                </h3>


                                <p class="mt-1 text-sm text-slate-600">
                                    @if($prestasi->is_pengurus_inti_ormawa)
                                        {{ $prestasi->jabatan_ormawa }}
                                    @else
                                        {{ $prestasi->peringkat_label }}
                                    @endif
                                    ·
                                    {{ $prestasi->penyelenggara }}
                                    ·
                                    Tahun {{ $prestasi->tahun }}
                                </p>


                                @if($prestasi->keterangan)
                                    <p class="mt-2 text-sm leading-6 text-slate-500">
                                        {{ $prestasi->keterangan }}
                                    </p>
                                @endif


                                @if($prestasi->dokumen_prestasi)
                                    <a
                                        href="{{ route('mahasiswa.prestasi.download', $prestasi) }}"
                                        class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline"
                                    >
                                        <x-icon name="document-arrow-down" class="h-4 w-4" />
                                        Unduh berkas bukti
                                    </a>
                                @endif

                            </div>


                            @if($canEdit)

                                <form
                                    method="POST"
                                    action="{{ route('mahasiswa.prestasi.destroy', $prestasi) }}"
                                    onsubmit="return confirm('Hapus data prestasi ini?')"
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


                        {{-- EDIT FORM --}}
                        @if($canEdit)

                            <details class="mt-4 border-t border-slate-200 pt-4">

                                <summary class="cursor-pointer text-sm font-semibold text-brand-700 hover:underline">
                                    Ubah data ini
                                </summary>

                                <form
                                    method="POST"
                                    action="{{ route('mahasiswa.prestasi.update', $prestasi) }}"
                                    enctype="multipart/form-data"
                                    class="mt-4 grid gap-4 md:grid-cols-2"
                                >
                                    @csrf
                                    @method('PUT')

                                    @if($prestasi->is_pengurus_inti_ormawa)
                                        <input type="hidden" name="kategori_bukti" value="organisasi">

                                        {{-- NAMA ORGANISASI --}}
                                        <div class="md:col-span-2">
                                            <label class="form-label">Nama Organisasi Mahasiswa</label>
                                            <input
                                                class="form-input"
                                                type="text"
                                                name="nama_organisasi"
                                                value="{{ $prestasi->nama_prestasi }}"
                                                required
                                            >
                                        </div>

                                        {{-- JABATAN --}}
                                        <div>
                                            <label class="form-label">Jabatan dalam Kepengurusan</label>
                                            <input
                                                class="form-input"
                                                type="text"
                                                name="jabatan_ormawa"
                                                value="{{ $prestasi->jabatan_ormawa }}"
                                                required
                                            >
                                        </div>

                                        {{-- KAMPUS --}}
                                        <div>
                                            <label class="form-label">Perguruan Tinggi / Kampus</label>
                                            <input
                                                class="form-input"
                                                type="text"
                                                name="penyelenggara"
                                                value="{{ $prestasi->penyelenggara }}"
                                                required
                                            >
                                        </div>

                                        {{-- TAHUN --}}
                                        <div>
                                            <label class="form-label">Tahun SK / Masa Bakti</label>
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

                                        {{-- DOKUMEN --}}
                                        <div>
                                            <label class="form-label">Ganti Dokumen SK <span class="text-xs text-slate-400 font-normal">(opsional)</span></label>
                                            <input
                                                class="form-input"
                                                type="file"
                                                name="dokumen"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                            >
                                        </div>

                                        {{-- KETERANGAN --}}
                                        <div class="md:col-span-2">
                                            <label class="form-label">Nomor SK / Keterangan</label>
                                            <textarea
                                                class="form-input"
                                                name="keterangan"
                                                rows="2"
                                            >{{ $prestasi->keterangan }}</textarea>
                                        </div>

                                    @else
                                        <input type="hidden" name="kategori_bukti" value="lomba">

                                        {{-- NAMA LOMBA --}}
                                        <div class="md:col-span-2">
                                            <label class="form-label">Nama Kompetisi / Kegiatan</label>
                                            <input
                                                class="form-input"
                                                type="text"
                                                name="nama_prestasi"
                                                value="{{ $prestasi->nama_prestasi }}"
                                                required
                                            >
                                        </div>

                                        {{-- TINGKAT --}}
                                        <div>
                                            <label class="form-label">Tingkat</label>
                                            <select
                                                class="form-input"
                                                name="tingkat"
                                                required
                                            >
                                                @foreach(\App\Models\Prestasi::TINGKAT_OPTIONS as $value => $label)
                                                    <option
                                                        value="{{ $value }}"
                                                        @selected($prestasi->tingkat === $value)
                                                    >
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- PERINGKAT --}}
                                        <div>
                                            <label class="form-label">Peringkat / Capaian</label>
                                            <select
                                                class="form-input"
                                                name="peringkat"
                                                required
                                            >
                                                @foreach(\App\Models\Prestasi::PERINGKAT_OPTIONS as $value => $label)
                                                    <option
                                                        value="{{ $value }}"
                                                        @selected($prestasi->peringkat === $value)
                                                    >
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- PENYELENGGARA --}}
                                        <div>
                                            <label class="form-label">Penyelenggara</label>
                                            <input
                                                class="form-input"
                                                type="text"
                                                name="penyelenggara"
                                                value="{{ $prestasi->penyelenggara }}"
                                                required
                                            >
                                        </div>

                                        {{-- TAHUN --}}
                                        <div>
                                            <label class="form-label">Tahun</label>
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

                                        {{-- DOKUMEN --}}
                                        <div class="md:col-span-2">
                                            <label class="form-label">Ganti Dokumen Sertifikat <span class="text-xs text-slate-400 font-normal">(opsional)</span></label>
                                            <input
                                                class="form-input"
                                                type="file"
                                                name="dokumen"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                            >
                                        </div>

                                        {{-- KETERANGAN --}}
                                        <div class="md:col-span-2">
                                            <label class="form-label">Keterangan</label>
                                            <textarea
                                                class="form-input"
                                                name="keterangan"
                                                rows="2"
                                            >{{ $prestasi->keterangan }}</textarea>
                                        </div>
                                    @endif

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

                    <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50/60 p-8 text-center">

                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700 text-2xl">
                            ⚠️
                        </div>

                        <p class="mt-3 text-base font-bold text-amber-900">
                            Belum Ada Data Prestasi atau SK Organisasi
                        </p>

                        <p class="mx-auto mt-1 max-w-md text-sm text-amber-800">
                            Pendaftar jalur Beasiswa Prestasi Non-Akademik <strong>wajib menambahkan minimal 1 data</strong> prestasi kompetisi/lomba atau SK kepengurusan organisasi mahasiswa untuk dapat melanjutkan.
                        </p>

                    </div>

                @endforelse

            </div>

        </section>


        {{-- ========================================================= --}}
        {{-- NAVIGASI TAHAPAN --}}
        {{-- ========================================================= --}}

        <div class="mt-6 flex flex-wrap justify-between items-center gap-3">

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
                        Simpan & Lanjut ke Orang Tua
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
