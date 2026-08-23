<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentVerification;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DocumentVerificationService
{
    public const STAGE_DESA = 'desa';

    public const STAGE_KECAMATAN = 'kecamatan';

    public const STAGE_DUKCAPIL = 'dukcapil';

    public const STAGE_SOSIAL = 'sosial';

    public const STAGE_PENDIDIKAN = 'pendidikan';

    public static function stageFor(User $user): string
    {
        return match ($user->role) {
            UserRole::OPERATOR_DESA => self::STAGE_DESA,
            UserRole::OPERATOR_KECAMATAN => self::STAGE_KECAMATAN,
            UserRole::OPERATOR_DUKCAPIL => self::STAGE_DUKCAPIL,
            UserRole::OPERATOR_SOSIAL => self::STAGE_SOSIAL,
            UserRole::OPERATOR_PENDIDIKAN => self::STAGE_PENDIDIKAN,
            default => throw new \LogicException('Role tidak memiliki tahap penilaian dokumen.'),
        };
    }

    public static function stageLabel(string $stage): string
    {
        return match ($stage) {
            self::STAGE_DESA => 'Desa/Kelurahan',
            self::STAGE_KECAMATAN => 'Kecamatan',
            self::STAGE_DUKCAPIL => 'Dukcapil',
            self::STAGE_SOSIAL => 'Dinas Sosial',
            self::STAGE_PENDIDIKAN => 'Disdikbud',
            default => $stage,
        };
    }

    /**
     * Putaran penilaian aktif untuk aplikasi: dihitung dari jumlah submission
     * mahasiswa (log aksi "submitted") sehingga tiap pengiriman ulang setelah
     * BTL membuka putaran baru. Minimum 1.
     */
    public static function currentRound(Application $application): int
    {
        $round = $application->verificationLogs()
            ->where('action', 'submitted')
            ->count();

        return max(1, $round);
    }

    /**
     * Simpan hasil penilaian satu dokumen (autosave per klik). Hanya boleh
     * pada tahap yang masih berstatus "belum dinilai" (belum ada keputusan).
     */
    public function save(
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
     * Apakah verifikator masih boleh menilai dokumen pada tahapnya.
     */
    public static function canVerifyStage(Application $application, string $stage): bool
    {
        return match ($stage) {
            self::STAGE_DESA => in_array($application->status->value, [
                ApplicationStatus::SUBMITTED->value,
                ApplicationStatus::VERIFIKASI_DESA->value,
            ], true),
            self::STAGE_KECAMATAN => $application->status->value === ApplicationStatus::VERIFIKASI_KECAMATAN->value,
            self::STAGE_DUKCAPIL,
            self::STAGE_SOSIAL,
            self::STAGE_PENDIDIKAN => $application->status->value === ApplicationStatus::VERIFIKASI_DINAS->value,
            default => false,
        };
    }

    private function assertCanVerifyStage(Application $application, string $stage): void
    {
        if (! self::canVerifyStage($application, $stage)) {
            throw ValidationException::withMessages([
                'document_verification' => 'Penilaian dokumen hanya dapat diubah saat aplikasi berada pada tahap penilaian ini.',
            ]);
        }
    }
}
