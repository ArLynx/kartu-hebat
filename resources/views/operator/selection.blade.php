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
        <a href="{{ route('operator.selection.export', array_filter(['application_type' => $selectedType->value, 'jalur_beasiswa_id' => $selectedJalurId])) }}" class="btn-secondary !py-2 bg-emerald-50 text-emerald-700 border-emerald-300 hover:bg-emerald-100">
            <x-icon name="download" class="h-4 w-4" /> Unduh Format Excel
        </a>
        <button type="button" @click="openImportModal = true" class="btn-secondary !py-2 bg-blue-50 text-blue-700 border-blue-300 hover:bg-blue-100">
            <x-icon name="upload" class="h-4 w-4 mr-1 inline" /> 1. Impor ACC Excel
        </button>
        <button type="button" @click="openPublishModal = true" class="btn-primary !py-2 bg-emerald-600 hover:bg-emerald-700">
            <x-icon name="check-circle" class="h-4 w-4 mr-1 inline" /> 2. Publikasikan Hasil (Upload SK)
        </button>

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
                        <p class="text-xs text-slate-500">Unggah berkas Surat Keputusan (SK) untuk merilis hasil akhir ke publik &amp; mahasiswa.</p>
                    </div>
                    <button type="button" @click="openPublishModal = false" class="text-slate-400 hover:text-slate-600">
                        <x-icon name="close" class="h-5 w-5" />
                    </button>
                </div>

                <form method="POST" action="{{ route('operator.selection.publish') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
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
                        <span>Aksi ini akan menetapkan status akhir <b>DITERIMA</b> / <b>DITOLAK</b> secara permanen, melampirkan berkas SK ke pengumuman publik, dan mengirim notifikasi ke seluruh akun mahasiswa.</span>
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

<div class="mt-7 grid gap-3 sm:grid-cols-2">
    @foreach($applicationTypes as $type)
        <a href="{{ route('operator.selection', ['application_type' => $type->value]) }}"
           class="rounded-xl border p-5 transition {{ $selectedType === $type ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-100' : 'border-slate-200 bg-white hover:border-brand-200' }}">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-display text-lg font-bold text-navy-900">Jalur {{ $type->label() }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $type->description() }}</p>
                </div>
                <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-navy-900 px-3 py-1 text-sm font-bold text-white">
                    {{ number_format($typeCounts[$type->value] ?? 0) }}
                </span>
            </div>
        </a>
    @endforeach
</div>

<div class="mt-7 grid gap-5 sm:grid-cols-3">
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
</div>

<div class="mt-7 rounded-xl border border-blue-200 bg-blue-50 p-5">
    <div class="flex items-start gap-3">
        <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0 text-blue-700" />
        <div class="text-sm leading-6 text-blue-800">
            @if($selectedType === \App\Enums\ApplicationType::AKADEMIK)
                <p class="font-bold">Rumus jalur Akademik</p>
                <p>IPK berbobot 75% dan semester aktif berbobot 25%. IPK dinormalisasi terhadap skala 4,00; semester dinormalisasi sampai semester {{ config('kartu_hebat.scoring.academic_max_semester') }}.</p>
            @else
                <p class="font-bold">Rumus jalur Tidak Mampu</p>
                <p>Skor 100% berasal dari rata-rata desil Dinas Sosial dan Dinas Pendidikan. Desil 1 memperoleh prioritas tertinggi; desil 10 memperoleh skor terendah.</p>
            @endif
            <p class="mt-1">Skor otomatis merupakan alat bantu. Keputusan akhir tetap ditetapkan Operator Kabupaten.</p>
        </div>
    </div>
</div>

