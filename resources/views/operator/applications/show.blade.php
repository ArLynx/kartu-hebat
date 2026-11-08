@extends('layouts.portal')

@section('title', 'Verifikasi Berkas')
@section('header', 'Verifikasi Berkas')

@section('content')
@php
    $isAgencyOperator = auth()->user()->role->isAgency();
    $backRoute = $isAgencyOperator && $application->hasGrantedSanggahan()
        ? route('operator.sanggahan-queue.index')
        : route('operator.applications.index');
    $backLabel = $isAgencyOperator && $application->hasGrantedSanggahan()
        ? 'Kembali ke antrean sanggahan'
        : 'Kembali ke antrean';
@endphp
<div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <a href="{{ $backRoute }}" class="text-sm font-semibold text-brand-600">← {{ $backLabel }}</a>
        <h1 class="mt-3 text-3xl font-extrabold">Verifikasi Dokumen Mahasiswa</h1>
        <p class="mt-2 text-sm text-slate-600">{{ $application->nomor_pengajuan }} · Jalur {{ $application->application_type?->label() ?? 'belum dipilih' }} @if($application->pendaftaran?->jalurBeasiswa) · {{ $application->pendaftaran->jalurBeasiswa->nama }} @endif</p>
        @if($application->hasGrantedSanggahan())
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="status-chip status-success">Hasil Sanggahan</span>
                @if(auth()->user()->hasRole('operator_kabupaten'))
                    <a href="{{ route('operator.sanggahan.show', $application->sanggahan) }}" class="text-sm font-semibold text-brand-600">
                        Lihat detail sanggahan →
                    </a>
                @endif
            </div>
        @endif
    </div>
    <x-status-badge :status="$application->status" />
</div>

@if ($isAgencyOperator && $application->hasGrantedSanggahan())
    <x-sanggahan.reopened-context
        :application="$application"
        :agency="auth()->user()->role->agencyCode()"
        class="mt-6"
    />
@endif

@php
    $documentStageVerifications = $application->documents->mapWithKeys(
        fn ($document) => [$document->id => $document->verifications->where('stage', $docVerificationStage)->first()]
    );

    $documentAssessmentStates = $documentStageVerifications
        ->map(fn ($verification) => $verification?->result?->value)
        ->all();

    $documentAssessmentNotes = $documentStageVerifications
        ->map(fn ($verification) => $verification?->notes ?? '')
        ->all();

    $documentTmsDrafts = $documentStageVerifications
        ->map(fn ($verification) => $verification?->result === \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI
            ? ($verification->notes ?? '')
            : '')
        ->all();

    $documentNames = $application->documents->pluck('type.name', 'id')->all();

    $documentVerifyUrls = $application->documents
        ->mapWithKeys(fn ($document) => [
            $document->id => route('operator.applications.documents.verify', [$application, $document]),
        ])
        ->all();

    $documentDestroyUrls = $application->documents
        ->mapWithKeys(fn ($document) => [
            $document->id => route('operator.applications.documents.verify.destroy', [$application, $document]),
        ])
        ->all();
@endphp

