@extends('layouts.portal')

@section('title', 'Rekonsiliasi Hasil Verifikasi')
@section('header', 'Rekonsiliasi Hasil Verifikasi')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="section-kicker">Operator Kabupaten</p>
        <h1 class="mt-2 text-3xl font-extrabold">Rekonsiliasi Hasil Verifikasi</h1>
        <p class="mt-2 text-sm text-slate-600">Konsolidasi keputusan Dukcapil, Dinas Sosial, dan Dinas Pendidikan per jalur.</p>
    </div>
    <form method="GET" class="flex gap-2">
        <select name="application_type" class="form-input">
            <option value="">Semua jalur</option>
            @foreach($applicationTypes as $type)
                <option value="{{ $type->value }}" @selected(request('application_type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <button class="btn-secondary">Filter</button>
    </form>
</div>

<div class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['users', 'Data Dipantau', $summary['total']],
        ['check', 'Tiga Dinas Lengkap', $summary['complete']],
        ['shield', 'Siap Seleksi', $summary['ready']],
        ['warning', 'Perlu Tindak Lanjut', $summary['problem']],
    ] as [$icon, $label, $value])
        <div class="card flex items-center gap-4 p-5">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                <x-icon :name="$icon" class="h-6 w-6" />
            </div>
            <div>
                <p class="metric-number">{{ number_format($value) }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $label }}</p>
            </div>
        </div>
    @endforeach
</div>

<div class="table-shell mt-7">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mahasiswa</th>
                    <th>Jalur</th>
                    <th>Dukcapil</th>
                    <th>Dinas Sosial</th>
                    <th>Dinas Pendidikan</th>
                    <th>Status Sistem</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    @php
                        $agency = $application->agencyVerifications->keyBy('agency');
                    @endphp
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $application->mahasiswa->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $application->nomor_pengajuan }}</p>
                        </td>
                        <td><span class="status-chip status-info">{{ $application->application_type?->label() ?? '-' }}</span></td>
                        @foreach(['dukcapil', 'sosial', 'pendidikan'] as $code)
                            <td>
                                @if($agency->has($code))
                                    @php $decision = $agency[$code]->decision; @endphp
                                    <span class="status-chip {{ $decision->value === 'MS' ? 'status-success' : ($decision->value === 'BTL' ? 'status-warning' : 'status-danger') }}">
                                        {{ $decision->value }}
                                    </span>
                                    @if($agency[$code]->score !== null)
                                        <p class="mt-1 text-xs text-slate-500">Nilai {{ number_format((float) $agency[$code]->score, 1) }}</p>
                                    @endif
                                    @if(data_get($agency[$code]->metadata, 'desil'))
                                        <p class="mt-1 text-xs text-slate-500">Desil {{ data_get($agency[$code]->metadata, 'desil') }}</p>
                                    @endif
                                @else
                                    <span class="status-chip status-neutral">Menunggu</span>
                                @endif
                            </td>
                        @endforeach
                        <td><x-status-badge :status="$application->status" /></td>
                        <td>
                            <a href="{{ route('operator.applications.show', $application) }}" class="text-brand-600">
                                <x-icon name="eye" class="h-5 w-5" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-14 text-center text-slate-500">Belum ada data rekonsiliasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $applications->links() }}</div>
@endsection
