@extends('layouts.portal')

@section('title', 'Sanggahan')
@section('header', 'Sanggahan')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="section-kicker">Kartu Hebat Mahasiswa</p>
            <h1 class="mt-1 text-2xl font-extrabold text-navy-950 sm:text-3xl">Sanggahan</h1>
            <p class="mt-1 text-sm text-slate-600">
                Ajukan keberatan atas keputusan Tidak Memenuhi Syarat (TMS) selama masa sanggah berlangsung.
            </p>
        </div>

        <x-sanggahan.schedule :periode="$periode" />

        @forelse ($tmsApplications as $application)
            @php
                $eligibility = $eligibilities[$application->id];
            @endphp

            <x-sanggahan.tms-card
                :application="$application"
                :can-submit="$eligibility['can_submit']"
                :reason="$eligibility['reason']"
                :show-form="$eligibility['can_submit']"
            />
        @empty
            <section class="card p-7 text-center">
                <p class="text-sm text-slate-600">
                    Tidak ada pengajuan berstatus TMS pada akun Anda. Sanggahan hanya tersedia untuk pengajuan TMS.
                </p>
            </section>
        @endforelse

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-extrabold text-slate-900">Riwayat Sanggahan</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Seluruh sanggahan yang pernah Anda ajukan, termasuk dari periode sebelumnya.
                </p>
            </div>

            @if ($sanggahans->isEmpty())
                <p class="px-6 py-8 text-center text-sm text-slate-500">Belum ada sanggahan yang diajukan.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nomor Pengajuan</th>
                                <th>Periode</th>
                                <th>Uraian</th>
                                <th>Status</th>
                                <th>Catatan Keputusan</th>
                                <th>Tanggal Pengajuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sanggahans as $sanggahan)
                                <tr>
                                    <td class="font-semibold text-slate-900">
                                        {{ $sanggahan->application?->nomor_pengajuan ?? '-' }}
                                    </td>
                                    <td>{{ $sanggahan->periode?->nama ?? '-' }}</td>
                                    <td class="max-w-xs whitespace-pre-line">{{ $sanggahan->uraian }}</td>
                                    <td>
                                        <x-status-badge :status="$sanggahan->status" />
                                    </td>
                                    <td>{{ $sanggahan->catatan_keputusan ?? '-' }}</td>
                                    <td>{{ $sanggahan->created_at?->format('d M Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
