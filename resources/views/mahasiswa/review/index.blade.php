@extends('layouts.portal')

@section('title', 'Review Pendaftaran Beasiswa')
@section('header', 'Review')

@section('content')
@php
    $data = $pendaftaran->dataPribadi;
    $pendidikan = $pendaftaran->pendidikan;
    $orangTua = $pendaftaran->orangTua;
@endphp

<div class="max-w-6xl">
    @include('mahasiswa.partials.flow-header', [
        'title' => 'Review Pendaftaran',
        'description' => 'Periksa kembali seluruh data sebelum masuk ke tahap submit. Gunakan tombol Perbaiki pada bagian yang belum sesuai.',
        'currentStep' => 6,
    ])

    @if($missingStages !== [])
        <div class="mt-7 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            <p class="font-bold">Pendaftaran belum siap disubmit.</p>
            <p class="mt-1">Tahap yang belum lengkap: {{ implode(', ', $missingStages) }}.</p>
        </div>
    @else
        <div class="mt-7 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-800">
            Seluruh tahap wajib telah lengkap. Lanjutkan ke tahap submit setelah memastikan data benar.
        </div>
    @endif

    <div class="mt-7 space-y-6">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">
                <div><h2 class="text-xl font-bold">Data Pribadi</h2><p class="mt-1 text-sm text-slate-500">Identitas dan alamat mahasiswa.</p></div>
                <a href="{{ route('mahasiswa.data-pribadi.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Perbaiki</a>
            </div>
            <dl class="grid gap-x-8 gap-y-5 p-6 text-sm sm:p-8 md:grid-cols-2">
                <div><dt class="text-slate-500">Nama Lengkap</dt><dd class="mt-1 font-semibold">{{ $data?->nama_lengkap ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">NIK / NISN</dt><dd class="mt-1 font-semibold">{{ $data?->nik ?: '-' }} / {{ $data?->nisn ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Tempat, Tanggal Lahir</dt><dd class="mt-1 font-semibold">{{ $data?->tempat_lahir ?: '-' }}, {{ $data?->tanggal_lahir?->format('d/m/Y') ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Jenis Kelamin / Agama</dt><dd class="mt-1 font-semibold">{{ $data?->jenis_kelamin === 'L' ? 'Laki-laki' : ($data?->jenis_kelamin === 'P' ? 'Perempuan' : '-') }} / {{ $data?->agama ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Nomor HP</dt><dd class="mt-1 font-semibold">{{ $data?->no_hp ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="mt-1 font-semibold">{{ auth()->user()->email }}</dd></div>
                <div class="md:col-span-2"><dt class="text-slate-500">Alamat</dt><dd class="mt-1 font-semibold leading-6">{{ $data?->alamat ?: '-' }}, {{ $data?->desa ?: '-' }}, {{ $data?->kecamatan ?: '-' }}, {{ $data?->kabupaten ?: '-' }}, {{ $data?->provinsi ?: '-' }} {{ $data?->kode_pos }}</dd></div>
            </dl>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">
                <div><h2 class="text-xl font-bold">Pendidikan</h2><p class="mt-1 text-sm text-slate-500">Data perguruan tinggi dan akademik.</p></div>
                <a href="{{ route('mahasiswa.pendidikan.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Perbaiki</a>
            </div>
            <dl class="grid gap-x-8 gap-y-5 p-6 text-sm sm:p-8 md:grid-cols-2">
                <div><dt class="text-slate-500">NIM</dt><dd class="mt-1 font-semibold">{{ $pendidikan?->nim ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Perguruan Tinggi</dt><dd class="mt-1 font-semibold">{{ $pendidikan?->universitas ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Fakultas / Program Studi</dt><dd class="mt-1 font-semibold">{{ $pendidikan?->fakultas ?: '-' }} / {{ $pendidikan?->program_studi ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Jenjang / Semester</dt><dd class="mt-1 font-semibold">{{ $pendidikan?->jenjang ?: '-' }} / {{ $pendidikan?->semester ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">IPK</dt><dd class="mt-1 font-semibold">{{ $pendidikan?->ipk ?? '-' }}</dd></div>
                <div><dt class="text-slate-500">Status</dt><dd class="mt-1 font-semibold">{{ $pendidikan?->status_mahasiswa ? ucfirst(str_replace('_', ' ', $pendidikan->status_mahasiswa)) : '-' }}</dd></div>
                <div><dt class="text-slate-500">Tahun Masuk</dt><dd class="mt-1 font-semibold">{{ $pendidikan?->tahun_masuk ?: '-' }}</dd></div>
                <div><dt class="text-slate-500">Tahun Lulus</dt><dd class="mt-1 font-semibold">{{ $pendidikan?->tahun_lulus ?: '-' }}</dd></div>
            </dl>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">
                <div><h2 class="text-xl font-bold">Prestasi</h2><p class="mt-1 text-sm text-slate-500">{{ $pendaftaran->prestasis->count() }} prestasi tercatat.</p></div>
                <a href="{{ route('mahasiswa.prestasi.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Perbaiki</a>
            </div>
            <div class="p-6 sm:p-8">
                @forelse($pendaftaran->prestasis as $prestasi)
                    <div class="border-b border-slate-100 py-4 first:pt-0 last:border-0 last:pb-0">
                        <p class="font-semibold">{{ $prestasi->nama_prestasi }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ ucfirst(str_replace('_', ' ', $prestasi->jenis)) }} · {{ ucfirst($prestasi->tingkat) }} · {{ $prestasi->peringkat }} · {{ $prestasi->tahun }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada prestasi yang dicantumkan.</p>
                @endforelse
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">
                <div><h2 class="text-xl font-bold">Orang Tua / Wali</h2><p class="mt-1 text-sm text-slate-500">Identitas dan penghasilan keluarga.</p></div>
                <a href="{{ route('mahasiswa.orang-tua.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Perbaiki</a>
            </div>
            <dl class="grid gap-x-8 gap-y-5 p-6 text-sm sm:p-8 md:grid-cols-2">
                <div><dt class="text-slate-500">Ayah</dt><dd class="mt-1 font-semibold">{{ $orangTua?->nama_ayah ?: '-' }} · {{ $orangTua?->pekerjaan_ayah ?: '-' }}</dd><dd class="mt-1 text-slate-600">Rp {{ number_format((float) ($orangTua?->penghasilan_ayah ?? 0), 0, ',', '.') }}/bulan</dd></div>
                <div><dt class="text-slate-500">Ibu</dt><dd class="mt-1 font-semibold">{{ $orangTua?->nama_ibu ?: '-' }} · {{ $orangTua?->pekerjaan_ibu ?: '-' }}</dd><dd class="mt-1 text-slate-600">Rp {{ number_format((float) ($orangTua?->penghasilan_ibu ?? 0), 0, ',', '.') }}/bulan</dd></div>
                @if($orangTua?->memiliki_wali)
                    <div class="md:col-span-2"><dt class="text-slate-500">Wali</dt><dd class="mt-1 font-semibold">{{ $orangTua->nama_wali }} · {{ $orangTua->pekerjaan_wali }}</dd><dd class="mt-1 text-slate-600">Rp {{ number_format((float) $orangTua->penghasilan_wali, 0, ',', '.') }}/bulan</dd></div>
                @endif
            </dl>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">
                <div><h2 class="text-xl font-bold">Dokumen</h2><p class="mt-1 text-sm text-slate-500">Dokumen persyaratan kategori.</p></div>
                <a href="{{ route('mahasiswa.dokumen.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Perbaiki</a>
            </div>
            <div class="divide-y divide-slate-100 px-6 sm:px-8">
                @forelse($requiredTypes as $type)
                    @php
                        $document = $pendaftaran->dokumens->firstWhere('jenis_dokumen_id', $type->id);
                        $assessment = $document
                            ? $pendaftaran->application?->documents
                                ->firstWhere('document_type_id', $document->jenisDokumen->id)
                                ?->verifications
                                ->last()
                            : null;
                    @endphp
                    <div class="flex items-center justify-between gap-4 py-4">
                        <div>
                            <p class="text-sm font-semibold">{{ $type->nama }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $document?->nama_file_asli ?: 'Belum diunggah' }}</p>
                            @if($assessment && $assessment->result !== \App\Enums\DocumentVerificationResult::BELUM_DINILAI)
                                <p class="mt-1 text-xs font-semibold {{ $assessment->result === \App\Enums\DocumentVerificationResult::TIDAK_MEMENUHI ? 'text-red-600' : 'text-emerald-600' }}">
                                    {{ $assessment->result->label() }}
                                    @if($assessment->notes)
                                        — {{ $assessment->notes }}
                                    @endif
                                </p>
                            @endif
                        </div>
                        @if($document)
                            <a href="{{ route('mahasiswa.dokumen.download', $document) }}" class="text-sm font-semibold text-brand-700 hover:underline">Unduh</a>
                        @else
                            <span class="status-chip status-warning">Belum lengkap</span>
                        @endif
                    </div>
                @empty
                    <p class="py-6 text-sm text-amber-700">Master persyaratan dokumen belum tersedia.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 flex flex-wrap justify-between gap-3">
        <a href="{{ route('mahasiswa.dokumen.index') }}" class="btn-secondary">Kembali ke Dokumen</a>
        @if($canEdit)
            <form method="POST" action="{{ route('mahasiswa.review.confirm') }}">
                @csrf
                <button class="btn-primary" type="submit" @disabled($missingStages !== [])>
                    Konfirmasi Review & Lanjut ke Submit <x-icon name="arrow-right" class="h-4 w-4" />
                </button>
            </form>
        @else
            <a href="{{ route('mahasiswa.submit.index') }}" class="btn-primary">Lihat Status Submit</a>
        @endif
    </div>
</div>
@endsection
