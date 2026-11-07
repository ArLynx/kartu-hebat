@extends('layouts.portal')

@section('title', 'Penetapan Calon Penerima')
@section('header', 'Penetapan Calon Penerima')

@section('content')
<div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="section-kicker">Seleksi per jalur</p>
        <h1 class="mt-2 text-3xl font-extrabold">Penetapan Calon Penerima</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
            Ranking, kuota, dan keputusan kandidat dipisahkan antara jalur Akademik dan Tidak Mampu.
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2" x-data="{ openImportModal: false, openPublishModal: false }">
        @if($activeTab === 'lolos')
            <a href="{{ route('operator.selection.export', array_filter(['application_type' => $selectedType->value, 'jalur_beasiswa_id' => $selectedJalurId, 'periode' => $selectedPeriode])) }}" class="btn-secondary !py-2 bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100" title="Unduh Excel kandidat lolos jalur ini">
                <x-icon name="download" class="h-4 w-4" /> Unduh Excel Jalur Ini
            </a>
            <a href="{{ route('operator.selection.export', ['periode' => $selectedPeriode]) }}" class="btn-secondary !py-2 bg-slate-50 text-slate-700 border-slate-300 hover:bg-slate-100" title="Unduh Excel kandidat lolos semua jalur">
                <x-icon name="download" class="h-4 w-4" /> Unduh Semua Jalur
            </a>
            @if($isCurrentPeriod)
                <button type="button" @click="openImportModal = true" class="btn-secondary !py-2 bg-blue-50 text-blue-700 border-blue-300 hover:bg-blue-100">
                    <x-icon name="upload" class="h-4 w-4 mr-1 inline" /> 1. Impor ACC Excel
                </button>
                <button type="button" @click="openPublishModal = true" class="btn-primary !py-2 bg-emerald-600 hover:bg-emerald-700">
                    <x-icon name="check-circle" class="h-4 w-4 mr-1 inline" /> 2. Publikasikan Hasil (Upload SK)
                </button>
            @elseif($hasReopenedSanggahan)
                <button type="button" @click="openPublishModal = true" class="btn-primary !py-2 bg-emerald-600 hover:bg-emerald-700">
                    <x-icon name="check-circle" class="h-4 w-4 mr-1 inline" /> Publikasikan Hasil Perbaikan (Upload SK)
                </button>
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-800">
                    <x-icon name="lock" class="h-4 w-4 text-amber-600" />
                    Mode Arsip (Sanggahan)
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-800">
                    <x-icon name="lock" class="h-4 w-4 text-amber-600" />
                    Mode Arsip (Read-Only)
                </span>
            @endif
        @else
            <a href="{{ route('operator.selection.export-tms', array_filter(['application_type' => $selectedType->value, 'jalur_beasiswa_id' => $selectedJalurId, 'periode' => $selectedPeriode])) }}" class="btn-secondary !py-2 bg-rose-50 text-rose-700 border-rose-300 hover:bg-rose-100" title="Unduh rekap berkas TMS verifikasi dinas untuk jalur ini">
                <x-icon name="download" class="h-4 w-4" /> Unduh Excel TMS (Jalur Ini)
            </a>
            <a href="{{ route('operator.selection.export-tms', ['periode' => $selectedPeriode]) }}" class="btn-secondary !py-2 bg-slate-50 text-rose-700 border-rose-200 hover:bg-rose-50" title="Unduh rekap berkas TMS verifikasi dinas semua jalur">
                <x-icon name="download" class="h-4 w-4" /> Unduh TMS (Semua)
            </a>
        @endif

        <!-- Step 1: Import Modal -->
        <div x-show="openImportModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
             @keydown.escape.window="openImportModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl transition-all"
                 @click.outside="openImportModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Impor Keputusan ACC Pimpinan</h3>
                        <p class="text-xs text-slate-500">Unggah berkas Excel hasil persetujuan/penyesuaian pimpinan untuk ditinjau secara internal.</p>
                    </div>
                    <button type="button" @click="openImportModal = false" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="close" class="h-5 w-5" />
                    </button>
                </div>

                <form method="POST" action="{{ route('operator.selection.import') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Berkas Excel ACC Pimpinan (.xlsx, .xls, .csv) <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-slate-500 mb-1">Unggah file Excel yang telah diperiksa status kelulusannya (DITERIMA / DITOLAK).</p>
                        <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required class="form-input !py-1.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-blue-600 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-blue-700">
                    </div>

                    <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-xs text-blue-800 flex items-start gap-2">
                        <x-icon name="info" class="h-4 w-4 shrink-0 mt-0.5 text-blue-600" />
                        <span>Hasil impor ini <b>TIDAK langsung terlihat oleh mahasiswa</b>. Status akan diperbarui di tabel internal operator sehingga Anda dapat memeriksa kembali susunan nama dengan aman sebelum mempublikasikannya.</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" @click="openImportModal = false" class="btn-secondary !py-2">Batal</button>
                        <button type="submit" class="btn-primary !py-2 bg-blue-600 hover:bg-blue-700">
                            Simpan ke Tabel Internal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Step 2: Publish Modal -->
        <div x-show="openPublishModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm"
             @keydown.escape.window="openPublishModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl transition-all"
                 @click.outside="openPublishModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Publikasikan Hasil Seleksi Resmi</h3>
                        <p class="text-xs text-slate-500">Unggah berkas Surat Keputusan (SK) untuk merilis hasil kandidat yang belum pernah dipublikasikan.</p>
                    </div>
                    <button type="button" @click="openPublishModal = false" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="close" class="h-5 w-5" />
                    </button>
                </div>

                <form method="POST" action="{{ route('operator.selection.publish') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="periode" value="{{ $selectedPeriode }}">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Berkas SK Penetapan Resmi (PDF) <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-slate-500 mb-1">Surat Keputusan (SK) atau Berita Acara yang telah ditandatangani pimpinan.</p>
                        <input type="file" name="sk_file" accept=".pdf,application/pdf" required class="form-input !py-1.5 text-sm file:mr-3 file:rounded file:border-0 file:bg-navy-900 file:px-2.5 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-navy-800">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Judul Pengumuman (Opsional)
                        </label>
                        <input type="text" name="title" placeholder="Pengumuman Hasil Seleksi Kartu Hebat Mahasiswa" class="form-input text-sm">
                    </div>

                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800 flex items-start gap-2">
                        <x-icon name="alert-triangle" class="h-4 w-4 shrink-0 mt-0.5 text-amber-600" />
                        <span>Aksi ini hanya menetapkan kandidat yang <b>belum pernah dipublikasikan</b>. Penerima lama tidak menerima notifikasi ulang dan berkas SK lama tetap utuh; hasil perbaikan terbit sebagai pengumuman terpisah dengan SK baru.</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" @click="openPublishModal = false" class="btn-secondary !py-2">Batal</button>
                        <button type="submit" class="btn-primary !py-2 bg-emerald-600 hover:bg-emerald-700">
                            Rilis Pengumuman Resmi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- SISTEM 2 TAB: KANDIDAT LOLOS VS DAFTAR BERKAS TMS (BERWARNA & JELAS) --}}
