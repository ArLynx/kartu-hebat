@php
    $periode = $periode ?? null;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <label for="tahun" class="form-label">Tahun Anggaran / Periode <span class="text-red-500">*</span></label>
        <input id="tahun" name="tahun" type="number" min="2000" max="2099" class="form-input" value="{{ old('tahun', $periode->tahun ?? date('Y')) }}" placeholder="2026" required>
        <p class="form-help">Format 4 digit tahun (contoh: {{ date('Y') }}).</p>
        @error('tahun')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="nama" class="form-label">Nama Periode</label>
        <input id="nama" name="nama" type="text" class="form-input" value="{{ old('nama', $periode->nama ?? '') }}" maxlength="255" placeholder="Contoh: Beasiswa Kartu Hebat {{ date('Y') }}">
        <p class="form-help">Nama deskriptif untuk mempermudah identifikasi (opsional).</p>
        @error('nama')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="tanggal_mulai" class="form-label">Tanggal Mulai Pendaftaran <span class="text-red-500">*</span></label>
        <input id="tanggal_mulai" name="tanggal_mulai" type="date" class="form-input" value="{{ old('tanggal_mulai', isset($periode->tanggal_mulai) ? $periode->tanggal_mulai->format('Y-m-d') : '') }}" required>
        @error('tanggal_mulai')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="tanggal_selesai" class="form-label">Tanggal Selesai Pendaftaran <span class="text-red-500">*</span></label>
        <input id="tanggal_selesai" name="tanggal_selesai" type="date" class="form-input" value="{{ old('tanggal_selesai', isset($periode->tanggal_selesai) ? $periode->tanggal_selesai->format('Y-m-d') : '') }}" required>
        @error('tanggal_selesai')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label for="status" class="form-label">Status Periode <span class="text-red-500">*</span></label>
        <select id="status" name="status" class="form-input" required>
            <option value="draft" @selected(old('status', $periode->status ?? 'draft') === 'draft')>Draft (Belum dibuka)</option>
            <option value="aktif" @selected(old('status', $periode->status ?? 'draft') === 'aktif')>Aktif (Pendaftaran dibuka)</option>
            <option value="ditutup" @selected(old('status', $periode->status ?? 'draft') === 'ditutup')>Ditutup (Pendaftaran telah berakhir)</option>
        </select>
        <p class="form-help">Kategori beasiswa hanya dapat didaftar oleh mahasiswa saat periode berstatus <strong>Aktif</strong> dan dalam rentang tanggal yang ditentukan.</p>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
    <a href="{{ route('superadmin.periodes.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">
        {{ isset($periode) ? 'Simpan Perubahan' : 'Tambah Periode' }}
    </button>
</div>
