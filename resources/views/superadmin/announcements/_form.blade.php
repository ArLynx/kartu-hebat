@php
    $announcement = $announcement ?? null;
@endphp

<div class="grid gap-6">
    {{-- JUDUL PENGUMUMAN --}}
    <div>
        <label for="title" class="form-label">Judul Pengumuman <span class="text-rose-500">*</span></label>
        <input id="title" name="title" type="text" class="form-input" 
               value="{{ old('title', $announcement->title ?? '') }}" 
               placeholder="Contoh: Jadwal Pembukaan Pendaftaran Beasiswa Semester Ganjil" required maxlength="255">
        <p class="form-help">Judul pengumuman yang jelas dan ringkas.</p>
        @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        {{-- KATEGORI / TIPE --}}
        <div>
            <label for="type" class="form-label">Kategori / Label <span class="text-rose-500">*</span></label>
            <select id="type" name="type" class="form-input" required>
                <option value="informasi" @selected(old('type', $announcement->type ?? 'informasi') === 'informasi')>Informasi Umum (Badge Biru)</option>
                <option value="panduan" @selected(old('type', $announcement->type ?? 'informasi') === 'panduan')>Panduan Teknis (Badge Hijau)</option>
                <option value="hasil" @selected(old('type', $announcement->type ?? 'informasi') === 'hasil')>Pengumuman Hasil Seleksi (Badge Ungu)</option>
            </select>
            <p class="form-help">Menentukan badge warna dan label pada kartu depan.</p>
            @error('type')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        {{-- TANGGAL PUBLIKASI --}}
        <div>
            <label for="published_at" class="form-label">Tanggal Publikasi</label>
            <input id="published_at" name="published_at" type="datetime-local" class="form-input" 
                   value="{{ old('published_at', isset($announcement->published_at) ? $announcement->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
            <p class="form-help">Tanggal & waktu yang akan ditampilkan di kotak kalender kartu depan.</p>
            @error('published_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- RINGKASAN SINGKAT (EXCERPT) --}}
    <div>
        <label for="excerpt" class="form-label">Ringkasan Singkat (Excerpt)</label>
        <textarea id="excerpt" name="excerpt" rows="2" class="form-input" 
                  placeholder="Tulis 1-2 kalimat ringkasan yang akan langsung terbaca pada kartu pengumuman di landing page...">{{ old('excerpt', $announcement->excerpt ?? '') }}</textarea>
        <p class="form-help">Maksimal 500 karakter. Jika dikosongkan, sistem akan memotong awal kalimat dari isi pengumuman.</p>
        @error('excerpt')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    {{-- ISI LENGKAP PENGUMUMAN (BODY) --}}
    <div>
        <label for="body" class="form-label">Isi Lengkap Pengumuman <span class="text-rose-500">*</span></label>
        <textarea id="body" name="body" rows="8" class="form-input font-sans text-sm leading-relaxed" 
                  placeholder="Tuliskan isi pengumuman lengkap, petunjuk teknis, atau jadwal kegiatan di sini..." required>{{ old('body', $announcement->body ?? '') }}</textarea>
        <p class="form-help">Dapat memuat rincian tanggal, persyaratan, atau arahan dari Dinas/Pemerintah Daerah.</p>
        @error('body')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    {{-- LAMPIRAN BERKAS PDF --}}
    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <label for="attachment" class="form-label text-sm font-bold text-slate-800">Lampiran Dokumen PDF (Opsional)</label>
        <p class="text-xs text-slate-500 mt-0.5">Sertakan berkas Surat Edaran (SE), juknis, atau pengumuman bertandatangan format PDF (Maks. 2 MB / 2.048 KB).</p>
        
        @if(isset($announcement) && $announcement->attachment_path)
            <div class="mt-3 flex items-center justify-between rounded-lg border border-slate-200 bg-white p-3">
                <div class="flex items-center gap-2 min-w-0">
                    <x-icon name="document" class="h-5 w-5 text-red-600 shrink-0" />
                    <span class="truncate text-xs font-semibold text-slate-700">{{ basename($announcement->attachment_path) }}</span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('superadmin.announcements.file', $announcement) }}" target="_blank" class="text-xs font-bold text-brand-700 hover:underline">
                        Lihat Dokumen
                    </a>
                    <label class="flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer">
                        <input type="checkbox" name="remove_attachment" value="1" class="rounded text-rose-600">
                        <span>Hapus lampiran ini</span>
                    </label>
                </div>
            </div>
        @endif

        <div class="mt-3">
            <input id="attachment" name="attachment" type="file" accept=".pdf" class="form-input text-xs">
            @error('attachment')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- STATUS TAMPIL / AKTIF --}}
    <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50/50 p-4">
        <input id="is_active" name="is_active" type="checkbox" value="1" 
               @checked(old('is_active', $announcement->is_active ?? true))
               class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
        <div>
            <label for="is_active" class="font-bold text-sm text-slate-800 cursor-pointer">
                Tayangkan di Halaman Depan (Status Aktif)
            </label>
            <p class="text-xs text-slate-600 mt-0.5">
                Jika dicentang, pengumuman ini akan langsung terlihat oleh pengunjung di halaman depan dan daftar pengumuman publik. Jika tidak dicentang, akan disimpan sebagai Draf.
            </p>
        </div>
    </div>
</div>

<div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
    <a href="{{ route('superadmin.announcements.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">
        {{ isset($announcement) ? 'Simpan Perubahan' : 'Terbitkan Pengumuman' }}
    </button>
</div>
