@extends('layouts.portal')

@section('title', 'Dokumen Beasiswa')
@section('header', 'Dokumen')

@section('content')
<div class="max-w-6xl">
    @include('mahasiswa.partials.flow-header', [
        'title' => 'Dokumen Persyaratan',
        'description' => 'Unggah seluruh dokumen yang diwajibkan untuk kategori '.$pendaftaran->kategoriBeasiswa?->nama.'.',
        'currentStep' => 5,
    ])

    @unless($canEdit)
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Pendaftaran telah dikirim. Dokumen hanya dapat dilihat atau diunduh.</div>
    @endunless

    @if($requiredTypes->isEmpty())
        <div class="mt-7 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
            Persyaratan dokumen untuk kategori ini belum dikonfigurasi. Hubungi administrator; pendaftaran tidak dapat disubmit sebelum master dokumen tersedia.
        </div>
    @else
        <div class="mt-7 grid gap-5 lg:grid-cols-2">
            @foreach($requiredTypes as $type)
                @php
                    $document = $pendaftaran->dokumens->firstWhere('jenis_dokumen_id', $type->id);
                    $formats = strtoupper(str_replace(',', ', ', $type->format_file));
                @endphp
                <section class="rounded-xl border {{ $document ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white' }} p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $document ? 'bg-emerald-100 text-emerald-700' : 'bg-brand-100 text-brand-700' }}">
                            <x-icon :name="$document ? 'check' : 'document'" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold">{{ $type->nama }}</h2>
                                <span class="rounded bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700">WAJIB</span>
                            </div>
                            @if($type->deskripsi)<p class="mt-1 text-sm leading-6 text-slate-500">{{ $type->deskripsi }}</p>@endif
                            <p class="mt-2 text-xs text-slate-500">Format {{ $formats }} · Maksimal {{ number_format($type->maksimal_ukuran / 1024, 1, ',', '.') }} MB</p>

                            @if($document)
                                <div class="mt-4 rounded-lg border border-emerald-200 bg-white p-3 text-sm">
                                    <p class="truncate font-semibold text-slate-700">{{ $document->nama_file_asli ?: basename($document->file_path) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $document->ukuran_file ? number_format($document->ukuran_file / 1024, 1, ',', '.').' KB' : 'Ukuran tidak tersedia' }}
                                        · Status {{ ucfirst($document->status) }}
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-3">
                                        @if(in_array($document->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true))
                                            <a href="{{ route('mahasiswa.dokumen.preview', $document) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-brand-700 hover:underline">Lihat</a>
                                        @endif
                                        <a href="{{ route('mahasiswa.dokumen.download', $document) }}" class="text-xs font-semibold text-brand-700 hover:underline">Unduh</a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($canEdit)
                        <div class="mt-4 border-t border-slate-200 pt-4">
                            <form method="POST" action="{{ route('mahasiswa.dokumen.store') }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row">
                                @csrf
                                <input type="hidden" name="jenis_dokumen_id" value="{{ $type->id }}">
                                <input type="file" name="file" class="block min-w-0 flex-1 text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-navy-900" accept="{{ collect(preg_split('/[\s,|;\/]+/', $type->format_file))->filter()->map(fn($format) => '.'.strtolower($format))->join(',') }}" required>
                                <button class="btn-secondary !px-3 !py-2">{{ $document ? 'Ganti' : 'Unggah' }}</button>
                            </form>
                            @if($document)
                                <form method="POST" action="{{ route('mahasiswa.dokumen.destroy', $document) }}" class="mt-2 text-right" onsubmit="return confirm('Hapus dokumen ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-semibold text-red-600 hover:underline">Hapus dokumen</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endif

    <div class="mt-6 flex flex-wrap justify-between gap-3">
        <a href="{{ route('mahasiswa.orang-tua.index') }}" class="btn-secondary">Kembali ke Orang Tua</a>
        <a href="{{ route('mahasiswa.review.index') }}" class="btn-primary">Lanjut ke Review <x-icon name="arrow-right" class="h-4 w-4" /></a>
    </div>
</div>
@endsection