<div class="mt-7 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-4">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-1">Kategori:</span>
        <a href="{{ route('operator.selection', ['application_type' => $selectedType->value]) }}"
           class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ empty($selectedJalurId) ? 'bg-navy-900 text-white shadow-sm ring-2 ring-navy-900/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            <span>Semua</span>
            <span class="rounded-full px-2 py-0.5 text-[11px] {{ empty($selectedJalurId) ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                {{ number_format($totalCurrentTypeCount) }}
            </span>
        </a>
        @foreach($jalurBeasiswas as $jalur)
            @php $isActive = (int) $selectedJalurId === (int) $jalur->id; @endphp
            <a href="{{ route('operator.selection', ['application_type' => $selectedType->value, 'jalur_beasiswa_id' => $jalur->id]) }}"
               class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ $isActive ? ($jalur->kode === 'REGULER' ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-600/20' : 'bg-purple-600 text-white shadow-sm ring-2 ring-purple-600/20') : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                <span>{{ $jalur->nama }}</span>
                <span class="rounded-full px-2 py-0.5 text-[11px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                    {{ number_format($jalurCounts[$jalur->id] ?? 0) }}
                </span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="flex items-center gap-2">
        <input type="hidden" name="application_type" value="{{ $selectedType->value }}">
        @if($selectedJalurId)
            <input type="hidden" name="jalur_beasiswa_id" value="{{ $selectedJalurId }}">
        @endif
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <input name="search" value="{{ request('search') }}" class="form-input !py-1.5 !pl-9 text-sm" placeholder="Cari kandidat...">
        </div>
        <button class="btn-secondary !py-1.5 !px-3 text-sm">Cari</button>
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
                        $desils = collect([$profile?->desil_sosial, $profile?->desil_pendidikan])->filter(fn($value) => $value !== null);
                        $verifiedDesil = $desils->isNotEmpty() ? number_format((float) $desils->average(), 2) : '-';
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
                            @if($application->pendaftaran?->jalurBeasiswa)
                                <p class="mt-1">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold {{ $application->pendaftaran->jalurBeasiswa->kode === 'REGULER' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                        {{ $application->pendaftaran->jalurBeasiswa->nama }}
                                    </span>
                                </p>
                            @endif
                        </td>
                        <td>
                            @if($selectedType === \App\Enums\ApplicationType::AKADEMIK)
                                <p class="font-medium">IPK {{ $profile?->ipk ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Semester {{ $profile?->semester ?? '-' }}</p>
                            @else
                                <p class="font-medium">Desil {{ $verifiedDesil }}</p>
                                <p class="mt-1 text-xs text-slate-500">Sosial {{ $profile?->desil_sosial ?? '-' }} · Pendidikan {{ $profile?->desil_pendidikan ?? '-' }}</p>
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
                            @if($application->selection?->published_at)
                                <x-status-badge :status="$application->status" />
                                <p class="mt-1 text-[11px] text-slate-500">Dipublikasikan {{ $application->selection->published_at->translatedFormat('d M Y') }}</p>
                            @elseif($application->selection?->manual_decision)
                                <span class="status-chip {{ $application->selection->manual_decision === 'DITERIMA' ? 'status-success' : 'status-danger' }}">
                                    Internal: {{ $application->selection->manual_decision === 'DITERIMA' ? 'Diterima' : 'Ditolak' }}
                                </span>
                                <p class="mt-1 text-[11px] text-slate-500">Belum dipublikasikan</p>
                            @else
                                <span class="status-chip status-purple">Menunggu Penetapan</span>
                            @endif
                        </td>
                        <td>
                            @if($application->selection?->published_at)
                                <div class="min-w-[310px] rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                    Keputusan telah dikunci setelah publikasi.
                                </div>
                            @else
                                <form method="POST" action="{{ route('operator.selection.store', $application) }}" class="flex min-w-[310px] items-center gap-2">
                                    @csrf
                                    <select name="decision" class="form-input !py-2" required>
                                        <option value="">Pilih keputusan</option>
                                        <option value="DITERIMA" @selected($application->selection?->manual_decision === 'DITERIMA')>Diterima</option>
                                        <option value="DITOLAK" @selected($application->selection?->manual_decision === 'DITOLAK')>Ditolak</option>
                                    </select>
                                    <input name="notes" class="form-input !py-2" placeholder="Catatan" value="{{ $application->selection?->notes }}">
                                    <button class="btn-primary !px-3 !py-2" title="Simpan keputusan">
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
@endsection
