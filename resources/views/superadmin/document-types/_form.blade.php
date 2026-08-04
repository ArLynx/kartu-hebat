@php
    $documentType = $documentType ?? null;
    $mimeValue = old(
        'allowed_mimes',
        isset($documentType) ? implode(',', $documentType->allowed_mimes ?? []) : 'pdf,jpg,jpeg,png'
    );
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <label for="code" class="form-label">Kode <span class="text-red-500">*</span></label>
        <input id="code" name="code" type="text" class="form-input uppercase" value="{{ old('code', $documentType->code ?? '') }}" maxlength="50" placeholder="SURAT-AKTIF" required>
        <p class="form-help">Kode yang sama dipakai untuk sinkronisasi ke <code>jenis_dokumens</code>.</p>
        @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="name" class="form-label">Nama Dokumen <span class="text-red-500">*</span></label>
        <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $documentType->name ?? '') }}" maxlength="255" required>
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="application_type" class="form-label">Jalur Pengajuan</label>
        <select id="application_type" name="application_type" class="form-input">
            <option value="">Semua jalur</option>
            @foreach($applicationTypes as $type)
                <option value="{{ $type->value }}" @selected(old('application_type', $documentType->application_type?->value ?? '') === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @error('application_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="allowed_mimes" class="form-label">Format File <span class="text-red-500">*</span></label>
        <input id="allowed_mimes" name="allowed_mimes" type="text" class="form-input" value="{{ $mimeValue }}" maxlength="255" placeholder="pdf,jpg,jpeg,png" required>
        <p class="form-help">Pisahkan ekstensi dengan koma, tanpa titik.</p>
        @error('allowed_mimes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="max_size_kb" class="form-label">Ukuran Maksimal (KB) <span class="text-red-500">*</span></label>
        <input id="max_size_kb" name="max_size_kb" type="number" min="1" max="102400" class="form-input" value="{{ old('max_size_kb', $documentType->max_size_kb ?? 2048) }}" required>
        @error('max_size_kb')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="sort_order" class="form-label">Urutan Tampil <span class="text-red-500">*</span></label>
        <input id="sort_order" name="sort_order" type="number" min="0" max="65535" class="form-input" value="{{ old('sort_order', $documentType->sort_order ?? 0) }}" required>
        @error('sort_order')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6">
    <label for="description" class="form-label">Deskripsi</label>
    <textarea id="description" name="description" rows="4" class="form-input" maxlength="5000">{{ old('description', $documentType->description ?? '') }}</textarea>
    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2">
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
        <input id="is_required" name="is_required" type="checkbox" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('is_required', $documentType->is_required ?? true))>
        <span>
            <span class="block font-semibold text-slate-900">Dokumen wajib</span>
            <span class="mt-1 block text-xs text-slate-500">Wajib pada jalur lama sesuai application type.</span>
        </span>
    </label>

    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4">
        <input id="is_active" name="is_active" type="checkbox" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('is_active', $documentType->is_active ?? true))>
        <span>
            <span class="block font-semibold text-slate-900">Document type aktif</span>
            <span class="mt-1 block text-xs text-slate-500">Status ini juga disinkronkan ke jenis dokumen terintegrasi.</span>
        </span>
    </label>
</div>

<div class="mt-7 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
    Saat disimpan, nama, kode, format, ukuran maksimal, dan status aktif otomatis diperbarui pada tabel <code>jenis_dokumens</code> agar dapat digunakan sebagai persyaratan kategori.
</div>

<div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
    <a href="{{ route('superadmin.document-types.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">Simpan Document Type</button>
</div>
