@props(['periode'])

@php
    $masaSanggahStatus = $periode?->masaSanggahStatus() ?? \App\Enums\MasaSanggahStatus::BELUM_DIJADWALKAN;
    $periodeIsActive = $periode?->status === 'aktif';
    $windowIsUsable = $periodeIsActive && $masaSanggahStatus === \App\Enums\MasaSanggahStatus::SEDANG_BERLANGSUNG;
@endphp

<section class="card p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Masa Sanggah</p>
            <h2 class="mt-2 text-lg font-extrabold text-slate-900">
                {{ $periode?->nama ?? 'Belum ada periode aktif' }}
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                @if ($periode?->tanggal_mulai_sanggah && $periode?->tanggal_selesai_sanggah)
                    {{ $periode->tanggal_mulai_sanggah->format('d M Y') }}
                    &mdash;
                    {{ $periode->tanggal_selesai_sanggah->format('d M Y') }}
                @else
                    Jadwal masa sanggah belum diatur oleh pengelola.
                @endif
            </p>
        </div>

        <div class="flex flex-col items-start gap-2 sm:items-end">
            <x-status-badge :status="$masaSanggahStatus" />
            <span class="status-chip {{ $periodeIsActive ? 'status-success' : 'status-neutral' }}">
                Periode {{ $periodeIsActive ? 'aktif' : 'tidak aktif' }}
            </span>
        </div>
    </div>

    @if ($masaSanggahStatus->isScheduled() && ! $windowIsUsable)
        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            @if (! $periodeIsActive)
                Masa sanggah hanya dapat digunakan selama periode beasiswa masih aktif.
            @else
                Sanggahan hanya dapat diajukan selama masa sanggah berlangsung.
            @endif
        </p>
    @endif
</section>
