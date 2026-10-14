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

                            <label class="form-label">
                                Pekerjaan Ayah
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="pekerjaan_ayah"
                                maxlength="150"
                                value="{{ old('pekerjaan_ayah', $orangTua?->pekerjaan_ayah) }}"
                                placeholder="Masukkan pekerjaan ayah"
                                required
                            >

                        </div>


                        {{-- PENGHASILAN AYAH --}}
                        <div>

                            <label class="form-label">
                                Penghasilan Ayah per Bulan
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="number"
                                name="penghasilan_ayah"
                                min="0"
                                step="1000"
                                value="{{ old('penghasilan_ayah', $orangTua?->penghasilan_ayah) }}"
                                placeholder="Contoh: 2000000"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Masukkan angka tanpa titik atau koma. Contoh: 2000000.
                            </p>

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

                            <label class="form-label">
                                Pekerjaan Ibu
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="pekerjaan_ibu"
                                maxlength="150"
                                value="{{ old('pekerjaan_ibu', $orangTua?->pekerjaan_ibu) }}"
                                placeholder="Masukkan pekerjaan ibu"
                                required
                            >

                        </div>


                        {{-- PENGHASILAN IBU --}}
                        <div>

                            <label class="form-label">
                                Penghasilan Ibu per Bulan
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="number"
                                name="penghasilan_ibu"
                                min="0"
                                step="1000"
                                value="{{ old('penghasilan_ibu', $orangTua?->penghasilan_ibu) }}"
                                placeholder="Contoh: 2000000"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Masukkan angka tanpa titik atau koma. Contoh: 2000000.
                            </p>

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
                        <div>

                            <label class="form-label">
                                Pekerjaan Wali
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="pekerjaan_wali"
                                maxlength="150"
                                value="{{ old('pekerjaan_wali', $orangTua?->pekerjaan_wali) }}"
                                placeholder="Masukkan pekerjaan wali"
                                data-wali-required
                            >

                        </div>


                        {{-- PENGHASILAN WALI --}}
                        <div>

                            <label class="form-label">
                                Penghasilan Wali per Bulan
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="number"
                                name="penghasilan_wali"
                                min="0"
                                step="1000"
                                value="{{ old('penghasilan_wali', $orangTua?->penghasilan_wali) }}"
                                placeholder="Contoh: 2000000"
                                data-wali-required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Masukkan angka tanpa titik atau koma. Contoh: 2000000.
                            </p>

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

    const checkbox = document.getElementById('memiliki-wali');
    const fields = document.getElementById('wali-fields');

    if (checkbox && fields) {
        const syncWali = () => {
            fields.classList.toggle(
                'hidden',
                !checkbox.checked
            );

            fields
                .querySelectorAll('[data-wali-required]')
                .forEach((input) => {
                    input.required = checkbox.checked;
                });
        };

        checkbox.addEventListener('change', syncWali);
        syncWali();
    }

    const setupParentNikSync = (statusSelectId, nikInputId, requiredMarkId, optionalMarkId) => {
        const statusSelect = document.getElementById(statusSelectId);
        const nikInput = document.getElementById(nikInputId);
        const requiredMark = document.getElementById(requiredMarkId);
        const optionalMark = document.getElementById(optionalMarkId);

        if (!statusSelect || !nikInput) {
            return;
        }

        const syncNik = () => {
            const isHidup = statusSelect.value === 'hidup';
            nikInput.required = isHidup;

            if (requiredMark && optionalMark) {
                requiredMark.classList.toggle('hidden', !isHidup);
                optionalMark.classList.toggle('hidden', isHidup);
            }

            if (isHidup) {
                nikInput.placeholder = 'Masukkan 16 digit NIK';
            } else {
                nikInput.placeholder = 'Masukkan NIK jika ada (opsional)';
            }
        };

        statusSelect.addEventListener('change', syncNik);
        syncNik();
    };

    setupParentNikSync('status_ayah', 'nik_ayah', 'nik_ayah_required', 'nik_ayah_optional');
    setupParentNikSync('status_ibu', 'nik_ibu', 'nik_ibu_required', 'nik_ibu_optional');

});
</script>

@endpush