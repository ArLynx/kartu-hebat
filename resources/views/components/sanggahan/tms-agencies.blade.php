@props(['application'])

@php
    $tmsVerifications = $application->agencyVerifications->filter(
        fn ($verification) => $verification->decision === \App\Enums\VerificationDecision::TMS,
    );
@endphp

<div {{ $attributes->class(['space-y-3']) }}>
    @forelse ($tmsVerifications as $verification)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-bold text-red-800">
                {{ config('kartu_hebat.agencies.'.$verification->agency, $verification->agency) }}
            </p>
            <p class="mt-1 text-sm leading-6 text-red-700">
                {{ $verification->notes ?: 'Verifikator tidak menuliskan catatan.' }}
            </p>
        </div>
    @empty
        <p class="text-sm text-red-800">Catatan dinas yang memutuskan TMS tidak ditemukan.</p>
    @endforelse
</div>
