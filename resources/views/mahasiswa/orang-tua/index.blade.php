@extends('layouts.portal')

@section('title', 'Orang Tua Beasiswa')
@section('header', 'Orang Tua')

@section('content')

    @php
        $orangTua = $pendaftaran->orangTua;
        $statusAyahVal = old('status_ayah', $orangTua?->status_ayah ?? 'hidup');
        $statusIbuVal = old('status_ibu', $orangTua?->status_ibu ?? 'hidup');
        $bothParentsDeceased = $statusAyahVal === 'meninggal_dunia' && $statusIbuVal === 'meninggal_dunia';
        $memilikiWali = $bothParentsDeceased && (bool) old('memiliki_wali', $orangTua?->memiliki_wali);

        $standardJobs = ['Wirausaha', 'ASN', 'BUMN / BUMD', 'TNI / POLRI'];

        $resolveJobState = function (?string $savedJob, string $jobField, string $otherField) use ($standardJobs): array {
            $oldJob = old($jobField);
            if ($oldJob !== null) {
                if (in_array($oldJob, $standardJobs, true) || $oldJob === 'Lainnya' || $oldJob === '') {
                    return [
                        'selected' => $oldJob,
                        'lainnya' => (string) old($otherField, ''),
                    ];
                }

                return [
                    'selected' => 'Lainnya',
                    'lainnya' => (string) $oldJob,
                ];
            }

            $saved = trim((string) $savedJob);
            if ($saved === '') {
                return ['selected' => '', 'lainnya' => ''];
            }

            if (in_array($saved, $standardJobs, true)) {
                return ['selected' => $saved, 'lainnya' => ''];
            }

            return ['selected' => 'Lainnya', 'lainnya' => $saved];
        };

        $ayahJob = $resolveJobState($orangTua?->pekerjaan_ayah, 'pekerjaan_ayah', 'pekerjaan_ayah_lainnya');
        $ibuJob = $resolveJobState($orangTua?->pekerjaan_ibu, 'pekerjaan_ibu', 'pekerjaan_ibu_lainnya');
        $waliJob = $resolveJobState($orangTua?->pekerjaan_wali, 'pekerjaan_wali', 'pekerjaan_wali_lainnya');
    @endphp

    <div class="max-w-6xl">

        @include('mahasiswa.partials.flow-header', [
            'title' => 'Data Orang Tua / Wali',
            'description' => 'Lengkapi data orang tua atau wali sesuai dengan kondisi sebenarnya.',
            'currentStep' => 4,
        ])

        @unless($canEdit)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Pendaftaran telah dikirim. Data orang tua/wali hanya dapat dilihat dan tidak dapat diubah.
            </div>
        @endunless

        <form
            method="POST"
            action="{{ route('mahasiswa.orang-tua.update') }}"
            enctype="multipart/form-data"
            class="mt-7 space-y-6"
        >
            @csrf
            @method('PUT')

            <fieldset @disabled(!$canEdit) class="space-y-6">

                {{-- ========================================================= --}}
                {{-- DATA AYAH --}}
                {{-- ========================================================= --}}

                <section class="card overflow-hidden">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                        <h2 class="text-xl font-bold">
                            Data Ayah
                        </h2>
                    </div>

                    <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">

                        {{-- NAMA AYAH --}}
                        <div>
                            <label class="form-label">
                                Nama Ayah
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_ayah"
                                maxlength="150"
                                value="{{ old('nama_ayah', $orangTua?->nama_ayah) }}"
                                placeholder="Masukkan nama ayah"
                                required
                            >
                        </div>

                        {{-- STATUS AYAH --}}
                        <div>
                            <label class="form-label" for="status_ayah">
                                Status Ayah
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="status_ayah"
                                class="form-input"
                                name="status_ayah"
                                required
                            >
                                <option value="hidup"
                                    @selected(old('status_ayah', $orangTua?->status_ayah ?? 'hidup') === 'hidup')>
                                    Hidup
                                </option>

                                <option value="meninggal_dunia"
                                    @selected(old('status_ayah', $orangTua?->status_ayah) === 'meninggal_dunia')>
                                    Meninggal Dunia
                                </option>
                            </select>
                        </div>

                        {{-- NIK AYAH --}}
                        <div>
                            <label class="form-label" for="nik_ayah">
                                NIK Ayah
                                <span id="nik_ayah_required" class="text-red-500">*</span>
                                <span id="nik_ayah_optional" class="ml-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 hidden">Opsional — Boleh dikosongkan</span>
                            </label>

                            <input
                                id="nik_ayah"
                                class="form-input"
                                type="text"
                                name="nik_ayah"
                                maxlength="16"
                                inputmode="numeric"
                                value="{{ old('nik_ayah', $orangTua?->nik_ayah) }}"
                                placeholder="Masukkan 16 digit NIK ayah"
                            >
                            <p id="nik_ayah_deceased_hint" class="mt-1.5 hidden text-xs text-slate-500">
                                Karena status Ayah meninggal dunia, kolom NIK ini bersifat opsional dan <strong>boleh dikosongkan</strong>.
                            </p>
                        </div>

                        {{-- PEKERJAAN AYAH --}}
                        <div>
                            <label class="form-label" for="pekerjaan_ayah">
                                Pekerjaan Ayah
                                <span id="pekerjaan_ayah_required" class="text-red-500">*</span>
                                <span id="pekerjaan_ayah_optional" class="ml-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 hidden">Opsional — Boleh dikosongkan</span>
                            </label>

                            <select
                                id="pekerjaan_ayah"
                                class="form-input"
                                name="pekerjaan_ayah"
                            >
                                <option id="pekerjaan_ayah_placeholder" value="">Pilih pekerjaan ayah</option>
                                @foreach ($standardJobs as $jobOption)
                                    <option value="{{ $jobOption }}" @selected($ayahJob['selected'] === $jobOption)>
                                        {{ $jobOption }}
                                    </option>
                                @endforeach
                                <option value="Lainnya" @selected($ayahJob['selected'] === 'Lainnya')>
                                    Lainnya
                                </option>
                            </select>
                            <p id="pekerjaan_ayah_deceased_hint" class="mt-1.5 hidden text-xs text-slate-500">
                                Karena status Ayah meninggal dunia, pilihan pekerjaan ini bersifat opsional dan <strong>boleh tidak dipilih (dikosongkan)</strong>.
                            </p>
                        </div>

                        {{-- JABATAN & SK JABATAN AYAH (MUNCUL JIKA PILIH ASN) --}}
                        <div id="jabatan_ayah_wrapper" class="{{ $ayahJob['selected'] === 'ASN' ? '' : 'hidden' }} md:col-span-2 space-y-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                            <div>
                                <label class="form-label" for="jabatan_ayah">
                                    Jabatan ASN Ayah
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="jabatan_ayah"
                                    class="form-input bg-white"
                                    type="text"
                                    name="jabatan_ayah"
                                    maxlength="150"
                                    value="{{ old('jabatan_ayah', $orangTua?->jabatan_ayah) }}"
                                    placeholder="Contoh: Kepala Dinas / Kepala Bidang / Sekretaris Dinas / Guru / Staf Pelaksana / Jabatan Fungsional"
                                >

                                <p class="mt-1.5 text-xs leading-5 text-slate-500">
                                    Tuliskan jabatan lengkap sesuai SK kepegawaian terakhir.
                                </p>
                                @error('jabatan_ayah')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div data-file-field>
                                @if($orangTua?->sk_jabatan_ayah)
                                    <label class="form-label" for="sk_jabatan_ayah">
                                        Ganti SK Jabatan Terakhir Ayah
                                        <span class="font-normal text-slate-400">(opsional · PDF, JPG, PNG maks 2 MB)</span>
                                    </label>
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 px-3.5 py-2.5 text-xs">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-emerald-900">{{ basename($orangTua->sk_jabatan_ayah) }}</p>
                                            <p class="mt-0.5 text-emerald-700">SK Jabatan Terakhir Ayah sudah diunggah</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-3">
                                            <a href="{{ route('mahasiswa.orang-tua.sk.preview', 'ayah') }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 hover:underline">Lihat SK</a>
                                            <a href="{{ route('mahasiswa.orang-tua.sk.download', 'ayah') }}" class="font-semibold text-brand-700 hover:underline">Unduh SK</a>
                                            @if($canEdit)
                                                <button
                                                    type="button"
                                                    onclick="if (confirm('Hapus SK Jabatan Terakhir Ayah yang sudah diunggah?')) { const f = document.getElementById('delete-sk-form'); f.action = '{{ route('mahasiswa.orang-tua.sk.destroy', 'ayah') }}'; f.submit(); }"
                                                    class="font-semibold text-red-600 hover:underline"
                                                >
                                                    Hapus dokumen
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <label class="form-label" for="sk_jabatan_ayah">
                                        Unggah SK Jabatan Terakhir Ayah
                                        <span class="text-red-500">*</span>
                                        <span class="font-normal text-slate-400">(wajib · PDF, JPG, PNG maks 2 MB)</span>
                                    </label>
                                @endif

                                <input
                                    id="sk_jabatan_ayah"
                                    class="form-input bg-white"
                                    type="file"
                                    name="sk_jabatan_ayah"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    data-max-kb="2048"
                                    data-has-existing="{{ filled($orangTua?->sk_jabatan_ayah) ? '1' : '0' }}"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Unggah salinan/scan SK Jabatan terakhir Ayah sebagai bukti kepegawaian ASN untuk diverifikasi.
                                </p>
                                <p data-file-error class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"></p>
                                @error('sk_jabatan_ayah')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- PEKERJAAN AYAH LAINNYA (MUNCUL JIKA PILIH LAINNYA) --}}
                        <div id="pekerjaan_ayah_lainnya_wrapper" class="{{ $ayahJob['selected'] === 'Lainnya' ? '' : 'hidden' }} md:col-span-2">
                            <label class="form-label" for="pekerjaan_ayah_lainnya">
                                Sebutkan Pekerjaan Ayah
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="pekerjaan_ayah_lainnya"
                                class="form-input"
                                type="text"
                                name="pekerjaan_ayah_lainnya"
                                maxlength="150"
                                value="{{ $ayahJob['lainnya'] }}"
                                placeholder="Contoh: Petani / Buruh / Karyawan Swasta / Pensiunan"
                            >
                        </div>

                    </div>

                </section>

                {{-- ========================================================= --}}
                {{-- DATA IBU --}}
                {{-- ========================================================= --}}

                <section class="card overflow-hidden">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                        <h2 class="text-xl font-bold">
                            Data Ibu
                        </h2>
                    </div>

                    <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">

                        {{-- NAMA IBU --}}
                        <div>
                            <label class="form-label">
                                Nama Ibu
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_ibu"
                                maxlength="150"
                                value="{{ old('nama_ibu', $orangTua?->nama_ibu) }}"
                                placeholder="Masukkan nama ibu"
                                required
                            >
                        </div>

                        {{-- STATUS IBU --}}
                        <div>
                            <label class="form-label" for="status_ibu">
                                Status Ibu
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="status_ibu"
                                class="form-input"
                                name="status_ibu"
                                required
                            >
                                <option value="hidup"
                                    @selected(old('status_ibu', $orangTua?->status_ibu ?? 'hidup') === 'hidup')>
                                    Hidup
                                </option>

                                <option value="meninggal_dunia"
                                    @selected(old('status_ibu', $orangTua?->status_ibu) === 'meninggal_dunia')>
                                    Meninggal Dunia
                                </option>
                            </select>
                        </div>

                        {{-- NIK IBU --}}
                        <div>
                            <label class="form-label" for="nik_ibu">
                                NIK Ibu
                                <span id="nik_ibu_required" class="text-red-500">*</span>
                                <span id="nik_ibu_optional" class="ml-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 hidden">Opsional — Boleh dikosongkan</span>
                            </label>

                            <input
                                id="nik_ibu"
                                class="form-input"
                                type="text"
                                name="nik_ibu"
                                maxlength="16"
                                inputmode="numeric"
                                value="{{ old('nik_ibu', $orangTua?->nik_ibu) }}"
                                placeholder="Masukkan 16 digit NIK ibu"
                            >
                            <p id="nik_ibu_deceased_hint" class="mt-1.5 hidden text-xs text-slate-500">
                                Karena status Ibu meninggal dunia, kolom NIK ini bersifat opsional dan <strong>boleh dikosongkan</strong>.
                            </p>
                        </div>

                        {{-- PEKERJAAN IBU --}}
                        <div>
                            <label class="form-label" for="pekerjaan_ibu">
                                Pekerjaan Ibu
                                <span id="pekerjaan_ibu_required" class="text-red-500">*</span>
                                <span id="pekerjaan_ibu_optional" class="ml-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 hidden">Opsional — Boleh dikosongkan</span>
                            </label>

                            <select
                                id="pekerjaan_ibu"
                                class="form-input"
                                name="pekerjaan_ibu"
                            >
                                <option id="pekerjaan_ibu_placeholder" value="">Pilih pekerjaan ibu</option>
                                @foreach ($standardJobs as $jobOption)
                                    <option value="{{ $jobOption }}" @selected($ibuJob['selected'] === $jobOption)>
                                        {{ $jobOption }}
                                    </option>
                                @endforeach
                                <option value="Lainnya" @selected($ibuJob['selected'] === 'Lainnya')>
                                    Lainnya
                                </option>
                            </select>
                            <p id="pekerjaan_ibu_deceased_hint" class="mt-1.5 hidden text-xs text-slate-500">
                                Karena status Ibu meninggal dunia, pilihan pekerjaan ini bersifat opsional dan <strong>boleh tidak dipilih (dikosongkan)</strong>.
                            </p>
                        </div>

                        {{-- JABATAN & SK JABATAN IBU (MUNCUL JIKA PILIH ASN) --}}
                        <div id="jabatan_ibu_wrapper" class="{{ $ibuJob['selected'] === 'ASN' ? '' : 'hidden' }} md:col-span-2 space-y-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                            <div>
                                <label class="form-label" for="jabatan_ibu">
                                    Jabatan ASN Ibu
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="jabatan_ibu"
                                    class="form-input bg-white"
                                    type="text"
                                    name="jabatan_ibu"
                                    maxlength="150"
                                    value="{{ old('jabatan_ibu', $orangTua?->jabatan_ibu) }}"
                                    placeholder="Contoh: Kepala Dinas / Kepala Bidang / Sekretaris Dinas / Guru / Staf Pelaksana / Jabatan Fungsional"
                                >

                                <p class="mt-1.5 text-xs leading-5 text-slate-500">
                                    Tuliskan jabatan lengkap sesuai SK kepegawaian terakhir.
                                </p>
                                @error('jabatan_ibu')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div data-file-field>
                                @if($orangTua?->sk_jabatan_ibu)
                                    <label class="form-label" for="sk_jabatan_ibu">
                                        Ganti SK Jabatan Terakhir Ibu
                                        <span class="font-normal text-slate-400">(opsional · PDF, JPG, PNG maks 2 MB)</span>
                                    </label>
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 px-3.5 py-2.5 text-xs">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-emerald-900">{{ basename($orangTua->sk_jabatan_ibu) }}</p>
                                            <p class="mt-0.5 text-emerald-700">SK Jabatan Terakhir Ibu sudah diunggah</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-3">
                                            <a href="{{ route('mahasiswa.orang-tua.sk.preview', 'ibu') }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 hover:underline">Lihat SK</a>
                                            <a href="{{ route('mahasiswa.orang-tua.sk.download', 'ibu') }}" class="font-semibold text-brand-700 hover:underline">Unduh SK</a>
                                            @if($canEdit)
                                                <button
                                                    type="button"
                                                    onclick="if (confirm('Hapus SK Jabatan Terakhir Ibu yang sudah diunggah?')) { const f = document.getElementById('delete-sk-form'); f.action = '{{ route('mahasiswa.orang-tua.sk.destroy', 'ibu') }}'; f.submit(); }"
                                                    class="font-semibold text-red-600 hover:underline"
                                                >
                                                    Hapus dokumen
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <label class="form-label" for="sk_jabatan_ibu">
                                        Unggah SK Jabatan Terakhir Ibu
                                        <span class="text-red-500">*</span>
                                        <span class="font-normal text-slate-400">(wajib · PDF, JPG, PNG maks 2 MB)</span>
                                    </label>
                                @endif

                                <input
                                    id="sk_jabatan_ibu"
                                    class="form-input bg-white"
                                    type="file"
                                    name="sk_jabatan_ibu"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    data-max-kb="2048"
                                    data-has-existing="{{ filled($orangTua?->sk_jabatan_ibu) ? '1' : '0' }}"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Unggah salinan/scan SK Jabatan terakhir Ibu sebagai bukti kepegawaian ASN untuk diverifikasi.
                                </p>
                                <p data-file-error class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"></p>
                                @error('sk_jabatan_ibu')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- PEKERJAAN IBU LAINNYA (MUNCUL JIKA PILIH LAINNYA) --}}
                        <div id="pekerjaan_ibu_lainnya_wrapper" class="{{ $ibuJob['selected'] === 'Lainnya' ? '' : 'hidden' }} md:col-span-2">
                            <label class="form-label" for="pekerjaan_ibu_lainnya">
                                Sebutkan Pekerjaan Ibu
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="pekerjaan_ibu_lainnya"
                                class="form-input"
                                type="text"
                                name="pekerjaan_ibu_lainnya"
                                maxlength="150"
                                value="{{ $ibuJob['lainnya'] }}"
                                placeholder="Contoh: Ibu Rumah Tangga / Petani / Pedagang / Pensiunan"
                            >
                        </div>

                    </div>

                </section>

                {{-- ========================================================= --}}
                {{-- DATA WALI --}}
                {{-- ========================================================= --}}

                <section id="wali-section" class="card overflow-hidden transition-colors {{ $bothParentsDeceased ? '' : 'bg-slate-100/80 opacity-65' }}">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                        <label id="wali-label" class="flex items-start gap-3 {{ $bothParentsDeceased ? 'cursor-pointer' : 'cursor-not-allowed' }}">

                            <input
                                id="memiliki-wali"
                                type="checkbox"
                                name="memiliki_wali"
                                value="1"
                                class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                                @checked($memilikiWali)
                                @disabled(! $bothParentsDeceased)
                            >

                            <span>
                                <strong class="block text-lg text-navy-900">
                                    Menggunakan Wali
                                </strong>

                                <span id="wali-hint" class="mt-1 block text-sm text-slate-500">
                                    Opsi wali hanya dapat dicentang apabila Status Ayah dan Status Ibu keduanya Meninggal Dunia.
                                </span>
                            </span>

                        </label>

                    </div>

                    <div
                        id="wali-fields"
                        class="grid gap-5 p-6 sm:p-8 md:grid-cols-2 {{ $memilikiWali ? '' : 'hidden' }}"
                    >

                        {{-- NAMA WALI --}}
                        <div>
                            <label class="form-label">
                                Nama Wali
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_wali"
                                maxlength="150"
                                value="{{ old('nama_wali', $orangTua?->nama_wali) }}"
                                placeholder="Masukkan nama wali"
                                data-wali-required
                            >
                        </div>

                        {{-- NIK WALI --}}
                        <div>
                            <label class="form-label" for="nik_wali">
                                NIK Wali
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="nik_wali"
                                class="form-input"
                                type="text"
                                name="nik_wali"
                                maxlength="16"
                                inputmode="numeric"
                                value="{{ old('nik_wali', $orangTua?->nik_wali) }}"
                                placeholder="Masukkan 16 digit NIK wali"
                                data-wali-required
                            >
                        </div>

                        {{-- PEKERJAAN WALI --}}
                        <div class="md:col-span-2">
                            <label class="form-label" for="pekerjaan_wali">
                                Pekerjaan Wali
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="pekerjaan_wali"
                                class="form-input"
                                name="pekerjaan_wali"
                                data-wali-required
                            >
                                <option value="">Pilih pekerjaan wali</option>
                                @foreach ($standardJobs as $jobOption)
                                    <option value="{{ $jobOption }}" @selected($waliJob['selected'] === $jobOption)>
                                        {{ $jobOption }}
                                    </option>
                                @endforeach
                                <option value="Lainnya" @selected($waliJob['selected'] === 'Lainnya')>
                                    Lainnya
                                </option>
                            </select>
                        </div>

                        {{-- JABATAN & SK JABATAN WALI (MUNCUL JIKA PILIH ASN) --}}
                        <div id="jabatan_wali_wrapper" class="{{ $waliJob['selected'] === 'ASN' ? '' : 'hidden' }} md:col-span-2 space-y-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:p-5">
                            <div>
                                <label class="form-label" for="jabatan_wali">
                                    Jabatan ASN Wali
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="jabatan_wali"
                                    class="form-input bg-white"
                                    type="text"
                                    name="jabatan_wali"
                                    maxlength="150"
                                    value="{{ old('jabatan_wali', $orangTua?->jabatan_wali) }}"
                                    placeholder="Contoh: Kepala Bidang / Sekretaris Dinas / Guru / Staf Pelaksana"
                                >

                                <p class="mt-1.5 text-xs leading-5 text-slate-500">
                                    Tuliskan jabatan lengkap sesuai SK kepegawaian terakhir.
                                </p>
                                @error('jabatan_wali')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div data-file-field>
                                @if($orangTua?->sk_jabatan_wali)
                                    <label class="form-label" for="sk_jabatan_wali">
                                        Ganti SK Jabatan Terakhir Wali
                                        <span class="font-normal text-slate-400">(opsional · PDF, JPG, PNG maks 2 MB)</span>
                                    </label>
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 px-3.5 py-2.5 text-xs">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-emerald-900">{{ basename($orangTua->sk_jabatan_wali) }}</p>
                                            <p class="mt-0.5 text-emerald-700">SK Jabatan Terakhir Wali sudah diunggah</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-3">
                                            <a href="{{ route('mahasiswa.orang-tua.sk.preview', 'wali') }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 hover:underline">Lihat SK</a>
                                            <a href="{{ route('mahasiswa.orang-tua.sk.download', 'wali') }}" class="font-semibold text-brand-700 hover:underline">Unduh SK</a>
                                            @if($canEdit)
                                                <button
                                                    type="button"
                                                    onclick="if (confirm('Hapus SK Jabatan Terakhir Wali yang sudah diunggah?')) { const f = document.getElementById('delete-sk-form'); f.action = '{{ route('mahasiswa.orang-tua.sk.destroy', 'wali') }}'; f.submit(); }"
                                                    class="font-semibold text-red-600 hover:underline"
                                                >
                                                    Hapus dokumen
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <label class="form-label" for="sk_jabatan_wali">
                                        Unggah SK Jabatan Terakhir Wali
                                        <span class="text-red-500">*</span>
                                        <span class="font-normal text-slate-400">(wajib · PDF, JPG, PNG maks 2 MB)</span>
                                    </label>
                                @endif

                                <input
                                    id="sk_jabatan_wali"
                                    class="form-input bg-white"
                                    type="file"
                                    name="sk_jabatan_wali"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    data-max-kb="2048"
                                    data-has-existing="{{ filled($orangTua?->sk_jabatan_wali) ? '1' : '0' }}"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Unggah salinan/scan SK Jabatan terakhir Wali sebagai bukti kepegawaian ASN untuk diverifikasi.
                                </p>
                                <p data-file-error class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700"></p>
                                @error('sk_jabatan_wali')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- PEKERJAAN WALI LAINNYA (MUNCUL JIKA PILIH LAINNYA) --}}
                        <div id="pekerjaan_wali_lainnya_wrapper" class="{{ $waliJob['selected'] === 'Lainnya' ? '' : 'hidden' }} md:col-span-2">
                            <label class="form-label" for="pekerjaan_wali_lainnya">
                                Sebutkan Pekerjaan Wali
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="pekerjaan_wali_lainnya"
                                class="form-input"
                                type="text"
                                name="pekerjaan_wali_lainnya"
                                maxlength="150"
                                value="{{ $waliJob['lainnya'] }}"
                                placeholder="Contoh: Petani / Buruh / Karyawan Swasta / Pensiunan"
                            >
                        </div>

                    </div>

                </section>

            </fieldset>

            {{-- ========================================================= --}}
            {{-- NAVIGASI --}}
            {{-- ========================================================= --}}

            <div class="flex flex-wrap justify-between gap-3">

                <?php
                    $isNonAkademik = $pendaftaran->kategoriBeasiswa?->application_type === \App\Enums\ApplicationType::NON_AKADEMIK;
                    $prevRoute = $isNonAkademik ? route('mahasiswa.prestasi.index') : route('mahasiswa.pendidikan.index');
                    $prevLabel = $isNonAkademik ? 'Kembali ke Prestasi' : 'Kembali ke Pendidikan';
                ?>

                <a
                    href="{{ $prevRoute }}"
                    class="btn-secondary"
                >
                    {{ $prevLabel }}
                </a>

                @if($canEdit)
                    <button
                        class="btn-primary"
                        type="submit"
                    >
                        Simpan & Lanjut ke Dokumen

                        <x-icon
                            name="arrow-right"
                            class="h-4 w-4"
                        />
                    </button>
                @else
                    <a
                        href="{{ route('mahasiswa.dokumen.index') }}"
                        class="btn-primary"
                    >
                        Lanjut ke Dokumen
                    </a>
                @endif

            </div>

        </form>

        @if($canEdit)
            <form id="delete-sk-form" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif

    </div>
