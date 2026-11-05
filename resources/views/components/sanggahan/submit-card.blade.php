@props(['application', 'canSubmit' => false, 'reason' => null])

@php($sanggahan = $application->sanggahan)

<section class="mt-7 rounded-2xl border border-red-200 bg-red-50/60 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-800">
                <x-icon name="warning" class="h-3.5 w-3.5" />
                Tidak Memenuhi Syarat (TMS)
            </span>
            <h2 class="mt-3 text-lg font-extrabold text-red-900">Hasil Verifikasi: TMS</h2>
            <p class="mt-1 text-sm text-red-800">
                Pengajuan <strong>{{ $application->nomor_pengajuan }}</strong> dinyatakan TMS oleh dinas berikut.
            </p>
        </div>

        @if ($sanggahan)
            <x-status-badge :status="$sanggahan->status" />
        @endif
    </div>

    <x-sanggahan.tms-agencies :application="$application" class="mt-4" />

    @if ($sanggahan)
        <div class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Sanggahan Anda</p>
            <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $sanggahan->uraian }}</p>
            <p class="mt-2 text-xs font-semibold text-slate-500">
                Status:
                <span class="font-bold text-slate-800">{{ $sanggahan->status->label() }}</span>
            </p>
        </div>
    @endif

    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-red-800">
            @if ($sanggahan)
                Sanggahan sudah dikirim dan tidak dapat diubah atau ditarik.
            @elseif ($canSubmit)
                Anda dapat mengajukan sanggahan selama masa sanggah periode ini berlangsung.
            @else
                {{ $reason ?? 'Pengajuan sanggahan belum dapat dilakukan.' }}
            @endif
        </p>

        @if ($sanggahan)
            <a href="{{ route('mahasiswa.sanggahan.index') }}" class="btn-secondary shrink-0">
                Lihat Sanggahan
            </a>
        @elseif ($canSubmit)
            <a href="{{ route('mahasiswa.sanggahan.index') }}" class="btn-primary shrink-0">
                Ajukan Sanggahan
            </a>
        @else
            <span class="btn-secondary shrink-0 cursor-not-allowed opacity-60" aria-disabled="true">
                Ajukan Sanggahan
            </span>
        @endif
    </div>
</section>
