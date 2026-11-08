@props(['periode', 'periodeLabel' => 'Belum ada periode aktif'])

@php
    use App\Enums\MasaSanggahStatus;

    $masaSanggahStatus = $periode?->masaSanggahStatus() ?? MasaSanggahStatus::BELUM_DIJADWALKAN;

    $statusNoticeClass = match ($masaSanggahStatus) {
        MasaSanggahStatus::BELUM_DIMULAI => 'border-blue-100 bg-blue-50/60 text-blue-800',
        MasaSanggahStatus::SEDANG_BERLANGSUNG => 'border-emerald-100 bg-emerald-50/60 text-emerald-800',
        default => 'border-slate-200 bg-slate-50/80 text-slate-600',
    };
@endphp

<section data-reveal class="w-full">

    <div class="relative overflow-hidden rounded-[1.75rem] border border-amber-200/80 bg-white shadow-[0_20px_55px_rgba(15,23,42,0.07)]">

        {{-- Top Accent --}}
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 via-orange-400 to-amber-300"></div>

        {{-- Ambient Decoration --}}
        <div aria-hidden="true"
            class="pointer-events-none absolute -right-16 -top-16 h-52 w-52 rounded-full bg-amber-100/60 blur-[75px]">
        </div>

        <div class="relative p-4 sm:p-5 lg:p-5">

            {{-- Header --}}
            <div class="flex items-start gap-3">

                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-600">

                    <x-icon name="file-warning" class="h-5 w-5" />

                </div>

                <div class="min-w-0 flex-1">

                    <div class="flex flex-wrap items-center gap-2">

                        <span
                            class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-[9px] font-extrabold uppercase tracking-[0.13em] text-amber-700">
                            Pemberitahuan Penting
                        </span>

                        <span class="text-[10px] font-semibold text-slate-400">
                            Masa Sanggah Seleksi
                        </span>

                    </div>

                    <h2 class="mt-2 text-base font-extrabold leading-snug tracking-tight text-slate-900 sm:text-lg">
                        Kesempatan Mengajukan Sanggahan
                    </h2>

                    <p class="mt-1 text-[10px] font-semibold text-slate-500">
                        {{ $periode?->nama ?? $periodeLabel }}
                    </p>

                    <x-status-badge :status="$masaSanggahStatus" class="mt-2" />

                </div>

            </div>

            {{-- Description --}}
            <p class="mt-3 text-[11px] leading-5 text-slate-500 sm:text-xs">
                Bagi pendaftar yang dinyatakan tidak diterima, tersedia kesempatan mengajukan
                sanggahan apabila terdapat ketidaksesuaian atau kekeliruan dalam pemeriksaan
                berkas persyaratan.
            </p>

            {{-- Schedule Information --}}
            <div class="mt-4">

                <p class="mb-2.5 text-[9px] font-extrabold uppercase tracking-[0.16em] text-slate-400">
                    Jadwal Pelaksanaan
                </p>

                @if ($masaSanggahStatus->isScheduled())
                    <div class="grid grid-cols-2 gap-2.5">

                        {{-- Start Date --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3">

                            <div class="flex items-center gap-1.5 text-slate-400">

                                <x-icon name="calendar-days" class="h-3.5 w-3.5" />

                                <span class="text-[9px] font-extrabold uppercase tracking-wider">
                                    Mulai Sanggah
                                </span>

                            </div>

                            <p class="mt-2 text-xs font-extrabold text-slate-800 sm:text-sm">
                                {{ $periode->tanggal_mulai_sanggah->format('d M Y') }}
                            </p>

                        </div>

                        {{-- End Date --}}
                        <div class="rounded-xl border border-red-100 bg-red-50/50 p-3">

                            <div class="flex items-center gap-1.5 text-red-500">

                                <x-icon name="calendar-clock" class="h-3.5 w-3.5" />

                                <span class="text-[9px] font-extrabold uppercase tracking-wider">
                                    Batas Akhir
                                </span>

                            </div>

                            <p class="mt-2 text-xs font-extrabold text-slate-800 sm:text-sm">
                                {{ $periode->tanggal_selesai_sanggah->format('d M Y') }}
                            </p>

                        </div>

                    </div>
                @else
                    <p
                        class="rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-4 text-xs font-semibold text-slate-500">
                        Jadwal masa sanggah belum diatur oleh pengelola.
                    </p>
                @endif

            </div>

            {{-- Status Notice --}}
            @if ($masaSanggahStatus->notice())
                <p class="mt-3 rounded-xl border px-3 py-2.5 text-[10px] leading-5 {{ $statusNoticeClass }}">
                    {{ $masaSanggahStatus->notice() }}
                </p>
            @endif

            {{-- Rules --}}
            <div class="mt-3 rounded-xl border border-amber-200/80 bg-amber-50/60 p-3">

                <div class="flex items-center gap-2">

                    <x-icon name="alert-triangle" class="h-4 w-4 shrink-0 text-amber-600" />

                    <h3 class="text-[11px] font-extrabold text-amber-900">
                        Ketentuan dan Perhatian
                    </h3>

                </div>

                <ul class="mt-2.5 grid gap-x-4 gap-y-2 text-[10px] leading-[1.55] text-amber-950/80 sm:grid-cols-2">

                    <li class="flex items-start gap-2">

                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-600"></span>

                        <span>
                            Sanggahan hanya diperuntukkan bagi pendaftar yang dinyatakan tidak diterima.
                        </span>

                    </li>

                    <li class="flex items-start gap-2">

                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-600"></span>

                        <span>
                            Sanggahan digunakan untuk mengklarifikasi ketidaksesuaian atau kekeliruan
                            berkas persyaratan.
                        </span>

                    </li>

                    <li class="flex items-start gap-2">

                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-600"></span>

                        <span>
                            Sanggahan hanya untuk mengoreksi dugaan kekeliruan verifikasi berkas, bukan untuk
                            memperbaiki atau mengunggah ulang dokumen.
                        </span>

                    </li>

                    <li class="flex items-start gap-2">

                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-600"></span>

                        <span>
                            Pengajuan hanya dapat dilakukan selama periode sanggah berlangsung.
                        </span>

                    </li>

                </ul>

            </div>

            {{-- Action Area --}}
            <div
                class="mt-3 flex flex-col gap-2.5 rounded-xl border border-slate-200 bg-slate-50/80 p-3 sm:flex-row sm:items-center sm:justify-between">

                <div class="min-w-0">

                    <p class="text-[11px] font-extrabold text-slate-800">
                        Siap mengajukan sanggahan?
                    </p>

                    <p class="mt-1 text-[9px] leading-4 text-slate-500">
                        Baca seluruh ketentuan sebelum melanjutkan.
                    </p>

                </div>

                <a href="{{ route('login') }}"
                    class="inline-flex min-h-[36px] shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-700 px-3.5 text-[9px] font-extrabold text-white transition hover:bg-blue-800">

                    <x-icon name="user" class="h-3.5 w-3.5" />

                    Ajukan Sanggahan

                </a>

            </div>

            {{-- Footer Notice --}}
            <div class="mt-2 flex items-start gap-2">

                <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-500" />

                <p class="text-[9px] leading-4 text-slate-400">
                    Pengajuan sanggahan wajib dilakukan melalui portal dengan akun mahasiswa yang
                    bersangkutan.
                </p>

            </div>

        </div>

    </div>

</section>
