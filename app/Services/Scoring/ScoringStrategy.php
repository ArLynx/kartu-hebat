<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\MahasiswaProfile;

interface ScoringStrategy
{
    public function type(): ApplicationType;

    /**
     * @return array<string, array{raw: float|int|string, normalized: float}>
     */
    public function values(?MahasiswaProfile $profile, Application $application): array;
}
