<?php

return [
    'name' => env('KHM_PROGRAM_NAME', 'Kartu Hebat Mahasiswa'),
    'government' => env('KHM_GOVERNMENT_NAME', 'Pemerintah Kabupaten Murung Raya'),
    'kabupaten_code' => env('KHM_KABUPATEN_CODE', '6212'),
    'current_period' => env('KHM_CURRENT_PERIOD', '2026/2027 Ganjil'),
    'registration_open' => env('KHM_REGISTRATION_OPEN', '2026-08-01'),
    'registration_close' => env('KHM_REGISTRATION_CLOSE', '2026-11-30'),
    'quota' => (int) env('KHM_QUOTA_AKADEMIK', 250)
        + (int) env('KHM_QUOTA_TIDAK_MAMPU', 250)
        + (int) env('KHM_QUOTA_DISABILITAS', 50)
        + (int) env('KHM_QUOTA_NON_AKADEMIK', 30),
    'quotas' => [
        'AKADEMIK' => (int) env('KHM_QUOTA_AKADEMIK', 250),
        'TIDAK_MAMPU' => (int) env('KHM_QUOTA_TIDAK_MAMPU', 250),
        'DISABILITAS' => (int) env('KHM_QUOTA_DISABILITAS', 50),
        'NON_AKADEMIK' => (int) env('KHM_QUOTA_NON_AKADEMIK', 30),
    ],
    'scoring' => [
        'academic_max_semester' => (int) env('KHM_ACADEMIC_MAX_SEMESTER', 8),
        'disability_weights' => [
            'ipk' => (int) env('KHM_DISABILITY_WEIGHT_IPK', 50),
            'semester' => (int) env('KHM_DISABILITY_WEIGHT_SEMESTER', 15),
            'disability_grade' => (int) env('KHM_DISABILITY_WEIGHT_GRADE', 20),
            'disability_type' => (int) env('KHM_DISABILITY_WEIGHT_TYPE', 15),
        ],
        'non_academic_weights' => [
            'ipk' => (int) env('KHM_NON_ACADEMIC_WEIGHT_IPK', 25),
            'achievement_level' => (int) env('KHM_NON_ACADEMIC_WEIGHT_LEVEL', 45),
            'achievement_rank' => (int) env('KHM_NON_ACADEMIC_WEIGHT_RANK', 30),
        ],
        'non_academic_rubric' => [
            'tingkat' => [
                'internasional' => (int) env('KHM_NON_ACADEMIC_LEVEL_INTERNASIONAL', 100),
                'nasional' => (int) env('KHM_NON_ACADEMIC_LEVEL_NASIONAL', 80),
                'provinsi' => (int) env('KHM_NON_ACADEMIC_LEVEL_PROVINSI', 60),
                'kabupaten' => (int) env('KHM_NON_ACADEMIC_LEVEL_KABUPATEN', 40),
                'kampus' => (int) env('KHM_NON_ACADEMIC_LEVEL_KAMPUS', 20),
            ],
            'peringkat' => [
                'juara_1' => (int) env('KHM_NON_ACADEMIC_RANK_1', 100),
                'juara_2' => (int) env('KHM_NON_ACADEMIC_RANK_2', 85),
                'juara_3' => (int) env('KHM_NON_ACADEMIC_RANK_3', 70),
                'favorit' => (int) env('KHM_NON_ACADEMIC_RANK_FAVORIT', 50),
                'peserta' => (int) env('KHM_NON_ACADEMIC_RANK_PESERTA', 20),
            ],
        ],
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
        'kesehatan' => 'Dinas Kesehatan',
        'parsepor' => 'Dinas Parsepor',
    ],
];