<div id="tab-seleksi" class="mt-6 scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
    <nav class="flex flex-wrap items-center gap-2.5" aria-label="Tabs Seleksi">
        <a href="{{ route('operator.selection', array_filter(['tab' => 'lolos', 'application_type' => $selectedType->value, 'jalur_beasiswa_id' => $selectedJalurId, 'periode' => $selectedPeriode])) }}#tab-seleksi"
           class="inline-flex items-center gap-2.5 rounded-xl px-4 py-2.5 text-sm font-extrabold transition-all duration-200 {{ $activeTab === 'lolos' ? 'bg-emerald-600 text-white shadow-md ring-2 ring-emerald-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900' }}">
            <x-icon name="check-circle" class="h-5 w-5 {{ $activeTab === 'lolos' ? 'text-white' : 'text-emerald-600' }}" />
            <span>Calon Penerima Lolos (Siap Ditetapkan)</span>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $activeTab === 'lolos' ? 'bg-white/20 text-white' : 'bg-white text-slate-700 shadow-xs' }}">
                {{ number_format($totalCurrentTypeCount) }}
            </span>
        </a>

        <a href="{{ route('operator.selection', array_filter(['tab' => 'tms', 'application_type' => $selectedType->value, 'jalur_beasiswa_id' => $selectedJalurId, 'periode' => $selectedPeriode])) }}#tab-seleksi"
           class="inline-flex items-center gap-2.5 rounded-xl px-4 py-2.5 text-sm font-extrabold transition-all duration-200 {{ $activeTab === 'tms' ? 'bg-rose-600 text-white shadow-md ring-2 ring-rose-600/20' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/80' }}">
            <x-icon name="x-circle" class="h-5 w-5 {{ $activeTab === 'tms' ? 'text-white' : 'text-rose-600' }}" />
            <span>Daftar Berkas TMS (Gugur Administrasi / Masa Sanggah)</span>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $activeTab === 'tms' ? 'bg-white/20 text-white' : 'bg-rose-200/80 text-rose-900' }}">
                {{ number_format($currentTrackTmsCount) }}
            </span>
        </a>
    </nav>
</div>

