<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\MahasiswaProfile;

class AcademicScoring implements ScoringStrategy
{
    public function type(): ApplicationType
    {
        return ApplicationType::AKADEMIK;
    }

    public function values(?MahasiswaProfile $profile, Application $application): array
    {
        $ipk = (float) ($application->pendaftaran?->pendidikan?->ipk ?? $profile?->ipk ?? 0);
        $semester = (int) ($application->pendaftaran?->pendidikan?->semester ?? $profile?->semester ?? 0);
        $maxSemester = max(1, (int) config('kartu_hebat.scoring.academic_max_semester', 8));

        return [
            'ipk' => [
                'raw' => $ipk,
                'normalized' => min(100, max(0, ($ipk / 4) * 100)),
            ],
            'semester' => [
                'raw' => $semester,
                'normalized' => min(100, max(0, ($semester / $maxSemester) * 100)),
            ],
        ];
    }
}
