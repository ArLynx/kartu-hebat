<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\Criterion;
use App\Models\Selection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelectionScoringService
{
    public function calculate(Application $application, ?int $scorerId = null): Selection
    {
        $application->loadMissing('mahasiswa.profile');
        $profile = $application->mahasiswa->profile;
        $type = $application->application_type;

        if (!$type) {
            throw ValidationException::withMessages([
                'application_type' => 'Jalur pengajuan belum dipilih.',
            ]);
        }

        $criteria = Criterion::query()
            ->where('is_active', true)
            ->where('application_type', $type->value)
            ->orderBy('sort_order')
            ->get();

        if ($criteria->isEmpty()) {
            throw ValidationException::withMessages([
                'application_type' => 'Kriteria seleksi untuk jalur '.$type->label().' belum tersedia.',
            ]);
        }

        $values = match ($type) {
            ApplicationType::AKADEMIK => $this->academicValues($profile?->ipk, $profile?->semester),
            ApplicationType::TIDAK_MAMPU => $this->desilValues(
                $profile?->desil_sosial,
                $profile?->desil_pendidikan,
            ),
        };

        return DB::transaction(function () use ($application, $criteria, $values, $scorerId): Selection {
            $criterionIds = $criteria->modelKeys();
            $application->scores()->whereNotIn('criterion_id', $criterionIds)->delete();

            $total = 0.0;

            foreach ($criteria as $criterion) {
                $value = $values[$criterion->code] ?? ['raw' => 0, 'normalized' => 0];
                $normalized = (float) $value['normalized'];
                $weighted = $normalized * ((float) $criterion->weight / 100);
                $total += $weighted;

                $application->scores()->updateOrCreate(
                    ['criterion_id' => $criterion->id],
                    [
                        'scorer_id' => $scorerId,
                        'raw_value' => (float) $value['raw'],
                        'normalized_score' => round($normalized, 4),
                        'weighted_score' => round($weighted, 4),
                        'source' => 'automatic',
                    ],
                );
            }

            return $application->selection()->updateOrCreate(
                [],
                ['final_score' => round($total, 4)],
            );
        });
    }

    public function recalculateRanking(
        ?int $kabupatenId = null,
        ?string $period = null,
        ?ApplicationType $applicationType = null,
    ): Collection {
        $period ??= config('kartu_hebat.current_period');
        $types = $applicationType ? [$applicationType] : ApplicationType::cases();
        $rankedIds = collect();

        DB::transaction(function () use ($types, $kabupatenId, $period, $rankedIds): void {
            foreach ($types as $type) {
                $selections = Selection::query()
                    ->whereHas('application', function ($application) use ($kabupatenId, $period, $type): void {
                        $application
                            ->where('periode', $period)
                            ->where('application_type', $type->value)
                            ->whereIn('status', [
                                ApplicationStatus::SELEKSI_KABUPATEN->value,
                                ApplicationStatus::DITERIMA->value,
                                ApplicationStatus::DITOLAK->value,
                            ]);

                        if ($kabupatenId !== null) {
                            $application->whereHas(
                                'mahasiswa.profile.village',
                                fn ($village) => $village->where('kabupaten_id', $kabupatenId),
                            );
                        }
                    })
                    ->with('application.mahasiswa.profile.village')
                    ->get();

                $selections
                    ->groupBy(fn (Selection $selection) => $selection->application->mahasiswa->profile?->village?->kabupaten_id)
                    ->each(function (Collection $countySelections) use ($rankedIds): void {
                        $countySelections
                            ->sort(function (Selection $left, Selection $right): int {
                                return (float) $right->final_score <=> (float) $left->final_score
                                    ?: $left->application_id <=> $right->application_id;
                            })
                            ->values()
                            ->each(function (Selection $selection, int $index) use ($rankedIds): void {
                                $selection->update(['rank' => $index + 1]);
                                $rankedIds->push($selection->id);
                            });
                    });
            }
        });

        return Selection::query()
            ->whereKey($rankedIds->all())
            ->orderBy('rank')
            ->get();
    }

    private function academicValues(float|string|null $ipk, ?int $semester): array
    {
        $ipkValue = (float) ($ipk ?? 0);
        $maxSemester = max(1, (int) config('kartu_hebat.scoring.academic_max_semester', 8));

        return [
            'ipk' => [
                'raw' => $ipkValue,
                'normalized' => min(100, max(0, ($ipkValue / 4) * 100)),
            ],
            'semester' => [
                'raw' => (int) ($semester ?? 0),
                'normalized' => min(100, max(0, ((int) ($semester ?? 0) / $maxSemester) * 100)),
            ],
        ];
    }

    private function desilValues(?int $desilSosial, ?int $desilPendidikan): array
    {
        $available = collect([$desilSosial, $desilPendidikan])
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
