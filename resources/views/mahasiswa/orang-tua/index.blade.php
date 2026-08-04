@extends('layouts.portal')

@section('title', 'Orang Tua Beasiswa')
@section('header', 'Orang Tua')

@section('content')
@php
    $orangTua = $pendaftaran->orangTua;
    $memilikiWali = (bool) old('memiliki_wali', $orangTua?->memiliki_wali);
@endphp

<div class="max-w-6xl">
    @include('mahasiswa.partials.flow-header', [
        'title' => 'Data Orang Tua / Wali',
        'description' => 'Isi identitas, pekerjaan, dan penghasilan bulanan orang tua atau wali.',
        'currentStep' => 4,
    ])

    @unless($canEdit)
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">Pendaftaran telah dikirim. Data orang tua/wali hanya dapat dilihat.</div>
    @endunless

    <form method="POST" action="{{ route('mahasiswa.orang-tua.update') }}" class="mt-7 space-y-6">
        @csrf
        @method('PUT')

        <fieldset @disabled(!$canEdit) class="space-y-6">
            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5 sm:px-8"><h2 class="text-xl font-bold">Data Ayah</h2></div>
                <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">
                    <div><label class="form-label">Nama Ayah</label><input class="form-input" name="nama_ayah" value="{{ old('nama_ayah', $orangTua?->nama_ayah) }}" required></div>
                    <div><label class="form-label">NIK Ayah <span class="font-normal text-slate-400">(opsional)</span></label><input class="form-input" name="nik_ayah" maxlength="16" inputmode="numeric" value="{{ old('nik_ayah', $orangTua?->nik_ayah) }}"></div>
                    <div><label class="form-label">Pekerjaan Ayah</label><input class="form-input" name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $orangTua?->pekerjaan_ayah) }}" required></div>
                    <div><label class="form-label">Penghasilan Ayah per Bulan</label><input class="form-input" type="number" name="penghasilan_ayah" min="0" step="1000" value="{{ old('penghasilan_ayah', $orangTua?->penghasilan_ayah) }}" required></div>
                </div>
            </section>

            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5 sm:px-8"><h2 class="text-xl font-bold">Data Ibu</h2></div>
                <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">
                    <div><label class="form-label">Nama Ibu</label><input class="form-input" name="nama_ibu" value="{{ old('nama_ibu', $orangTua?->nama_ibu) }}" required></div>
                    <div><label class="form-label">NIK Ibu <span class="font-normal text-slate-400">(opsional)</span></label><input class="form-input" name="nik_ibu" maxlength="16" inputmode="numeric" value="{{ old('nik_ibu', $orangTua?->nik_ibu) }}"></div>
                    <div><label class="form-label">Pekerjaan Ibu</label><input class="form-input" name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $orangTua?->pekerjaan_ibu) }}" required></div>
                    <div><label class="form-label">Penghasilan Ibu per Bulan</label><input class="form-input" type="number" name="penghasilan_ibu" min="0" step="1000" value="{{ old('penghasilan_ibu', $orangTua?->penghasilan_ibu) }}" required></div>
                </div>
            </section>

            <section class="card overflow-hidden">
                <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input id="memiliki-wali" type="checkbox" name="memiliki_wali" value="1" class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked($memilikiWali)>
                        <span><strong class="block text-lg text-navy-900">Menggunakan Wali</strong><span class="mt-1 block text-sm text-slate-500">Centang apabila tanggung jawab utama berada pada wali selain ayah/ibu.</span></span>
                    </label>
                </div>
                <div id="wali-fields" class="grid gap-5 p-6 sm:p-8 md:grid-cols-2 {{ $memilikiWali ? '' : 'hidden' }}">
                    <div><label class="form-label">Nama Wali</label><input class="form-input" data-wali-required name="nama_wali" value="{{ old('nama_wali', $orangTua?->nama_wali) }}"></div>
                    <div><label class="form-label">NIK Wali</label><input class="form-input" data-wali-required name="nik_wali" maxlength="16" inputmode="numeric" value="{{ old('nik_wali', $orangTua?->nik_wali) }}"></div>
                    <div><label class="form-label">Pekerjaan Wali</label><input class="form-input" data-wali-required name="pekerjaan_wali" value="{{ old('pekerjaan_wali', $orangTua?->pekerjaan_wali) }}"></div>
                    <div><label class="form-label">Penghasilan Wali per Bulan</label><input class="form-input" data-wali-required type="number" name="penghasilan_wali" min="0" step="1000" value="{{ old('penghasilan_wali', $orangTua?->penghasilan_wali) }}"></div>
                </div>
            </section>
        </fieldset>

        <div class="flex flex-wrap justify-between gap-3">
            <a href="{{ route('mahasiswa.prestasi.index') }}" class="btn-secondary">Kembali ke Prestasi</a>
            @if($canEdit)
                <button class="btn-primary" type="submit">Simpan & Lanjut ke Dokumen <x-icon name="arrow-right" class="h-4 w-4" /></button>
            @else
                <a href="{{ route('mahasiswa.dokumen.index') }}" class="btn-primary">Lanjut ke Dokumen</a>
            @endif
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkbox = document.getElementById('memiliki-wali');
    const fields = document.getElementById('wali-fields');
    if (!checkbox || !fields) return;

    const sync = () => {
        fields.classList.toggle('hidden', !checkbox.checked);
        fields.querySelectorAll('[data-wali-required]').forEach((input) => {
            input.required = checkbox.checked;
        });
    };

    checkbox.addEventListener('change', sync);
    sync();
});
</script>
@endpush
