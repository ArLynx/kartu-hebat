<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationType;
use App\Enums\KategoriPrestasi;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use Illuminate\Validation\ValidationException;

class PrestasiScoring implements ScoringStrategy
{
    public function __construct(private readonly NonAcademicTierResolver $tierResolver) {}

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

        // Skor hanya dihitung dari prestasi lomba; kepengurusan ormawa menentukan tier peringkat,
        // bukan skor kriteria, agar tidak mencampur dua dasar penilaian yang berbeda.
        $best = $this->tierResolver->bestRecordOfCategory($prestasis, KategoriPrestasi::LOMBA)
            ?? $prestasis->first();

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

        if (isset($rubric[$normalized])) {
            return (float) $rubric[$normalized];
        }

        if (str_contains($normalized, 'harapan') && isset($rubric['favorit'])) {
            return (float) $rubric['favorit'];
        }

        return (float) ($rubric['peserta'] ?? 20);
    }
}
