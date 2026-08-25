<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\DistrictVerification;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVerification;
use App\Models\User;
use App\Models\VerificationLog;
use App\Models\VillageVerification;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflowService
{
    public function __construct(
        private readonly SelectionScoringService $scoring,
    ) {}

    public function submit(Application $application, User $student): Application
    {
        if (
            $application->mahasiswa_id !== $student->id
            || ! $this->isActiveApplicationPeriod($application)
            || ! $application->status->isEditableByStudent()
        ) {
            throw ValidationException::withMessages([
                'application' => 'Pengajuan ini tidak dapat dikirim atau diubah.',
            ]);
        }

        if (! $application->application_type) {
            throw ValidationException::withMessages([
                'application_type' => 'Pilih jalur pengajuan Akademik atau Tidak Mampu sebelum mengirim pengajuan.',
            ]);
        }

        if (! $student->isProfileComplete()) {
            throw ValidationException::withMessages([
                'profile' => 'Lengkapi data diri, pendidikan, dan alamat sebelum mengirim pengajuan.',
            ]);
        }

        if ($application->application_type === ApplicationType::AKADEMIK && $student->profile?->ipk === null) {
            throw ValidationException::withMessages([
                'ipk' => 'IPK wajib diisi untuk jalur Akademik.',
            ]);
        }

        if ($application->application_type === ApplicationType::NON_AKADEMIK && $student->profile?->ipk === null) {
            throw ValidationException::withMessages([
                'ipk' => 'IPK wajib diisi untuk jalur Non-Akademik sebagai baseline.',
            ]);
        }

        if ($application->application_type === ApplicationType::NON_AKADEMIK && ($application->pendaftaran_id === null || $application->pendaftaran?->prestasis()->exists() === false)) {
            throw ValidationException::withMessages([
                'prestasis' => 'Minimal satu data prestasi wajib diisi untuk jalur Non-Akademik.',
            ]);
        }

        $application->loadMissing('pendaftaran.kategoriBeasiswa.jenisDokumens');
        $requiredSourceTypes = collect($application->pendaftaran?->kategoriBeasiswa?->jenisDokumens ?? [])
            ->where('aktif', true)
            ->values();
        $requiredTypes = DocumentType::query()
            ->whereIn('code', $requiredSourceTypes->pluck('kode'))
            ->where('is_active', true)
            ->get();

        $unmappedCodes = $requiredSourceTypes->pluck('kode')->diff($requiredTypes->pluck('code'));

        if ($unmappedCodes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'documents' => 'Jenis dokumen wajib '.$unmappedCodes->implode(', ')
                    .' tidak ditemukan pada data master dokumen. Data master perlu disinkronkan oleh pengelola sebelum pengajuan dapat dikirim.',
            ]);
        }

        $uploadedIds = $application->documents()->pluck('document_type_id');
        $missing = $requiredTypes
            ->except($uploadedIds->all())
            ->pluck('name')
            ->all();

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'documents' => 'Dokumen wajib belum lengkap: '.implode(', ', $missing).'.',
            ]);
        }

        $target = match ($application->status) {
            ApplicationStatus::BTL_DESA => ApplicationStatus::VERIFIKASI_DESA,
            ApplicationStatus::BTL_KECAMATAN => ApplicationStatus::VERIFIKASI_KECAMATAN,
            default => ApplicationStatus::VERIFIKASI_DESA,
        };

        return DB::transaction(function () use ($application, $student, $target): Application {
            $from = $application->status;

            if ($from === ApplicationStatus::BTL_KECAMATAN) {
                $application->agencyVerifications()->delete();
                $application->scores()->delete();
                $application->selection()->delete();
                $student->profile?->update([
                    'status_kependudukan' => 'belum_diverifikasi',
                    'desil_sosial' => null,
                    'desil_pendidikan' => null,
                ]);
            }

            $application->update([
                'status' => $target,
                'submitted_at' => $application->submitted_at ?? now(),
                'locked_at' => now(),
                'catatan' => null,
            ]);

            $this->log($application, $from, $target, 'submitted', 'Pengajuan dikirim oleh mahasiswa.', $student);
            $this->notifyStudent($application, 'Pengajuan berhasil dikirim', 'Berkas Anda masuk ke tahap '.$target->label().'.');
            $this->notifyNextOperators($application, $target);

            return $application->fresh();
        });
    }

    public function verify(
        Application $application,
        User $operator,
        VerificationDecision $decision,
        ?string $notes = null,
        ?float $score = null,
        ?int $desil = null,
    ): Application {
        if (! $this->isActiveApplicationPeriod($application)) {
            throw ValidationException::withMessages([
                'decision' => 'Pengajuan periode historis tidak dapat diverifikasi ulang.',
            ]);
        }

        if (in_array($decision, [VerificationDecision::MS, VerificationDecision::TMS], true)) {
            $this->assertAllDocumentsVerified($application, $operator, $decision);
        }

        return DB::transaction(function () use ($application, $operator, $decision, $notes, $score, $desil): Application {
            $from = $application->status;
            $target = $this->storeVerificationAndResolveTarget(
                $application,
                $operator,
                $decision,
                $notes,
                $score,
                $desil,
            );

            if ($target !== null && $target !== $from) {
                $application->update([
                    'status' => $target,
                    'catatan' => $decision === VerificationDecision::MS ? null : $notes,
                    'locked_at' => $target->isEditableByStudent() ? null : now(),
                ]);

                if ($target === ApplicationStatus::SELEKSI_KABUPATEN) {
                    $this->scoring->recalculateRanking($operator->kabupaten_id, applicationType: $application->application_type);
                }

                $this->log(
                    $application,
                    $from,
                    $target,
                    'verification_'.$decision->value,
                    $notes,
                    $operator,
                    ['role' => $operator->role->value],
                );

                $this->notifyStudent(
                    $application,
                    'Status verifikasi diperbarui',
                    $target->label().($notes ? ': '.$notes : '.'),
                );

                $this->notifyNextOperators($application, $target);
            } else {
                $this->log(
                    $application,
                    $from,
                    $from,
                    'agency_verification_'.$decision->value,
                    $notes,
                    $operator,
                    ['agency' => $operator->role->agencyCode()],
                );
            }

            return $application->fresh();
        });
    }

    private function storeVerificationAndResolveTarget(
        Application $application,
        User $operator,
        VerificationDecision $decision,
        ?string $notes,
        ?float $score,
        ?int $desil,
    ): ?ApplicationStatus {
        return match ($operator->role) {
            UserRole::OPERATOR_DESA => $this->verifyVillage($application, $operator, $decision, $notes),
            UserRole::OPERATOR_KECAMATAN => $this->verifyDistrict($application, $operator, $decision, $notes),
            UserRole::OPERATOR_DUKCAPIL,
            UserRole::OPERATOR_SOSIAL,
            UserRole::OPERATOR_PENDIDIKAN => $this->verifyAgency(
                $application,
                $operator,
                $decision,
                $notes,
                $score,
                $desil,
            ),
            default => throw ValidationException::withMessages([
                'decision' => 'Role ini tidak berwenang melakukan verifikasi.',
            ]),
        };
    }

    private function verifyVillage(
        Application $application,
        User $operator,
        VerificationDecision $decision,
        ?string $notes,
    ): ApplicationStatus {
        if (! in_array($application->status, [ApplicationStatus::SUBMITTED, ApplicationStatus::VERIFIKASI_DESA], true)) {
            throw ValidationException::withMessages(['decision' => 'Pengajuan tidak berada pada antrean verifikasi desa.']);
        }

        VillageVerification::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'verifier_id' => $operator->id,
                'decision' => $decision,
                'notes' => $notes,
                'verified_at' => now(),
            ],
        );

        return match ($decision) {
            VerificationDecision::MS => ApplicationStatus::VERIFIKASI_KECAMATAN,
            VerificationDecision::BTL => ApplicationStatus::BTL_DESA,
            VerificationDecision::TMS => ApplicationStatus::TMS,
        };
    }

    private function verifyDistrict(
        Application $application,
        User $operator,
        VerificationDecision $decision,
        ?string $notes,
    ): ApplicationStatus {
        if ($application->status !== ApplicationStatus::VERIFIKASI_KECAMATAN) {
            throw ValidationException::withMessages(['decision' => 'Pengajuan tidak berada pada antrean verifikasi kecamatan.']);
        }

        DistrictVerification::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'verifier_id' => $operator->id,
                'decision' => $decision,
                'notes' => $notes,
                'verified_at' => now(),
            ],
        );

        return match ($decision) {
            VerificationDecision::MS => ApplicationStatus::VERIFIKASI_DINAS,
            VerificationDecision::BTL => ApplicationStatus::BTL_KECAMATAN,
            VerificationDecision::TMS => ApplicationStatus::TMS,
        };
    }

    private function verifyAgency(
        Application $application,
        User $operator,
        VerificationDecision $decision,
        ?string $notes,
        ?float $score,
        ?int $desil,
    ): ?ApplicationStatus {
        if ($application->status !== ApplicationStatus::VERIFIKASI_DINAS) {
            throw ValidationException::withMessages(['decision' => 'Pengajuan tidak berada pada antrean verifikasi dinas.']);
        }

        if (
            $decision !== VerificationDecision::MS
            || ! in_array($operator->role, [UserRole::OPERATOR_SOSIAL, UserRole::OPERATOR_PENDIDIKAN], true)
        ) {
            $desil = null;
        }

        AgencyVerification::query()->updateOrCreate(
            [
                'application_id' => $application->id,
                'agency' => $operator->role->agencyCode(),
            ],
            [
                'verifier_id' => $operator->id,
                'decision' => $decision,
                'score' => $score,
                'notes' => $notes,
                'metadata' => array_filter(['desil' => $desil], fn ($value) => $value !== null),
                'verified_at' => now(),
            ],
        );

        $profile = $application->mahasiswa()->with('profile')->firstOrFail()->profile;

        if ($profile) {
            match ($operator->role) {
                UserRole::OPERATOR_DUKCAPIL => $profile->update([
                    'status_kependudukan' => match ($decision) {
                        VerificationDecision::MS => 'sesuai',
                        VerificationDecision::BTL => 'perlu_perbaikan',
                        VerificationDecision::TMS => 'tidak_sesuai',
                    },
                ]),
                UserRole::OPERATOR_SOSIAL => $profile->update([
                    'desil_sosial' => $desil,
                ]),
                UserRole::OPERATOR_PENDIDIKAN => $profile->update([
                    'desil_pendidikan' => $desil,
                ]),
                default => null,
            };
        }

        $verifications = $application->agencyVerifications()->get();

        if ($verifications->count() < count(config('kartu_hebat.agencies'))) {
            return null;
        }

        if ($verifications->contains(fn ($verification) => $verification->decision === VerificationDecision::TMS)) {
            return ApplicationStatus::TMS;
        }

        if ($verifications->contains(fn ($verification) => $verification->decision === VerificationDecision::BTL)) {
            return ApplicationStatus::BTL_KECAMATAN;
        }

        $this->scoring->calculate($application, $operator->id);

        return ApplicationStatus::SELEKSI_KABUPATEN;
    }

    private function notifyStudent(Application $application, string $title, string $message): void
    {
        $application->mahasiswa->notify(new ApplicationStatusChanged(
            $application,
            $title,
            $message,
            $application->pendaftaran_id
                ? route('mahasiswa.dashboard', absolute: false)
                : route('student.history', absolute: false),
        ));
    }

    private function notifyNextOperators(Application $application, ApplicationStatus $status): void
    {
        $application->loadMissing('mahasiswa.profile.village');

        $query = User::query()->where('status', 'active');
        $title = 'Pengajuan siap diverifikasi';
        $message = $application->nomor_pengajuan.' siap diproses.';

        match ($status) {
            ApplicationStatus::VERIFIKASI_DESA => $query
                ->where('role', UserRole::OPERATOR_DESA->value)
                ->where('village_id', $application->mahasiswa->profile->village_id),
            ApplicationStatus::VERIFIKASI_KECAMATAN => $query
                ->where('role', UserRole::OPERATOR_KECAMATAN->value)
                ->where('kecamatan_id', $application->mahasiswa->profile->village->kecamatan_id),
            ApplicationStatus::VERIFIKASI_DINAS => $query
                ->whereIn('role', [
                    UserRole::OPERATOR_DUKCAPIL->value,
                    UserRole::OPERATOR_SOSIAL->value,
                    UserRole::OPERATOR_PENDIDIKAN->value,
                ])
                ->where('kabupaten_id', $application->mahasiswa->profile->village->kabupaten_id),
            ApplicationStatus::SELEKSI_KABUPATEN => $query
                ->where('role', UserRole::OPERATOR_KABUPATEN->value)
                ->where('kabupaten_id', $application->mahasiswa->profile->village->kabupaten_id),
            default => $query->whereRaw('1 = 0'),
        };

        $query->get()->each(function (User $operator) use ($application, $title, $message): void {
            $operator->notify(new ApplicationStatusChanged(
                $application,
                $title,
                $message,
                route('operator.applications.show', $application, absolute: false),
            ));
        });
    }

    private function assertAllDocumentsVerified(Application $application, User $operator, VerificationDecision $decision): void
    {
        $stage = DocumentVerificationService::stageFor($operator);

        $documentIds = Document::query()
            ->where('application_id', $application->id)
            ->pluck('id');

        if ($documentIds->isEmpty()) {
            return;
        }

        $verifiedDocumentIds = DocumentVerification::query()
            ->where('application_id', $application->id)
            ->where('stage', $stage)
            ->pluck('document_id');

        $unverifiedIds = $documentIds->diff($verifiedDocumentIds);

        if ($unverifiedIds->isNotEmpty()) {
            $unverifiedNames = Document::query()
                ->whereIn('id', $unverifiedIds)
                ->with('type')
                ->get()
                ->pluck('type.name')
                ->implode(', ');

            throw ValidationException::withMessages([
                'document_verification' => "Semua dokumen harus dinilai terlebih dahulu sebelum mengajukan keputusan {$decision->label()}. Dokumen yang belum dinilai: {$unverifiedNames}.",
            ]);
        }
    }

    private function isActiveApplicationPeriod(Application $application): bool
    {
        if ($application->pendaftaran_id) {
            $application->loadMissing('pendaftaran.periode');

            $period = $application->pendaftaran?->periode;
            $today = today();

            return $period?->status === 'aktif'
                && $period->tanggal_mulai?->lte($today)
                && $period->tanggal_selesai?->gte($today);
        }

        return $application->periode === config('kartu_hebat.current_period');
    }

    private function log(
        Application $application,
        ?ApplicationStatus $from,
        ApplicationStatus $to,
        string $action,
        ?string $notes = null,
        ?User $actor = null,
        array $metadata = [],
    ): void {
        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $actor?->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'action' => $action,
            'notes' => $notes,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
