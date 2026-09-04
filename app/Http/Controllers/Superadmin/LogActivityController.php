<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\VerificationLog;
use App\Models\Pendaftaran;
use App\Models\AgencyVerification;
use App\Models\DocumentVerification;
use App\Models\User;
use Illuminate\Http\Request;

class LogActivityController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VERIFIKASI & SELEKSI
        |--------------------------------------------------------------------------
        */

        $verificationQuery = VerificationLog::query()
            ->with([
                'actor',
                'application.mahasiswa.profile',
                'application.pendaftaran.jalurBeasiswa',
            ]);

        /*
        |--------------------------------------------------------------------------
        | FILTER OPERATOR
        |--------------------------------------------------------------------------
        */

        if ($request->filled('operator')) {
            $verificationQuery->where(
                'actor_id',
                $request->operator
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH MAHASISWA / NOMOR PENDAFTARAN
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $verificationQuery->where(function ($query) use ($search) {

                $query->whereHas(
                    'application.mahasiswa',
                    function ($q) use ($search) {
                        $q->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    }
                );

                $query->orWhereHas(
                    'application.pendaftaran',
                    function ($q) use ($search) {
                        $q->where(
                            'nomor_pendaftaran',
                            'like',
                            "%{$search}%"
                        );
                    }
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER AKTIVITAS VERIFIKASI
        |--------------------------------------------------------------------------
        */

        if ($request->filled('activity')) {

            match ($request->activity) {

                /*
                |--------------------------------------------------------------
                | PENGAJUAN
                |--------------------------------------------------------------
                */

                'submitted' => $verificationQuery->whereIn(
                    'action',
                    [
                        'submitted',
                        'submit',
                        'diajukan',
                    ]
                ),

                /*
                |--------------------------------------------------------------
                | MS
                |--------------------------------------------------------------
                */

                'ms' => $verificationQuery->whereIn(
                    'action',
                    [
                        'ms',
                        'agency_verification_ms',
                        'verification_ms',
                        'verify_ms',
                        'verifikasi_ms',
                        'verified_ms',
                        'approve',
                        'approved',
                        'approve_ms',
                        'approved_ms',
                    ]
                ),

                /*
                |--------------------------------------------------------------
                | TMS
                |--------------------------------------------------------------
                */

                'tms' => $verificationQuery->whereIn(
                    'action',
                    [
                        'tms',
                        'agency_verification_tms',
                        'verification_tms',
                        'verify_tms',
                        'verifikasi_tms',
                        'verified_tms',
                        'reject',
                        'rejected',
                        'reject_tms',
                        'rejected_tms',
                    ]
                ),

                /*
                |--------------------------------------------------------------
                | SELEKSI
                |--------------------------------------------------------------
                */

                'selected' => $verificationQuery->whereIn(
                    'action',
                    [
                        'selected',
                        'selection',
                        'seleksi',
                        'seleksi_kabupaten',
                        'selection_decision_recorded',
                        'selection_result_published',
                    ]
                ),

                default => null,
            };
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION VERIFIKASI
        |--------------------------------------------------------------------------
        */

        $verificationLogs = $verificationQuery
            ->latest('created_at')
            ->paginate(
                15,
                ['*'],
                'verification_page'
            )
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | LABEL AKTIVITAS VERIFIKASI
        |--------------------------------------------------------------------------
        */

        $verificationLogs
            ->getCollection()
            ->transform(function ($log) {

                $log->activity_label =
                    $this->verificationActivityLabel(
                        $log->action,
                        $log->actor,
                        $log->metadata ?? []
                    );

                return $log;
            });


        /*
        |--------------------------------------------------------------------------
        | AUDIT PERUBAHAN DATA
        |--------------------------------------------------------------------------
        */

        $auditQuery = AuditLog::query()
            ->with([
                'user',
                'auditable',
            ]);

        /*
        |--------------------------------------------------------------------------
        | FILTER OPERATOR
        |--------------------------------------------------------------------------
        */

        if ($request->filled('operator')) {
            $auditQuery->where(
                'user_id',
                $request->operator
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $auditQuery->where(function ($query) use ($search) {

                /*
                | Cari nama operator
                */

                $query->whereHas(
                    'user',
                    function ($q) use ($search) {
                        $q->where(
                            'name',
                            'like',
                            "%{$search}%"
                        );
                    }
                );

                /*
                | Cari jenis aktivitas
                */

                $query->orWhere(
                    'event',
                    'like',
                    "%{$search}%"
                );

                /*
                | Cari nama class data
                */

                $query->orWhere(
                    'auditable_type',
                    'like',
                    "%{$search}%"
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER PERUBAHAN DATA
        |--------------------------------------------------------------------------
        */

        if ($request->filled('event')) {

            $auditQuery->where(
                'event',
                $request->event
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION AUDIT
        |--------------------------------------------------------------------------
        */

        $auditLogs = $auditQuery
            ->latest('created_at')
            ->paginate(
                15,
                ['*'],
                'audit_page'
            )
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | SIAPKAN INFORMASI AUDIT
        |--------------------------------------------------------------------------
        */

        $auditLogs
            ->getCollection()
            ->transform(function ($log) {

                /*
                |--------------------------------------------------------------------------
                | Default
                |--------------------------------------------------------------------------
                */

                $log->student_info = null;

                $log->document_verification_info = null;


                /*
                |--------------------------------------------------------------------------
                | 1. MAHASISWA PROFILE
                |--------------------------------------------------------------------------
                */

                if (
                    $log->auditable
                    &&
                    str_ends_with(
                        $log->auditable_type ?? '',
                        'MahasiswaProfile'
                    )
                ) {

                    $profile = $log->auditable;

                    $student = null;

                    if ($profile->user_id) {

                        $student = User::query()
                            ->find($profile->user_id);
                    }

                    if ($student) {

                        $pendaftaran = Pendaftaran::query()
                            ->where(
                                'user_id',
                                $student->id
                            )
                            ->latest('id')
                            ->first();

                        $log->student_info = [
                            'name' => $student->name,

                            'user_id' => $student->id,

                            'nik' => $profile->nik,

                            'nim' => $profile->nim,

                            'nomor_pendaftaran' =>
                                $pendaftaran?->nomor_pendaftaran,

                            'pendaftaran_id' =>
                                $pendaftaran?->id,
                        ];
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | 2. AGENCY VERIFICATION
                |--------------------------------------------------------------------------
                */

                if (
                    ! $log->student_info
                    &&
                    $log->auditable instanceof AgencyVerification
                ) {

                    $verification = $log->auditable;

                    $verification->loadMissing([
                        'application.mahasiswa.profile',
                        'application.pendaftaran',
                    ]);

                    $application =
                        $verification->application;

                    $student =
                        $application?->mahasiswa;

                    if ($student) {

                        $profile =
                            $student->profile;

                        $pendaftaran =
                            $application?->pendaftaran;

                        $log->student_info = [

                            'name' =>
                                $student->name,

                            'user_id' =>
                                $student->id,

                            'nik' =>
                                $profile?->nik,

                            'nim' =>
                                $profile?->nim,

                            'nomor_pendaftaran' =>
                                $application?->nomor_pengajuan
                                ??
                                $pendaftaran?->nomor_pendaftaran,

                            'pendaftaran_id' =>
                                $pendaftaran?->id,
                        ];
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | 3. DOCUMENT VERIFICATION
                |--------------------------------------------------------------------------
                |
                | INI YANG MEMBUAT LOG VERIFIKASI DOKUMEN
                | TERHUBUNG KE MAHASISWA.
                |
                */

                if (
                    ! $log->student_info
                    &&
                    $log->auditable instanceof DocumentVerification
                ) {

                    $verification =
                        $log->auditable;

                    /*
                    | Load hubungan:
                    | DocumentVerification
                    |      ↓
                    | Application
                    |      ↓
                    | Mahasiswa
                    |      ↓
                    | Profile
                    |      ↓
                    | Pendaftaran
                    */

                    $verification->loadMissing([
                        'application.mahasiswa.profile',
                        'application.pendaftaran',
                        'document.type',
                    ]);

                    $application =
                        $verification->application;

                    $student =
                        $application?->mahasiswa;

                    $profile =
                        $student?->profile;

                    $pendaftaran =
                        $application?->pendaftaran;


                    /*
                    |--------------------------------------------------------------
                    | DATA MAHASISWA
                    |--------------------------------------------------------------
                    */

                    if ($student) {

                        $log->student_info = [

                            'name' =>
                                $student->name,

                            'user_id' =>
                                $student->id,

                            'nik' =>
                                $profile?->nik,

                            'nim' =>
                                $profile?->nim,

                            'nomor_pendaftaran' =>
                                $application?->nomor_pengajuan
                                ??
                                $pendaftaran?->nomor_pendaftaran,

                            'pendaftaran_id' =>
                                $pendaftaran?->id,
                        ];
                    }


                    /*
                    |--------------------------------------------------------------
                    | DATA DOKUMEN
                    |--------------------------------------------------------------
                    */

                    $log->document_verification_info = [

                        'document_name' =>
                            $verification
                                ->document
                                ?->type
                                ?->name
                            ??
                            'Dokumen Beasiswa',

                        'stage' =>
                            $verification->stage,

                        'result' =>
                            $this->formatVerificationResult(
                                $verification->result
                            ),

                        'notes' =>
                            $verification->notes,
                    ];
                }


                /*
                |--------------------------------------------------------------------------
                | 4. SELECTION
                |--------------------------------------------------------------------------
                |
                | Kalau audit berasal dari Selection,
                | cari mahasiswa melalui Application.
                |
                */

                if (
                    ! $log->student_info
                    &&
                    str_ends_with(
                        $log->auditable_type ?? '',
                        'Selection'
                    )
                ) {

                    $selection =
                        $log->auditable;

                    if ($selection) {

                        $selection->loadMissing([
                            'application.mahasiswa.profile',
                            'application.pendaftaran',
                        ]);

                        $application =
                            $selection->application;

                        $student =
                            $application?->mahasiswa;

                        $profile =
                            $student?->profile;

                        $pendaftaran =
                            $application?->pendaftaran;

                        if ($student) {

                            $log->student_info = [

                                'name' =>
                                    $student->name,

                                'user_id' =>
                                    $student->id,

                                'nik' =>
                                    $profile?->nik,

                                'nim' =>
                                    $profile?->nim,

                                'nomor_pendaftaran' =>
                                    $application?->nomor_pengajuan
                                    ??
                                    $pendaftaran?->nomor_pendaftaran,

                                'pendaftaran_id' =>
                                    $pendaftaran?->id,
                            ];
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | 5. FALLBACK
                |--------------------------------------------------------------------------
                |
                | Kalau model mempunyai user_id,
                | coba cari mahasiswa dari user tersebut.
                |
                */

                if (! $log->student_info) {

                    $studentId =
                        $log->auditable?->user_id;

                    if ($studentId) {

                        $student = User::query()
                            ->find($studentId);

                        if ($student) {

                            $pendaftaran =
                                Pendaftaran::query()
                                    ->where(
                                        'user_id',
                                        $student->id
                                    )
                                    ->latest('id')
                                    ->first();

                            $log->student_info = [

                                'name' =>
                                    $student->name,

                                'user_id' =>
                                    $student->id,

                                'nik' =>
                                    $student->profile?->nik,

                                'nim' =>
                                    $student->profile?->nim,

                                'nomor_pendaftaran' =>
                                    $pendaftaran
                                    ?->nomor_pendaftaran,

                                'pendaftaran_id' =>
                                    $pendaftaran?->id,
                            ];
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | NAMA DATA YANG MANUSIAWI
                |--------------------------------------------------------------------------
                */

                $log->data_label =
                    $this->dataLabel(
                        $log->auditable_type
                    );


                /*
                |--------------------------------------------------------------------------
                | NAMA AKTIVITAS YANG MANUSIAWI
                |--------------------------------------------------------------------------
                */

                $log->event_label =
                    $this->eventLabel(
                        $log->event,
                        $log->old_values ?? [],
                        $log->new_values ?? [],
                        $log->auditable_type
                    );


                /*
                |--------------------------------------------------------------------------
                | RINGKASAN PERUBAHAN
                |--------------------------------------------------------------------------
                */

                $log->change_summary =
                    $this->changeSummary(
                        $log->old_values ?? [],
                        $log->new_values ?? [],
                        $log->event
                    );


                return $log;
            });


        /*
        |--------------------------------------------------------------------------
        | DAFTAR OPERATOR
        |--------------------------------------------------------------------------
        */

        $operators = User::query()
            ->whereIn('role', [

                'operator_dukcapil',

                'operator_dinas_sosial',

                'operator_dinas_pendidikan',

                'operator_dinas_kesehatan',

                'operator_parsepor',

                'operator_kabupaten',

            ])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'role',
            ]);


        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'superadmin.log-aktivitas.index',
            [
                'verificationLogs' =>
                    $verificationLogs,

                'auditLogs' =>
                    $auditLogs,

                'operators' =>
                    $operators,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LABEL AKTIVITAS VERIFIKASI / SELEKSI
    |--------------------------------------------------------------------------
    */

    private function verificationActivityLabel(
        ?string $action,
        $actor,
        array $metadata = []
    ): string {

        /*
        |--------------------------------------------------------------------------
        | ROLE OPERATOR
        |--------------------------------------------------------------------------
        */

        $role =
            $actor?->role;

        if ($role instanceof \BackedEnum) {
            $role =
                $role->value;
        }

        $agency = match ($role) {

            'operator_dukcapil'
                => 'Dukcapil',

            'operator_dinas_sosial'
                => 'Dinas Sosial',

            'operator_dinas_pendidikan'
                => 'Dinas Pendidikan',

            'operator_dinas_kesehatan'
                => 'Dinas Kesehatan',

            'operator_parsepor'
                => 'Parsepor',

            'operator_kabupaten'
                => 'Kabupaten',

            default
                => 'Sistem',
        };


        /*
        |--------------------------------------------------------------------------
        | NORMALISASI ACTION
        |--------------------------------------------------------------------------
        */

        $normalizedAction =
            is_string($action)
            ? strtolower(trim($action))
            : '';


        /*
        |--------------------------------------------------------------------------
        | KEPUTUSAN DARI METADATA
        |--------------------------------------------------------------------------
        */

        $decision =
            $metadata['decision']
            ??
            $metadata['keputusan']
            ??
            $metadata['result']
            ??
            $metadata['status_verifikasi']
            ??
            $metadata['manual_decision']
            ??
            null;

        if ($decision instanceof \BackedEnum) {
            $decision =
                $decision->value;
        }

        if (is_string($decision)) {

            $decision =
                strtolower(
                    trim($decision)
                );
        }


        /*
        |--------------------------------------------------------------------------
        | MS
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $normalizedAction,
                [
                    'ms',
                    'agency_verification_ms',
                    'verification_ms',
                    'verify_ms',
                    'verifikasi_ms',
                    'verified_ms',
                    'approve',
                    'approved',
                    'approve_ms',
                    'approved_ms',
                ],
                true
            )
            ||
            in_array(
                $decision,
                [
                    'ms',
                    'approve',
                    'approved',
                    'terverifikasi',
                    'verified',
                ],
                true
            )
        ) {

            return
                "Verifikasi {$agency} — MS";
        }


        /*
        |--------------------------------------------------------------------------
        | TMS
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $normalizedAction,
                [
                    'tms',
                    'agency_verification_tms',
                    'verification_tms',
                    'verify_tms',
                    'verifikasi_tms',
                    'verified_tms',
                    'reject',
                    'rejected',
                    'reject_tms',
                    'rejected_tms',
                ],
                true
            )
            ||
            in_array(
                $decision,
                [
                    'tms',
                    'reject',
                    'rejected',
                    'tidak_lulus',
                    'tidak_terverifikasi',
                ],
                true
            )
        ) {

            return
                "Verifikasi {$agency} — TMS";
        }


        /*
        |--------------------------------------------------------------------------
        | PENGAJUAN
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $normalizedAction,
                [
                    'submitted',
                    'submit',
                    'diajukan',
                ],
                true
            )
        ) {

            return 'Mengajukan Beasiswa';
        }


        /*
        |--------------------------------------------------------------------------
        | SELEKSI KABUPATEN
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $normalizedAction,
                [
                    'selected',
                    'selection',
                    'seleksi',
                    'seleksi_kabupaten',
                    'selection_decision_recorded',
                ],
                true
            )
        ) {

            /*
            | Kalau ada keputusan manual
            */

            if (
                in_array(
                    $decision,
                    [
                        'diterima',
                        'accept',
                        'accepted',
                        'lulus',
                        'terpilih',
                    ],
                    true
                )
            ) {

                return
                    'Menetapkan Penerima Beasiswa';
            }


            if (
                in_array(
                    $decision,
                    [
                        'ditolak',
                        'reject',
                        'rejected',
                        'tidak_lulus',
                    ],
                    true
                )
            ) {

                return
                    'Menolak Pengajuan Beasiswa';
            }


            return 'Memproses Seleksi Kabupaten';
        }


        /*
        |--------------------------------------------------------------------------
        | HASIL SELEKSI DIPUBLIKASIKAN
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $normalizedAction,
                [
                    'selection_result_published',
                    'published',
                    'publish',
                    'publikasi',
                ],
                true
            )
        ) {

            if (
                in_array(
                    $decision,
                    [
                        'diterima',
                        'accept',
                        'accepted',
                        'lulus',
                        'terpilih',
                    ],
                    true
                )
            ) {

                return
                    'Menetapkan Penerima Beasiswa';
            }


            if (
                in_array(
                    $decision,
                    [
                        'ditolak',
                        'reject',
                        'rejected',
                        'tidak_lulus',
                    ],
                    true
                )
            ) {

                return
                    'Menolak Pengajuan Beasiswa';
            }


            return
                'Mempublikasikan Hasil Seleksi';
        }


        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */

        return ucfirst(
            str_replace(
                ['_', '-'],
                ' ',
                $action
                ??
                'Aktivitas Verifikasi'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NAMA DATA
    |--------------------------------------------------------------------------
    */

    private function dataLabel(
        ?string $type
    ): string {

        return match (true) {

            str_ends_with(
                $type ?? '',
                'MahasiswaProfile'
            )
                => 'Profil Mahasiswa',

            str_ends_with(
                $type ?? '',
                'Pendaftaran'
            )
                => 'Pendaftaran Beasiswa',

            str_ends_with(
                $type ?? '',
                'DocumentVerification'
            )
                => 'Verifikasi Dokumen',

            str_ends_with(
                $type ?? '',
                'Dokumen'
            )
                => 'Dokumen Beasiswa',

            str_ends_with(
                $type ?? '',
                'Pendidikan'
            )
                => 'Data Pendidikan',

            str_ends_with(
                $type ?? '',
                'Prestasi'
            )
                => 'Data Prestasi',

            str_ends_with(
                $type ?? '',
                'OrangTua'
            )
                => 'Data Orang Tua',

            str_ends_with(
                $type ?? '',
                'Application'
            )
                => 'Pengajuan Beasiswa',

            str_ends_with(
                $type ?? '',
                'AgencyVerification'
            )
                => 'Verifikasi Dinas',

            str_ends_with(
                $type ?? '',
                'Selection'
            )
                => 'Keputusan Seleksi',

            str_ends_with(
                $type ?? '',
                'User'
            )
                => 'Akun Pengguna',

            default
                => class_basename(
                    $type ?? 'Data'
                ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | NAMA AKTIVITAS AUDIT
    |--------------------------------------------------------------------------
    */

    private function eventLabel(
        ?string $event,
        array $oldValues = [],
        array $newValues = [],
        ?string $auditableType = null
    ): string {

        /*
        |--------------------------------------------------------------------------
        | DOCUMENT VERIFICATION
        |--------------------------------------------------------------------------
        */

        if (
            str_ends_with(
                $auditableType ?? '',
                'DocumentVerification'
            )
        ) {

            $result =
                $newValues['result']
                ??
                $newValues['decision']
                ??
                null;

            if ($result instanceof \BackedEnum) {
                $result =
                    $result->value;
            }

            $result =
                strtolower(
                    trim(
                        (string) $result
                    )
                );


            if (
                in_array(
                    $result,
                    [
                        'memenuhi',
                        'memenuhi_syarat',
                        'valid',
                        'sesuai',
                        'ms',
                    ],
                    true
                )
            ) {

                return
                    'Memverifikasi Dokumen — Memenuhi Syarat';
            }


            if (
                in_array(
                    $result,
                    [
                        'tidak_memenuhi',
                        'tidak_memenuhi_syarat',
                        'tidak_valid',
                        'tidak_sesuai',
                        'tms',
                    ],
                    true
                )
            ) {

                return
                    'Memverifikasi Dokumen — Tidak Memenuhi Syarat';
            }


            return
                'Memverifikasi Dokumen';
        }


        /*
        |--------------------------------------------------------------------------
        | SELECTION
        |--------------------------------------------------------------------------
        */

        if (
            str_ends_with(
                $auditableType ?? '',
                'Selection'
            )
        ) {

            $decision =
                $newValues['manual_decision']
                ??
                $newValues['decision']
                ??
                null;

            if ($decision instanceof \BackedEnum) {

                $decision =
                    $decision->value;
            }

            $decision =
                strtolower(
                    trim(
                        (string) $decision
                    )
                );


            if (
                in_array(
                    $decision,
                    [
                        'diterima',
                        'accept',
                        'accepted',
                        'lulus',
                        'terpilih',
                    ],
                    true
                )
            ) {

                return
                    'Menetapkan Penerima Beasiswa';
            }


            if (
                in_array(
                    $decision,
                    [
                        'ditolak',
                        'reject',
                        'rejected',
                        'tidak_lulus',
                    ],
                    true
                )
            ) {

                return
                    'Menolak Pengajuan Beasiswa';
            }


            return
                'Memproses Keputusan Seleksi';
        }


        /*
        |--------------------------------------------------------------------------
        | MENAMBAHKAN DATA
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $event,
                [
                    'created',
                    'create',
                ],
                true
            )
        ) {

            return
                'Menambahkan ' .
                $this->dataLabel(
                    $auditableType
                );
        }


        /*
        |--------------------------------------------------------------------------
        | MENGHAPUS DATA
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $event,
                [
                    'deleted',
                    'delete',
                ],
                true
            )
        ) {

            return
                'Menghapus ' .
                $this->dataLabel(
                    $auditableType
                );
        }


        /*
        |--------------------------------------------------------------------------
        | MENGUBAH DATA
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $event,
                [
                    'updated',
                    'update',
                ],
                true
            )
        ) {

            $changedFields =
                $this->getChangedFields(
                    $oldValues,
                    $newValues
                );


            /*
            | Tidak ditemukan field
            */

            if (
                empty($changedFields)
            ) {

                return
                    'Mengubah ' .
                    $this->dataLabel(
                        $auditableType
                    );
            }


            /*
            | Satu field
            */

            if (
                count($changedFields) === 1
            ) {

                return
                    'Mengubah ' .
                    $this->fieldLabel(
                        $changedFields[0]
                    );
            }


            /*
            | Beberapa field
            */

            $labels =
                collect($changedFields)
                    ->map(
                        fn ($field) =>
                            $this->fieldLabel(
                                $field
                            )
                    )
                    ->values()
                    ->all();


            if (
                count($labels) === 2
            ) {

                return
                    'Mengubah ' .
                    $labels[0] .
                    ' dan ' .
                    $labels[1];
            }


            if (
                count($labels) > 3
            ) {

                return
                    'Mengubah ' .
                    implode(
                        ', ',
                        array_slice(
                            $labels,
                            0,
                            3
                        )
                    ) .
                    ' dan ' .
                    (
                        count($labels) - 3
                    ) .
                    ' data lainnya';
            }


            return
                'Mengubah ' .
                implode(
                    ', ',
                    array_slice(
                        $labels,
                        0,
                        -1
                    )
                ) .
                ' dan ' .
                end($labels);
        }


        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */

        return ucfirst(
            str_replace(
                ['_', '-'],
                ' ',
                $event ?? 'Aktivitas'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FIELD YANG BERUBAH
    |--------------------------------------------------------------------------
    */

    private function getChangedFields(
        array $oldValues,
        array $newValues
    ): array {

        $changedFields = [];

        $fields = array_unique(
            array_merge(
                array_keys($oldValues),
                array_keys($newValues)
            )
        );


        foreach ($fields as $field) {

            /*
            | Timestamp bukan aktivitas pengguna
            */

            if (
                in_array(
                    $field,
                    [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ],
                    true
                )
            ) {

                continue;
            }


            $oldValue =
                $oldValues[$field] ?? null;

            $newValue =
                $newValues[$field] ?? null;


            if (
                $this->valuesAreEqual(
                    $oldValue,
                    $newValue
                )
            ) {

                continue;
            }


            $changedFields[] =
                $field;
        }


        return $changedFields;
    }


    /*
    |--------------------------------------------------------------------------
    | BANDINGKAN NILAI
    |--------------------------------------------------------------------------
    */

    private function valuesAreEqual(
        $oldValue,
        $newValue
    ): bool {

        /*
        | Array
        */

        if (
            is_array($oldValue)
            ||
            is_array($newValue)
        ) {

            return
                json_encode($oldValue)
                ===
                json_encode($newValue);
        }


        /*
        | Null dan string kosong dianggap sama
        */

        if (
            (
                $oldValue === null
                ||
                $oldValue === ''
            )
            &&
            (
                $newValue === null
                ||
                $newValue === ''
            )
        ) {

            return true;
        }


        return
            (string) $oldValue
            ===
            (string) $newValue;
    }


    /*
    |--------------------------------------------------------------------------
    | RINGKASAN PERUBAHAN
    |--------------------------------------------------------------------------
    */

    private function changeSummary(
        array $oldValues,
        array $newValues,
        ?string $event
    ): array {

        $summary = [];


        /*
        |--------------------------------------------------------------------------
        | TAMBAH DATA
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $event,
                [
                    'created',
                    'create',
                ],
                true
            )
        ) {

            foreach (
                $newValues
                as $field => $value
            ) {

                if (
                    in_array(
                        $field,
                        [
                            'created_at',
                            'updated_at',
                        ],
                        true
                    )
                ) {

                    continue;
                }


                $summary[] = [

                    'field' =>
                        $this->fieldLabel(
                            $field
                        ),

                    'before' =>
                        null,

                    'after' =>
                        $this->formatValue(
                            $value
                        ),
                ];
            }


            return $summary;
        }


        /*
        |--------------------------------------------------------------------------
        | HAPUS DATA
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $event,
                [
                    'deleted',
                    'delete',
                ],
                true
            )
        ) {

            foreach (
                $oldValues
                as $field => $value
            ) {

                if (
                    in_array(
                        $field,
                        [
                            'created_at',
                            'updated_at',
                            'deleted_at',
                        ],
                        true
                    )
                ) {

                    continue;
                }


                $summary[] = [

                    'field' =>
                        $this->fieldLabel(
                            $field
                        ),

                    'before' =>
                        $this->formatValue(
                            $value
                        ),

                    'after' =>
                        null,
                ];
            }


            return $summary;
        }


        /*
        |--------------------------------------------------------------------------
        | PERUBAHAN DATA
        |--------------------------------------------------------------------------
        */

        $fields = array_unique(
            array_merge(
                array_keys($oldValues),
                array_keys($newValues)
            )
        );


        foreach ($fields as $field) {

            if (
                in_array(
                    $field,
                    [
                        'created_at',
                        'updated_at',
                        'deleted_at',
                    ],
                    true
                )
            ) {

                continue;
            }


            $oldValue =
                $oldValues[$field] ?? null;

            $newValue =
                $newValues[$field] ?? null;


            /*
            | Hanya tampilkan yang benar-benar berubah
            */

            if (
                $this->valuesAreEqual(
                    $oldValue,
                    $newValue
                )
            ) {

                continue;
            }


            $summary[] = [

                'field' =>
                    $this->fieldLabel(
                        $field
                    ),

                'before' =>
                    $this->formatValue(
                        $oldValue
                    ),

                'after' =>
                    $this->formatValue(
                        $newValue
                    ),
            ];
        }


        return $summary;
    }


    /*
    |--------------------------------------------------------------------------
    | NAMA FIELD
    |--------------------------------------------------------------------------
    */

    private function fieldLabel(
        string $field
    ): string {

        return match ($field) {

            'status_kependudukan'
                => 'Status Kependudukan',

            'nik'
                => 'NIK',

            'nim'
                => 'NIM',

            'phone'
                => 'Nomor HP',

            'email'
                => 'Email',

            'name'
                => 'Nama',

            'universitas'
                => 'Perguruan Tinggi',

            'fakultas'
                => 'Fakultas',

            'program_studi'
                => 'Program Studi',

            'jenjang'
                => 'Jenjang',

            'semester'
                => 'Semester',

            'ipk'
                => 'IPK',

            'tahun_masuk'
                => 'Tahun Masuk',

            'status_mahasiswa'
                => 'Status Mahasiswa',

            'alamat'
                => 'Alamat',

            'village_id'
                => 'Desa / Kelurahan',

            'kecamatan_id'
                => 'Kecamatan',

            'kabupaten_id'
                => 'Kabupaten',

            'provinsi_id'
                => 'Provinsi',

            'penghasilan_keluarga'
                => 'Penghasilan Keluarga',

            'jumlah_tanggungan'
                => 'Jumlah Tanggungan',

            'desil_sosial'
                => 'Desil Sosial',

            'desil_pendidikan'
                => 'Desil Pendidikan',

            'prestasi'
                => 'Prestasi',

            'status'
                => 'Status Pengajuan',

            'catatan'
                => 'Catatan',

            'nomor_pendaftaran'
                => 'Nomor Pendaftaran',

            'nomor_pengajuan'
                => 'Nomor Pengajuan',

            'kategori_beasiswa_id'
                => 'Kategori Beasiswa',

            'jalur_beasiswa_id'
                => 'Kategori Beasiswa',

            'periode_id'
                => 'Periode Beasiswa',

            'file'
                => 'File',

            'jenis_dokumen'
                => 'Jenis Dokumen',

            'agency'
                => 'Dinas',

            'decision'
                => 'Keputusan Verifikasi',

            'result'
                => 'Hasil Verifikasi',

            'stage'
                => 'Tahap Verifikasi',

            'round'
                => 'Putaran Verifikasi',

            'notes'
                => 'Catatan Verifikasi',

            'manual_decision'
                => 'Keputusan Seleksi',

            'decided_by'
                => 'Penentu Keputusan',

            'decided_at'
                => 'Waktu Keputusan',

            'published_at'
                => 'Waktu Publikasi',

            'locked_at'
                => 'Waktu Penguncian',

            'submitted_at'
                => 'Waktu Pengajuan',

            default
                => ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $field
                    )
                ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT HASIL VERIFIKASI
    |--------------------------------------------------------------------------
    */

    private function formatVerificationResult(
        $value
    ): string {

        if ($value instanceof \BackedEnum) {

            $value =
                $value->value;
        }

        $value =
            strtolower(
                trim(
                    (string) $value
                )
            );


        return match ($value) {

            'memenuhi',
            'memenuhi_syarat',
            'valid',
            'sesuai',
            'ms'
                => 'Memenuhi Syarat',

            'tidak_memenuhi',
            'tidak_memenuhi_syarat',
            'tidak_valid',
            'tidak_sesuai',
            'tms'
                => 'Tidak Memenuhi Syarat',

            default
                => $value !== ''
                    ? ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $value
                        )
                    )
                    : 'Belum Dinilai',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT NILAI
    |--------------------------------------------------------------------------
    */

    private function formatValue(
        $value
    ): string {

        if (
            $value === null
            ||
            $value === ''
        ) {

            return 'Tidak ada';
        }


        if (is_bool($value)) {

            return $value
                ? 'Ya'
                : 'Tidak';
        }


        if ($value instanceof \BackedEnum) {

            $value =
                $value->value;
        }


        if (is_array($value)) {

            return json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
            );
        }


        return match (
            strtolower(
                (string) $value
            )
        ) {

            'belum_diverifikasi'
                => 'Belum Diverifikasi',

            'terverifikasi'
                => 'Terverifikasi',

            'valid'
                => 'Valid',

            'tidak_valid'
                => 'Tidak Valid',

            'sesuai'
                => 'Sesuai',

            'tidak_sesuai'
                => 'Tidak Sesuai',

            'memenuhi'
                => 'Memenuhi Syarat',

            'tidak_memenuhi'
                => 'Tidak Memenuhi Syarat',

            'diterima'
                => 'Diterima',

            'ditolak'
                => 'Ditolak',

            'seleksi_kabupaten'
                => 'Seleksi Kabupaten',

            'verifikasi_dinas'
                => 'Verifikasi Dinas',

            default
                => (string) $value,
        };
    }
}