{{-- PILIHAN TAHUN / PERIODE BEASISWA --}}
<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
            <x-icon name="calendar" class="h-5 w-5" />
        </div>
        <div>
            <h2 class="text-sm font-bold text-navy-950">Tahun / Periode Beasiswa</h2>
            <p class="text-xs text-slate-500">Pilih tahun periode untuk melihat penetapan penerima dan alokasi kuota.</p>
        </div>
    </div>
    @if(isset($availablePeriodes) && $availablePeriodes->count() > 0)
        <form method="GET" action="{{ route('operator.selection') }}#tab-seleksi" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="hidden" name="application_type" value="{{ $selectedType->value }}">
            @if($selectedJalurId)
                <input type="hidden" name="jalur_beasiswa_id" value="{{ $selectedJalurId }}">
            @endif
            @if(request('status_filter'))
                <input type="hidden" name="status_filter" value="{{ request('status_filter') }}">
            @endif
            <select name="periode" class="form-input text-sm font-semibold !py-2 sm:min-w-[260px]" onchange="this.form.submit()">
                @foreach($availablePeriodes as $p)
                    <option value="{{ $p }}" @selected($selectedPeriode === $p)>
                        Periode {{ $p }} @if($p === config('kartu_hebat.current_period')) (Aktif) @endif
                    </option>
                @endforeach
            </select>
        </form>
    @endif
</div>

@if(! $isCurrentPeriod)
    <div class="mt-6 flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 shadow-sm">
        <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />
        <div class="leading-relaxed">
            <p class="font-bold">Mode Arsip: Periode {{ $selectedPeriode }}</p>
            <p class="mt-0.5 text-xs text-sky-700">
                @if($hasReopenedSanggahan)
                    Kandidat hasil sanggahan yang dikabulkan tetap dapat ditetapkan karena masih menunggu penyelesaian. Kandidat lain pada periode lampau bersifat permanen (read-only).
                @else
                    Data penetapan penerima pada periode lampau bersifat permanen (read-only) untuk keperluan riwayat dan pelaporan SK penetapan. Keputusan kandidat pada periode ini telah dikunci.
                @endif
            </p>
        </div>
    </div>
@endif

<div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kategori:</span>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('operator.selection', ['tab' => $activeTab, 'application_type' => $selectedType->value, 'periode' => $selectedPeriode]) }}#tab-seleksi"
           class="inline-flex flex-col items-start gap-0.5 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ empty($selectedJalurId) ? 'bg-navy-900 text-white shadow-sm ring-2 ring-navy-900/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            <span class="inline-flex items-center gap-2">
                <span>Semua</span>
                <span class="rounded-full px-2 py-0.5 text-[11px] {{ empty($selectedJalurId) ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                    {{ number_format($totalCurrentTypeCount) }}
                </span>
            </span>
            <span class="text-[10px] font-semibold {{ empty($selectedJalurId) ? 'text-white/80' : 'text-slate-500' }}">
                UKT: Rp {{ number_format((float) ($typeUktTotals[$selectedType->value] ?? 0), 0, ',', '.') }}
            </span>
        </a>
        @foreach($jalurBeasiswas as $jalur)
            @php $isActive = (int) $selectedJalurId === (int) $jalur->id; @endphp
            <a href="{{ route('operator.selection', ['tab' => $activeTab, 'application_type' => $selectedType->value, 'jalur_beasiswa_id' => $jalur->id, 'periode' => $selectedPeriode]) }}#tab-seleksi"
               class="inline-flex flex-col items-start gap-0.5 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ $isActive ? ($jalur->kode === 'REGULER' ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-600/20' : 'bg-purple-600 text-white shadow-sm ring-2 ring-purple-600/20') : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <span class="inline-flex items-center gap-2">
                    <span>{{ $jalur->nama }}</span>
                    <span class="rounded-full px-2 py-0.5 text-[11px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ number_format($jalurCounts[$jalur->id] ?? 0) }}
                    </span>
                </span>
                <span class="text-[10px] font-semibold {{ $isActive ? 'text-white/80' : 'text-slate-500' }}">
                    UKT: Rp {{ number_format((float) ($jalurUktTotals[$jalur->id] ?? 0), 0, ',', '.') }}
                </span>
            </a>
        @endforeach
    </div>
</div>

<div class="mt-5 grid gap-3 sm:grid-cols-2">
    @foreach($applicationTypes as $type)
        <a href="{{ route('operator.selection', ['tab' => $activeTab, 'application_type' => $type->value, 'periode' => $selectedPeriode]) }}#tab-seleksi"
           class="rounded-xl border p-5 transition {{ $selectedType === $type ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-100' : 'border-slate-200 bg-white hover:border-brand-200' }}">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-display text-lg font-bold text-navy-900">Jalur {{ $type->label() }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $type->description() }}</p>
                    <p class="mt-2 text-xs font-semibold text-emerald-700">
                        Total UKT: Rp {{ number_format((float) ($typeUktTotals[$type->value] ?? 0), 0, ',', '.') }}
                    </p>
                </div>
                <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-navy-900 px-3 py-1 text-sm font-bold text-white">
                    {{ number_format($typeCounts[$type->value] ?? 0) }}
                </span>
            </div>
        </a>
    @endforeach
