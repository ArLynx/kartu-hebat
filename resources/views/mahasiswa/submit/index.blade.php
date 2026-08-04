@extends('layouts.portal')

@section('title', 'Submit Pendaftaran Beasiswa')
@section('header', 'Submit')

@section('content')
<div class="max-w-6xl">
    @include('mahasiswa.partials.flow-header', [
        'title' => 'Submit Pendaftaran',
        'description' => 'Tahap terakhir. Setelah dikirim, seluruh data dan dokumen akan dikunci selama proses verifikasi.',
        'currentStep' => 7,
    ])

    @if($stepStatuses[7])
        <section class="card mt-7 p-7 sm:p-10">
            <div class="mx-auto max-w-2xl text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"><x-icon name="check" class="h-8 w-8" /></div>
                <h2 class="mt-5 text-2xl font-extrabold">Pendaftaran Sudah Dikirim</h2>
                <p class="mt-3 leading-7 text-slate-600">Pendaftaran {{ $pendaftaran->nomor_pendaftaran }} dikirim pada {{ $pendaftaran->submitted_at?->format('d/m/Y H:i') ?: 'waktu yang tidak tercatat' }} dan tidak dapat diubah.</p>
                <a href="{{ route('mahasiswa.dashboard') }}" class="btn-primary mt-6">Kembali ke Dashboard</a>
            </div>
        </section>
    @else
        @if($missingStages !== [])
            <div class="mt-7 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
                Pendaftaran belum dapat dikirim. Lengkapi: <strong>{{ implode(', ', $missingStages) }}</strong>.
            </div>
        @endif

        <section class="card mt-7 overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Ringkasan Final</p>
                <h2 class="mt-2 text-2xl font-extrabold">{{ $pendaftaran->nomor_pendaftaran }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $pendaftaran->periode?->nama }} · {{ $pendaftaran->kategoriBeasiswa?->nama }}</p>
            </div>

            <form method="POST" action="{{ route('mahasiswa.submit.store') }}" class="space-y-5 p-6 sm:p-8" onsubmit="return confirm('Kirim pendaftaran sekarang? Data akan dikunci setelah dikirim.')">
                @csrf
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <input type="checkbox" name="pernyataan_kebenaran" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" required>
                    <span class="text-sm leading-6 text-slate-700">Saya menyatakan seluruh data dan dokumen yang disampaikan benar, sah, dan dapat dipertanggungjawabkan.</span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <input type="checkbox" name="pernyataan_final" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" required>
                    <span class="text-sm leading-6 text-slate-700">Saya memahami bahwa pendaftaran akan dikunci setelah submit dan perubahan hanya dapat dilakukan apabila status dikembalikan menjadi revisi.</span>
                </label>

                <div class="rounded-xl bg-navy-900 p-5 text-white">
                    <div class="flex items-start gap-3">
                        <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0 text-amber-300" />
                        <p class="text-sm leading-6 text-slate-200">Periksa kembali halaman Review sebelum submit. Tindakan submit tidak dapat dibatalkan oleh mahasiswa.</p>
                    </div>
                </div>

                <div class="flex flex-wrap justify-between gap-3 pt-2">
                    <a href="{{ route('mahasiswa.review.index') }}" class="btn-secondary">Kembali ke Review</a>
                    <button class="btn-primary" type="submit" @disabled($missingStages !== [] || !$canEdit)>
                        Submit Pendaftaran <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>
            </form>
        </section>
    @endif
</div>
@endsection
