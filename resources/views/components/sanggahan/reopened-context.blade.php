@props(['application', 'agency' => null])

@php
    $sanggahan = $application->sanggahan;
    $grantedLog = $application->verificationLogs->firstWhere('action', 'sanggahan_granted');
    $metadata = $grantedLog?->metadata ?? [];
    $annulled = collect($metadata['annulled_verifications'] ?? [])
        ->when($agency, fn ($items) => $items->where('agency', $agency));

    if ($annulled->isEmpty() && $agency && in_array($agency, $metadata['annulled_agencies'] ?? [], true)) {
        $annulled = collect([['agency' => $agency, 'notes' => null]]);
    }
@endphp

<div {{ $attributes->class(['rounded-2xl border border-amber-200 bg-amber-50 p-5']) }}>
    <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <x-icon name="warning" class="h-5 w-5" />
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-amber-900">Pengajuan dibuka kembali lewat sanggahan</p>
            <p class="mt-1 text-xs leading-5 text-amber-800">
                Sanggahan mahasiswa dikabulkan
                @if ($sanggahan?->decided_at)
                    pada {{ $sanggahan->decided_at->format('d M Y H:i') }}
                @endif
                @if ($sanggahan?->decidedBy)
                    oleh {{ $sanggahan->decidedBy->name }}
                @endif.
                Keputusan TMS dinas yang dianulir sudah dihapus, sehingga dinas perlu menilai ulang dokumen dan mengirim keputusan baru.
            </p>

            @if ($sanggahan?->uraian)
                <div class="mt-3 rounded-xl border border-amber-200 bg-white/70 px-4 py-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700">Uraian Sanggahan</p>
                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $sanggahan->uraian }}</p>
                </div>
            @endif

            @if ($sanggahan?->catatan_keputusan)
                <div class="mt-3 rounded-xl border border-amber-200 bg-white/70 px-4 py-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700">Catatan Keputusan Kabupaten</p>
                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $sanggahan->catatan_keputusan }}</p>
                </div>
            @endif

            @if ($annulled->isNotEmpty())
                <div class="mt-3 space-y-2">
                    @foreach ($annulled as $verification)
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider text-red-700">
                                TMS yang Dianulir &middot; {{ config('kartu_hebat.agencies.'.$verification['agency'], $verification['agency']) }}
                            </p>
                            <p class="mt-1 text-sm leading-6 text-red-800">
                                {{ $verification['notes'] ?: 'Verifikator tidak menuliskan catatan.' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
