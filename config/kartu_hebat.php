<?php

return [
    'name' => env('KHM_PROGRAM_NAME', 'Kartu Hebat Mahasiswa'),
    'government' => env('KHM_GOVERNMENT_NAME', 'Pemerintah Kabupaten Murung Raya'),
    'kabupaten_code' => env('KHM_KABUPATEN_CODE', '6212'),
    'current_period' => env('KHM_CURRENT_PERIOD', '2026/2027 Ganjil'),
    'registration_open' => env('KHM_REGISTRATION_OPEN', '2026-08-01'),
    'registration_close' => env('KHM_REGISTRATION_CLOSE', '2026-11-30'),
    'quota' => (int) env('KHM_QUOTA_AKADEMIK', 250) + (int) env('KHM_QUOTA_TIDAK_MAMPU', 250),
    'quotas' => [
        'AKADEMIK' => (int) env('KHM_QUOTA_AKADEMIK', 250),
        'TIDAK_MAMPU' => (int) env('KHM_QUOTA_TIDAK_MAMPU', 250),
    ],
    'scoring' => [
        'academic_max_semester' => (int) env('KHM_ACADEMIC_MAX_SEMESTER', 8),
    ],
    'document_disk' => env('KHM_DOCUMENT_DISK', 'local'),
    'max_document_kb' => (int) env('KHM_MAX_DOCUMENT_KB', 2048),
    'integration' => [
        'default_application_type' => env('KHM_DEFAULT_APPLICATION_TYPE', 'AKADEMIK'),
    ],
    'agencies' => [
        'dukcapil' => 'Dinas Kependudukan dan Pencatatan Sipil',
        'sosial' => 'Dinas Sosial',
        'pendidikan' => 'Dinas Pendidikan dan Kebudayaan',
    ],
];
