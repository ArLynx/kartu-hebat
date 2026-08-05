<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use Illuminate\Validation\ValidationException;

class DesilScoring implements ScoringStrategy
{
    public function type(): ApplicationType
    {
        return ApplicationType::TIDAK_MAMPU;
    }

    public function values(?MahasiswaProfile $profile, Application $application): array
    {
        $available = collect([$profile?->desil_sosial, $profile?->desil_pendidikan])
            ->filter(fn ($desil) => $desil !== null)
            ->map(fn ($desil) => (int) $desil);

        if ($available->isEmpty()) {
            throw ValidationException::withMessages([
                'desil' => 'Desil terverifikasi wajib tersedia untuk perhitungan jalur Tidak Mampu.',
            ]);
        }

        $desil = (float) $available->average();
        $normalized = 100 - (($desil - 1) / 9 * 100);

        return [
            'desil' => [
                'raw' => round($desil, 2),
                'normalized' => min(100, max(0, $normalized)),
            ],
        ];
    }
}
