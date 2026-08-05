<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use Illuminate\Validation\ValidationException;

class DisabilityScoring implements ScoringStrategy
{
    public function type(): ApplicationType
    {
        return ApplicationType::DISABILITAS;
    }

    public function values(?MahasiswaProfile $profile, Application $application): array
    {
        if (! $profile || empty($profile->disability_type)) {
            throw ValidationException::withMessages([
                'disability_type' => 'Jenis disabilitas wajib diisi untuk jalur Disabilitas.',
            ]);
        }

        $ipk = (float) ($profile->ipk ?? 0);
        $semester = (int) ($profile->semester ?? 0);
        $maxSemester = max(1, (int) config('kartu_hebat.scoring.academic_max_semester', 8));

        $gradeScore = $this->disabilityGradeScore($profile->disability_grade);
        $typeScore = $this->disabilityTypeScore($profile->disability_type);

        return [
            'ipk' => [
                'raw' => $ipk,
                'normalized' => min(100, max(0, ($ipk / 4) * 100)),
            ],
            'semester' => [
                'raw' => $semester,
                'normalized' => min(100, max(0, ($semester / $maxSemester) * 100)),
            ],
            'disability_grade' => [
                'raw' => $profile->disability_grade ?? 'Tidak Diketahui',
                'normalized' => $gradeScore,
            ],
            'disability_type' => [
                'raw' => $profile->disability_type,
                'normalized' => $typeScore,
            ],
        ];
    }

    private function disabilityGradeScore(?string $grade): float
    {
        return match ($grade) {
            'BERAT' => 100.0,
            'SEDANG' => 75.0,
            'RINGAN' => 50.0,
            default => 0.0,
        };
    }

    private function disabilityTypeScore(string $type): float
    {
        return match ($type) {
            'TUNANETRA', 'TUNARUNGU', 'TUNAWICARA' => 100.0,
            'TUNADAKSA' => 90.0,
            'TUNAGRAHITA' => 85.0,
            'DISABILITAS_GANDA' => 100.0,
            default => 60.0,
        };
    }
}
