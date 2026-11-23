@props(['application', 'rejectedDocuments' => null])

@php
    $rejectedDocuments ??= collect();
@endphp

<div {{ $attributes->class(['space-y-4']) }}>
    @if (! $application->tmsDetailsRevealed())
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="flex items-start gap-3">
                <x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0 text-slate-500" />
                <div>
                    <p class="text-sm font-bold text-slate-800">Menunggu Pengumuman Hasil</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        {{ $application->tmsDetailsNotice() ?? 'Rincian hasil verifikasi akan diumumkan melalui Masa Sanggah periode ini.' }}
                    </p>
                </div>
            </div>
        </div>
    @else
        <x-sanggahan.tms-agencies :application="$application" />

        @if ($rejectedDocuments->isNotEmpty())
            <div class="rounded-xl border border-red-200 bg-white px-4 py-3">
                <p class="text-xs font-extrabold uppercase tracking-wider text-red-700">
                    Berkas yang dinilai Tidak Memenuhi Syarat
                </p>

                <ul class="mt-3 space-y-3">
                    @foreach ($rejectedDocuments as $verification)
                        <li class="rounded-lg border border-red-100 bg-red-50/60 px-3 py-2.5">
                            <p class="text-sm font-bold text-red-900">
                                {{ $verification->document?->type?->name ?? 'Dokumen' }}
                            </p>
                            <p class="mt-0.5 text-xs font-semibold text-red-700">
                                {{ \App\Services\AgencyVerificationService::stageLabel($verification->stage) }}
                                @if ($verification->document?->original_name)
                                    &middot; {{ $verification->document->original_name }}
                                @endif
                            </p>
                            <p class="mt-1.5 text-sm leading-6 text-red-800">
                                {{ $verification->notes ?: 'Verifikator tidak menuliskan catatan.' }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
