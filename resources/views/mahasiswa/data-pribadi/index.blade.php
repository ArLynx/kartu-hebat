@extends('layouts.portal')

@section('title', 'Data Pribadi Beasiswa')
@section('header', 'Data Pribadi')

@section('content')
    @php
        $data = $pendaftaran->dataPribadi;
        $selectedVillageId = (int) old('village_id', $data?->village_id ?? 0);
        $selectedVillage = $villages->firstWhere('id', $selectedVillageId);
        $initialQuery = $selectedVillage ? ($selectedVillage->display_name . ' — Kec. ' . $selectedVillage->kecamatan->name) : '';
        $villageList = $villages->map(fn($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'type' => ucfirst($v->type ?? 'desa'),
            'displayName' => $v->display_name,
            'kecamatan' => $v->kecamatan?->name ?? '',
            'kabupaten' => $v->kabupaten?->name ?? 'Murung Raya',
            'searchKey' => strtolower($v->display_name . ' ' . $v->name . ' ' . ($v->kecamatan?->name ?? '')),
        ])->values();
    @endphp

    <div class="max-w-6xl">

        {{-- ============================================================= --}}
        {{-- FLOW HEADER --}}
        {{-- ============================================================= --}}

        @include('mahasiswa.partials.flow-header', [
            'title' => 'Data Pribadi',
            'description' =>
                'Lengkapi identitas sesuai dokumen resmi untuk pendaftaran ' .
                $pendaftaran->nomor_pendaftaran .
                '.',
            'currentStep' => 1,
        ])


        {{-- ============================================================= --}}
        {{-- STATUS JIKA SUDAH DIKIRIM --}}
        {{-- ============================================================= --}}

        @unless ($canEdit)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800">
                Pendaftaran telah dikirim. Data hanya dapat dilihat dan tidak dapat diubah.
            </div>
        @endunless


        {{-- ============================================================= --}}
        {{-- INFORMASI PENGISIAN --}}
        {{-- ============================================================= --}}

        @if ($canEdit)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
                <div class="flex items-start gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                        <x-icon name="information-circle" class="h-5 w-5" />
                    </div>

                    <div>
                        <h3 class="font-bold text-blue-900">
                            Informasi Pengisian
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-blue-800">
                            Anda dapat membuka tab tahapan pendaftaran atau menu pada sidebar
                            untuk melihat dan mempelajari setiap bagian yang harus dilengkapi.
                            Data wajib ditandai dengan tanda
                            <span class="font-bold text-red-600">*</span>
                            dan harus diisi sebelum dapat disimpan dan dilanjutkan ke tahap berikutnya.
                        </p>
                    </div>

                </div>
            </div>
        @endif


        {{-- ============================================================= --}}
        {{-- FORM --}}
        {{-- ============================================================= --}}

        <form
            method="POST"
            action="{{ route('mahasiswa.data-pribadi.update') }}"
            class="mt-7 space-y-6"
        >
            @csrf
            @method('PUT')


            <fieldset
                @disabled(!$canEdit)
                class="space-y-6"
            >

                {{-- ===================================================== --}}
                {{-- INFORMASI MAHASISWA --}}
                {{-- ===================================================== --}}

                <section class="card overflow-visible">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8 rounded-t-xl">
                        <h2 class="text-xl font-bold">
                            Informasi Mahasiswa
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Isi sesuai dengan identitas resmi mahasiswa.
                        </p>
                    </div>


                    <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">

                        {{-- ================================================= --}}
                        {{-- NIK --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                NIK
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nik"
                                maxlength="16"
                                inputmode="numeric"
                                value="{{ old('nik', $data?->nik) }}"
                                placeholder="Masukkan NIK"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                NIK harus terdiri dari 16 digit.
                            </p>
                        </div>


                        {{-- ================================================= --}}
                        {{-- NOMOR KK --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Nomor Kartu Keluarga (KK)
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nomor_kk"
                                maxlength="16"
                                inputmode="numeric"
                                value="{{ old('nomor_kk', $data?->nomor_kk) }}"
                                placeholder="Masukkan 16 digit Nomor KK"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Nomor KK harus terdiri dari 16 digit sesuai Kartu Keluarga.
                            </p>
                        </div>


                        {{-- ================================================= --}}
                        {{-- REKENING BANK KALTENG --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Nomor Rekening Bank Kalteng
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nomor_rekening"
                                inputmode="numeric"
                                value="{{ old('nomor_rekening', $data?->nomor_rekening) }}"
                                placeholder="Masukkan nomor rekening Bank Kalteng"
                                required
                            >

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Wajib menggunakan rekening atas nama mahasiswa pada
                                PT Bank Pembangunan Daerah Kalimantan Tengah (Bank Kalteng).
                            </p>
                        </div>


                        {{-- ================================================= --}}
                        {{-- NAMA LENGKAP --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Nama Lengkap
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="nama_lengkap"
                                value="{{ old('nama_lengkap', $data?->nama_lengkap ?: auth()->user()->name) }}"
                                placeholder="Masukkan nama lengkap"
                                required
                            >
                        </div>


                        {{-- ================================================= --}}
                        {{-- NO HP --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Nomor HP/WhatsApp
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="no_hp"
                                inputmode="tel"
                                value="{{ old('no_hp', $data?->no_hp) }}"
                                placeholder="Contoh: 081234567890"
                                required
                            >
                        </div>


                        {{-- ================================================= --}}
                        {{-- TEMPAT LAHIR --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Tempat Lahir
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="tempat_lahir"
                                value="{{ old('tempat_lahir', $data?->tempat_lahir) }}"
                                placeholder="Masukkan tempat lahir"
                                required
                            >
                        </div>


                        {{-- ================================================= --}}
                        {{-- TANGGAL LAHIR --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Tanggal Lahir
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="date"
                                name="tanggal_lahir"
                                max="{{ today()->subDay()->format('Y-m-d') }}"
                                value="{{ old('tanggal_lahir', $data?->tanggal_lahir?->format('Y-m-d')) }}"
                                required
                            >
                        </div>


                        {{-- ================================================= --}}
                        {{-- JENIS KELAMIN --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Jenis Kelamin
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                class="form-input"
                                name="jenis_kelamin"
                                required
                            >
                                <option value="">
                                    Pilih jenis kelamin
                                </option>

                                <option
                                    value="L"
                                    @selected(old('jenis_kelamin', $data?->jenis_kelamin) === 'L')
                                >
                                    Laki-laki
                                </option>

                                <option
                                    value="P"
                                    @selected(old('jenis_kelamin', $data?->jenis_kelamin) === 'P')
                                >
                                    Perempuan
                                </option>
                            </select>
                        </div>


                        {{-- ================================================= --}}
                        {{-- AGAMA --}}
                        {{-- ================================================= --}}

                        <div>
                            <label class="form-label">
                                Agama
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                class="form-input"
                                name="agama"
                                required
                            >
                                <option value="">
                                    Pilih agama
                                </option>

                                @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $agama)
                                    <option
                                        value="{{ $agama }}"
                                        @selected(old('agama', $data?->agama) === $agama)
                                    >
                                        {{ $agama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        {{-- ================================================= --}}
                        {{-- EMAIL --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label class="form-label">
                                Email Akun
                            </label>

                            <input
                                class="form-input bg-slate-100"
                                type="email"
                                value="{{ auth()->user()->email }}"
                                readonly
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Email mengikuti akun mahasiswa dan tidak dapat diubah
                                pada halaman ini.
                            </p>

                        </div>

                    </div>

                </section>


                {{-- ===================================================== --}}
                {{-- ALAMAT --}}
                {{-- ===================================================== --}}

                <section class="card overflow-visible">

                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8 rounded-t-xl">

                        <h2 class="text-xl font-bold">
                            Alamat
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Isi alamat sesuai dengan dokumen identitas mahasiswa.
                        </p>

                    </div>


                    <div class="grid gap-5 p-6 sm:p-8 md:grid-cols-2">

                        {{-- ================================================= --}}
                        {{-- ALAMAT LENGKAP --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label class="form-label">
                                Alamat Lengkap (Sesuai KTP)
                                <span class="text-red-500">*</span>
                            </label>

                            <textarea
                                class="form-input"
                                name="alamat"
                                rows="3"
                                placeholder="Masukkan alamat lengkap sesuai KTP"
                                required
                            >{{ old('alamat', $data?->alamat) }}</textarea>

                        </div>


                        {{-- ================================================= --}}
                        {{-- PROVINSI --}}
                        {{-- ================================================= --}}

                        <div>

                            <label class="form-label">
                                Provinsi
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="provinsi"
                                value="{{ old('provinsi', $data?->provinsi ?? 'Kalimantan Tengah') }}"
                                placeholder="Masukkan provinsi"
                                required
                            >

                        </div>


                        {{-- ================================================= --}}
                        {{-- DESA / KELURAHAN (PENCARIAN LANGSUNG & OPTGROUP KECAMATAN) --}}
                        {{-- ================================================= --}}

                        <div
                            class="md:col-span-2"
                            x-data="{
                                open: false,
                                query: @js($initialQuery),
                                selectedId: @js($selectedVillageId ?: ''),
                                villages: @js($villageList),
                                get selectedVillage() {
                                    return this.villages.find(v => v.id == this.selectedId) || null;
                                },
                                get filteredVillages() {
                                    if (!this.query.trim()) {
                                        return this.villages;
                                    }
                                    if (this.selectedVillage && this.query === (this.selectedVillage.displayName + ' — Kec. ' + this.selectedVillage.kecamatan)) {
                                        return this.villages;
                                    }
                                    const q = this.query.toLowerCase().trim();
                                    return this.villages.filter(v => v.searchKey.includes(q));
                                },
                                get groupedVillages() {
                                    const list = this.filteredVillages;
                                    const map = new Map();
                                    for (const v of list) {
                                        if (!map.has(v.kecamatan)) {
                                            map.set(v.kecamatan, []);
                                        }
                                        map.get(v.kecamatan).push(v);
                                    }
                                    return Array.from(map.entries()).map(([kecamatan, items]) => ({ kecamatan, items }));
                                },
                                selectVillage(village) {
                                    this.selectedId = village.id;
                                    this.query = village.displayName + ' — Kec. ' + village.kecamatan;
                                    this.open = false;
                                },
                                clearSelection() {
                                    this.selectedId = '';
                                    this.query = '';
                                    this.open = true;
                                    this.$nextTick(() => this.$refs.inputField?.focus());
                                },
                                onClickAway() {
                                    this.open = false;
                                    if (this.selectedVillage) {
                                        this.query = this.selectedVillage.displayName + ' — Kec. ' + this.selectedVillage.kecamatan;
                                    } else {
                                        this.query = '';
                                        this.selectedId = '';
                                    }
                                }
                            }"
                            @click.away="onClickAway()"
                            @keydown.escape.window="onClickAway()"
                        >

                            <label class="form-label">
                                Desa/Kelurahan sesuai Master Wilayah
                                <span class="text-red-500">*</span>
                            </label>

                            {{-- Input tersembunyi untuk submit form --}}
                            <input
                                type="hidden"
                                name="village_id"
                                :value="selectedId || ''"
                                value="{{ $selectedVillageId ?: '' }}"
                                required
                            >

                            {{-- Kotak Input Tunggal Pencarian & Pilihan --}}
                            <div class="relative mt-1">
                                <input
                                    x-ref="inputField"
                                    type="text"
                                    x-model="query"
                                    @focus="open = true; $el.select()"
                                    @click="open = true"
                                    @input="open = true"
                                    placeholder="Ketik untuk mencari atau pilih desa / kelurahan..."
                                    class="form-input pr-16"
                                    :class="{ 'border-blue-500 ring-2 ring-blue-100': open, 'border-red-400': {{ $errors->has('village_id') ? 'true' : 'false' }} }"
                                    autocomplete="off"
                                    @disabled(!$canEdit)
                                >

                                {{-- Tombol Aksi di Sisi Kanan (Clear & Arrow) --}}
                                <div class="absolute right-2 top-1/2 flex -translate-y-1/2 items-center gap-1">
                                    <template x-if="selectedId && {{ $canEdit ? 'true' : 'false' }}">
                                        <button
                                            type="button"
                                            @click.stop="clearSelection()"
                                            class="rounded p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                            title="Hapus pilihan"
                                        >
                                            <x-icon name="x" class="h-4 w-4" />
                                        </button>
                                    </template>
                                    <button
                                        type="button"
                                        @click="open = !open; if(open) { $refs.inputField?.focus(); }"
                                        @disabled(!$canEdit)
                                        class="rounded p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                    >
                                        <span
                                            class="inline-block transition-transform duration-200"
                                            :class="open ? 'rotate-180' : ''"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </span>
                                    </button>
                                </div>

                                {{-- Menu Dropdown yang Menampilkan Seluruh Desa Per Kecamatan (Optgroup Style) --}}
                                <div
                                    x-show="open"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 translate-y-1"
                                    class="absolute left-0 right-0 z-50 mt-1.5 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl focus:outline-none"
                                    role="listbox"
                                >
                                    <template x-for="group in groupedVillages" :key="group.kecamatan">
                                        <div class="border-b border-slate-100 last:border-b-0">
                                            {{-- Header Kecamatan (Optgroup) --}}
                                            <div class="sticky top-0 z-10 flex items-center justify-between border-y border-slate-200 bg-slate-100/95 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-slate-700 backdrop-blur-sm">
                                                <span x-text="'Kecamatan ' + group.kecamatan"></span>
                                                <span class="text-[10px] font-semibold text-slate-400" x-text="group.items.length + ' wilayah'"></span>
                                            </div>

                                            {{-- Daftar Desa / Kelurahan di Kecamatan ini --}}
                                            <ul class="divide-y divide-slate-100">
                                                <template x-for="village in group.items" :key="village.id">
                                                    <li
                                                        @click="selectVillage(village)"
                                                        class="group flex cursor-pointer items-center justify-between px-3 py-2 text-sm transition hover:bg-blue-50"
                                                        :class="{ 'bg-blue-50/90 font-semibold text-blue-900': selectedId == village.id }"
                                                        role="option"
                                                        :aria-selected="selectedId == village.id"
                                                    >
                                                        <div class="flex min-w-0 items-center gap-2.5 pr-2">
                                                            <span
                                                                class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                                                :class="village.type.toLowerCase() === 'kelurahan' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-sky-100 text-sky-800 border border-sky-200'"
                                                                x-text="village.type"
                                                            ></span>
                                                            <span class="font-medium text-slate-900 group-hover:text-blue-900" x-text="village.name"></span>
                                                        </div>

                                                        <template x-if="selectedId == village.id">
                                                            <x-icon name="check" class="h-4 w-4 shrink-0 text-blue-600" />
                                                        </template>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>

                                    {{-- Kondisi jika tidak ditemukan --}}
                                    <div
                                        x-show="groupedVillages.length === 0"
                                        class="p-6 text-center text-sm text-slate-500"
                                    >
                                        <p class="font-semibold text-slate-700">Tidak ada desa atau kelurahan yang cocok</p>
                                        <p class="mt-1 text-xs text-slate-400">Pastikan ejaan nama desa atau kecamatan di Murung Raya sudah sesuai.</p>
                                    </div>
                                </div>
                            </div>

                            @error('village_id')
                                <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror

                            {{-- Fallback untuk browser tanpa JavaScript --}}
                            <noscript>
                                <select
                                    class="form-input mt-2"
                                    name="village_id"
                                    required
                                >
                                    <option value="">Pilih desa/kelurahan</option>
                                    @foreach ($villages->groupBy(fn($v) => $v->kecamatan->name) as $kecamatanName => $group)
                                        <optgroup label="Kecamatan {{ $kecamatanName }}">
                                            @foreach ($group as $village)
                                                <option
                                                    value="{{ $village->id }}"
                                                    @selected((int) old('village_id', $data?->village_id) === (int) $village->id)
                                                >
                                                    {{ $village->display_name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </noscript>

                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Pilihan ini menentukan antrean verifikator desa, kecamatan, lintas dinas, dan kabupaten.
                            </p>

                        </div>


                        {{-- ================================================= --}}
                        {{-- KODE POS --}}
                        {{-- ================================================= --}}

                        <div>

                            <label class="form-label">
                                Kode Pos
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                class="form-input"
                                type="text"
                                name="kode_pos"
                                inputmode="numeric"
                                maxlength="10"
                                value="{{ old('kode_pos', $data?->kode_pos) }}"
                                placeholder="Masukkan kode pos"
                                required
                            >

                        </div>

                    </div>

                </section>

            </fieldset>


            {{-- ============================================================= --}}
            {{-- BUTTON --}}
            {{-- ============================================================= --}}

            <div class="flex flex-wrap justify-between gap-3">

                <a
                    href="{{ route('mahasiswa.dashboard') }}"
                    class="btn-secondary"
                >
                    Kembali
                </a>


                @if ($canEdit)

                    <button
                        class="btn-primary"
                        type="submit"
                    >
                        Simpan & Lanjut ke Pendidikan

                        <x-icon
                            name="arrow-right"
                            class="h-4 w-4"
                        />
                    </button>

                @else

                    <a
                        href="{{ route('mahasiswa.pendidikan.index') }}"
                        class="btn-primary"
                    >
                        Lanjut ke Pendidikan

                        <x-icon
                            name="arrow-right"
                            class="h-4 w-4"
                        />
                    </a>

                @endif

            </div>

        </form>

    </div>
@endsection