@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', () => {

    const setupJobDynamicFields = (jobSelectId, jabatanWrapperId, jabatanInputId, skInputId, lainnyaWrapperId, lainnyaInputId, isEnabledFn = () => true) => {
        const jobSelect = document.getElementById(jobSelectId);
        const jabatanWrapper = document.getElementById(jabatanWrapperId);
        const jabatanInput = document.getElementById(jabatanInputId);
        const skInput = document.getElementById(skInputId);
        const lainnyaWrapper = document.getElementById(lainnyaWrapperId);
        const lainnyaInput = document.getElementById(lainnyaInputId);

        if (!jobSelect) {
            return () => {};
        }

        const syncJob = () => {
            const active = isEnabledFn();
            const val = jobSelect.value;

            const isAsn = active && val === 'ASN';
            const isLainnya = active && val === 'Lainnya';

            if (jabatanWrapper && jabatanInput) {
                jabatanWrapper.classList.toggle('hidden', !isAsn);
                jabatanInput.required = isAsn;
            }

            if (skInput) {
                skInput.required = isAsn && skInput.dataset.hasExisting !== '1';
            }

            if (lainnyaWrapper && lainnyaInput) {
                lainnyaWrapper.classList.toggle('hidden', !isLainnya);
                lainnyaInput.required = isLainnya;
            }
        };

        jobSelect.addEventListener('change', syncJob);
        syncJob();

        return syncJob;
    };

    const syncAyahJob = setupJobDynamicFields(
        'pekerjaan_ayah',
        'jabatan_ayah_wrapper',
        'jabatan_ayah',
        'sk_jabatan_ayah',
        'pekerjaan_ayah_lainnya_wrapper',
        'pekerjaan_ayah_lainnya',
        () => document.getElementById('status_ayah')?.value === 'hidup'
    );

    const syncIbuJob = setupJobDynamicFields(
        'pekerjaan_ibu',
        'jabatan_ibu_wrapper',
        'jabatan_ibu',
        'sk_jabatan_ibu',
        'pekerjaan_ibu_lainnya_wrapper',
        'pekerjaan_ibu_lainnya',
        () => document.getElementById('status_ibu')?.value === 'hidup'
    );

    const checkbox = document.getElementById('memiliki-wali');
    const fields = document.getElementById('wali-fields');
    const waliSection = document.getElementById('wali-section');
    const waliLabel = document.getElementById('wali-label');

    const syncWaliJob = setupJobDynamicFields(
        'pekerjaan_wali',
        'jabatan_wali_wrapper',
        'jabatan_wali',
        'sk_jabatan_wali',
        'pekerjaan_wali_lainnya_wrapper',
        'pekerjaan_wali_lainnya',
        () => Boolean(checkbox && checkbox.checked && !checkbox.disabled)
    );

    const syncWali = () => {
        if (!checkbox || !fields) {
            return;
        }

        const active = checkbox.checked && !checkbox.disabled;
        fields.classList.toggle('hidden', !active);

        fields
            .querySelectorAll('[data-wali-required]')
            .forEach((input) => {
                input.required = active;
            });

        syncWaliJob();
    };

    const syncWaliEligibility = () => {
        if (!checkbox) {
            return;
        }

        const statusAyah = document.getElementById('status_ayah')?.value;
        const statusIbu = document.getElementById('status_ibu')?.value;
        const bothDeceased = statusAyah === 'meninggal_dunia' && statusIbu === 'meninggal_dunia';

        if (!bothDeceased) {
            checkbox.checked = false;
            checkbox.disabled = true;
            if (waliSection) {
                waliSection.classList.add('bg-slate-100/80', 'opacity-65');
            }
            if (waliLabel) {
                waliLabel.classList.remove('cursor-pointer');
                waliLabel.classList.add('cursor-not-allowed');
            }
        } else {
            checkbox.disabled = false;
            if (waliSection) {
                waliSection.classList.remove('bg-slate-100/80', 'opacity-65');
            }
            if (waliLabel) {
                waliLabel.classList.add('cursor-pointer');
                waliLabel.classList.remove('cursor-not-allowed');
            }
        }

        syncWali();
    };

    if (checkbox && fields) {
        checkbox.addEventListener('change', syncWali);
        syncWaliEligibility();
    }

    const setupParentStatusSync = (
        statusSelectId,
        nikInputId,
        nikReqId,
        nikOptId,
        nikHintId,
        jobSelectId,
        jobPlaceholderId,
        defaultJobPlaceholder,
        jobReqId,
        jobOptId,
        jobHintId,
        onStatusChanged
    ) => {
        const statusSelect = document.getElementById(statusSelectId);
        const nikInput = document.getElementById(nikInputId);
        const nikReq = document.getElementById(nikReqId);
        const nikOpt = document.getElementById(nikOptId);
        const nikHint = document.getElementById(nikHintId);
        const jobSelect = document.getElementById(jobSelectId);
        const jobPlaceholder = document.getElementById(jobPlaceholderId);
        const jobReq = document.getElementById(jobReqId);
        const jobOpt = document.getElementById(jobOptId);
        const jobHint = document.getElementById(jobHintId);

        if (!statusSelect || !nikInput) {
            return;
        }

        const syncStatus = () => {
            const isHidup = statusSelect.value === 'hidup';

            nikInput.required = isHidup;
            if (nikReq && nikOpt) {
                nikReq.classList.toggle('hidden', !isHidup);
                nikOpt.classList.toggle('hidden', isHidup);
            }
            if (nikHint) {
                nikHint.classList.toggle('hidden', isHidup);
            }
            nikInput.placeholder = isHidup
                ? 'Masukkan 16 digit NIK'
                : 'Boleh dikosongkan jika tidak ada (opsional)';

            if (jobSelect) {
                jobSelect.required = isHidup;
            }
            if (jobPlaceholder) {
                jobPlaceholder.textContent = isHidup
                    ? defaultJobPlaceholder
                    : '— Tidak diisi / Boleh dikosongkan (Opsional) —';
            }
            if (jobReq && jobOpt) {
                jobReq.classList.toggle('hidden', !isHidup);
                jobOpt.classList.toggle('hidden', isHidup);
            }
            if (jobHint) {
                jobHint.classList.toggle('hidden', isHidup);
            }

            if (typeof onStatusChanged === 'function') {
                onStatusChanged();
            }
        };

        statusSelect.addEventListener('change', syncStatus);
        syncStatus();
    };

    setupParentStatusSync(
        'status_ayah',
        'nik_ayah',
        'nik_ayah_required',
        'nik_ayah_optional',
        'nik_ayah_deceased_hint',
        'pekerjaan_ayah',
        'pekerjaan_ayah_placeholder',
        'Pilih pekerjaan ayah',
        'pekerjaan_ayah_required',
        'pekerjaan_ayah_optional',
        'pekerjaan_ayah_deceased_hint',
        () => {
            syncAyahJob();
            syncWaliEligibility();
        }
    );

    setupParentStatusSync(
        'status_ibu',
        'nik_ibu',
        'nik_ibu_required',
        'nik_ibu_optional',
        'nik_ibu_deceased_hint',
        'pekerjaan_ibu',
        'pekerjaan_ibu_placeholder',
        'Pilih pekerjaan ibu',
        'pekerjaan_ibu_required',
        'pekerjaan_ibu_optional',
        'pekerjaan_ibu_deceased_hint',
        () => {
            syncIbuJob();
            syncWaliEligibility();
        }
    );

});
</script>

@endpush
