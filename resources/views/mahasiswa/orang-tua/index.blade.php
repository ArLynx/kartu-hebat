@extends('layouts.portal')

@section('title', 'Orang Tua Beasiswa')
@section('header', 'Orang Tua')

@section('content')

    @php
        $orangTua = $pendaftaran->orangTua;
        $memilikiWali = (bool) old('memiliki_wali', $orangTua?->memiliki_wali);

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
                                <span id="nik_ayah_optional" class="font-normal text-slate-400 hidden">(opsional)</span>
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
                        </div>

                        {{-- PEKERJAAN AYAH --}}
                        <div>
                            <label class="form-label" for="pekerjaan_ayah">
                                Pekerjaan Ayah
                                <span id="pekerjaan_ayah_required" class="text-red-500">*</span>
                                <span id="pekerjaan_ayah_optional" class="font-normal text-slate-400 hidden">(opsional)</span>
                            </label>

                            <select
                                id="pekerjaan_ayah"
                                class="form-input"
                                name="pekerjaan_ayah"
                            >
                                <option value="">Pilih pekerjaan ayah</option>
                                @foreach ($standardJobs as $jobOption)
                                    <option value="{{ $jobOption }}" @selected($ayahJob['selected'] === $jobOption)>
                                        {{ $jobOption }}
                                    </option>
                                @endforeach
                                <option value="Lainnya" @selected($ayahJob['selected'] === 'Lainnya')>
                                    Lainnya
                                </option>
                            </select>
                        </div>

                        {{-- JABATAN AYAH (MUNCUL JIKA PILIH ASN) --}}
                        <div id="jabatan_ayah_wrapper" class="{{ $ayahJob['selected'] === 'ASN' ? '' : 'hidden' }} md:col-span-2">
                            <label class="form-label" for="jabatan_ayah">
                                Jabatan ASN Ayah
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="jabatan_ayah"
                                class="form-input"
                                type="text"
                                name="jabatan_ayah"
                                maxlength="150"
                                value="{{ old('jabatan_ayah', $orangTua?->jabatan_ayah) }}"
                                placeholder="Contoh: Kepala Dinas / Kepala Bidang / Sekretaris Dinas / Guru / Staf Pelaksana / Jabatan Fungsional"
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Tuliskan jabatan lengkap sesuai SK kepegawaian terakhir.
                            </p>
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
                                <span id="nik_ibu_optional" class="font-normal text-slate-400 hidden">(opsional)</span>
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
                        </div>

                        {{-- PEKERJAAN IBU --}}
                        <div>
                            <label class="form-label" for="pekerjaan_ibu">
                                Pekerjaan Ibu
                                <span id="pekerjaan_ibu_required" class="text-red-500">*</span>
                                <span id="pekerjaan_ibu_optional" class="font-normal text-slate-400 hidden">(opsional)</span>
                            </label>

                            <select
                                id="pekerjaan_ibu"
                                class="form-input"
                                name="pekerjaan_ibu"
                            >
                                <option value="">Pilih pekerjaan ibu</option>
                                @foreach ($standardJobs as $jobOption)
                                    <option value="{{ $jobOption }}" @selected($ibuJob['selected'] === $jobOption)>
                                        {{ $jobOption }}
                                    </option>
                                @endforeach
                                <option value="Lainnya" @selected($ibuJob['selected'] === 'Lainnya')>
                                    Lainnya
                                </option>
                            </select>
                        </div>

                        {{-- JABATAN IBU (MUNCUL JIKA PILIH ASN) --}}
                        <div id="jabatan_ibu_wrapper" class="{{ $ibuJob['selected'] === 'ASN' ? '' : 'hidden' }} md:col-span-2">
                            <label class="form-label" for="jabatan_ibu">
                                Jabatan ASN Ibu
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="jabatan_ibu"
                                class="form-input"
                                type="text"
                                name="jabatan_ibu"
                                maxlength="150"
                                value="{{ old('jabatan_ibu', $orangTua?->jabatan_ibu) }}"
                                placeholder="Contoh: Kepala Dinas / Kepala Bidang / Sekretaris Dinas / Guru / Staf Pelaksana / Jabatan Fungsional"
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Tuliskan jabatan lengkap sesuai SK kepegawaian terakhir.
                            </p>
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

                <section class="card overflow-hidden">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">

                        <label class="flex cursor-pointer items-start gap-3">

                            <input
                                id="memiliki-wali"
                                type="checkbox"
                                name="memiliki_wali"
                                value="1"
                                class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                @checked($memilikiWali)
                            >

                            <span>
                                <strong class="block text-lg text-navy-900">
                                    Menggunakan Wali
                                </strong>

                                <span class="mt-1 block text-sm text-slate-500">
                                    Centang apabila tanggung jawab utama berada pada wali selain ayah/ibu.
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

                        {{-- JABATAN WALI (MUNCUL JIKA PILIH ASN) --}}
                        <div id="jabatan_wali_wrapper" class="{{ $waliJob['selected'] === 'ASN' ? '' : 'hidden' }} md:col-span-2">
                            <label class="form-label" for="jabatan_wali">
                                Jabatan ASN Wali
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="jabatan_wali"
                                class="form-input"
                                type="text"
                                name="jabatan_wali"
                                maxlength="150"
                                value="{{ old('jabatan_wali', $orangTua?->jabatan_wali) }}"
                                placeholder="Contoh: Kepala Bidang / Sekretaris Dinas / Guru / Staf Pelaksana"
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Tuliskan jabatan lengkap sesuai SK kepegawaian terakhir.
                            </p>
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

    </div>
