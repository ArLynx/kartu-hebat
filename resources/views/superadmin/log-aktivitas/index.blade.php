@extends('layouts.portal')

@section('content')

    @php
        /*
    |--------------------------------------------------------------------------
    | HELPER UNTUK BROWSER
    |--------------------------------------------------------------------------
    */
        $detectBrowser = function ($userAgent) {
            if (!$userAgent) {
                return 'Tidak diketahui';
            }

            if (str_contains($userAgent, 'Edg/')) {
                return 'Microsoft Edge';
            }

            if (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')) {
                return 'Opera';
            }

            if (str_contains($userAgent, 'Chrome/')) {
                return 'Google Chrome';
            }

            if (str_contains($userAgent, 'Firefox/')) {
                return 'Mozilla Firefox';
            }

            if (str_contains($userAgent, 'Safari/')) {
                return 'Safari';
            }

            return 'Browser tidak diketahui';
        };

        /*
    |--------------------------------------------------------------------------
    | LABEL ROLE
    |--------------------------------------------------------------------------
    */
        $roleLabel = function ($user) {
            if (!$user) {
                return 'Aktivitas Sistem';
            }

            try {
                if ($user->role && method_exists($user->role, 'label')) {
                    return $user->role->label();
                }
            } catch (\Throwable $e) {
            }

            return match ($user->role instanceof \BackedEnum ? $user->role->value : $user->role) {
                'superadmin' => 'Superadmin',
                'operator_dukcapil' => 'Operator Dinas Dukcapil',
                'operator_dinas_sosial' => 'Operator Dinas Sosial',
                'operator_dinas_pendidikan' => 'Operator Dinas Pendidikan',
                'operator_dinas_kesehatan' => 'Operator Dinas Kesehatan',
                'operator_parsepor' => 'Operator Dinas Pariwisata, Pemuda dan Olahraga',
                'operator_kabupaten' => 'Operator Kabupaten',
                'mahasiswa' => 'Mahasiswa',
                default => 'Pengguna Sistem',
            };
        };

        /*
    |--------------------------------------------------------------------------
    | LABEL EVENT
    |--------------------------------------------------------------------------
    */
        $eventLabel = function ($event) {
            return match ($event) {
                'created', 'create' => 'Menambahkan',
                'updated', 'update' => 'Mengubah',
                'deleted', 'delete' => 'Menghapus',
                default => ucwords(str_replace(['_', '-'], ' ', $event ?? 'Aktivitas')),
            };
        };

        /*
    |--------------------------------------------------------------------------
    | LABEL DATA
    |--------------------------------------------------------------------------
    */
        $dataLabel = function ($type) {
            return match (true) {
                str_ends_with($type ?? '', 'MahasiswaProfile') => 'Profil Mahasiswa',

                str_ends_with($type ?? '', 'Pendaftaran') => 'Pendaftaran Beasiswa',

                str_ends_with($type ?? '', 'Dokumen') => 'Dokumen',

                str_ends_with($type ?? '', 'Pendidikan') => 'Data Pendidikan',

                str_ends_with($type ?? '', 'Prestasi') => 'Data Prestasi',

                str_ends_with($type ?? '', 'OrangTua') => 'Data Orang Tua',

                str_ends_with($type ?? '', 'Application') => 'Pengajuan Beasiswa',

                str_ends_with($type ?? '', 'User') => 'Akun Pengguna',

                default => class_basename($type ?? 'Data'),
            };
        };

        /*
    |--------------------------------------------------------------------------
    | LABEL FIELD
    |--------------------------------------------------------------------------
    */
        $fieldLabel = function ($field) {
            return match ($field) {
                'status_kependudukan' => 'Status Kependudukan',

                'nik' => 'NIK',

                'nim' => 'NIM',

                'phone' => 'Nomor HP',

                'universitas' => 'Perguruan Tinggi',

                'program_studi' => 'Program Studi',

                'semester' => 'Semester',

                'ipk' => 'IPK',

                'alamat' => 'Alamat',

                'village_id' => 'Desa / Kelurahan',

                'penghasilan_keluarga' => 'Penghasilan Keluarga',

                'jumlah_tanggungan' => 'Jumlah Tanggungan',

                'desil_sosial' => 'Desil Sosial',

                'desil_pendidikan' => 'Desil Pendidikan',

                'prestasi' => 'Prestasi',

                'status' => 'Status',

                'catatan' => 'Catatan',

                'nomor_pendaftaran' => 'Nomor Pendaftaran',

                'updated_at' => 'Terakhir Diperbarui',

                'created_at' => 'Dibuat Pada',

                default => ucwords(str_replace('_', ' ', $field)),
            };
        };

        /*
    |--------------------------------------------------------------------------
    | FORMAT VALUE
    |--------------------------------------------------------------------------
    */
        $formatValue = function ($value) {
            if ($value === null || $value === '') {
                return 'Tidak ada';
            }

            if (is_bool($value)) {
                return $value ? 'Ya' : 'Tidak';
            }

            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }

            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            return match ((string) $value) {
                'belum_diverifikasi' => 'Belum Diverifikasi',

                'terverifikasi' => 'Terverifikasi',

                'valid' => 'Valid',

                'tidak_valid' => 'Tidak Valid',

                'draft' => 'Draft',

                'DRAFT' => 'Draft',

                'VERIFIKASI_DINAS' => 'Verifikasi Dinas',

                'SELEKSI_KABUPATEN' => 'Seleksi Kabupaten',

                'DITERIMA' => 'Diterima',

                'DITOLAK' => 'Ditolak',

                default => (string) $value,
            };
        };
    @endphp


    <div class="space-y-8">

        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}

        <div>
            <p class="text-sm font-bold uppercase tracking-[0.25em] text-blue-700">
                Monitoring Sistem
            </p>

            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">
                Log Aktivitas
            </h1>

            <p class="mt-3 text-base text-slate-600">
                Pantau seluruh aktivitas operator dalam proses verifikasi,
                seleksi, dan perubahan data untuk memastikan setiap tindakan
                dilakukan sesuai ketentuan.
            </p>
        </div>


        {{-- ========================================================= --}}
        {{-- TAB --}}
        {{-- ========================================================= --}}

        <div class="mb-6 flex gap-2 rounded-xl bg-slate-100 p-1 w-fit">

            <a href="{{ request()->fullUrlWithQuery(['tab' => 'verification']) }}"
                class="{{ request('tab', 'verification') === 'verification'
                    ? 'rounded-lg bg-white px-5 py-2.5 font-semibold text-blue-600 shadow-sm'
                    : 'rounded-lg px-5 py-2.5 text-slate-500 hover:text-slate-700' }}">
                Verifikasi & Seleksi
            </a>

            <a href="{{ request()->fullUrlWithQuery(['tab' => 'audit']) }}"
                class="{{ request('tab') === 'audit'
                    ? 'rounded-lg bg-white px-5 py-2.5 font-semibold text-blue-600 shadow-sm'
                    : 'rounded-lg px-5 py-2.5 text-slate-500 hover:text-slate-700' }}">
                Perubahan Data
            </a>

        </div>


        {{-- ========================================================= --}}
        {{-- PENJELASAN JENIS LOG --}}
        {{-- ========================================================= --}}

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

            {{-- VERIFIKASI & SELEKSI --}}
            <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-5">
                <div class="flex items-start gap-3">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
                        ✓
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-blue-900">
                            Verifikasi & Seleksi
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Mencatat tindakan operator dalam proses verifikasi
                            dan seleksi pengajuan beasiswa. Superadmin dapat
                            memantau keputusan <strong>MS (Memenuhi Syarat)</strong>
                            dan <strong>TMS (Tidak Memenuhi Syarat)</strong>
                            yang diberikan operator serta waktu dan data
                            pengajuan yang diproses.
                        </p>
                    </div>

                </div>
            </div>


            {{-- PERUBAHAN DATA --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-start gap-3">

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-700">
                        ↻
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-slate-900">
                            Perubahan Data
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Mencatat setiap tindakan operator terhadap data
                            dalam sistem. <strong>Menambahkan</strong> berarti
                            membuat data baru, <strong>Mengubah</strong> berarti
                            memperbarui data yang sudah ada, dan
                            <strong>Menghapus</strong> berarti menghapus data.
                            Superadmin dapat melihat data sebelum dan sesudah
                            perubahan untuk keperluan pengawasan.
                        </p>
                    </div>

                </div>
            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- TAB VERIFIKASI --}}
        {{-- ========================================================= --}}

        <div id="verification-tab">

            {{-- FILTER --}}

            <form method="GET" action="{{ route('superadmin.log-aktivitas.index') }}" class="mb-6">

                <input type="hidden" name="tab" value="verification">

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1fr_auto]">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Cari
                        </label>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Nama mahasiswa / nomor pendaftaran"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Aktivitas
                        </label>

                        <select name="activity"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="">
                                Semua aktivitas
                            </option>

                            <option value="submitted" @selected(request('activity') === 'submitted')>
                                Mengajukan Beasiswa
                            </option>

                            <option value="ms" @selected(request('activity') === 'ms')>
                                Verifikasi MS
                            </option>

                            <option value="tms" @selected(request('activity') === 'tms')>
                                Verifikasi TMS
                            </option>

                        </select>
                    </div>


                    <div class="flex items-end">

                        <button type="submit"
                            class="w-full rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 lg:w-auto">
                            Filter
                        </button>

                    </div>

                </div>

            </form>


            {{-- TABLE VERIFIKASI --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Waktu
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Pengguna
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Mahasiswa
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Aktivitas
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Perubahan Status
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Keterangan
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @forelse($verificationLogs as $log)
                                @php

                                    $actor = $log->actor;

                                    $application = $log->application;

                                    $mahasiswa = $application?->mahasiswa;

                                    $pendaftaran = $application?->pendaftaran;

                                    $namaMahasiswa = $mahasiswa?->name ?? 'Mahasiswa tidak tersedia';

                                    $nomorPendaftaran =
                                        $application?->nomor_pengajuan ??
                                        ($pendaftaran?->nomor_pendaftaran ?? 'Nomor tidak tersedia');

                                    $activity = $log->activity_label ?? 'Aktivitas Verifikasi';

                                @endphp


                                <tr class="hover:bg-slate-50">

                                    {{-- WAKTU --}}

                                    <td class="whitespace-nowrap px-6 py-5">

                                        <p class="font-semibold text-slate-800">
                                            {{ $log->created_at?->format('d M Y') }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $log->created_at?->format('H:i:s') }}
                                        </p>

                                    </td>


                                    {{-- PENGGUNA --}}

                                    <td class="px-6 py-5">

                                        <p class="font-semibold text-slate-800">
                                            {{ $actor?->name ?? 'Sistem' }}
                                        </p>

                                        <span
                                            class="mt-2 inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                            {{ $roleLabel($actor) }}
                                        </span>

                                    </td>


                                    {{-- MAHASISWA --}}

                                    <td class="px-6 py-5">

                                        <p class="font-semibold text-slate-800">
                                            {{ $namaMahasiswa }}
                                        </p>

                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ $nomorPendaftaran }}
                                        </p>

                                    </td>


                                    {{-- AKTIVITAS --}}

                                    <td class="px-6 py-5">

                                        <span
                                            class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">

                                            {{ $activity }}

                                        </span>

                                    </td>


                                    {{-- STATUS --}}

                                    <td class="px-6 py-5">

                                        <div class="flex flex-wrap items-center gap-2 text-sm">

                                            @if ($log->from_status)
                                                <span class="rounded-lg bg-slate-100 px-3 py-1 text-slate-600">
                                                    {{ $formatValue($log->from_status) }}
                                                </span>
                                            @endif


                                            @if ($log->from_status || $log->to_status)
                                                <span class="text-slate-400">
                                                    →
                                                </span>
                                            @endif


                                            @if ($log->to_status)
                                                <span class="rounded-lg bg-blue-50 px-3 py-1 font-medium text-blue-700">
                                                    {{ $formatValue($log->to_status) }}
                                                </span>
                                            @endif

                                        </div>

                                    </td>


                                    {{-- KETERANGAN --}}

                                    <td class="max-w-xs px-6 py-5 text-sm text-slate-600">

                                        {{ $log->notes ?? '—' }}

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                        Belum ada aktivitas verifikasi atau seleksi.
                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- PAGINATION --}}

                @if ($verificationLogs->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">

                        {{ $verificationLogs->links() }}

                    </div>
                @endif

            </div>

        </div>



        {{-- ========================================================= --}}
        {{-- TAB AUDIT --}}
        {{-- ========================================================= --}}

        <div id="audit-tab" class="hidden">

            {{-- FILTER --}}

            <form method="GET" action="{{ route('superadmin.log-aktivitas.index') }}" class="mb-6">

                <input type="hidden" name="tab" value="audit">

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_1fr_1fr_auto]">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Operator
                        </label>

                        <select name="operator"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

                            <option value="">Semua operator</option>

                            @foreach ($operators as $operator)
                                <option value="{{ $operator->id }}"
                                    @selected((string) request('operator') === (string) $operator->id)>
                                    {{ $operator->name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Cari
                        </label>

                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Nama pengguna / aktivitas"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Aktivitas
                        </label>

                        <select name="event"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">

                            <option value="">
                                Semua aktivitas
                            </option>

                            <option value="created" @selected(request('event') === 'created')>
                                Menambahkan
                            </option>

                            <option value="updated" @selected(request('event') === 'updated')>
                                Mengubah
                            </option>

                            <option value="deleted" @selected(request('event') === 'deleted')>
                                Menghapus
                            </option>

                        </select>

                    </div>


                    <div class="flex items-end">

                        <button type="submit"
                            class="w-full rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 lg:w-auto">
                            Filter
                        </button>

                    </div>

                </div>

            </form>


            {{-- TABLE AUDIT --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Waktu
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Pengguna
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Aktivitas
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Data
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    IP Address
                                </th>

                                <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Detail
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @forelse($auditLogs as $log)
                                @php

                                    $user = $log->user;

                                    $studentInfo = $log->student_info ?? null;

                                    $namaUser = $user?->name ?? 'Aktivitas Sistem';

                                    $roleUser = $roleLabel($user);

                                    $activity = $log->event_label ?? $eventLabel($log->event);

                                    $data = $log->data_label ?? $dataLabel($log->auditable_type);

                                    $browser = $detectBrowser($log->user_agent);

                                    $auditModalData = [
                                        'user_name' => $namaUser,
                                        'user_role' => $roleUser,
                                        'user_id' => $log->user_id,

                                        'student_name' => $studentInfo['name'] ?? null,
                                        'student_nim' => $studentInfo['nim'] ?? null,
                                        'student_nik' => $studentInfo['nik'] ?? null,
                                        'nomor_pendaftaran' => $studentInfo['nomor_pendaftaran'] ?? null,

                                        'activity' => $activity,
                                        'data_label' => $data,
                                        'change_summary' => $log->change_summary ?? [],

                                        'ip_address' => $log->ip_address ?? null,
                                        'browser' => $browser,
                                        'user_agent' => $log->user_agent ?? null,

                                        'created_at' => $log->created_at?->format('d F Y, H:i:s'),

                                        'old_values' => $log->old_values ?? [],
                                        'new_values' => $log->new_values ?? [],

                                        'auditable_id' => $log->auditable_id,
                                        'auditable_type' => $log->auditable_type,
                                    ];

                                @endphp


                                <tr class="hover:bg-slate-50">

                                    {{-- WAKTU --}}

                                    <td class="whitespace-nowrap px-6 py-5">

                                        <p class="font-semibold text-slate-800">
                                            {{ $log->created_at?->format('d M Y') }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $log->created_at?->format('H:i:s') }}
                                        </p>

                                    </td>


                                    {{-- PENGGUNA --}}

                                    <td class="px-6 py-5">

                                        <p class="font-semibold text-slate-800">
                                            {{ $namaUser }}
                                        </p>

                                        <span
                                            class="mt-2 inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                            {{ $roleUser }}
                                        </span>

                                    </td>


                                    {{-- AKTIVITAS --}}

                                    <td class="px-6 py-5">

                                        @if ($log->event === 'created')
                                            <span
                                                class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">
                                                Menambahkan
                                            </span>
                                        @elseif($log->event === 'deleted')
                                            <span
                                                class="inline-flex rounded-full border border-red-200 bg-red-50 px-3 py-1 text-sm font-medium text-red-700">
                                                Menghapus
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                                                Mengubah
                                            </span>
                                        @endif

                                    </td>


                                    {{-- DATA --}}

                                    <td class="px-6 py-5">

                                        <p class="font-semibold text-slate-800">
                                            {{ $data }}
                                        </p>

                                        @if ($studentInfo)
                                            <p class="mt-1 text-sm text-slate-600">
                                                {{ $studentInfo['name'] ?? 'Mahasiswa' }}
                                            </p>

                                            @if (!empty($studentInfo['nomor_pendaftaran']))
                                                <p class="mt-1 text-xs text-slate-500">
                                                    {{ $studentInfo['nomor_pendaftaran'] }}
                                                </p>
                                            @endif
                                        @else
                                            <p class="mt-1 text-xs text-slate-400">
                                                Data sistem
                                            </p>
                                        @endif

                                    </td>


                                    {{-- IP ADDRESS --}}

                                    <td class="px-6 py-5">

                                        <span class="font-mono text-sm text-slate-600">
                                            {{ $log->ip_address ?? '—' }}
                                        </span>

                                    </td>


                                    {{-- DETAIL --}}

                                    <td class="px-6 py-5">

                                        <button type="button"
                                            data-audit="{{ base64_encode(json_encode($auditModalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
                                            onclick="openAuditModal(this)"
                                            class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 transition hover:text-blue-900">

                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />

                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 15.375a3.375 3.375 0 1 0 0-6.75 3.375 3.375 0 0 0 0 6.75z" />
                                            </svg>

                                            Lihat detail

                                        </button>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                        Belum ada aktivitas perubahan data.
                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- PAGINATION --}}

                @if ($auditLogs->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">

                        {{ $auditLogs->links() }}

                    </div>
                @endif

            </div>

        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- MODAL DETAIL AUDIT --}}
    {{-- ============================================================= --}}

    <div id="audit-modal" class="fixed inset-0 z-[9999] hidden" aria-hidden="true">

        {{-- BACKDROP --}}

        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" onclick="closeAuditModal()"></div>


        {{-- MODAL WRAPPER --}}

        <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">

            <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                onclick="event.stopPropagation()">

                {{-- HEADER --}}

                <div class="flex shrink-0 items-start justify-between border-b border-slate-200 px-6 py-5 sm:px-8">

                    <div>

                        <p class="text-sm font-bold uppercase tracking-wider text-blue-700">
                            Detail Aktivitas
                        </p>

                        <h2 class="mt-1 text-2xl font-bold text-slate-900">
                            Informasi Log Aktivitas
                        </h2>

                    </div>


                    <button type="button" onclick="closeAuditModal()"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup">

                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>

                    </button>

                </div>


                {{-- BODY --}}
                {{-- INI YANG MEMBUAT MODAL BISA SCROLL --}}

                <div class="min-h-0 flex-1 overflow-y-auto">

                    <div class="space-y-7 px-6 py-6 sm:px-8">


                        {{-- ================================================= --}}
                        {{-- INFORMASI UTAMA --}}
                        {{-- ================================================= --}}

                        <div class="grid grid-cols-1 gap-x-12 gap-y-6 md:grid-cols-2">

                            <div>

                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Dilakukan Oleh
                                </p>

                                <p id="audit-user-name" class="mt-2 text-lg font-semibold text-slate-900">
                                    —
                                </p>

                                <p id="audit-user-role" class="mt-1 text-sm text-slate-500">
                                    —
                                </p>

                            </div>


                            <div>

                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Data Mahasiswa
                                </p>

                                <div id="audit-student-info" class="mt-2">
                                    —
                                </div>

                            </div>


                            <div>

                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Aktivitas
                                </p>

                                <p id="audit-event" class="mt-2 text-base font-semibold text-slate-700">
                                    —
                                </p>

                            </div>


                            <div>

                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Waktu
                                </p>

                                <p id="audit-created-at" class="mt-2 text-base text-slate-700">
                                    —
                                </p>

                            </div>


                            <div>

                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Data Yang Diproses
                                </p>

                                <p id="audit-data" class="mt-2 text-base font-semibold text-slate-700">
                                    —
                                </p>

                            </div>


                            <div>

                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Alamat IP
                                </p>

                                <p id="audit-ip" class="mt-2 font-mono text-sm text-slate-700">
                                    —
                                </p>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- BROWSER --}}
                        {{-- ================================================= --}}

                        <div class="border-t border-slate-200 pt-6">

                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Perangkat & Browser
                            </p>

                            <p id="audit-browser" class="mt-2 font-semibold text-slate-800">
                                —
                            </p>

                            <div id="audit-user-agent"
                                class="mt-3 break-all rounded-xl bg-slate-50 p-4 font-mono text-xs leading-6 text-slate-600">
                                —
                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- RINGKASAN PERUBAHAN --}}
                        {{-- ================================================= --}}

                        <div class="border-t border-slate-200 pt-6">

                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Ringkasan Perubahan
                            </p>

                            <div id="audit-change-summary"
                                class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- DATA SEBELUM --}}
                        {{-- ================================================= --}}

                        <div class="border-t border-slate-200 pt-6">

                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Data Sebelum Perubahan
                            </p>

                            <div id="audit-old-values" class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- DATA SESUDAH --}}
                        {{-- ================================================= --}}

                        <div class="border-t border-slate-200 pt-6">

                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                Data Setelah Perubahan
                            </p>

                            <div id="audit-new-values" class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                            </div>

                        </div>


                        {{-- FOOTER --}}

                        <div class="flex shrink-0 justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">

                            <button type="button" onclick="closeAuditModal()"
                                class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Tutup
                            </button>

                        </div>

                    </div>

                </div>

            </div>



            {{-- ============================================================= --}}
            {{-- JAVASCRIPT --}}
            {{-- ============================================================= --}}

            <script>
                /*
                |--------------------------------------------------------------------------
                | TAB
                |--------------------------------------------------------------------------
                */

                function showTab(tab) {

                    const verificationTab =
                        document.getElementById('verification-tab');

                    const auditTab =
                        document.getElementById('audit-tab');

                    const verificationButton =
                        document.getElementById('tab-verification');

                    const auditButton =
                        document.getElementById('tab-audit');


                    if (tab === 'verification') {

                        verificationTab.classList.remove('hidden');

                        auditTab.classList.add('hidden');


                        verificationButton.classList.add(
                            'border-blue-600',
                            'text-blue-700'
                        );

                        verificationButton.classList.remove(
                            'border-transparent',
                            'text-slate-500'
                        );


                        auditButton.classList.remove(
                            'border-blue-600',
                            'text-blue-700'
                        );

                        auditButton.classList.add(
                            'border-transparent',
                            'text-slate-500'
                        );

                    } else {

                        verificationTab.classList.add('hidden');

                        auditTab.classList.remove('hidden');


                        auditButton.classList.add(
                            'border-blue-600',
                            'text-blue-700'
                        );

                        auditButton.classList.remove(
                            'border-transparent',
                            'text-slate-500'
                        );


                        verificationButton.classList.remove(
                            'border-blue-600',
                            'text-blue-700'
                        );

                        verificationButton.classList.add(
                            'border-transparent',
                            'text-slate-500'
                        );

                    }
                }


                /*
                |--------------------------------------------------------------------------
                | FORMAT VALUE DI JAVASCRIPT
                |--------------------------------------------------------------------------
                */

                function formatAuditValue(value) {

                    if (
                        value === null ||
                        value === undefined ||
                        value === ''
                    ) {
                        return 'Tidak ada';
                    }


                    if (typeof value === 'boolean') {
                        return value ? 'Ya' : 'Tidak';
                    }


                    if (typeof value === 'object') {

                        return JSON.stringify(
                            value,
                            null,
                            2
                        );
                    }


                    const labels = {

                        'belum_diverifikasi': 'Belum Diverifikasi',

                        'terverifikasi': 'Terverifikasi',

                        'sesuai': 'Sesuai',

                        'tidak_sesuai': 'Tidak Sesuai',

                        'valid': 'Valid',

                        'tidak_valid': 'Tidak Valid',

                        'ms': 'Memenuhi Syarat (MS)',

                        'tms': 'Tidak Memenuhi Syarat (TMS)',

                        'draft': 'Draft',

                        'DRAFT': 'Draft',

                        'VERIFIKASI_DINAS': 'Verifikasi Dinas',

                        'SELEKSI_KABUPATEN': 'Seleksi Kabupaten',

                        'DITERIMA': 'Diterima',

                        'DITOLAK': 'Ditolak',

                    };


                    return labels[value] ?? String(value);
                }


                /*
                |--------------------------------------------------------------------------
                | RENDER DATA
                |--------------------------------------------------------------------------
                */

                function renderAuditValues(
                    elementId,
                    values,
                    emptyText
                ) {

                    const container =
                        document.getElementById(elementId);

                    container.innerHTML = '';

                    if (
                        !values ||
                        typeof values !== 'object'
                    ) {
                        container.innerHTML = `
                <div class="bg-slate-50 px-4 py-4 text-sm text-slate-500">
                    ${emptyText}
                </div>
            `;
                        return;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Jangan tampilkan field teknis/database.
                    |--------------------------------------------------------------------------
                    */

                    const ignoredFields = [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ];

                    const entries = Object.entries(values)
                        .filter(([field]) => !ignoredFields.includes(field));

                    if (entries.length === 0) {

                        container.innerHTML = `
                <div class="bg-slate-50 px-4 py-4 text-sm text-slate-500">
                    ${emptyText}
                </div>
            `;

                        return;
                    }

                    const wrapper =
                        document.createElement('div');

                    wrapper.className =
                        'divide-y divide-slate-200';

                    entries.forEach(
                        ([field, value]) => {

                            const row =
                                document.createElement('div');

                            row.className =
                                'grid grid-cols-1 sm:grid-cols-[220px_1fr]';

                            const label =
                                document.createElement('div');

                            label.className =
                                'bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600';

                            label.textContent =
                                humanizeField(field);

                            const content =
                                document.createElement('div');

                            content.className =
                                'break-words px-4 py-3 text-sm text-slate-700';

                            const formatted =
                                formatAuditValue(value);

                            if (
                                typeof value === 'object' &&
                                value !== null
                            ) {

                                const pre =
                                    document.createElement('pre');

                                pre.className =
                                    'whitespace-pre-wrap break-words font-mono text-xs leading-5 text-slate-600';

                                pre.textContent =
                                    formatted;

                                content.appendChild(pre);

                            } else {

                                content.textContent =
                                    formatted;
                            }

                            row.appendChild(label);
                            row.appendChild(content);

                            wrapper.appendChild(row);
                        }
                    );

                    container.appendChild(wrapper);
                }


                /*
                |--------------------------------------------------------------------------
                | FIELD LABEL
                |--------------------------------------------------------------------------
                */

                function humanizeField(field) {

                    const labels = {

                        'status_kependudukan': 'Status Kependudukan',

                        'nik': 'NIK',

                        'nim': 'NIM',

                        'phone': 'Nomor HP',

                        'universitas': 'Perguruan Tinggi',

                        'program_studi': 'Program Studi',

                        'semester': 'Semester',

                        'ipk': 'IPK',

                        'alamat': 'Alamat',

                        'village_id': 'Desa / Kelurahan',

                        'penghasilan_keluarga': 'Penghasilan Keluarga',

                        'jumlah_tanggungan': 'Jumlah Tanggungan',

                        'desil_sosial': 'Desil Sosial',

                        'desil_pendidikan': 'Desil Pendidikan',

                        'prestasi': 'Prestasi',

                        'status': 'Status',

                        'catatan': 'Catatan',

                        'nomor_pendaftaran': 'Nomor Pendaftaran',

                        'updated_at': 'Terakhir Diperbarui',

                        'created_at': 'Dibuat Pada',

                    };


                    if (labels[field]) {
                        return labels[field];
                    }


                    return field
                        .replaceAll('_', ' ')
                        .replace(/\b\w/g, char => char.toUpperCase());
                }


                /*
                |--------------------------------------------------------------------------
                | RENDER RINGKASAN
                |--------------------------------------------------------------------------
                */

                function renderChangeSummary(summary) {

                    const container =
                        document.getElementById(
                            'audit-change-summary'
                        );


                    container.innerHTML = '';


                    if (
                        !summary ||
                        summary.length === 0
                    ) {

                        container.innerHTML = `
                <div class="bg-slate-50 px-4 py-4 text-sm text-slate-500">
                    Tidak ada perubahan data yang tercatat.
                </div>
            `;

                        return;
                    }


                    const wrapper =
                        document.createElement('div');

                    wrapper.className =
                        'divide-y divide-slate-200';


                    summary.forEach(item => {

                        const row =
                            document.createElement('div');

                        row.className =
                            'grid grid-cols-1 gap-4 px-4 py-4 md:grid-cols-[220px_1fr_1fr]';


                        const field =
                            document.createElement('div');

                        field.className =
                            'font-semibold text-slate-700';

                        field.textContent =
                            item.field ?? 'Data';


                        const before =
                            document.createElement('div');

                        before.innerHTML = `
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Sebelum
                </p>
                <p class="text-sm text-slate-600">
                    ${escapeHtml(item.before ?? 'Tidak ada')}
                </p>
            `;


                        const after =
                            document.createElement('div');

                        after.innerHTML = `
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Sesudah
                </p>
                <p class="text-sm font-semibold text-slate-800">
                    ${escapeHtml(item.after ?? 'Tidak ada')}
                </p>
            `;


                        row.appendChild(field);

                        row.appendChild(before);

                        row.appendChild(after);

                        wrapper.appendChild(row);

                    });


                    container.appendChild(wrapper);
                }


                /*
                |--------------------------------------------------------------------------
                | ESCAPE HTML
                |--------------------------------------------------------------------------
                */

                function escapeHtml(value) {

                    return String(value)
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');
                }


                /*
                |--------------------------------------------------------------------------
                | OPEN MODAL
                |--------------------------------------------------------------------------
                */

                function openAuditModal(button) {

                    let data = {};

                    try {
                        const base64 = button.dataset.audit;

                        const binary = atob(base64);

                        const bytes = Uint8Array.from(
                            binary,
                            char => char.charCodeAt(0)
                        );

                        const json = new TextDecoder('utf-8').decode(bytes);

                        data = JSON.parse(json);

                    } catch (error) {
                        console.error('Gagal membaca data log aktivitas:', error);

                        alert('Detail aktivitas tidak dapat dibuka.');

                        return;
                    }

                    document.getElementById(
                            'audit-user-name'
                        ).textContent =
                        data.user_name || 'Aktivitas Sistem';


                    document.getElementById(
                            'audit-user-role'
                        ).textContent =
                        data.user_role || 'Pengguna Sistem';
                    document.getElementById(
                            'audit-event'
                        ).textContent =
                        data.activity || 'Aktivitas';


                    document.getElementById(
                            'audit-created-at'
                        ).textContent =
                        data.created_at || '—';


                    document.getElementById(
                            'audit-data'
                        ).textContent =
                        data.data_label || '—';
                    document.getElementById(
                            'audit-ip'
                        ).textContent =
                        data.ip_address || '—';


                    document.getElementById(
                            'audit-browser'
                        ).textContent =
                        data.browser || 'Browser tidak diketahui';


                    document.getElementById(
                            'audit-user-agent'
                        ).textContent =
                        data.user_agent || 'User Agent tidak tersedia';


                    /*
                    |--------------------------------------------------------------------------
                    | DATA MAHASISWA
                    |--------------------------------------------------------------------------
                    */

                    const studentContainer =
                        document.getElementById(
                            'audit-student-info'
                        );


                    if (data.student_name) {

                        studentContainer.innerHTML = `

                <p class="text-lg font-semibold text-slate-900">
                    ${escapeHtml(data.student_name)}
                </p>

                ${
                    data.student_nim
                    ? `
                                    <p class="mt-1 text-sm text-slate-600">
                                        NIM: ${escapeHtml(data.student_nim)}
                                    </p>
                                `
                    : ''
                }

                ${
                    data.student_nik
                    ? `
                                    <p class="mt-1 text-sm text-slate-600">
                                        NIK: ${escapeHtml(data.student_nik)}
                                    </p>
                                `
                    : ''
                }

                ${
                    data.nomor_pendaftaran
                    ? `
                                    <p class="mt-1 text-sm font-medium text-blue-700">
                                        No. Pendaftaran:
                                        ${escapeHtml(data.nomor_pendaftaran)}
                                    </p>
                                `
                    : ''
                }

            `;

                    } else {

                        studentContainer.innerHTML = `

                <p class="text-base font-semibold text-slate-700">
                    Tidak terkait mahasiswa
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Log ini tidak memiliki hubungan langsung dengan data mahasiswa.
                </p>

            `;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SUMMARY
                    |--------------------------------------------------------------------------
                    */

                    renderChangeSummary(
                        data.change_summary
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | OLD VALUES
                    |--------------------------------------------------------------------------
                    */

                    renderAuditValues(
                        'audit-old-values',
                        data.old_values,
                        'Tidak ada data sebelum perubahan.'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | NEW VALUES
                    |--------------------------------------------------------------------------
                    */

                    renderAuditValues(
                        'audit-new-values',
                        data.new_values,
                        'Tidak ada data setelah perubahan.'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | SHOW MODAL
                    |--------------------------------------------------------------------------
                    */

                    const modal =
                        document.getElementById(
                            'audit-modal'
                        );


                    modal.classList.remove('hidden');

                    modal.setAttribute(
                        'aria-hidden',
                        'false'
                    );


                    /*
                    | Jangan biarkan halaman belakang
                    | ikut scroll.
                    */

                    document.body.classList.add(
                        'overflow-hidden'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | CLOSE MODAL
                |--------------------------------------------------------------------------
                */

                function closeAuditModal() {

                    const modal =
                        document.getElementById(
                            'audit-modal'
                        );


                    modal.classList.add('hidden');

                    modal.setAttribute(
                        'aria-hidden',
                        'true'
                    );


                    document.body.classList.remove(
                        'overflow-hidden'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | ESC UNTUK TUTUP MODAL
                |--------------------------------------------------------------------------
                */

                document.addEventListener(
                    'keydown',
                    function(event) {

                        if (
                            event.key === 'Escape'
                        ) {
                            closeAuditModal();
                        }

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | TAB DEFAULT
                |--------------------------------------------------------------------------
                */

                document.addEventListener(
                    'DOMContentLoaded',
                    function() {

                        const params =
                            new URLSearchParams(
                                window.location.search
                            );


                        if (
                            params.get('tab') === 'audit'
                        ) {

                            showTab('audit');

                        } else {

                            showTab('verification');

                        }

                    }
                );
            </script>

        @endsection
