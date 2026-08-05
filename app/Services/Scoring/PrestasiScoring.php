<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use App\Models\Prestasi;
use Illuminate\Validation\ValidationException;

class PrestasiScoring implements ScoringStrategy
{
    public function type(): ApplicationType
    {
        return ApplicationType::NON_AKADEMIK;
    }

    public function values(?MahasiswaProfile $profile, Application $application): array
    {
        $prestasis = $application->pendaftaran?->prestasis ?? collect();

        if ($prestasis->isEmpty()) {
            throw ValidationException::withMessages([
                'prestasis' => 'Minimal satu data prestasi wajib diisi untuk jalur Non-Akademik.',
            ]);
        }

        $best = $prestasis
            ->sortByDesc(fn (Prestasi $p) => $this->tingkatScore($p->tingkat))
            ->sortByDesc(fn (Prestasi $p) => $this->peringkatScore($p->peringkat))
            ->sortByDesc(fn (Prestasi $p) => (int) ($p->tahun ?? 0))
            ->first();

        $ipk = (float) ($profile?->ipk ?? 0);

        return [
            'ipk' => [
                'raw' => $ipk,
                'normalized' => min(100, max(0, ($ipk / 4) * 100)),
            ],
            'achievement_level' => [
                'raw' => $best->tingkat ?? 'Tidak Diketahui',
                'normalized' => $this->tingkatScore($best->tingkat),
            ],
            'achievement_rank' => [
                'raw' => $best->peringkat ?? 'Tidak Diketahui',
                'normalized' => $this->peringkatScore($best->peringkat),
            ],
        ];
    }

    private function tingkatScore(?string $tingkat): float
    {
        $rubric = (array) config('kartu_hebat.scoring.non_academic_rubric.tingkat', []);
        $key = strtolower(trim((string) $tingkat));

        return (float) ($rubric[$key] ?? 0);
    }

    private function peringkatScore(?string $peringkat): float
    {
        $rubric = (array) config('kartu_hebat.scoring.non_academic_rubric.peringkat', []);
        $normalized = strtolower(trim((string) $peringkat));
        $normalized = preg_replace('/\s+/', '_', $normalized) ?? '';

        return (float) ($rubric[$normalized] ?? 0);
    }
}