@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', () => {

    const setupJobDynamicFields = (jobSelectId, jabatanWrapperId, jabatanInputId, lainnyaWrapperId, lainnyaInputId, isEnabledFn = () => true) => {
        const jobSelect = document.getElementById(jobSelectId);
        const jabatanWrapper = document.getElementById(jabatanWrapperId);
        const jabatanInput = document.getElementById(jabatanInputId);
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
        'pekerjaan_ayah_lainnya_wrapper',
        'pekerjaan_ayah_lainnya'
    );

    const syncIbuJob = setupJobDynamicFields(
        'pekerjaan_ibu',
        'jabatan_ibu_wrapper',
        'jabatan_ibu',
        'pekerjaan_ibu_lainnya_wrapper',
        'pekerjaan_ibu_lainnya'
    );

    const checkbox = document.getElementById('memiliki-wali');
    const fields = document.getElementById('wali-fields');

    const syncWaliJob = setupJobDynamicFields(
        'pekerjaan_wali',
        'jabatan_wali_wrapper',
        'jabatan_wali',
        'pekerjaan_wali_lainnya_wrapper',
        'pekerjaan_wali_lainnya',
        () => Boolean(checkbox && checkbox.checked)
    );

    if (checkbox && fields) {
        const syncWali = () => {
            fields.classList.toggle('hidden', !checkbox.checked);

            fields
                .querySelectorAll('[data-wali-required]')
                .forEach((input) => {
                    input.required = checkbox.checked;
                });

            syncWaliJob();
        };

        checkbox.addEventListener('change', syncWali);
        syncWali();
    }

    const setupParentStatusSync = (
        statusSelectId,
        nikInputId,
        nikReqId,
        nikOptId,
        jobSelectId,
        jobReqId,
        jobOptId,
        onStatusChanged
    ) => {
        const statusSelect = document.getElementById(statusSelectId);
        const nikInput = document.getElementById(nikInputId);
        const nikReq = document.getElementById(nikReqId);
        const nikOpt = document.getElementById(nikOptId);
        const jobSelect = document.getElementById(jobSelectId);
        const jobReq = document.getElementById(jobReqId);
        const jobOpt = document.getElementById(jobOptId);

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
            nikInput.placeholder = isHidup
                ? 'Masukkan 16 digit NIK'
                : 'Masukkan NIK jika ada (opsional)';

            if (jobSelect) {
                jobSelect.required = isHidup;
            }
            if (jobReq && jobOpt) {
                jobReq.classList.toggle('hidden', !isHidup);
                jobOpt.classList.toggle('hidden', isHidup);
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
        'pekerjaan_ayah',
        'pekerjaan_ayah_required',
        'pekerjaan_ayah_optional',
        syncAyahJob
    );

    setupParentStatusSync(
        'status_ibu',
        'nik_ibu',
        'nik_ibu_required',
        'nik_ibu_optional',
        'pekerjaan_ibu',
        'pekerjaan_ibu_required',
        'pekerjaan_ibu_optional',
        syncIbuJob
    );

});
</script>

@endpush
