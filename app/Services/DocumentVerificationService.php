<?php

namespace App\Services;

use App\Enums\DocumentVerificationResult;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentVerification;
use App\Models\User;

class DocumentVerificationService
{
    public const STAGE_DUKCAPIL = AgencyVerificationService::STAGE_DUKCAPIL;

    public const STAGE_SOSIAL = AgencyVerificationService::STAGE_SOSIAL;

    public const STAGE_PENDIDIKAN = AgencyVerificationService::STAGE_PENDIDIKAN;

    public const STAGE_KESEHATAN = AgencyVerificationService::STAGE_KESEHATAN;

    public const STAGE_PARSEPOR = AgencyVerificationService::STAGE_PARSEPOR;

    public function __construct(
        private readonly AgencyVerificationService $agencyVerification,
    ) {}

    public static function stageFor(User $user): string
    {
        return AgencyVerificationService::stageFor($user);
    }

    public static function stageLabel(string $stage): string
    {
        return AgencyVerificationService::stageLabel($stage);
    }

    public static function requiredAgencies(Application $application): array
    {
        return AgencyVerificationService::requiredAgencies($application);
    }

    public static function currentRound(Application $application): int
    {
        return AgencyVerificationService::currentRound($application);
    }

    public static function canVerifyStage(Application $application, string $stage): bool
    {
        return AgencyVerificationService::canVerifyStage($application, $stage);
    }

    public function save(
        Application $application,
        Document $document,
        User $verifier,
        DocumentVerificationResult $result,
        ?string $notes = null,
    ): DocumentVerification {
        return $this->agencyVerification->assessDocument($application, $document, $verifier, $result, $notes);
    }

    public function cancel(
        Application $application,
        Document $document,
        User $verifier,
    ): void {
        $this->agencyVerification->cancelDocumentAssessment($application, $document, $verifier);
    }

    public function resetForDocument(Document $document): void
    {
        $this->agencyVerification->resetForDocument($document);
    }

    public function resetForApplication(Application $application): void
    {
        $this->agencyVerification->resetForApplication($application);
    }
}
