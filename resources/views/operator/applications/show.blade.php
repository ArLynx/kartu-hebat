@extends('layouts.portal')

@section('title', 'Verifikasi Berkas')
@section('header', 'Verifikasi Berkas')

@section('content')
<div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <a href="{{ route('operator.applications.index') }}" class="text-sm font-semibold text-brand-600">← Kembali ke antrean</a>
        <h1 class="mt-3 text-3xl font-extrabold">Verifikasi Dokumen Mahasiswa</h1>
        <p class="mt-2 text-sm text-slate-600">{{ $application->nomor_pengajuan }} · Jalur {{ $application->application_type?->label() ?? 'belum dipilih' }}</p>
    </div>
    <x-status-badge :status="$application->status" />
</div>

<div class="mt-7 grid gap-6 xl:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        <section class="card p-6">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xl font-extrabold text-brand-700">
                    {{ mb_substr($application->mahasiswa->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="text-2xl font-bold">{{ $application->mahasiswa->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $application->mahasiswa->profile?->nim }} · {{ $application->mahasiswa->profile?->universitas }}</p>
                </div>
            </div>

            <dl class="mt-6 grid gap-x-8 gap-y-5 border-t border-slate-200 pt-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Jalur Pengajuan', $application->application_type?->label() ?? 'Belum dipilih'],
                    ['NIK', $application->mahasiswa->profile?->nik],
                    ['Program Studi', $application->mahasiswa->profile?->program_studi],
                    ['Semester / IPK', ($application->mahasiswa->profile?->semester ?? '-').' / '.($application->mahasiswa->profile?->ipk ?? '-')],
                    ['Nomor Telepon', $application->mahasiswa->profile?->phone],
                    ['Penghasilan Keluarga', $application->mahasiswa->profile?->penghasilan_keluarga ? 'Rp '.number_format($application->mahasiswa->profile->penghasilan_keluarga, 0, ',', '.') : '-'],
                    ['Tanggungan', $application->mahasiswa->profile?->jumlah_tanggungan],
                    ['Status Kependudukan', str($application->mahasiswa->profile?->status_kependudukan ?? 'belum_diverifikasi')->replace('_', ' ')->title()],
                    ['Desil Sosial', $application->mahasiswa->profile?->desil_sosial],
                    ['Desil Pendidikan', $application->mahasiswa->profile?->desil_pendidikan],
                ] as [$label, $value])
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $value ?: '-' }}</dd>
                    </div>
                @endforeach
                @if($application->application_type === \App\Enums\ApplicationType::DISABILITAS)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis Disabilitas</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $application->mahasiswa->profile?->disability_type ? str($application->mahasiswa->profile->disability_type)->replace('_', ' ')->title()->toString() : '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Grade Disabilitas</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $application->mahasiswa->profile?->disability_grade ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nomor Dokumen Disabilitas</dt>
                        <dd class="mt-1 text-sm font-medium text-slate-900">{{ $application->mahasiswa->profile?->disability_document_number ?: '-' }}</dd>
                    </div>
                @endif
                @if($application->application_type === \App\Enums\ApplicationType::NON_AKADEMIK)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Daftar Prestasi</dt>
                        <dd class="mt-2 space-y-2">
                            @forelse($application->pendaftaran?->prestasis ?? [] as $p)
                                <div class="rounded border border-slate-200 p-2 text-sm">
                                    <div class="font-semibold text-slate-900">{{ $p->nama_prestasi }} ({{ $p->tahun ?? '-' }})</div>
                                    <div class="text-xs text-slate-500">{{ $p->jenis }} · Tingkat {{ $p->tingkat }} · Peringkat {{ $p->peringkat }}</div>
                                    <div class="text-xs text-slate-500">Penyelenggara: {{ $p->penyelenggara ?? '-' }}</div>
                                </div>
                            @empty
                                <span class="text-sm text-slate-500">Belum ada data prestasi.</span>
                            @endforelse
                        </dd>
                    </div>
                @endif
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Alamat</dt>
                    <dd class="mt-1 text-sm leading-6 text-slate-900">{{ $application->mahasiswa->profile?->alamat }}, {{ $application->mahasiswa->profile?->village?->display_name }}, Kecamatan {{ $application->mahasiswa->profile?->village?->kecamatan?->name }}</dd>
                </div>
            </dl>
        </section>

        <div
            x-data="{
                previewOpen: false,
                previewUrl: '',
                previewDownloadUrl: '',
                previewName: '',
                previewMime: '',
                openPreview(url, downloadUrl, name, mime) {
                    this.previewUrl = url;
                    this.previewDownloadUrl = downloadUrl;
                    this.previewName = name;
                    this.previewMime = mime;
                    this.previewOpen = true;
                    document.body.classList.add('overflow-hidden');
                },
                closePreview() {
                    this.previewOpen = false;
                    this.previewUrl = '';
                    this.previewDownloadUrl = '';
                    this.previewName = '';
                    this.previewMime = '';
                    document.body.classList.remove('overflow-hidden');
                },
            }"
            @keydown.escape.window="closePreview()"
        >
            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-xl font-bold">Dokumen Persyaratan</h2>
                    <p class="mt-1 text-sm text-slate-500">PDF dan gambar dapat diperiksa langsung tanpa diunduh. Dokumen tetap dilindungi oleh otorisasi pengguna.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($application->documents as $document)
                        @php
                            $isPreviewable = $document->mime_type === 'application/pdf'
                                || in_array($document->mime_type, ['image/jpeg', 'image/png'], true);
                            $assessment = $document->verifications
                                ->where('stage', $docVerificationStage)
                                ->first();
                        @endphp
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-700">
                                <x-icon name="document" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-slate-900">{{ $document->type->name }}</p>
                                    @if($assessment)
                                        @if($assessment->result === \App\Enums\DocumentVerificationResult::MEMENUHI)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                                <x-icon name="check" class="h-3 w-3" /> Memenuhi
                                            </span>
                                        @elseif($assessment->result === \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700">
                                                <x-icon name="x" class="h-3 w-3" /> Tidak Memenuhi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                                                Belum Dinilai
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $document->original_name }} · {{ $document->human_size }}</p>
                                @if($assessment?->notes)
                                    <p class="mt-2 text-xs italic leading-5 text-slate-500">Catatan: {{ $assessment->notes }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($isPreviewable)
                                    <button
                                        type="button"
                                        class="btn-secondary !px-3 !py-2"
                                        @click="openPreview(
                                            {{ Js::from(route('documents.preview', $document)) }},
                                            {{ Js::from(route('documents.download', $document)) }},
                                            {{ Js::from($document->original_name) }},
                                            {{ Js::from($document->mime_type) }}
                                        )"
                                    >
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Lihat
                                    </button>
                                @endif
                                <a href="{{ route('documents.download', $document) }}" class="btn-secondary !px-3 !py-2">
                                    <x-icon name="download" class="h-4 w-4" />
                                    Unduh
                                </a>
                                @if($canEditChecklist)
                                    <div
                                        x-data="{
                                            showTmsNotes: {{ $assessment?->result === \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI ? 'true' : 'false' }}
                                        }"
                                        class="ml-1 flex items-center gap-1"
                                    >
                                        <form method="POST" action="{{ route('operator.applications.documents.verify', [$application, $document]) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="result" value="memenuhi">
                                            <button class="rounded-lg border px-2 py-1.5 text-xs font-semibold transition
                                                {{ $assessment?->result === \App\Enums\DocumentVerificationResult::MEMENUHI ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-500 hover:border-emerald-300 hover:text-emerald-700' }}">
                                                ✓ MS
                                            </button>
                                        </form>
                                        <button
                                            type="button"
                                            class="rounded-lg border px-2 py-1.5 text-xs font-semibold transition
                                                {{ $assessment?->result === \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI ? 'border-red-600 bg-red-50 text-red-700' : 'border-slate-200 text-slate-500 hover:border-red-300 hover:text-red-700' }}"
                                            @click="showTmsNotes = true"
                                        >
                                            ✕ TMS
                                        </button>
                                        <div
                                            x-cloak
                                            x-show="showTmsNotes"
                                            @click.outside="showTmsNotes = false"
                                            class="absolute right-4 top-9 z-20 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-xl"
                                        >
                                            <p class="text-xs font-bold text-slate-700">Alasan Tidak Memenuhi Syarat</p>
                                            <p class="mt-1 text-[10px] text-slate-500">Catatan wajib diisi untuk penilaian ini.</p>
                                            <form method="POST" action="{{ route('operator.applications.documents.verify', [$application, $document]) }}" class="mt-2">
                                                @csrf
                                                <input type="hidden" name="result" value="tidak_memenuhi">
                                                <textarea name="notes" rows="3" class="form-input text-xs" placeholder="Contoh: KTP tidak terbaca / KK tidak sesuai…" required>{{ $assessment?->result === \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI ? $assessment->notes : '' }}</textarea>
                                                <div class="mt-2 flex items-center justify-end gap-2">
                                                    <button type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-800" @click="showTmsNotes = false">Batal</button>
                                                    <button class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700">Simpan TMS</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center text-sm text-slate-500">Tidak ada dokumen terunggah.</div>
                    @endforelse
                </div>
            </section>

            <div
                x-cloak
                x-show="previewOpen"
                x-transition.opacity
                class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="document-preview-title"
            >
                <button
                    type="button"
                    class="absolute inset-0 bg-slate-950/75"
                    aria-label="Tutup pratinjau"
                    @click="closePreview()"
                ></button>

                <div
                    x-show="previewOpen"
                    x-transition
                    class="relative flex h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                    @click.stop
                >
                    <div class="flex items-center gap-4 border-b border-slate-200 px-4 py-3 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <h3 id="document-preview-title" class="truncate text-base font-bold text-slate-900" x-text="previewName"></h3>
                            <p class="mt-0.5 text-xs text-slate-500">Pratinjau dokumen mahasiswa</p>
                        </div>
                        <a :href="previewDownloadUrl" class="btn-secondary !px-3 !py-2">
                            <x-icon name="download" class="h-4 w-4" />
                            <span class="hidden sm:inline">Unduh</span>
                        </a>
                        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800" @click="closePreview()" aria-label="Tutup">
                            <x-icon name="x" class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 bg-slate-100">
                        <template x-if="previewMime === 'application/pdf'">
                            <iframe :src="previewUrl" class="h-full w-full" :title="'Pratinjau ' + previewName"></iframe>
                        </template>
                        <template x-if="previewMime.startsWith('image/')">
                            <div class="flex h-full items-start justify-center overflow-auto p-4 sm:p-6">
                                <img :src="previewUrl" :alt="previewName" class="max-h-full max-w-full rounded-lg bg-white object-contain shadow-sm">
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        @if($application->documentVerifications->isNotEmpty())
            <section class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-xl font-bold">Riwayat Penilaian Dokumen</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Putaran {{ $docVerificationRound }}</span>
                </div>
                <p class="mt-2 text-sm leading-6 text-slate-500">Ringkasan penilaian berkas dari seluruh tahap verifikasi.</p>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                                <th class="py-2 pr-4 font-semibold">Dokumen</th>
                                <th class="py-2 pr-4 font-semibold">Tahap</th>
                                <th class="py-2 pr-4 font-semibold">Hasil</th>
                                <th class="py-2 font-semibold">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($docVerifications as $verification)
                                <tr>
                                    <td class="py-3 pr-4 font-semibold text-slate-800">{{ $verification->document?->type?->name }}</td>
                                    <td class="py-3 pr-4 text-slate-600">{{ \App\Services\DocumentVerificationService::stageLabel($verification->stage) }}</td>
                                    <td class="py-3 pr-4">
                                        <span @class([
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold',
                                            'bg-emerald-50 text-emerald-700' => $verification->result === \App\Enums\DocumentVerificationResult::MEMENUHI,
                                            'bg-red-50 text-red-700' => $verification->result === \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI,
                                            'bg-slate-100 text-slate-500' => $verification->result === \App\Enums\DocumentVerificationResult::BELUM_DINILAI,
                                        ])>
                                            {{ $verification->result->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-600">{{ $verification->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="card p-6">
            <h2 class="text-xl font-bold">Riwayat Keputusan</h2>
            <div class="mt-5 space-y-4">
                @forelse($application->verificationLogs as $log)
                    <div class="flex gap-4 rounded-xl bg-slate-50 p-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-brand-700">
                            <x-icon name="history" class="h-4 w-4" />
                        </div>
                        <div>
                            <p class="text-sm font-bold">{{ $log->to_status->label() }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $log->actor?->name ?? 'Sistem' }} · {{ $log->created_at->translatedFormat('d M Y, H:i') }}</p>
                            @if($log->notes)<p class="mt-2 text-sm leading-6 text-slate-600">{{ $log->notes }}</p>@endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada keputusan.</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        @if($canVerify)
            <section class="card sticky top-24 p-6">
                <h2 class="text-xl font-bold">Keputusan Verifikasi</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Pilih keputusan setelah seluruh data dan dokumen jalur {{ $application->application_type?->label() ?? 'pengajuan' }} diperiksa.</p>

                <form method="POST" action="{{ route('operator.applications.verify', $application) }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label class="form-label">Keputusan</label>
                        <div class="space-y-3">
                            @foreach([
                                ['MS', 'Memenuhi Syarat', 'emerald'],
                                ['BTL', 'Butuh Perbaikan', 'amber'],
                                ['TMS', 'Tidak Memenuhi Syarat', 'red'],
                            ] as [$value, $label, $tone])
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:bg-slate-50">
                                    <input type="radio" name="decision" value="{{ $value }}" class="text-brand-600 focus:ring-brand-500" required>
                                    <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if(auth()->user()->role->isAgency())
                        <div>
                            <label class="form-label">Nilai Verifikasi Instansi (opsional)</label>
                            <input type="number" name="score" min="0" max="100" step="0.01" value="{{ old('score') }}" class="form-input" placeholder="0–100">
                            <p class="mt-1 text-xs leading-5 text-slate-500">Nilai ini hanya catatan instansi dan tidak masuk rumus seleksi otomatis.</p>
                        </div>
                    @endif

                    @if(auth()->user()->hasRole('operator_sosial', 'operator_pendidikan') && $application->application_type === \App\Enums\ApplicationType::TIDAK_MAMPU)
                        <div>
                            <label class="form-label">Desil Verifikasi</label>
                            <input type="number" name="desil" min="1" max="10" value="{{ old('desil') }}" class="form-input" placeholder="1–10">
                            <p class="mt-1 text-xs leading-5 text-slate-500">Wajib pada keputusan MS. Desil ini menjadi dasar skor jalur Tidak Mampu.</p>
                        </div>
                    @endif

                    <div>
                        <label class="form-label">Catatan Petugas</label>
                        <textarea name="notes" rows="5" class="form-input" placeholder="Wajib diisi untuk keputusan BTL atau TMS.">{{ old('notes') }}</textarea>
                    </div>

                    @if($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php
                        $hasRejectedDocuments = $application->documents->contains(
                            fn ($document) => $document->verifications
                                ->where('stage', $docVerificationStage)
                                ->where('result', \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI)
                                ->isNotEmpty()
                        );
                    @endphp

                    <button
                        class="btn-primary w-full justify-center"
                        onclick="return confirm('{{ $hasRejectedDocuments ? 'Masih ada dokumen yang ditandai TIDAK MEMENUHI SYARAT pada tahap ini. Tetap simpan keputusan ini?' : 'Simpan keputusan verifikasi ini?' }}')"
                    >
                        Simpan Keputusan
                    </button>
                </form>
            </section>
        @else
            <section class="card p-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <x-icon name="shield" class="h-6 w-6" />
                </div>
                <h2 class="mt-4 text-lg font-bold">Mode Tinjau</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Role atau status saat ini tidak mengizinkan keputusan verifikasi baru.</p>
            </section>
        @endif
    </aside>
</div>
@endsection