</div>

@if($activeTab === 'lolos')

<div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <div class="card p-5">
        <p class="text-sm font-semibold text-slate-500">Kuota {{ $selectedType->label() }}</p>
        <p class="metric-number mt-2">{{ number_format($quota) }}</p>
    </div>
    <div class="card p-5">
        <p class="text-sm font-semibold text-slate-500">Ditetapkan Diterima</p>
        <p class="metric-number mt-2">{{ number_format($acceptedCount) }}</p>
    </div>
    <div class="card p-5">
        <p class="text-sm font-semibold text-slate-500">Hasil Dipublikasikan</p>
        <p class="metric-number mt-2">{{ number_format($publishedCount) }}</p>
    </div>
    <div class="card p-5">
        <p class="text-sm font-semibold text-slate-500">Total UKT {{ $selectedJalur?->nama ?? $selectedType->label() }}</p>
        <p class="mt-2 font-display text-2xl font-bold tracking-tight text-navy-900">Rp {{ number_format($totalCurrentTypeUkt, 0, ',', '.') }}</p>
    </div>
</div>

<div class="mt-7 rounded-xl border border-blue-200 bg-blue-50 p-5">
    <div class="flex items-start gap-3">
        <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />
        <div class="text-sm leading-6 text-blue-800">
            @if($selectedType === \App\Enums\ApplicationType::TIDAK_MAMPU)
                <p class="font-bold">Dasar Urutan Seleksi Beasiswa Tidak Mampu (Peraturan Bupati):</p>
                <p>1. Desil 1 s.d. 5 DTSEN (prioritas desil terendah) &bull; 2. Nilai IPK total/kumulatif &bull; 3. Tahun angkatan masuk PT lebih awal &bull; 4. Akreditasi Perguruan Tinggi.</p>
            @elseif($selectedType === \App\Enums\ApplicationType::AKADEMIK)
                <p class="font-bold">Dasar Urutan Seleksi Beasiswa Prestasi Akademik (Peraturan Bupati):</p>
                <p>1. Nilai IPK total/kumulatif &bull; 2. Tahun angkatan masuk PT lebih awal &bull; 3. Akreditasi Perguruan Tinggi.</p>
            @elseif($selectedType === \App\Enums\ApplicationType::NON_AKADEMIK)
                <p class="font-bold">Dasar Urutan Seleksi Beasiswa Prestasi Non Akademik (Peraturan Bupati):</p>
                <p>1. Tingkatan Juara 1, 2, atau 3 kompetisi non akademik &bull; 2. Kepengurusan inti ormawa tingkat universitas (BEM/DPM) &bull; 3. Tahun angkatan masuk PT lebih awal &bull; 4. Akreditasi Perguruan Tinggi.</p>
            @elseif($selectedType === \App\Enums\ApplicationType::DISABILITAS)
                <p class="font-bold">Dasar Urutan Seleksi Beasiswa Penyandang Disabilitas (Peraturan Bupati):</p>
                <p>1. Tahun angkatan masuk PT lebih awal &bull; 2. Akreditasi Perguruan Tinggi.</p>
            @endif
            <p class="mt-1 text-xs text-blue-700">Urutan peringkat otomatis dihitung secara bertingkat (cascading tie-breaker) sesuai hierarki kriteria Perbup.</p>
        </div>
    </div>
</div>

