@props(['application', 'canSubmit' => false, 'reason' => null, 'showForm' => false])

@php($sanggahan = $application->sanggahan)

<section class="card overflow-hidden">
    <div class="border-b border-slate-200 px-6 py-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Pengajuan TMS</p>
                <h2 class="mt-1 text-lg font-extrabold text-slate-900">{{ $application->nomor_pengajuan }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $application->application_type?->label() }} &middot; {{ $application->periode }}
                </p>
            </div>

            @if ($sanggahan)
                <x-status-badge :status="$sanggahan->status" />
            @endif
        </div>
    </div>

    <div class="space-y-4 px-6 py-5">
        <div>
            <h3 class="text-sm font-bold text-slate-900">Dinas yang memutuskan TMS</h3>
            <x-sanggahan.tms-agencies :application="$application" class="mt-3" />
        </div>

        @if ($sanggahan)
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Sanggahan Anda</p>
                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $sanggahan->uraian }}</p>

                @if ($sanggahan->status->isDecided())
                    <p class="mt-3 text-xs font-semibold text-slate-500">
                        Keputusan:
                        <span class="font-bold text-slate-800">{{ $sanggahan->status->label() }}</span>
                    </p>
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
                        {{ $sanggahan->catatan_keputusan ?: 'Operator tidak menuliskan catatan.' }}
                    </p>
                @else
                    <p class="mt-3 text-xs font-semibold text-amber-700">
                        Sanggahan sudah dikirim dan menunggu pemeriksaan operator. Isi tidak dapat diubah atau ditarik.
                    </p>
                @endif
            </div>
        @endif
    </div>

    @if ($showForm)
        <form method="POST" action="{{ route('mahasiswa.sanggahan.store') }}" class="space-y-4 border-t border-slate-200 px-6 py-5">
            @csrf
            <input type="hidden" name="application_id" value="{{ $application->id }}">

            <div>
                <label for="uraian-{{ $application->id }}" class="form-label">Uraian Sanggahan</label>
                <textarea
                    id="uraian-{{ $application->id }}"
                    name="uraian"
                    rows="5"
                    minlength="40"
                    maxlength="2000"
                    class="form-input"
                    required
                    placeholder="Jelaskan bagian mana dari keputusan TMS yang menurut Anda keliru, minimal 40 karakter."
                >{{ old('application_id') == $application->id ? old('uraian') : '' }}</textarea>
                @error('uraian')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <input
                    type="checkbox"
                    name="pernyataan_kebenaran"
                    value="1"
                    class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    required
                >
                <span class="text-sm leading-6 text-slate-700">
                    Saya menyatakan bahwa uraian sanggahan ini benar dan dapat dipertanggungjawabkan.
                </span>
            </label>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Sanggahan hanya dapat diajukan satu kali dan tidak dapat diubah setelah dikirim.
                </p>
                <button class="btn-primary" type="submit">Kirim Sanggahan</button>
            </div>
        </form>
    @elseif (! $sanggahan && ! $canSubmit)
        <div class="border-t border-slate-200 px-6 py-4">
            <p class="text-sm text-slate-500">
                {{ $reason ?? 'Tombol pengajuan sanggahan aktif hanya ketika pengajuan berstatus TMS, periode aktif, dan masa sanggah sedang berlangsung.' }}
            </p>
        </div>
    @endif
</section>
