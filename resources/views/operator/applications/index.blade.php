@extends('layouts.portal')

@section('title', 'Antrean Pengajuan')
@section('header', 'Antrean Pengajuan')

@section('content')
<div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-3xl font-extrabold">Daftar Pengajuan</h1>
        <p class="mt-2 text-sm text-slate-600">Data dibatasi berdasarkan role, wilayah, dan jalur pengajuan.</p>
    </div>

    <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[260px_190px_220px_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-3 h-5 w-5 text-slate-400" />
            <input name="search" value="{{ request('search') }}" class="form-input !pl-10" placeholder="Nama, NIK, NIM, nomor...">
        </div>
        <select name="application_type" class="form-input">
            <option value="">Semua jalur</option>
            @foreach($applicationTypes as $type)
                <option value="{{ $type->value }}" @selected(request('application_type') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <select name="status" class="form-input">
            <option value="">Antrean aktif</option>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button class="btn-primary justify-center">Filter</button>
    </form>
</div>

<div class="table-shell mt-7">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mahasiswa</th>
                    <th>Jalur</th>
                    <th>Data Seleksi</th>
                    <th>Wilayah</th>
                    <th>Status</th>
                    <th>Tanggal Kirim</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $application->mahasiswa->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $application->nomor_pengajuan }}</p>
                        </td>
                        <td>
                            <span class="status-chip {{ $application->application_type === \App\Enums\ApplicationType::AKADEMIK ? 'status-info' : 'status-warning' }}">
                                {{ $application->application_type?->label() ?? 'Belum dipilih' }}
                            </span>
                        </td>
                        <td>
                            @if($application->application_type === \App\Enums\ApplicationType::AKADEMIK)
                                <p>IPK {{ $application->mahasiswa->profile?->ipk ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Semester {{ $application->mahasiswa->profile?->semester ?? '-' }}</p>
                            @else
                                <p>Desil S/P: {{ $application->mahasiswa->profile?->desil_sosial ?? '-' }} / {{ $application->mahasiswa->profile?->desil_pendidikan ?? '-' }}</p>
                                <p class="mt-1 max-w-48 truncate text-xs text-slate-500">{{ $application->mahasiswa->profile?->universitas }}</p>
                            @endif
                        </td>
                        <td>
                            <p>{{ $application->mahasiswa->profile?->village?->display_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">Kec. {{ $application->mahasiswa->profile?->village?->kecamatan?->name }}</p>
                        </td>
                        <td><x-status-badge :status="$application->status" /></td>
                        <td>{{ $application->submitted_at?->translatedFormat('d M Y') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('operator.applications.show', $application) }}" class="btn-secondary !px-3 !py-2">
                                <x-icon name="eye" class="h-4 w-4" />
                                Periksa
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-14 text-center text-slate-500">Tidak ada pengajuan pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $applications->links() }}</div>
@endsection
