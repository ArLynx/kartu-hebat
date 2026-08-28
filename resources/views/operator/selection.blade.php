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
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('operator.reports.pdf', ['application_type' => $selectedType->value]) }}" class="btn-secondary !py-2">
            <x-icon name="download" class="h-4 w-4" /> PDF Rekap
        </a>
        <a href="{{ route('operator.reports.recipients-pdf', ['application_type' => $selectedType->value]) }}" class="btn-secondary !py-2">
            <x-icon name="download" class="h-4 w-4" /> PDF Penerima
        </a>
        <a href="{{ route('operator.reports.excel', ['application_type' => $selectedType->value]) }}" class="btn-secondary !py-2">
            <x-icon name="download" class="h-4 w-4" /> Excel
        </a>
        <form method="POST" action="{{ route('operator.selection.publish') }}" onsubmit="return confirm('Publikasikan seluruh keputusan baru dari kedua jalur?')">
            @csrf
            <button class="btn-primary !py-2">Publikasikan Hasil</button>
        </form>
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

<form method="GET" class="mt-7 flex max-w-md gap-3">
    <input type="hidden" name="application_type" value="{{ $selectedType->value }}">
    <div class="relative flex-1">
        <x-icon name="search" class="pointer-events-none absolute left-3 top-3 h-5 w-5 text-slate-400" />
        <input name="search" value="{{ request('search') }}" class="form-input !pl-10" placeholder="Cari kandidat...">
    </div>
    <button class="btn-secondary !py-2">Cari</button>
</form>

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
                    <tr><td colspan="7" class="py-14 text-center text-slate-500">Belum ada kandidat jalur {{ $selectedType->label() }} yang siap diseleksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $applications->links() }}</div>
@endsection
