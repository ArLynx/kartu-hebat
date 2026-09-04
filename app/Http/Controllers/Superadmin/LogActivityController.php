<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\AgencyVerification;
use App\Models\DocumentVerification;
use App\Models\Pendaftaran;
use App\Models\User;
use App\Models\VerificationLog;
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

                'submitted' => $verificationQuery->whereIn(
                    'action',
                    [
                        'submitted',
                        'submit',
                        'diajukan',
                    ]
                ),

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
        | LABEL VERIFIKASI
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

                $query->orWhere(
                    'event',
                    'like',
                    "%{$search}%"
                );

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
        | SIAPKAN DATA AUDIT
        |--------------------------------------------------------------------------
        */

        $auditLogs
            ->getCollection()
            ->transform(function ($log) {

                /*
                |--------------------------------------------------------------------------
                | DEFAULT
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
                        $student = User::find(
                            $profile->user_id
                        );
                    }

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
                                $profile->nik,

                            'nim' =>
                                $profile->nim,

                            'nomor_pendaftaran' =>
                                $pendaftaran?->nomor_pendaftaran,

                            'pendaftaran_id' =>
                                $pendaftaran?->id,
                        ];
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | 2. APPLICATION / PENGAJUAN BEASISWA
                |--------------------------------------------------------------------------
                |
                | INI BAGIAN YANG MEMPERBAIKI:
                |
                | "Tidak terkait mahasiswa"
                |
                | untuk log seperti:
                |
                | Mengubah Status Pengajuan dan Waktu Penguncian
                |
                */

                if (
                    ! $log->student_info
                    &&
                    $log->auditable instanceof Application
                ) {

                    $application =
                        $log->auditable;

                    /*
                    | Application
                    |      ↓
                    | mahasiswa
                    |      ↓
                    | profile
                    |      ↓
                    | pendaftaran
                    */

                    $application->loadMissing([
                        'mahasiswa.profile',
                        'pendaftaran',
                    ]);

                    $student =
                        $application->mahasiswa;

                    $profile =
                        $student?->profile;

                    $pendaftaran =
                        $application->pendaftaran;


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
                                $application->nomor_pengajuan
                                ??
                                $pendaftaran?->nomor_pendaftaran,

                            'pendaftaran_id' =>
                                $pendaftaran?->id,
                        ];
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | 3. AGENCY VERIFICATION
                |--------------------------------------------------------------------------
                */

                if (
                    ! $log->student_info
                    &&
                    $log->auditable instanceof AgencyVerification
                ) {

                    $verification =
                        $log->auditable;

                    $verification->loadMissing([
                        'application.mahasiswa.profile',
                        'application.pendaftaran',
                    ]);

                    $application =
                        $verification->application;

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


                /*
                |--------------------------------------------------------------------------
                | 4. DOCUMENT VERIFICATION
                |--------------------------------------------------------------------------
                |
                | Operator dinas melakukan verifikasi dokumen.
                |
                | DocumentVerification
                |        ↓
                | Application
                |        ↓
                | Mahasiswa
                |        ↓
                | Profile
                |        ↓
                | Pendaftaran
                |
                */

                if (
                    ! $log->student_info
                    &&
                    $log->auditable instanceof DocumentVerification
                ) {

                    $verification =
                        $log->auditable;

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
                    |--------------------------------------------------------------------------
                    | DATA MAHASISWA
                    |--------------------------------------------------------------------------
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
                    |--------------------------------------------------------------------------
                    | DATA DOKUMEN
                    |--------------------------------------------------------------------------
                    */

                    $documentName = 'Dokumen Beasiswa';

                    if (
                        $verification->document
                        &&
                        $verification->document->type
                    ) {
                        $documentName =
                            $verification
                                ->document
                                ->type
                                ->name;
                    }

                    $log->document_verification_info = [

                        'document_name' =>
                            $documentName,

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
                | 5. SELECTION / SELEKSI KABUPATEN
                |--------------------------------------------------------------------------
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
                | 6. FALLBACK USER ID
                |--------------------------------------------------------------------------
                */

                if (! $log->student_info) {

                    $studentId =
                        $log->auditable?->user_id;

                    if ($studentId) {

                        $student =
                            User::find($studentId);

                        if ($student) {

                            $pendaftaran =
                                Pendaftaran::query()
                                    ->where(
                                        'user_id',
                                        $student->id
                                    )
                                    ->latest('id')
                                    ->first();

                            $profile =
                                $student->profile;

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
                | LABEL DATA
                |--------------------------------------------------------------------------
                */

                $log->data_label =
                    $this->dataLabel(
                        $log->auditable_type
                    );


                /*
                |--------------------------------------------------------------------------
                | LABEL AKTIVITAS
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
        | OPERATOR
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
            ->get();


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
    | AKTIVITAS VERIFIKASI
    |--------------------------------------------------------------------------
    */

    private function verificationActivityLabel(
        ?string $action,
        $actor,
        array $metadata = []
    ): string {

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


        $action =
            is_string($action)
            ? strtolower(trim($action))
            : '';


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
                strtolower(trim($decision));
        }


        /*
        |--------------------------------------------------------------------------
        | MS
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $action,
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
                $action,
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
                $action,
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
                $action,
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
                'Memproses Seleksi Kabupaten';
        }


        /*
        |--------------------------------------------------------------------------
        | PUBLIKASI
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $action,
                [
                    'selection_result_published',
                    'published',
                    'publish',
                    'publikasi',
                ],
                true
            )
        ) {

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
                $action ?: 'Aktivitas Verifikasi'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DATA LABEL
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
                => 'Pengajuan Beasiswa',

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
                => 'Data Sistem',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | EVENT LABEL
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


            return 'Memverifikasi Dokumen';
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
        | CREATED
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
        | DELETED
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
        | UPDATED
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


            if (empty($changedFields)) {

                return
                    'Mengubah ' .
                    $this->dataLabel(
                        $auditableType
                    );
            }


            /*
            | Satu field
            */

            if (count($changedFields) === 1) {

                return
                    'Mengubah ' .
                    $this->fieldLabel(
                        $changedFields[0]
                    );
            }


            /*
            | Dua field
            */

            if (count($changedFields) === 2) {

                return
                    'Mengubah ' .
                    $this->fieldLabel(
                        $changedFields[0]
                    ) .
                    ' dan ' .
                    $this->fieldLabel(
                        $changedFields[1]
                    );
            }


            /*
            | Lebih dari dua
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
                (
                    count($labels) > 3
                    ? ' dan ' .
                        (
                            count($labels) - 3
                        ) .
                        ' data lainnya'
                    : ''
                );
        }


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
    | CARI FIELD YANG BERUBAH
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
    | CEK NILAI SAMA
    |--------------------------------------------------------------------------
    */

    private function valuesAreEqual(
        $oldValue,
        $newValue
    ): bool {

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
        | CREATED
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

            foreach ($newValues as $field => $value) {

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
        | DELETED
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

            foreach ($oldValues as $field => $value) {

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
        | UPDATED
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
    | LABEL FIELD
    |--------------------------------------------------------------------------
    */

    private function fieldLabel(
        string $field
    ): string {

        return match ($field) {

            'status_kependudukan'
                => 'Status Kependudukan',

            'status'
                => 'Status Pengajuan',

            'nik'
                => 'NIK',

            'nim'
                => 'NIM',

            'name'
                => 'Nama',

            'email'
                => 'Email',

            'phone'
                => 'Nomor HP',

            'alamat'
                => 'Alamat',

            'universitas'
                => 'Perguruan Tinggi',

            'fakultas'
                => 'Fakultas',

            'program_studi'
                => 'Program Studi',

            'jenjang'
                => 'Jenjang Pendidikan',

            'semester'
                => 'Semester',

            'ipk'
                => 'IPK',

            'tahun_masuk'
                => 'Tahun Masuk',

            'status_mahasiswa'
                => 'Status Mahasiswa',

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

            'catatan'
                => 'Catatan',

            'nomor_pendaftaran'
                => 'Nomor Pendaftaran',

            'nomor_pengajuan'
                => 'Nomor Pengajuan',

            'kategori_beasiswa_id'
                => 'Kategori Beasiswa',

            'jalur_beasiswa_id'
                => 'Jalur Beasiswa',

            'periode_id'
                => 'Periode Beasiswa',

            'file'
                => 'File Dokumen',

            'jenis_dokumen'
                => 'Jenis Dokumen',

            'result'
                => 'Hasil Verifikasi',

            'decision'
                => 'Keputusan Verifikasi',

            'manual_decision'
                => 'Keputusan Seleksi',

            'stage'
                => 'Tahap Verifikasi',

            'notes'
                => 'Catatan Verifikasi',

            'agency'
                => 'Dinas',

            'decided_by'
                => 'Penentu Keputusan',

            'decided_at'
                => 'Waktu Keputusan',

            'submitted_at'
                => 'Waktu Pengajuan',

            'locked_at'
                => 'Waktu Penguncian',

            'published_at'
                => 'Waktu Publikasi',

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
    | HASIL VERIFIKASI
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
    | FORMAT VALUE
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

            'memenuhi_syarat'
                => 'Memenuhi Syarat',

            'tidak_memenuhi'
                => 'Tidak Memenuhi Syarat',

            'tidak_memenuhi_syarat'
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