@extends('layouts.portal')

@section('title', 'Sanggahan')
@section('header', 'Sanggahan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="section-kicker">Operator Kabupaten</p>
            <h1 class="mt-1 text-2xl font-extrabold text-navy-950 sm:text-3xl">Antrean Sanggahan</h1>
            <p class="mt-1.5 text-sm text-slate-600">
                Sanggahan atas keputusan TMS pada wilayah kabupaten Anda, beserta ringkasan statusnya.
            </p>
        </div>
    </div>

    <div>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($statuses as $status)
                <div @class([
                    'card border-l-4 p-5',
                    'border-l-amber-400' => $status === \App\Enums\SanggahanStatus::DIAJUKAN,
                    'border-l-emerald-400' => $status === \App\Enums\SanggahanStatus::DIKABULKAN,
                    'border-l-rose-400' => $status === \App\Enums\SanggahanStatus::DITOLAK,
                ])>
                    <p class="text-sm font-semibold text-slate-500">{{ $status->label() }}</p>
                    <p class="metric-number mt-2">{{ number_format($summary[$status->value]) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $status->description() }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-slate-500">
            Ringkasan filter periode dan pencarian:
            {{ $summary[\App\Enums\SanggahanStatus::DIAJUKAN->value] }} diajukan &middot;
            {{ $summary[\App\Enums\SanggahanStatus::DIKABULKAN->value] }} dikabulkan &middot;
            {{ $summary[\App\Enums\SanggahanStatus::DITOLAK->value] }} ditolak.
            Filter status tidak mempersempit ringkasan ini.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('operator.sanggahan.index') }}" class="grid gap-4 md:grid-cols-4">
            <div>
                <label for="periode_id" class="form-label text-xs">Periode</label>
                <select id="periode_id" name="periode_id" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="">Semua Periode</option>
                    @foreach ($periodes as $periode)
                        <option value="{{ $periode->id }}" @selected((string) request('periode_id') === (string) $periode->id)>
                            {{ $periode->nama ?: 'Periode '.$periode->tahun }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="form-label text-xs">Status Sanggahan</label>
                <select id="status" name="status" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="q" class="form-label text-xs">Cari Nomor Pengajuan / Nama</label>
                <div class="flex gap-2">
                    <input id="q" name="q" type="text" class="form-input text-sm" placeholder="Nomor pengajuan atau nama mahasiswa" value="{{ request('q') }}">
                    <button type="submit" class="btn-primary shrink-0 px-3">
                        <x-icon name="search" class="h-4 w-4" />
                    </button>
                    @if (request()->hasAny(['periode_id', 'status', 'q']))
                        <a href="{{ route('operator.sanggahan.index') }}" class="btn-secondary shrink-0 px-3" title="Reset filter">
                            ✕
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <section class="table-shell">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Nomor Pengajuan</th>
                        <th>Periode</th>
                        <th>Tanggal Diajukan</th>
                        <th>Status</th>
                        <th>Diputuskan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sanggahans as $sanggahan)
                        @php($application = $sanggahan->application)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-900">{{ $application?->mahasiswa?->name ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $application?->mahasiswa?->profile?->village?->display_name }}
                                </p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $application?->nomor_pengajuan ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $application?->application_type?->label() }}</p>
                            </td>
                            <td>{{ $sanggahan->periode?->nama ?? '-' }}</td>
                            <td class="whitespace-nowrap">{{ $sanggahan->created_at?->format('d M Y H:i') }}</td>
                            <td><x-status-badge :status="$sanggahan->status" /></td>
                            <td>
                                @if ($sanggahan->status->isDecided())
                                    <p class="text-sm font-semibold text-slate-800">{{ $sanggahan->decidedBy?->name ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $sanggahan->decided_at?->format('d M Y H:i') }}</p>
                                @else
                                    <span class="text-xs text-slate-500">Menunggu keputusan</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('operator.sanggahan.show', $sanggahan) }}" class="btn-secondary !px-3 !py-2 text-xs">
                                    <x-icon name="eye" class="h-4 w-4" />
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 text-center text-slate-500">Belum ada sanggahan yang sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>{{ $sanggahans->links() }}</div>
</div>
@endsection
