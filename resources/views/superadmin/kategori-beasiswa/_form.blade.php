@php
    $category = $category ?? null;
    $selectedDocuments = collect(old(
        'jenis_dokumen_ids',
        isset($category) ? $category->jenisDokumens->pluck('id')->all() : []
    ))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <label for="periode_id" class="form-label">Periode <span class="text-red-500">*</span></label>
        <select id="periode_id" name="periode_id" class="form-input" required>
            <option value="">Pilih periode</option>
            @foreach($periods as $period)
                <option value="{{ $period->id }}" @selected(old('periode_id', $category->periode_id ?? '') == $period->id)>
                    {{ $period->nama ?: $period->tahun }} — {{ ucfirst($period->status) }}
                </option>
            @endforeach
        </select>
        @error('periode_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="application_type" class="form-label">Jalur Pengajuan <span class="text-red-500">*</span></label>
        <select id="application_type" name="application_type" class="form-input" required>
            <option value="">Pilih jalur</option>
            @foreach($applicationTypes as $type)
                <option value="{{ $type->value }}" @selected(old('application_type', $category->application_type?->value ?? '') === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @error('application_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="kode" class="form-label">Kode <span class="text-red-500">*</span></label>
        <input id="kode" name="kode" type="text" class="form-input uppercase" value="{{ old('kode', $category->kode ?? '') }}" maxlength="50" placeholder="BEASISWA-AKADEMIK" required>
        <p class="form-help">Huruf kapital, angka, tanda hubung, atau garis bawah.</p>
        @error('kode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="nama" class="form-label">Nama Kategori <span class="text-red-500">*</span></label>
        <input id="nama" name="nama" type="text" class="form-input" value="{{ old('nama', $category->nama ?? '') }}" maxlength="255" required>
        @error('nama')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="kuota" class="form-label">Kuota <span class="text-red-500">*</span></label>
        <input id="kuota" name="kuota" type="number" min="0" max="1000000" class="form-input" value="{{ old('kuota', $category->kuota ?? 0) }}" required>
        @error('kuota')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="urutan" class="form-label">Urutan Tampil <span class="text-red-500">*</span></label>
        <input id="urutan" name="urutan" type="number" min="1" max="255" class="form-input" value="{{ old('urutan', $category->urutan ?? 1) }}" required>
        @error('urutan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="icon" class="form-label">Nama Ikon</label>
        <input id="icon" name="icon" type="text" class="form-input" value="{{ old('icon', $category->icon ?? '') }}" maxlength="50" placeholder="school">
        @error('icon')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="warna" class="form-label">Warna</label>
        <input id="warna" name="warna" type="text" class="form-input" value="{{ old('warna', $category->warna ?? '') }}" maxlength="30" placeholder="#2563eb atau blue">
        @error('warna')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6">
    <label for="deskripsi" class="form-label">Deskripsi</label>
    <textarea id="deskripsi" name="deskripsi" rows="4" class="form-input" maxlength="5000">{{ old('deskripsi', $category->deskripsi ?? '') }}</textarea>
    @error('deskripsi')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="mt-7 rounded-xl border border-slate-200 bg-slate-50 p-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-bold text-navy-900">Dokumen Persyaratan</h2>
            <p class="mt-1 text-xs text-slate-500">Urutan persyaratan mengikuti urutan daftar di bawah.</p>
        </div>
        <a href="{{ route('superadmin.document-types.create') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Tambah document type</a>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
        @forelse($documentTypes as $type)
            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-4 hover:border-brand-300">
                <input type="checkbox" name="jenis_dokumen_ids[]" value="{{ $type->id }}" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(in_array($type->id, $selectedDocuments, true))>
                <span class="min-w-0">
                    <span class="block font-semibold text-slate-900">{{ $type->nama }}</span>
                    <span class="mt-1 block text-xs text-slate-500">{{ $type->kode }} · {{ $type->format_file }} · Maks. {{ number_format($type->maksimal_ukuran) }} KB</span>
                    @unless($type->aktif)
                        <span class="mt-2 inline-flex status-chip status-neutral">Nonaktif</span>
                    @endunless
                </span>
            </label>
        @empty
            <p class="text-sm text-slate-500">Belum ada jenis dokumen. Tambahkan document type terlebih dahulu.</p>
        @endforelse
    </div>
    @error('jenis_dokumen_ids')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('jenis_dokumen_ids.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="mt-6 flex items-start gap-3 rounded-xl border border-slate-200 p-4">
    <input id="aktif" name="aktif" type="checkbox" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('aktif', $category->aktif ?? true))>
    <div>
        <label for="aktif" class="font-semibold text-slate-900">Kategori aktif</label>
        <p class="mt-1 text-xs text-slate-500">Kategori aktif dapat dipilih mahasiswa pada periode yang sesuai.</p>
    </div>
</div>

<div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
    <a href="{{ route('superadmin.kategori-beasiswa.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">Simpan Kategori</button>
</div>
