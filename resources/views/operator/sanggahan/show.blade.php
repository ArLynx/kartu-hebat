@extends('layouts.portal')

@section('title', 'Detail Sanggahan')
@section('header', 'Detail Sanggahan')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('operator.sanggahan.index') }}" class="text-sm font-semibold text-brand-600">← Kembali ke antrean sanggahan</a>
            <h1 class="mt-3 text-2xl font-extrabold text-navy-950 sm:text-3xl">Detail Sanggahan</h1>
            <p class="mt-1.5 text-sm text-slate-600">
                {{ $application->nomor_pengajuan }} &middot; {{ $application->application_type?->label() ?? '-' }}
                @if ($application->pendaftaran?->jalurBeasiswa)
                    &middot; {{ $application->pendaftaran->jalurBeasiswa->nama }}
                @endif
            </p>
        </div>

        <div class="flex flex-col items-start gap-2 sm:items-end">
            <span class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Status Sanggahan</span>
            <x-status-badge :status="$sanggahan->status" />
            <x-status-badge :status="$application->status" />
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <section class="card p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xl font-extrabold text-brand-700">
                        {{ mb_substr($application->mahasiswa->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl font-bold text-slate-900">{{ $application->mahasiswa->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $application->mahasiswa->profile?->nim }} &middot; {{ $application->mahasiswa->profile?->universitas }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $application->mahasiswa->profile?->village?->display_name }},
                            Kecamatan {{ $application->mahasiswa->profile?->village?->kecamatan?->name }}
                        </p>
                    </div>
                </div>

                <dl class="mt-6 grid gap-x-8 gap-y-5 border-t border-slate-200 pt-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['NIK', $application->mahasiswa->profile?->nik],
                        ['Program Studi', $application->mahasiswa->profile?->program_studi],
                        ['Semester / IPK', ($application->mahasiswa->profile?->semester ?? '-').' / '.($application->mahasiswa->profile?->ipk ?? '-')],
                        ['Nomor Telepon', $application->mahasiswa->profile?->phone],
                        ['Periode', $sanggahan->periode?->nama ?? $application->periode],
                        ['Tanggal Sanggahan', $sanggahan->created_at?->format('d M Y H:i')],
                    ] as [$label, $value])
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">{{ $value ?: '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="card p-6">
                <h2 class="text-xl font-bold text-slate-900">Uraian Sanggahan Mahasiswa</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Diajukan oleh {{ $sanggahan->submittedBy?->name ?? $application->mahasiswa->name }} pada
                    {{ $sanggahan->created_at?->format('d M Y H:i') }}.
                </p>
                <div class="mt-4 whitespace-pre-line rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-700">
                    {{ $sanggahan->uraian }}
                </div>
            </section>

            <section class="card p-6">
                <h2 class="text-xl font-bold text-slate-900">Keputusan TMS per Dinas</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Dinas yang memutuskan TMS beserta catatan verifikatornya.
                </p>
                <x-sanggahan.tms-agencies :application="$application" class="mt-4" />
            </section>

            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-xl font-bold text-slate-900">Dokumen yang Dinilai Tidak Memenuhi</h2>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                            Putaran {{ $documentVerificationRound }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        Penilaian dokumen pada putaran verifikasi berjalan dari dinas yang menandai berkas tidak memenuhi syarat.
                    </p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($rejectedDocuments as $verification)
                        <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">
                                    {{ $verification->document?->type?->name ?? 'Dokumen' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $verification->document?->original_name }}
                                    &middot; {{ \App\Services\AgencyVerificationService::stageLabel($verification->stage) }}
                                </p>
                                <p class="mt-2 text-sm leading-6 text-red-700">
                                    {{ $verification->notes ?: 'Verifikator tidak menuliskan catatan.' }}
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <span class="status-chip status-danger">{{ $verification->result->label() }}</span>
                                @if ($verification->document)
                                    <a href="{{ route('documents.download', $verification->document) }}" class="btn-secondary !px-3 !py-2 text-xs">
                                        <x-icon name="download" class="h-4 w-4" />
                                        Unduh
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-slate-500">
                            Tidak ada dokumen yang dinilai tidak memenuhi syarat.
                        </p>
                    @endforelse
                </div>
            </section>

            <section class="card p-6">
                <h2 class="text-xl font-bold text-slate-900">Riwayat Verifikasi</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Jejak keputusan pada pengajuan ini, terbaru lebih dulu.
                </p>
                <div class="mt-5 space-y-4">
                    @forelse ($application->verificationLogs as $log)
                        <div class="flex gap-4 rounded-xl bg-slate-50 p-4">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-brand-700">
                                <x-icon name="history" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">{{ $log->action_label }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $log->actor?->name ?? 'Sistem' }}
                                    &middot; {{ $log->created_at?->translatedFormat('d M Y, H:i') }}
                                    @if ($log->to_status)
                                        &middot; {{ $log->to_status->label() }}
                                    @endif
                                </p>
                                @if ($log->notes)
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $log->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada riwayat verifikasi.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-6">
                <h2 class="text-lg font-bold text-slate-900">Ringkasan Keputusan</h2>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Status sanggahan</span>
                        <x-status-badge :status="$sanggahan->status" />
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Status pengajuan</span>
                        <x-status-badge :status="$application->status" />
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Diputuskan oleh</span>
                        <span class="font-semibold text-slate-800">{{ $sanggahan->decidedBy?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Waktu keputusan</span>
                        <span class="font-semibold text-slate-800">{{ $sanggahan->decided_at?->format('d M Y H:i') ?? '-' }}</span>
                    </div>
                </div>

                @if ($sanggahan->status->isDecided())
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Catatan Keputusan</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">
                            {{ $sanggahan->catatan_keputusan ?: 'Operator tidak menuliskan catatan.' }}
                        </p>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-slate-500">
                        Keputusan sanggahan bersifat final dan tidak dapat diubah.
                    </p>
                @else
                    <form method="POST" action="{{ route('operator.sanggahan.decide', $sanggahan) }}" class="mt-5 space-y-4">
                        @csrf

                        <div class="space-y-3">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50">
                                <input type="radio" name="decision" value="{{ \App\Enums\SanggahanStatus::DIKABULKAN->value }}" class="mt-1 text-emerald-600 focus:ring-emerald-500" required @checked(old('decision') === \App\Enums\SanggahanStatus::DIKABULKAN->value)>
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">Kabulkan Sanggahan</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                                        Keputusan TMS dinas terkait dianulir dan pengajuan dikembalikan untuk verifikasi ulang.
                                    </span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50">
                                <input type="radio" name="decision" value="{{ \App\Enums\SanggahanStatus::DITOLAK->value }}" class="mt-1 text-rose-600 focus:ring-rose-500" required @checked(old('decision') === \App\Enums\SanggahanStatus::DITOLAK->value)>
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">Tolak Sanggahan</span>
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                                        Keputusan TMS dipertahankan dan bersifat final.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div>
                            <label for="catatan_keputusan" class="form-label">Catatan Keputusan</label>
                            <textarea id="catatan_keputusan" name="catatan_keputusan" rows="5" class="form-input" required maxlength="2000" placeholder="Wajib diisi untuk keputusan kabul maupun tolak.">{{ old('catatan_keputusan') }}</textarea>
                            @error('catatan_keputusan')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @error('decision')
                            <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="btn-primary w-full justify-center" onclick="return confirm('Keputusan sanggahan bersifat final dan tidak dapat diubah. Lanjutkan?')">
                            Simpan Keputusan
                        </button>
                    </form>
                @endif
            </section>

            <section class="card p-6">
                <h2 class="text-lg font-bold text-slate-900">Akses Cepat</h2>
                <div class="mt-4 space-y-2">
                    <a href="{{ route('operator.applications.show', $application) }}" class="btn-secondary w-full justify-center">
                        <x-icon name="eye" class="h-4 w-4" />
                        Lihat Pengajuan
                    </a>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
