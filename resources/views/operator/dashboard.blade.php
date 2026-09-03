@extends('layouts.portal')

@section('title', 'Dashboard Operator')
@section('header', 'Dashboard Operator')

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <p class="section-kicker">{{ auth()->user()->role->label() }}</p>
        <h1 class="mt-2 text-3xl font-extrabold">Ringkasan Verifikasi</h1>
        <p class="mt-2 text-sm text-slate-600">
            @if(auth()->user()->village)
                Wilayah {{ auth()->user()->village->display_name }}
            @elseif(auth()->user()->kecamatan)
                Kecamatan {{ auth()->user()->kecamatan->name }}
            @else
                Kabupaten {{ auth()->user()->kabupaten?->name }}
            @endif
        </p>
    </div>
    <a href="{{ route('operator.applications.index') }}" class="btn-primary">
        Buka Antrean
        <x-icon name="arrow-right" class="h-4 w-4" />
    </a>
</div>

<div class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['users', 'Total Pengajuan', $stats['total'], 'Seluruh pengajuan wilayah'],
        ['clock', 'Perlu Diproses', $stats['queue'], 'Antrean aktif saat ini'],
        ['warning', 'Belum Dikirim', $stats['revision'], 'Pengajuan yang masih draft'],
        ['check', 'Tahap Lanjut', $stats['completed'], 'Selesai atau siap seleksi'],
    ] as [$icon, $label, $value, $description])
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
                    <p class="metric-number mt-2">{{ number_format($value) }}</p>
                    <p class="mt-2 text-xs text-slate-400">{{ $description }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                    <x-icon :name="$icon" class="h-5 w-5" />
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-7 grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
    <section class="card p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">Pendaftar per Kecamatan</h2>
                <p class="mt-1 text-xs text-slate-500">Distribusi pengajuan pada cakupan wilayah operator.</p>
            </div>
            <x-icon name="chart" class="h-6 w-6 text-brand-600" />
        </div>

        <div class="mt-7 space-y-5">
            @php $maxDistrict = max(1, (int) $byDistrict->max('total')); @endphp
            @forelse($byDistrict as $district)
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">{{ $district->district_name }}</span>
                        <span class="font-bold text-navy-900">{{ number_format($district->total) }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-brand-600" style="width: {{ max(4, ($district->total / $maxDistrict) * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-sm text-slate-500">Belum ada data distribusi.</div>
            @endforelse
        </div>
    </section>

    <section class="card p-6">
        <h2 class="text-lg font-bold">Status Sistem Verifikasi</h2>
        <p class="mt-1 text-xs text-slate-500">Kontrol tahapan dan keamanan akun.</p>

        <div class="mt-6 space-y-4">
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 p-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <x-icon name="shield" class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-bold">Autentikasi Dua Faktor</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->two_factor_confirmed_at ? 'Aktif dan terkonfirmasi' : 'Belum diaktifkan' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 p-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                    <x-icon name="map" class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-bold">Pembatasan Wilayah</p>
                    <p class="text-xs text-slate-500">Policy wilayah diterapkan pada setiap pengajuan.</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 p-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                    <x-icon name="history" class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-bold">Audit Trail</p>
                    <p class="text-xs text-slate-500">Setiap perubahan utama dicatat oleh sistem.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<section class="table-shell mt-7">
    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
            <h2 class="text-lg font-bold">Aktivitas Pengajuan Terbaru</h2>
            <p class="mt-1 text-xs text-slate-500">Pengajuan terbaru dalam wilayah kewenangan.</p>
        </div>
        <a href="{{ route('operator.applications.index') }}" class="text-sm font-semibold text-brand-600">Lihat Semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Mahasiswa</th>
                    <th>Nomor Pengajuan</th>
                    <th>Jalur</th>
                    <th>Wilayah</th>
                    <th>Status</th>
                    <th>Diperbarui</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent as $application)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-900">{{ $application->mahasiswa->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $application->mahasiswa->profile?->nim }}</p>
                        </td>
                        <td class="font-medium">{{ $application->nomor_pengajuan }}</td>
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
                        <td>
                            <p>{{ $application->mahasiswa->profile?->village?->display_name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $application->mahasiswa->profile?->village?->kecamatan?->name }}</p>
                        </td>
                        <td><x-status-badge :status="$application->status" /></td>
                        <td>{{ $application->updated_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('operator.applications.show', $application) }}" class="text-brand-600 hover:text-brand-700">
                                <x-icon name="eye" class="h-5 w-5" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-12 text-center text-slate-500">Belum ada pengajuan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
