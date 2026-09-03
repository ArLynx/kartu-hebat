<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentVerification;
use App\Models\User;
use App\Models\VerificationLog;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgencyVerificationService
{
    public const STAGE_DUKCAPIL = 'dukcapil';

    public const STAGE_SOSIAL = 'sosial';

    public const STAGE_PENDIDIKAN = 'pendidikan';

    public const STAGE_KESEHATAN = 'kesehatan';

    public const STAGE_PARSEPOR = 'parsepor';

    public function __construct(
        private readonly SelectionScoringService $scoring,
    ) {}

    public static function stageFor(User $user): string
    {
        return match ($user->role) {
            UserRole::OPERATOR_DUKCAPIL => self::STAGE_DUKCAPIL,
            UserRole::OPERATOR_SOSIAL => self::STAGE_SOSIAL,
            UserRole::OPERATOR_PENDIDIKAN => self::STAGE_PENDIDIKAN,
            UserRole::OPERATOR_DINKES => self::STAGE_KESEHATAN,
            UserRole::OPERATOR_PARSEPOR => self::STAGE_PARSEPOR,
            default => throw new \LogicException('Role tidak memiliki tahap penilaian dokumen.'),
        };
    }

    public static function stageLabel(string $stage): string
    {
        return match ($stage) {
            self::STAGE_DUKCAPIL => 'Dukcapil',
            self::STAGE_SOSIAL => 'Dinas Sosial',
            self::STAGE_PENDIDIKAN => 'Disdikbud',
            self::STAGE_KESEHATAN => 'Dinkes',
            self::STAGE_PARSEPOR => 'Parsepor',
            default => $stage,
        };
    }

    /**
     * Dinas yang wajib memutuskan sebuah application. Agensi yang terikat
     * satu jalur (Dinkes→Disabilitas, Parsepor→Prestasi) hanya ikut jika
     * jalur aplikasi cocok; selain itu cukup 3 dinas utama (Dukcapil, Sosial, Disdikbud).
     *
     * @return array<int, string> kode agency
     */
    public static function requiredAgencies(Application $application): array
    {
        $agencies = array_keys(config('kartu_hebat.agencies'));

        $excluded = collect(UserRole::cases())
            ->filter(fn (UserRole $role) => $role->isAgency() && $role->verifiedTrack() !== null)
            ->filter(fn (UserRole $role) => $role->verifiedTrack() !== $application->application_type)
            ->map(fn (UserRole $role) => $role->agencyCode())
            ->all();

        return array_values(array_diff($agencies, $excluded));
    }

    /**
     * Putaran penilaian aktif untuk aplikasi: dihitung dari jumlah submission
     * mahasiswa (log aksi "submitted") sehingga tiap pengiriman ulang setelah
     * perbaikan membuka putaran baru. Minimum 1.
     */
    public static function currentRound(Application $application): int
    {
        $round = $application->verificationLogs()
            ->where('action', 'submitted')
            ->count();

        return max(1, $round);
    }

    /**
     * Apakah verifikator masih boleh menilai dokumen pada tahapnya.
     */
    public static function canVerifyStage(Application $application, string $stage): bool
    {
        if ($application->status->value !== ApplicationStatus::VERIFIKASI_DINAS->value) {
            return false;
        }

        return in_array($stage, self::requiredAgencies($application), true);
    }

    /**
     * Simpan hasil penilaian satu dokumen (autosave per klik).
     */
    public function assessDocument(
        Application $application,
        Document $document,
        User $verifier,
        DocumentVerificationResult $result,
        ?string $notes = null,
    ): DocumentVerification {
        $stage = self::stageFor($verifier);
        $this->assertCanVerifyStage($application, $stage);

        if (
            $result === DocumentVerificationResult::TIDAK_MEMENUHI
            && ($notes === null || trim($notes) === '')
        ) {
            throw ValidationException::withMessages([
                'notes' => 'Catatan wajib diisi saat dokumen ditandai tidak memenuhi syarat.',
            ]);
        }

        $round = self::currentRound($application);

        return DocumentVerification::query()->updateOrCreate(
            [
                'document_id' => $document->id,
                'stage' => $stage,
                'round' => $round,
            ],
            [
                'application_id' => $application->id,
                'verifier_id' => $verifier->id,
                'result' => $result,
                'notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
                'verified_at' => now(),
            ],
        );
    }

    /**
     * Batalkan penilaian dokumen tertentu.
     */
    public function cancelDocumentAssessment(
        Application $application,
        Document $document,
        User $verifier,
    ): void {
        $stage = self::stageFor($verifier);
        $this->assertCanVerifyStage($application, $stage);

        DocumentVerification::query()
            ->where('document_id', $document->id)
            ->where('stage', $stage)
            ->where('round', self::currentRound($application))
            ->delete();
    }

    public function resetForDocument(Document $document): void
    {
        DocumentVerification::query()
            ->where('document_id', $document->id)
            ->delete();
    }

    public function resetForApplication(Application $application): void
    {
        DocumentVerification::query()
            ->where('application_id', $application->id)
            ->delete();
    }

    /**
     * Validasi bahwa seluruh berkas wajib telah dinilai sebelum dinas membuat keputusan MS / TMS.
     */
    public function assertAllDocumentsVerified(
        Application $application,
        User $operator,
        VerificationDecision $decision,
    ): void {
        $stage = self::stageFor($operator);
        $round = self::currentRound($application);

        $documentIds = Document::query()
            ->where('application_id', $application->id)
            ->pluck('id');

        if ($documentIds->isEmpty()) {
            return;
        }

        $verifiedDocumentIds = DocumentVerification::query()
            ->where('application_id', $application->id)
            ->where('stage', $stage)
            ->where('round', $round)
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

    /**
     * Menerima dan memproses keputusan akhir verifikasi oleh dinas berwenang.
     * Mengelola pencatatan verifikasi dinas, pembaruan profil mahasiswa,
     * konsensus multi-dinas, transisi status aplikasi, dan notifikasi.
     */
    public function submitDecision(
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

        if ($application->status !== ApplicationStatus::VERIFIKASI_DINAS) {
            throw ValidationException::withMessages(['decision' => 'Pengajuan tidak berada pada antrean verifikasi dinas.']);
        }

        if (! in_array($operator->role->agencyCode(), self::requiredAgencies($application), true)) {
            throw ValidationException::withMessages([
                'decision' => 'Dinas ini tidak berwenang menilai aplikasi jalur '.($application->application_type?->label() ?? 'ini').'.',
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
        $requiredAgencies = self::requiredAgencies($application);

        if (count($verifications) < count($requiredAgencies)) {
            return null;
        }

        if ($verifications->contains(fn ($verification) => $verification->decision === VerificationDecision::TMS)) {
            return ApplicationStatus::TMS;
        }

        $this->scoring->calculate($application, $operator->id);

        return ApplicationStatus::SELEKSI_KABUPATEN;
    }

    private function assertCanVerifyStage(Application $application, string $stage): void
    {
        if (! self::canVerifyStage($application, $stage)) {
            throw ValidationException::withMessages([
                'document_verification' => 'Penilaian dokumen hanya dapat diubah saat aplikasi berada pada tahap penilaian ini.',
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
            ApplicationStatus::VERIFIKASI_DINAS => $query
                ->whereIn('role', collect(UserRole::cases())
                    ->filter(fn (UserRole $role) => $role->isAgency())
                    ->filter(fn (UserRole $role) => in_array(
                        $role->agencyCode(),
                        self::requiredAgencies($application),
                        true,
                    ))
                    ->pluck('value')
                    ->all())
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

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function log(
        Application $application,
        ?ApplicationStatus $from,
        ApplicationStatus $to,
        string $action,
        ?string $notes,
        ?User $actor = null,
        array $metadata = [],
    ): VerificationLog {
        return VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $actor?->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'action' => $action,
            'notes' => $notes,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);
    }
}
