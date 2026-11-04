@extends('layouts.portal')

@section('title', 'Monitoring LPJ & Blokir NIK')
@section('header', 'Monitoring LPJ & Blokir NIK')

@section('content')
<div class="space-y-6">

    {{-- HEADER & DESKRIPSI --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="section-kicker">Admin Kabupaten</p>
            <h1 class="mt-1 text-2xl font-extrabold text-navy-950 sm:text-3xl">
                Monitoring LPJ & Blokir NIK
            </h1>
            <p class="mt-1.5 text-sm text-slate-600">
                Pantau pengumpulan Laporan Pertanggungjawaban penerima beasiswa dan kelola status blokir NIK untuk pendaftaran periode berikutnya.
            </p>
        </div>
    </div>

    {{-- PILIHAN JENIS / JALUR BEASISWA (PERSIS SEPERTI DI HALAMAN PENETAPAN) --}}
    <div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between mb-3">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pilih Jenis / Jalur Beasiswa:</span>
                <p class="text-xs text-slate-500">Pilih salah satu kartu di bawah untuk menyaring data penerima LPJ, atau klik Semua Jalur.</p>
            </div>
            <div>
                <a href="{{ route('operator.lpj.index', array_merge(request()->except('application_type', 'page'))) }}"
                   class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition {{ empty($selectedType) ? 'bg-navy-900 text-white shadow-sm ring-2 ring-navy-900/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <span>Semua Jalur</span>
                    <span class="rounded-full px-2 py-0.5 text-[11px] {{ empty($selectedType) ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ number_format($totalPenerimaSemuaJalur) }}
                    </span>
                </a>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($applicationTypes as $type)
                @php
                    $isActive = ($selectedType === $type->value);
                    $count = $typeCounts[$type->value] ?? 0;
                    $sudah = $typeSudahLpjCounts[$type->value] ?? 0;
                @endphp
                <a href="{{ route('operator.lpj.index', array_merge(request()->except('page'), ['application_type' => $isActive ? null : $type->value])) }}"
                   class="rounded-xl border p-5 transition {{ $isActive ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-100 shadow-sm' : 'border-slate-200 bg-white hover:border-brand-200' }}">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-display text-lg font-bold text-navy-900">Jalur {{ $type->label() }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $type->description() }}</p>
                            <p class="mt-2 text-xs font-semibold {{ $sudah === $count && $count > 0 ? 'text-emerald-700' : 'text-slate-600' }}">
                                Progress LPJ: <span class="font-bold text-navy-950">{{ $sudah }}</span> dari <span class="font-bold text-navy-950">{{ $count }}</span> Mahasiswa Terkumpul
                            </p>
                        </div>
                        <span class="inline-flex min-w-10 items-center justify-center rounded-full px-3 py-1 text-sm font-bold {{ $isActive ? 'bg-brand-600 text-white' : 'bg-navy-900 text-white' }}">
                            {{ number_format($count) }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">
                Total Penerima {{ $selectedTypeEnum ? 'Jalur ' . $selectedTypeEnum->label() : '' }}
            </p>
            <p class="mt-1 text-2xl font-extrabold text-navy-950">{{ number_format($totalPenerima) }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Periode {{ $selectedPeriode?->tahun ?? 'Semua' }}</p>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
            <p class="text-xs font-medium text-emerald-700">Sudah Upload LPJ</p>
            <p class="mt-1 text-2xl font-extrabold text-emerald-800">{{ number_format($totalSudahLpj) }}</p>
            <p class="mt-1 text-[11px] text-emerald-600">
                {{ $totalPenerima > 0 ? round(($totalSudahLpj / $totalPenerima) * 100, 1) : 0 }}% terkumpul
            </p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
            <p class="text-xs font-medium text-amber-700">Belum Kumpul LPJ</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-800">{{ number_format($totalBelumLpj) }}</p>
            <p class="mt-1 text-[11px] text-amber-600">Kewajiban tersisa</p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
            <p class="text-xs font-medium text-amber-700">Perlu Penyelesaian LPJ</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-800">{{ number_format($totalDiblokir) }}</p>
            <p class="mt-1 text-[11px] text-amber-600">Pendaftaran baru ditangguhkan</p>
        </div>
    </div>

    {{-- FILTER BAR --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('operator.lpj.index') }}" class="grid gap-4 md:grid-cols-4">
            @if($selectedType)
                <input type="hidden" name="application_type" value="{{ $selectedType }}">
            @endif

            {{-- Filter Periode --}}
            <div>
                <label for="periode_id" class="form-label text-xs">Tahun / Periode</label>
                <select id="periode_id" name="periode_id" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="" @selected(empty($selectedPeriodeId))>Semua Tahun / Periode</option>
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" @selected($selectedPeriodeId === $p->id)>
                            Tahun {{ $p->tahun }} - {{ $p->nama ?: 'Beasiswa ' . $p->tahun }} ({{ ucfirst($p->status) }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Status LPJ --}}
            <div>
                <label for="status_lpj" class="form-label text-xs">Status Berkas LPJ</label>
                <select id="status_lpj" name="status_lpj" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="">Semua Status LPJ</option>
                    <option value="belum_upload" @selected(request('status_lpj') === 'belum_upload')>Belum Upload</option>
                    <option value="menunggu_verifikasi" @selected(request('status_lpj') === 'menunggu_verifikasi')>Menunggu Verifikasi</option>
                    <option value="disetujui" @selected(request('status_lpj') === 'disetujui')>Disetujui</option>
                    <option value="ditolak" @selected(request('status_lpj') === 'ditolak')>Perlu Perbaikan</option>
                </select>
            </div>

            {{-- Filter Status Kewajiban --}}
            <div>
                <label for="status_blacklist" class="form-label text-xs">Status Kewajiban LPJ</label>
                <select id="status_blacklist" name="status_blacklist" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="">Semua Status Kewajiban</option>
                    <option value="diblokir" @selected(request('status_blacklist') === 'diblokir')>Perlu Penyelesaian</option>
                    <option value="tidak_diblokir" @selected(request('status_blacklist') === 'tidak_diblokir')>Selesai / Terbuka</option>
                </select>
            </div>

            {{-- Pencarian --}}
            <div>
                <label for="q" class="form-label text-xs">Cari Mahasiswa</label>
                <div class="flex gap-2">
                    <input id="q" name="q" type="text" class="form-input text-sm" placeholder="Nama / NIK / No. Daftar" value="{{ request('q') }}">
                    <button type="submit" class="btn-primary shrink-0 px-3">
                        <x-icon name="search" class="h-4 w-4" />
                    </button>
                    @if(request()->hasAny(['q', 'status_lpj', 'status_blacklist', 'application_type']))
                        <a href="{{ route('operator.lpj.index', array_filter(['periode_id' => $selectedPeriodeId, 'application_type' => $selectedType])) }}" class="btn-secondary shrink-0 px-3" title="Reset filter pencarian">
                            ✕
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- INFO JADWAL LPJ --}}
        @if($selectedPeriode)
            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <span class="font-medium text-slate-700">Jadwal LPJ Periode Ini:</span>
                @if($selectedPeriode->tanggal_mulai_lpj && $selectedPeriode->tanggal_selesai_lpj)
                    <span class="rounded bg-slate-100 px-2 py-0.5 font-semibold text-slate-800">
                        {{ $selectedPeriode->tanggal_mulai_lpj->format('d M Y') }} s/d {{ $selectedPeriode->tanggal_selesai_lpj->format('d M Y') }}
                    </span>
                    @if($selectedPeriode->isLpjOpen())
                        <span class="text-emerald-600 font-semibold">● Sedang Dibuka</span>
                    @elseif($selectedPeriode->isLpjPastDeadline())
                        <span class="text-rose-600 font-semibold">● Telah Lewat Batas Waktu</span>
                    @else
                        <span class="text-amber-600 font-semibold">● Belum Dimulai</span>
                    @endif
                @else
                    <span class="italic text-slate-400">Jadwal LPJ belum diatur oleh Superadmin.</span>
                @endif
            </div>
        @endif
    </div>

    {{-- TABEL DATA --}}
    <section class="table-shell">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Perguruan Tinggi</th>
                        <th>Kategori</th>
                        <th>Status LPJ</th>
                        <th>Kewajiban LPJ</th>
                        <th>Keputusan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendaftarans as $pendaftaran)
                        @php
                            $lpj = $pendaftaran->laporanPertanggungjawaban;
                            $activeBlacklist = $pendaftaran->blacklists->firstWhere('aktif', true);
                            $nik = $pendaftaran->dataPribadi?->nik;
                            $nama = $pendaftaran->dataPribadi?->nama_lengkap ?? $pendaftaran->user?->name;
                        @endphp
                        <tr>
                            {{-- MAHASISWA --}}
                            <td>
                                <p class="font-bold text-slate-900">{{ $nama }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                    <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">NIK: {{ $nik ?? '-' }}</span>
                                    <span>{{ $pendaftaran->nomor_pendaftaran }}</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-medium text-slate-600">Thn {{ $pendaftaran->periode?->tahun }}</span>
                                </div>
                            </td>

                            {{-- PENDIDIKAN --}}
                            <td>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $pendaftaran->pendidikan?->universitas ?? '-' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $pendaftaran->pendidikan?->program_studi ?? '-' }} ({{ $pendaftaran->pendidikan?->jenjang ?? '-' }} Sem {{ $pendaftaran->pendidikan?->semester ?? '-' }})
                                </p>
                            </td>

                            {{-- KATEGORI --}}
                            <td>
                                <span class="status-chip status-info text-xs">
                                    {{ $pendaftaran->kategoriBeasiswa?->nama ?? 'Beasiswa' }}
                                </span>
                            </td>

                            {{-- STATUS LPJ --}}
                            <td>
                                @if(! $lpj || $lpj->status === 'belum_upload')
                                    <span class="status-chip status-warning text-xs">Belum Upload</span>
                                @elseif($lpj->status === 'menunggu_verifikasi')
                                    <span class="status-chip status-purple text-xs">Menunggu Verifikasi</span>
                                @elseif($lpj->status === 'disetujui')
                                    <span class="status-chip status-success text-xs">Diterima</span>
                                @elseif($lpj->status === 'ditolak')
                                    <span class="status-chip status-danger text-xs">Perlu Perbaikan</span>
                                @endif

                                @if($lpj && $lpj->file_path)
                                    <div class="mt-1.5">
                                        <a href="{{ route('operator.lpj.file', $pendaftaran) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline">
                                            <x-icon name="document" class="h-3.5 w-3.5" />
                                            Lihat Berkas LPJ
                                        </a>
                                        @if($lpj->uploaded_at)
                                            <p class="text-[10px] text-slate-400">
                                                Upload: {{ $lpj->uploaded_at->format('d/m/Y H:i') }}
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- STATUS KEWAJIBAN LPJ --}}
                            <td>
                                @if($activeBlacklist)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">
                                        <x-icon name="information-circle" class="h-3.5 w-3.5" />
                                        Perlu Penyelesaian
                                    </span>
                                    <p class="mt-1 max-w-[220px] text-[11px] leading-relaxed text-slate-600 break-words whitespace-normal" title="{{ $activeBlacklist->alasan }}">
                                        {{ $activeBlacklist->alasan }}
                                    </p>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
                                        <x-icon name="check" class="h-3.5 w-3.5" />
                                        Selesai
                                    </span>
                                    <p class="mt-1 text-[11px] text-slate-400">Tidak ada tanggungan LPJ</p>
                                @endif
                            </td>

                            {{-- KEPUTUSAN --}}
                            <td>
                                <form method="POST" action="{{ route('operator.lpj.blacklist', $pendaftaran) }}" class="flex items-center gap-2">
                                    @csrf
                                    @php
                                        $isAccepted = ! $activeBlacklist && $lpj && $lpj->status === 'disetujui';
                                        $isRejected = (bool) $activeBlacklist || ($lpj && $lpj->status === 'ditolak');
                                    @endphp
                                    <select name="action" 
                                            class="form-input !w-32 !py-2 text-xs font-semibold rounded-lg transition-colors
                                                   {{ $isAccepted ? '!border-emerald-500 !bg-emerald-50 !text-emerald-800' : ($isRejected ? '!border-rose-500 !bg-rose-50 !text-rose-800' : '!border-slate-300 text-slate-700') }}"
                                            onchange="this.className = 'form-input !w-32 !py-2 text-xs font-semibold rounded-lg transition-colors ' + (this.value === 'unblock' ? '!border-emerald-500 !bg-emerald-50 !text-emerald-800' : (this.value === 'block' ? '!border-rose-500 !bg-rose-50 !text-rose-800' : '!border-slate-300 text-slate-700'))"
                                            required>
                                        <option value="">Pilih</option>
                                        <option value="unblock" @selected($isAccepted)>Diterima</option>
                                        <option value="block" @selected($isRejected)>Ditolak</option>
                                    </select>
                                    <input name="alasan" 
                                           class="form-input !w-32 !py-2 text-xs" 
                                           placeholder="Catatan" 
                                           value="{{ $activeBlacklist?->alasan ?? $lpj?->catatan }}"
                                           title="{{ $activeBlacklist?->alasan ?? $lpj?->catatan }}">
                                    <button type="submit" class="btn-primary !px-3 !py-2 shrink-0" title="Simpan keputusan">
                                        <x-icon name="check" class="h-4 w-4" />
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                Tidak ada data penerima beasiswa yang cocok dengan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendaftarans->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $pendaftarans->links() }}
            </div>
        @endif
    </section>

</div>
@endsection