<div class="mt-7 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
    <div class="flex flex-wrap items-center gap-2">
        <form method="GET" action="{{ route('operator.selection') }}#tab-seleksi" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="lolos">
            <input type="hidden" name="periode" value="{{ $selectedPeriode }}">
            <input type="hidden" name="application_type" value="{{ $selectedType->value }}">
            @if($selectedJalurId)
                <input type="hidden" name="jalur_beasiswa_id" value="{{ $selectedJalurId }}">
            @endif
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
            <label for="status_filter" class="text-xs font-bold text-slate-600">Status Kandidat:</label>
            <select id="status_filter" name="status_filter" class="form-input !py-1.5 text-xs font-semibold" onchange="this.form.submit()">
                <option value="" @selected(empty($statusFilter))>Semua Status</option>
                <option value="kandidat" @selected($statusFilter === 'kandidat')>Siap Seleksi (Kandidat)</option>
                <option value="diterima" @selected($statusFilter === 'diterima')>Diterima (Lulus)</option>
                <option value="ditolak" @selected($statusFilter === 'ditolak')>Ditolak</option>
            </select>
        </form>
    </div>
    <form method="GET" action="{{ route('operator.selection') }}#tab-seleksi" class="flex w-full items-center gap-2 sm:w-auto">
        <input type="hidden" name="tab" value="lolos">
        <input type="hidden" name="periode" value="{{ $selectedPeriode }}">
        <input type="hidden" name="application_type" value="{{ $selectedType->value }}">
        @if($selectedJalurId)
            <input type="hidden" name="jalur_beasiswa_id" value="{{ $selectedJalurId }}">
        @endif
        @if($statusFilter)
            <input type="hidden" name="status_filter" value="{{ $statusFilter }}">
        @endif
        <div class="relative w-full sm:w-64">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <input name="search" value="{{ request('search') }}" class="form-input !py-1.5 !pl-9 text-sm" placeholder="Cari kandidat...">
        </div>
        <button class="btn-secondary !py-1.5 !px-3 text-sm">Cari</button>
        @if(request()->hasAny(['search', 'status_filter']))
            <a href="{{ route('operator.selection', array_filter(['tab' => 'lolos', 'periode' => $selectedPeriode, 'application_type' => $selectedType->value, 'jalur_beasiswa_id' => $selectedJalurId])) }}#tab-seleksi" class="btn-secondary !py-1.5 !px-3 text-sm" title="Reset filter pencarian">
                ✕
            </a>
        @endif
    </form>
</div>