<div
    class="mt-7 grid gap-6 xl:grid-cols-[1fr_360px]"
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
            assessments: {{ Js::from($documentAssessmentStates, JSON_FORCE_OBJECT) }},
            notes: {{ Js::from($documentAssessmentNotes, JSON_FORCE_OBJECT) }},
            tmsDrafts: {{ Js::from($documentTmsDrafts, JSON_FORCE_OBJECT) }},
            documentNames: {{ Js::from($documentNames, JSON_FORCE_OBJECT) }},
            verifyUrls: {{ Js::from($documentVerifyUrls, JSON_FORCE_OBJECT) }},
            destroyUrls: {{ Js::from($documentDestroyUrls, JSON_FORCE_OBJECT) }},
            saving: {},
            errorMessage: '',
            get unverifiedDocuments() {
                return Object.keys(this.assessments)
                    .filter((documentId) => ! this.assessments[documentId] || this.assessments[documentId] === 'belum_dinilai')
                    .map((documentId) => this.documentNames[documentId]);
            },
            get hasUnverifiedDocuments() {
                return this.unverifiedDocuments.length > 0;
            },
            get hasRejectedDocuments() {
                return Object.values(this.assessments).includes('tidak_memenuhi');
            },
            get allDocumentsMeet() {
                const results = Object.values(this.assessments);

                return results.length > 0 && results.every((result) => result === 'memenuhi');
            },
            async assessDocument(documentId, result, notes = null) {
                const url = this.verifyUrls[documentId];

                if (! url) {
                    return false;
                }

                this.saving[documentId] = true;
                this.errorMessage = '';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ result, notes }),
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (! response.ok) {
                        this.errorMessage = payload.message ?? 'Penilaian dokumen gagal disimpan.';

                        return false;
                    }

                    this.assessments[documentId] = payload.result;
                    this.notes[documentId] = payload.notes ?? '';

                    if (payload.result === 'tidak_memenuhi') {
                        this.tmsDrafts[documentId] = payload.notes ?? '';
                    }

                    return true;
                } catch (error) {
                    this.errorMessage = 'Tidak dapat menyimpan penilaian. Periksa koneksi lalu coba lagi.';

                    return false;
                } finally {
                    this.saving[documentId] = false;
                }
            },
            async resetAssessment(documentId) {
                const url = this.destroyUrls[documentId];

                if (! url) {
                    return false;
                }

                this.saving[documentId] = true;
                this.errorMessage = '';

                try {
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                    });

                    if (! response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        this.errorMessage = payload.message ?? 'Penilaian dokumen gagal dibatalkan.';

                        return false;
                    }

                    this.assessments[documentId] = null;
                    this.notes[documentId] = '';
                    this.tmsDrafts[documentId] = '';

                    return true;
                } catch (error) {
                    this.errorMessage = 'Tidak dapat membatalkan penilaian. Periksa koneksi lalu coba lagi.';

                    return false;
                } finally {
                    this.saving[documentId] = false;
                }
            },
            handleDecisionSubmit(event) {
                const form = event.target.form ?? event.target;
                const decision = form.querySelector('input[name=decision]:checked');

                if (! decision) {
                    alert('Pilih keputusan terlebih dahulu.');

                    return false;
                }

                if (decision.value === 'MS' && this.hasUnverifiedDocuments) {
                    alert('Semua dokumen harus dinilai terlebih dahulu sebelum mengajukan keputusan Memenuhi Syarat (MS).');

                    return false;
                }

                if (decision.value === 'TMS' && this.hasUnverifiedDocuments) {
                    alert('Semua dokumen harus dinilai terlebih dahulu sebelum mengajukan keputusan Tidak Memenuhi Syarat (TMS).');

                    return false;
                }

                if (this.allDocumentsMeet && decision.value === 'TMS') {
                    alert('Keputusan TMS tidak dapat dipilih karena seluruh dokumen sudah dinilai Memenuhi Syarat (MS). Batalkan penilaian dokumen terlebih dahulu jika diperlukan.');

                    return false;
                }

                if (this.hasRejectedDocuments) {
                    return confirm('Masih ada dokumen yang ditandai TIDAK MEMENUHI SYARAT pada tahap ini. Tetap simpan keputusan ini?');
                }

                return confirm('Simpan keputusan verifikasi ini?');
            },
        }"
        @keydown.escape.window="closePreview()"
    >
        <div class="flex flex-col gap-6">

        {{-- WARNING KHUSUS OPERATOR BKPSDM: DETEKSI KESAMAAN KARTU KELUARGA (KK) --}}
        @if(auth()->user()->role === \App\Enums\UserRole::OPERATOR_BKPSDM && isset($siblingApplications) && $siblingApplications->isNotEmpty())
            <div class="rounded-2xl border-2 border-amber-300 bg-amber-50/95 p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-200 text-amber-900">
                        <x-icon name="exclamation-triangle" class="h-6 w-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded bg-amber-200 px-2.5 py-0.5 text-[11px] font-extrabold text-amber-950 uppercase tracking-wide">
                                ⚠️ Verifikasi BKPSDM — Deteksi Kesamaan KK
                            </span>
                            <span class="text-xs font-semibold text-amber-900">
                                No. KK: <span class="font-mono font-bold">{{ $application->pendaftaran?->dataPribadi?->nomor_kk ?? '-' }}</span>
                            </span>
                        </div>

                        <h3 class="mt-2 text-base font-bold text-amber-950">
                            Terdeteksi Pendaftar Lain dalam 1 Kartu Keluarga (KK) yang Sama
                        </h3>

                        <p class="mt-1 text-xs text-amber-900 leading-relaxed">
                            Sistem mendeteksi adanya pendaftar lain pada periode ini dengan Nomor Kartu Keluarga yang sama. 
                            Sesuai ketentuan, <strong>anak ASN Eselon III ke atas dibatasi maksimal 1 anak per KK</strong> (non-eselon/umum maksimal 2 anak).
                            Mohon periksa dan verifikasi kebenaran jabatan orang tua pemohon di database kepegawaian BKPSDM.
                        </p>

                        {{-- DAFTAR PENDAFTAR LAIN DALAM KK --}}
                        <div class="mt-3 divide-y divide-amber-200/60 rounded-xl border border-amber-200 bg-white/95 p-3.5">
                            <p class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                                Pendaftar Lain dalam KK Ini ({{ $siblingApplications->count() }} orang):
                            </p>
                            @foreach($siblingApplications as $sibling)
                                @php
                                    $sOrtu = $sibling->pendaftaran?->orangTua;
                                    $sAyahAsn = strtoupper($sOrtu?->pekerjaan_ayah ?? '') === 'ASN';
                                    $sIbuAsn = strtoupper($sOrtu?->pekerjaan_ibu ?? '') === 'ASN';
                                @endphp
                                <div class="flex flex-col gap-2 py-2.5 sm:flex-row sm:items-center sm:justify-between first:pt-1 last:pb-1">
                                    <div class="text-xs">
                                        <p class="font-bold text-slate-900">
                                            {{ $sibling->mahasiswa->name }}
                                            <span class="font-normal text-slate-500 font-mono">({{ $sibling->nomor_pengajuan }})</span>
                                        </p>
                                        <p class="text-slate-600 mt-0.5">
                                            Jalur: <span class="font-semibold">{{ $sibling->application_type?->label() ?? '-' }}</span>
                                            @if($sibling->pendaftaran?->jalurBeasiswa)
                                                · Kategori: <span class="font-semibold">{{ $sibling->pendaftaran->jalurBeasiswa->nama }}</span>
                                            @endif
                                        </p>
                                        <p class="mt-1 text-[11px] text-slate-700">
                                            Ayah: <span class="font-semibold {{ $sAyahAsn ? 'text-amber-800 font-bold' : '' }}">{{ $sOrtu?->pekerjaan_ayah ?? '-' }}{{ $sOrtu?->jabatan_ayah ? ' ('.$sOrtu->jabatan_ayah.')' : '' }}</span> · 
                                            Ibu: <span class="font-semibold {{ $sIbuAsn ? 'text-amber-800 font-bold' : '' }}">{{ $sOrtu?->pekerjaan_ibu ?? '-' }}{{ $sOrtu?->jabatan_ibu ? ' ('.$sOrtu->jabatan_ibu.')' : '' }}</span>
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <a href="{{ route('operator.applications.show', $sibling) }}" target="_blank" class="btn-secondary !py-1 !px-2.5 text-xs inline-flex items-center gap-1">
                                            <x-icon name="arrow-top-right-on-square" class="h-3.5 w-3.5" />
                                            Lihat Berkas Saudara
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- DATA ORANG TUA PENDAFTAR INI --}}
                        @php
                            $curOrtu = $application->pendaftaran?->orangTua;
                        @endphp
                        @if($curOrtu)
                            <div class="mt-2.5 rounded-lg bg-amber-100/70 p-2.5 text-[11px] text-amber-950">
                                <span class="font-bold">Data Orang Tua Pendaftar Ini:</span>
                                Ayah: <span class="font-semibold">{{ $curOrtu->pekerjaan_ayah ?? '-' }}{{ $curOrtu->jabatan_ayah ? ' ('.$curOrtu->jabatan_ayah.')' : '' }}</span> · 
                                Ibu: <span class="font-semibold">{{ $curOrtu->pekerjaan_ibu ?? '-' }}{{ $curOrtu->jabatan_ibu ? ' ('.$curOrtu->jabatan_ibu.')' : '' }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

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

            @php
                $orangTua = $application->pendaftaran?->orangTua;
                $pekerjaanAyah = $orangTua?->pekerjaan_ayah ?: '-';
                if ($orangTua?->jabatan_ayah) {
                    $pekerjaanAyah .= ' ('.$orangTua->jabatan_ayah.')';
                }
                $pekerjaanIbu = $orangTua?->pekerjaan_ibu ?: '-';
                if ($orangTua?->jabatan_ibu) {
                    $pekerjaanIbu .= ' ('.$orangTua->jabatan_ibu.')';
                }
                $pekerjaanWali = $orangTua?->pekerjaan_wali ?: '-';
                if ($orangTua?->jabatan_wali) {
                    $pekerjaanWali .= ' ('.$orangTua->jabatan_wali.')';
                }

                $detailItems = [
                    ['Jalur Pengajuan', $application->application_type?->label() ?? 'Belum dipilih'],
                    ['Kategori Mahasiswa', $application->pendaftaran?->jalurBeasiswa?->nama ?? '-'],
                    ['NIK', $application->mahasiswa->profile?->nik],
                    ['Nomor KK', $application->pendaftaran?->dataPribadi?->nomor_kk ?? '-'],
                    ['Program Studi', $application->mahasiswa->profile?->program_studi],
                    ['Semester / IPK', ($application->mahasiswa->profile?->semester ?? '-').' / '.($application->mahasiswa->profile?->ipk ?? '-')],
                    ['Nomor Telepon', $application->mahasiswa->profile?->phone],
                    ['Status Kependudukan', str($application->mahasiswa->profile?->status_kependudukan ?? 'belum_diverifikasi')->replace('_', ' ')->title()],
                    ['Desil Sosial', $application->mahasiswa->profile?->desil_sosial],
                    ['Nama Ayah', ($orangTua?->nama_ayah ?: '-').($orangTua?->status_ayah === 'meninggal_dunia' ? ' (Alm.)' : '')],
                    ['Pekerjaan & Jabatan Ayah', $pekerjaanAyah],
                    ['Nama Ibu', ($orangTua?->nama_ibu ?: '-').($orangTua?->status_ibu === 'meninggal_dunia' ? ' (Almh.)' : '')],
                    ['Pekerjaan & Jabatan Ibu', $pekerjaanIbu],
                ];

                if ($orangTua?->memiliki_wali) {
                    $detailItems[] = ['Nama Wali', $orangTua->nama_wali ?: '-'];
                    $detailItems[] = ['Pekerjaan & Jabatan Wali', $pekerjaanWali];
                }
            @endphp

            <dl class="mt-6 grid gap-x-8 gap-y-5 border-t border-slate-200 pt-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($detailItems as [$label, $value])
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

            @php
                $formulir = $application->pendaftaran?->formulirPendaftaran;
                $formulirFiles = [
                    ['label' => 'Surat Permohonan', 'jenis' => 'surat', 'path' => $formulir?->surat_permohonan],
                    ['label' => 'Pakta Integritas', 'jenis' => 'pakta', 'path' => $formulir?->pakta_integritas],
                ];
            @endphp

            <div class="mt-6 border-t border-slate-200 pt-6">
                <div class="flex flex-col gap-1">
                    <h3 class="text-sm font-bold text-slate-900">Formulir Unggahan Mahasiswa</h3>
                    <p class="text-xs text-slate-500">Surat Permohonan dan Pakta Integritas yang diunggah pada tahap Review.</p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach($formulirFiles as $file)
                        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900">{{ $file['label'] }}</p>
                                <p class="mt-0.5 text-xs {{ $file['path'] ? 'font-medium text-emerald-700' : 'text-slate-500' }}">
                                    {{ $file['path'] ? 'Sudah diunggah (PDF)' : 'Belum diunggah' }}
                                </p>
                            </div>

                            @if($file['path'])
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="btn-secondary !px-3 !py-2"
                                        @click="openPreview(
                                            {{ Js::from(route('operator.applications.formulir.lihat', [$application, $file['jenis']])) }},
                                            {{ Js::from(route('operator.applications.formulir.download', [$application, $file['jenis']])) }},
                                            {{ Js::from($file['label']) }},
                                            'application/pdf'
                                        )"
                                    >
                                        <x-icon name="eye" class="h-4 w-4" />
                                        Lihat
                                    </button>
                                    <a href="{{ route('operator.applications.formulir.download', [$application, $file['jenis']]) }}" class="btn-secondary !px-3 !py-2">
                                        <x-icon name="download" class="h-4 w-4" />
                                        Unduh
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

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
                        @endphp
                        <div x-data="{ showTmsNotes: false }">
                            <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-700">
                                    <x-icon name="document" class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-bold text-slate-900">{{ $document->type->name }}</p>
                                        <span
                                            @class([
                                                'inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700',
                                                'hidden' => ($documentAssessmentStates[$document->id] ?? null) !== 'memenuhi',
                                            ])
                                            x-bind:class="{ 'hidden': assessments[{{ $document->id }}] !== 'memenuhi' }"
                                        >
                                            <x-icon name="check" class="h-3 w-3" /> Memenuhi
                                        </span>
                                        <span
                                            @class([
                                                'inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700',
                                                'hidden' => ($documentAssessmentStates[$document->id] ?? null) !== 'tidak_memenuhi',
                                            ])
                                            x-bind:class="{ 'hidden': assessments[{{ $document->id }}] !== 'tidak_memenuhi' }"
                                        >
                                            <x-icon name="x" class="h-3 w-3" /> Tidak Memenuhi
                                        </span>
                                        <span
                                            @class([
                                                'inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500',
                                                'hidden' => ! in_array($documentAssessmentStates[$document->id] ?? null, [null, 'belum_dinilai'], true),
                                            ])
                                            x-bind:class="{ 'hidden': assessments[{{ $document->id }}] !== null && assessments[{{ $document->id }}] !== 'belum_dinilai' }"
                                        >
                                            Belum Dinilai
                                        </span>
                                    </div>
                                    <p class="mt-1 truncate text-xs text-slate-500">{{ $document->original_name }} · {{ $document->human_size }}</p>
                                    <p
                                        @class([
                                            'mt-2 text-xs italic leading-5 text-slate-500',
                                            'hidden' => blank($documentAssessmentNotes[$document->id] ?? null),
                                        ])
                                        x-bind:class="{ 'hidden': ! notes[{{ $document->id }}] }"
                                        x-text="'Catatan: ' + (notes[{{ $document->id }}] ?? '')"
                                    >{{ filled($documentAssessmentNotes[$document->id] ?? null) ? 'Catatan: '.$documentAssessmentNotes[$document->id] : '' }}</p>
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
                                        <div class="ml-1 flex flex-wrap items-center gap-1">
                                            <button
                                                type="button"
                                                @class([
                                                    'rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-semibold text-slate-500 transition hover:border-amber-300 hover:text-amber-700 disabled:opacity-50',
                                                    'hidden' => ($documentAssessmentStates[$document->id] ?? null) === null,
                                                ])
                                                x-bind:class="{ 'hidden': ! assessments[{{ $document->id }}] }"
                                                x-bind:disabled="saving[{{ $document->id }}]"
                                                @click="if (confirm('Batalkan penilaian dokumen ini?')) { resetAssessment({{ $document->id }}); }"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="button"
                                                x-bind:disabled="saving[{{ $document->id }}]"
                                                @click="assessDocument({{ $document->id }}, 'memenuhi', assessments[{{ $document->id }}] === 'memenuhi' ? (notes[{{ $document->id }}] || null) : null)"
                                                @class([
                                                    'rounded-lg border px-2 py-1.5 text-xs font-semibold transition disabled:opacity-50',
                                                    'border-emerald-600 bg-emerald-50 text-emerald-700' => ($documentAssessmentStates[$document->id] ?? null) === 'memenuhi',
                                                    'border-slate-200 text-slate-500 hover:border-emerald-300 hover:text-emerald-700' => ($documentAssessmentStates[$document->id] ?? null) !== 'memenuhi',
                                                ])
                                                x-bind:class="{
                                                    'border-emerald-600 bg-emerald-50 text-emerald-700': assessments[{{ $document->id }}] === 'memenuhi',
                                                    'border-slate-200 text-slate-500 hover:border-emerald-300 hover:text-emerald-700': assessments[{{ $document->id }}] !== 'memenuhi',
                                                }"
                                            >
                                                ✓ MS
                                            </button>
                                            <button
                                                type="button"
                                                x-bind:disabled="saving[{{ $document->id }}]"
                                                @click="showTmsNotes = !showTmsNotes"
                                                @class([
                                                    'rounded-lg border px-2 py-1.5 text-xs font-semibold transition disabled:opacity-50',
                                                    'border-red-600 bg-red-50 text-red-700' => ($documentAssessmentStates[$document->id] ?? null) === 'tidak_memenuhi',
                                                    'border-slate-200 text-slate-500 hover:border-red-300 hover:text-red-700' => ($documentAssessmentStates[$document->id] ?? null) !== 'tidak_memenuhi',
                                                ])
                                                x-bind:class="{
                                                    'border-red-600 bg-red-50 text-red-700': assessments[{{ $document->id }}] === 'tidak_memenuhi',
                                                    'border-slate-200 text-slate-500 hover:border-red-300 hover:text-red-700': assessments[{{ $document->id }}] !== 'tidak_memenuhi',
                                                }"
                                            >
                                                ✕ TMS
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if($canEditChecklist)
                                <div x-cloak x-show="showTmsNotes" class="mx-6 mb-5 rounded-xl border border-red-200 bg-red-50 p-4">
                                    <p class="text-xs font-bold text-red-800">Alasan Tidak Memenuhi Syarat</p>
                                    <p class="mt-1 text-[10px] text-red-600">Catatan wajib diisi.</p>
                                    <form
                                        class="mt-3"
                                        @submit.prevent="assessDocument({{ $document->id }}, 'tidak_memenuhi', tmsDrafts[{{ $document->id }}]).then((saved) => { if (saved) { showTmsNotes = false; } })"
                                    >
                                        <textarea name="notes" rows="3" class="form-input text-xs" placeholder="Contoh: KTP tidak terbaca / KK tidak sesuai…" required x-model="tmsDrafts[{{ $document->id }}]"></textarea>
                                        <div class="mt-3 flex items-center justify-end gap-2">
                                            <button type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-800" @click="showTmsNotes = false">Batal</button>
                                            <button type="submit" x-bind:disabled="saving[{{ $document->id }}]" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-700 disabled:opacity-50">Simpan TMS</button>
                                        </div>
                                    </form>
                                </div>
                            @endif
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
                                    <td class="py-3 pr-4 text-slate-600">{{ \App\Services\AgencyVerificationService::stageLabel($verification->stage) }}</td>
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

                <form
                    method="POST"
                    action="{{ route('operator.applications.verify', $application) }}"
                    class="mt-6 space-y-5"
                    @submit="if (! handleDecisionSubmit($event)) { $event.preventDefault(); }"
                >
                    @csrf
                    <div>
                        <label class="form-label">Keputusan</label>
                        <div class="space-y-3">
                            @foreach([
                                ['MS', 'Memenuhi Syarat', 'emerald'],
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

                    @if(auth()->user()->hasRole('operator_sosial') && $application->application_type === \App\Enums\ApplicationType::TIDAK_MAMPU)
                        <div>
                            <label class="form-label">Desil Verifikasi</label>
                            <input type="number" name="desil" min="1" max="10" value="{{ old('desil') }}" class="form-input" placeholder="1–10">
                            <p class="mt-1 text-xs leading-5 text-slate-500">Wajib pada keputusan MS. Desil ini menjadi dasar skor jalur Tidak Mampu.</p>
                        </div>
                    @endif

                    <div>
                        <label class="form-label">Catatan Petugas</label>
                        <textarea name="notes" rows="5" class="form-input" placeholder="Wajib diisi untuk keputusan TMS.">{{ old('notes') }}</textarea>
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

                    @if($canEditChecklist)
                        <div
                            x-cloak
                            x-show="hasUnverifiedDocuments"
                            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700"
                        >
                            <p class="font-semibold">Peringatan: Masih ada dokumen yang belum dinilai.</p>
                            <p class="mt-1 text-xs">Dokumen yang belum dinilai:
                                <span class="font-medium" x-text="unverifiedDocuments.join(', ')"></span>
                            </p>
                            <p class="mt-1 text-xs">Anda harus menilai semua dokumen terlebih dahulu sebelum bisa mengajukan keputusan Memenuhi Syarat (MS) atau Tidak Memenuhi Syarat (TMS).</p>
                        </div>

                        <div
                            x-cloak
                            x-show="errorMessage"
                            class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        >
                            <p x-text="errorMessage"></p>
                        </div>
                    @endif

                    <button
                        type="submit"
                        class="btn-primary w-full justify-center"
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
