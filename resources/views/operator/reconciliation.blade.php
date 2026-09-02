@extends('layouts.portal')

@section('title', 'Rekonsiliasi Hasil Verifikasi')
@section('header', 'Rekonsiliasi Hasil Verifikasi')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="section-kicker">Operator Kabupaten</p>
        <h1 class="mt-2 text-3xl font-extrabold">Rekonsiliasi Hasil Verifikasi</h1>
        <p class="mt-2 text-sm text-slate-600">Konsolidasi keputusan lintas dinas per jalur. Dinas Kesehatan hanya terlibat pada jalur Disabilitas.</p>
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
        ['check', 'Dinas Lengkap', $summary['complete']],
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
                    @foreach($agencies as $code)
                        <th>{{ \App\Services\DocumentVerificationService::stageLabel($code) }}</th>
                    @endforeach
                    <th>Status Sistem</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    @php
                        $agency = $application->agencyVerifications->keyBy('agency');
                        $required = \App\Services\DocumentVerificationService::requiredAgencies($application);
                    @endphp
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $application->mahasiswa->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $application->nomor_pengajuan }}</p>
                        </td>
                        <td>
                            <div>
                                <span class="status-chip status-info">{{ $application->application_type?->label() ?? '-' }}</span>
                            </div>
                            @if($application->pendaftaran?->jalurBeasiswa)
                                <p class="mt-1">
                                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold {{ $application->pendaftaran->jalurBeasiswa->kode === 'REGULER' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                                        {{ $application->pendaftaran->jalurBeasiswa->nama }}
                                    </span>
                                </p>
                            @endif
                        </td>
                        @foreach($agencies as $code)
                            <td>
                                @if(! in_array($code, $required, true))
                                    <span class="status-chip status-neutral">Tidak berlaku</span>
                                @elseif($agency->has($code))
                                    @php $decision = $agency[$code]->decision; @endphp
                                    <span class="status-chip {{ $decision->value === 'MS' ? 'status-success' : 'status-danger' }}">
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
                    <tr><td colspan="{{ count($agencies) + 4 }}" class="py-14 text-center text-slate-500">Belum ada data rekonsiliasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $applications->links() }}</div>
@endsection