<div class="table-shell mt-5">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Kandidat</th>
                    <th>Dasar Penilaian</th>
                    <th>Wilayah</th>
                    <th>Skor</th>
                    <th>Status</th>
                    <th>Keputusan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    @php
                        $profile = $application->mahasiswa->profile;
                        $verifiedDesil = $profile?->desil_sosial !== null ? (string) $profile->desil_sosial : '-';
                    @endphp
                    <tr>
                        <td>
                            <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-full bg-navy-900 px-2 font-bold text-white">
                                {{ $application->selection?->rank ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $application->mahasiswa->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $application->nomor_pengajuan }}</p>
                            @if($application->hasGrantedSanggahan())
                                <p class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="status-chip status-success">Hasil Sanggahan</span>
                                    <a href="{{ route('operator.sanggahan.show', $application->sanggahan) }}" class="text-[11px] font-semibold text-brand-600">
                                        Lihat detail →
                                    </a>
                                </p>
                            @endif
                            @if($application->pendaftaran?->jalurBeasiswa)
                                <p class="mt-1">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $application->pendaftaran->jalurBeasiswa->kode === 'REGULER' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                        {{ $application->pendaftaran->jalurBeasiswa->nama }}
                                    </span>
                                </p>
                            @endif
                        </td>
                        <td>
                            @php
                                $pendidikan = $application->pendaftaran?->pendidikan;
                                $ipkVal = $pendidikan?->ipk ?? $profile?->ipk ?? '-';
                                $tahunMasukVal = $pendidikan?->tahun_masuk ?? '-';
                                $akreditasiVal = $pendidikan?->akreditasi_perguruan_tinggi ?? '-';
                            @endphp

                            @if($selectedType === \App\Enums\ApplicationType::TIDAK_MAMPU)
                                <p class="font-semibold text-slate-900">Desil {{ $verifiedDesil }} · IPK {{ $ipkVal }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Angkatan {{ $tahunMasukVal }} · Akr. {{ $akreditasiVal }}
                                </p>
                            @elseif($selectedType === \App\Enums\ApplicationType::AKADEMIK)
                                <p class="font-semibold text-slate-900">IPK {{ $ipkVal }}</p>
                                <p class="mt-1 text-xs text-slate-500">Angkatan {{ $tahunMasukVal }} · Akreditasi {{ $akreditasiVal }}</p>
                            @elseif($selectedType === \App\Enums\ApplicationType::NON_AKADEMIK)
                                @php
                                    $bestPrestasi = $application->pendaftaran?->prestasis?->first(fn ($p) => ! (bool) $p->is_pengurus_inti_ormawa);
                                    $ormawaPrestasi = $application->pendaftaran?->prestasis?->first(fn ($p) => (bool) $p->is_pengurus_inti_ormawa);
                                @endphp
                                <p class="font-semibold text-slate-900">
                                    {{ $bestPrestasi ? ($bestPrestasi->peringkat.' ('.ucfirst($bestPrestasi->tingkat).')') : 'Tanpa Lomba' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Ormawa: <span class="font-medium text-slate-700">{{ $ormawaPrestasi ? ($ormawaPrestasi->jabatan_ormawa ?: 'Pengurus Inti') : '-' }}</span> · Angkatan {{ $tahunMasukVal }} · Akr. {{ $akreditasiVal }}
                                </p>
                            @else
                                <p class="font-semibold text-slate-900">Angkatan {{ $tahunMasukVal }}</p>
                                <p class="mt-1 text-xs text-slate-500">Akreditasi PT: {{ $akreditasiVal }} · Disabilitas: {{ $profile?->disability_type ?? '-' }}</p>
                            @endif
                        </td>
                        <td>
                            <p>{{ $profile?->village?->display_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $profile?->village?->kecamatan?->name }}</p>
                        </td>
                        <td>
                            <p class="font-display text-lg font-bold text-navy-900">{{ number_format((float) ($application->selection?->final_score ?? 0), 2) }}</p>
                        </td>
                        <td>
                            @php
                                $internalDecision = $application->selection?->manualDecisionStatus();
                            @endphp
                            @if($application->selection?->published_at)
                                <x-status-badge :status="$application->status" />
                                <p class="mt-1 text-[11px] text-slate-500">Dipublikasikan {{ $application->selection->published_at->translatedFormat('d M Y') }}</p>
                            @elseif($internalDecision)
                                <span class="status-chip {{ $internalDecision === \App\Enums\ApplicationStatus::DITERIMA ? 'status-success' : 'status-danger' }}">
                                    Internal: {{ $internalDecision->label() }}
                                </span>
                                <p class="mt-1 text-[11px] text-slate-500">Belum dipublikasikan</p>
                            @else
                                <span class="status-chip status-purple">Menunggu Penetapan</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $isReopenedBySanggahan = $application->hasGrantedSanggahan();
                            @endphp
                            @if(! $isCurrentPeriod && ! $isReopenedBySanggahan)
                                <div class="min-w-[220px]">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                        <x-icon name="lock" class="h-3.5 w-3.5 text-slate-400" />
                                        Arsip Terkunci
                                    </span>
                                </div>
                            @elseif($application->selection?->published_at)
                                <div class="min-w-[310px] rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    Keputusan telah dikunci setelah publikasi.
                                </div>
                            @else
                                <form method="POST" action="{{ route('operator.selection.store', $application) }}" class="flex items-center gap-2">
                                    @csrf
                                    @php
                                        $currentDecision = $application->selection?->manualDecisionStatus();
                                    @endphp
                                    <select name="decision" 
                                            class="form-input !w-32 !py-2 text-xs font-semibold rounded-lg transition-colors
                                                   {{ $currentDecision === \App\Enums\ApplicationStatus::DITERIMA ? '!border-emerald-500 !bg-emerald-50 !text-emerald-800' : ($currentDecision === \App\Enums\ApplicationStatus::DITOLAK ? '!border-rose-500 !bg-rose-50 !text-rose-800' : '!border-slate-300 text-slate-700') }}"
                                            onchange="this.className = 'form-input !w-32 !py-2 text-xs font-semibold rounded-lg transition-colors ' + (this.value === 'DITERIMA' ? '!border-emerald-500 !bg-emerald-50 !text-emerald-800' : (this.value === 'DITOLAK' ? '!border-rose-500 !bg-rose-50 !text-rose-800' : '!border-slate-300 text-slate-700'))"
                                            required>
                                        <option value="">Pilih</option>
                                        <option value="DITERIMA" @selected($currentDecision === \App\Enums\ApplicationStatus::DITERIMA)>Diterima</option>
                                        <option value="DITOLAK" @selected($currentDecision === \App\Enums\ApplicationStatus::DITOLAK)>Ditolak</option>
                                    </select>
                                    <input name="notes" class="form-input !w-32 !py-2 text-xs" placeholder="Catatan" value="{{ $application->selection?->notes }}">
                                    <button class="btn-primary !px-3 !py-2 shrink-0" title="Simpan keputusan">
                                        <x-icon name="check" class="h-4 w-4" />
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-14 text-center text-slate-500">Belum ada kandidat jalur {{ $selectedType->label() }}@if($selectedJalurId) ({{ $jalurBeasiswas->firstWhere('id', $selectedJalurId)?->nama }})@endif yang siap diseleksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $applications->links() }}</div>
@else
    {{-- TAB 2: DAFTAR BERKAS TMS (GUGUR ADMINISTRASI / MASA SANGGAH) --}}
    <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm font-semibold text-slate-500">Berkas TMS Jalur {{ $selectedJalur?->nama ?? $selectedType->label() }}</p>
            <p class="metric-number mt-2 text-rose-600">{{ number_format($currentTrackTmsCount) }}</p>
            <p class="mt-1 text-xs text-slate-500">Mahasiswa gugur administrasi verifikasi dinas teknis</p>
        </div>
        <div class="card p-5">
            <p class="text-sm font-semibold text-slate-500">Total Berkas TMS Semua Jalur</p>
            <p class="metric-number mt-2 text-slate-800">{{ number_format($allTracksTmsCount) }}</p>
            <p class="mt-1 text-xs text-slate-500">Periode {{ $selectedPeriode }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm font-semibold text-slate-500">Tindak Lanjut Masa Sanggah</p>
            <p class="mt-2 text-sm font-bold text-navy-900">Hak Sanggah Mahasiswa</p>
            <p class="mt-1 text-xs text-slate-500">Berkas yang dikabulkan sanggahannya otomatis dipulihkan ke Tab Calon Lolos.</p>
        </div>
    </div>

    <div class="mt-7 rounded-xl border border-rose-200 bg-rose-50 p-5">
        <div class="flex items-start gap-3">
            <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-rose-700" />
            <div class="text-sm leading-6 text-rose-900">
                <p class="font-bold">Daftar Berkas Tidak Memenuhi Syarat (TMS) Verifikasi Dinas Teknis</p>
                <p class="mt-1 text-xs text-rose-800">
                    Mahasiswa di bawah ini dinyatakan TMS pada verifikasi administrasi oleh dinas teknis (Dukcapil, Dinsos, Disdikbud, Dinkes, BKPSDM, Parsepor).
                    Daftar ini digunakan untuk <b>merekap pengumuman sementara pra-sanggah</b> dan <b>memantau masa sanggah</b>.
                    Sesuai prinsip keadilan Perbup, mahasiswa berkas TMS <b>tidak diberi nomor peringkat maupun skor kuota</b>. Apabila sanggahan mahasiswa dikabulkan (karena koreksi kesalahan verifikator), berkas akan dipulihkan ke tahap seleksi untuk diperingkat bersama kandidat lainnya.
                </p>
            </div>
        </div>
    </div>

    <div class="mt-7 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
        <div class="text-xs font-medium text-slate-600">
            Menampilkan berkas TMS jalur <b>{{ $selectedType->label() }}</b>@if($selectedJalur) - <b>{{ $selectedJalur->nama }}</b>@endif ({{ number_format($currentTrackTmsCount) }} mahasiswa)
        </div>
        <form method="GET" action="{{ route('operator.selection') }}#tab-seleksi" class="flex w-full items-center gap-2 sm:w-auto">
            <input type="hidden" name="tab" value="tms">
            <input type="hidden" name="periode" value="{{ $selectedPeriode }}">
            <input type="hidden" name="application_type" value="{{ $selectedType->value }}">
            @if($selectedJalurId)
                <input type="hidden" name="jalur_beasiswa_id" value="{{ $selectedJalurId }}">
            @endif
            <div class="relative w-full sm:w-64">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                <input name="search" value="{{ request('search') }}" class="form-input !py-1.5 !pl-9 text-sm" placeholder="Cari nomor pengajuan / nama...">
            </div>
            <button class="btn-secondary !py-1.5 !px-3 text-sm">Cari</button>
            @if(request()->filled('search'))
                <a href="{{ route('operator.selection', array_filter(['tab' => 'tms', 'periode' => $selectedPeriode, 'application_type' => $selectedType->value, 'jalur_beasiswa_id' => $selectedJalurId])) }}#tab-seleksi" class="btn-secondary !py-1.5 !px-3 text-sm" title="Reset filter pencarian">
                    ✕
                </a>
            @endif
        </form>
    </div>

    <div class="table-shell mt-5">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="w-12 text-center">No</th>
                        <th>Mahasiswa</th>
                        <th>Jalur & Kategori</th>
                        <th>Wilayah</th>
                        <th>Dinas Penolak</th>
                        <th>Alasan / Catatan Penolakan Dinas</th>
                        <th>Status Sanggahan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tmsApplications as $application)
                        @php
                            $profile = $application->mahasiswa?->profile;
                            $tmsVerifications = $application->agencyVerifications->where('decision', \App\Enums\VerificationDecision::TMS);
                            $catatanTms = $tmsVerifications
                                ->filter(fn ($v) => filled($v->notes))
                                ->map(fn ($v) => \App\Services\AgencyVerificationService::stageLabel($v->agency).': '.$v->notes)
                                ->values();
                            if ($catatanTms->isEmpty() && filled($application->catatan)) {
                                $catatanTms = collect([$application->catatan]);
                            }

                            $isWaitingDinas = $application->status === \App\Enums\ApplicationStatus::VERIFIKASI_DINAS
                                && $application->sanggahan?->status === \App\Enums\SanggahanStatus::DIKABULKAN;

                            $grantedLog = $isWaitingDinas
                                ? $application->verificationLogs->firstWhere('action', 'sanggahan_granted')
                                : null;
                            $metadata = $grantedLog?->metadata ?? [];
                            $annulledAgencies = collect($metadata['annulled_agencies'] ?? []);
                            $annulledVerifications = collect($metadata['annulled_verifications'] ?? []);

                            if ($annulledVerifications->isEmpty() && $annulledAgencies->isNotEmpty()) {
                                $annulledVerifications = $annulledAgencies->map(fn ($ag) => [
                                    'agency' => $ag,
                                    'agency_label' => \App\Services\AgencyVerificationService::stageLabel($ag),
                                    'notes' => $application->sanggahan?->catatan_keputusan ?? 'Keputusan TMS dianulir lewat sanggahan.',
                                ]);
                            }

                            if ($tmsVerifications->isEmpty() && $annulledVerifications->isNotEmpty()) {
                                $catatanTms = $annulledVerifications
                                    ->filter(fn ($v) => filled($v['notes'] ?? null))
                                    ->map(fn ($v) => ($v['agency_label'] ?? \App\Services\AgencyVerificationService::stageLabel($v['agency'])).': '.$v['notes'])
                                    ->values();
                            }

                            $rowNum = ($tmsApplications->currentPage() - 1) * $tmsApplications->perPage() + $loop->iteration;
                        @endphp
                        <tr>
                            <td class="text-center font-bold text-slate-500">
                                {{ $rowNum }}
                            </td>
                            <td>
                                <p class="font-semibold text-slate-900">{{ $application->mahasiswa?->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $application->nomor_pengajuan }}</p>
                                @php
                                    $pendidikan = $application->pendaftaran?->pendidikan;
                                @endphp
                                @if($pendidikan)
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{ $pendidikan->nama_perguruan_tinggi }} · {{ $pendidikan->program_studi }} (Smtr {{ $pendidikan->semester }})
                                    </p>
                                @endif
                            </td>
                            <td>
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                    {{ $application->application_type->label() }}
                                </span>
                                @if($application->pendaftaran?->jalurBeasiswa)
                                    <p class="mt-1">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $application->pendaftaran->jalurBeasiswa->kode === 'REGULER' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                            {{ $application->pendaftaran->jalurBeasiswa->nama }}
                                        </span>
                                    </p>
                                @endif
                            </td>
                            <td>
                                <p class="text-xs font-semibold text-slate-800">{{ $profile?->village?->display_name ?? '-' }}</p>
                                <p class="text-[11px] text-slate-500">{{ $profile?->village?->kecamatan?->name ?? '-' }}</p>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($tmsVerifications as $v)
                                        <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200">
                                            {{ \App\Services\AgencyVerificationService::stageLabel($v->agency) }}
                                        </span>
                                    @empty
                                        @if($annulledVerifications->isNotEmpty())
                                            @foreach($annulledVerifications as $av)
                                                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800 border border-amber-200" title="Keputusan TMS dianulir, sedang dinilai ulang oleh dinas">
                                                    {{ $av['agency_label'] ?? \App\Services\AgencyVerificationService::stageLabel($av['agency']) }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200">
                                                Dinas Teknis
                                            </span>
                                        @endif
                                    @endforelse
                                </div>
                            </td>
                            <td class="max-w-xs">
                                <div class="space-y-1">
                                    @forelse($catatanTms as $note)
                                        <p class="text-xs text-slate-700 leading-relaxed">{{ $note }}</p>
                                    @empty
                                        <p class="text-xs italic text-slate-400">Tidak ada catatan rincian dari dinas.</p>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if($application->sanggahan)
                                    <div>
                                        <span class="status-chip {{ $application->sanggahan->status === \App\Enums\SanggahanStatus::DIKABULKAN ? 'status-success' : ($application->sanggahan->status === \App\Enums\SanggahanStatus::DITOLAK ? 'status-danger' : 'status-purple') }}">
                                            {{ $application->sanggahan->status->label() }}
                                        </span>

                                        @if($application->sanggahan->status === \App\Enums\SanggahanStatus::DIKABULKAN && $application->status === \App\Enums\ApplicationStatus::VERIFIKASI_DINAS)
                                            <p class="mt-0.5 text-[11px] font-semibold text-emerald-700">
                                                Menunggu dinas menilai ulang
                                            </p>
                                        @endif

                                        <a href="{{ route('operator.sanggahan.index') }}" class="mt-1 block text-[11px] font-semibold text-brand-600 hover:text-brand-800">
                                            Lihat di Antrean Sanggahan →
                                        </a>
                                    </div>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                        Belum Ada Sanggahan
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center text-slate-500">
                                Tidak ada berkas TMS untuk jalur {{ $selectedType->label() }}@if($selectedJalur) ({{ $selectedJalur->nama }})@endif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($tmsApplications)
        <div class="mt-6">{{ $tmsApplications->links() }}</div>
    @endif
@endif
@endsection
