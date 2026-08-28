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
    public function __construct(private readonly ScoringStrategyResolver $resolver) {}

    public function calculate(Application $application, ?int $scorerId = null): Selection
    {
        $application->loadMissing('mahasiswa.profile');
        $profile = $application->mahasiswa->profile;
        $type = $application->application_type;

        if (! $type) {
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

        $totalWeight = round((float) $criteria->sum('weight'), 2);

        if ($totalWeight !== 100.0) {
            throw ValidationException::withMessages([
                'criteria' => 'Total bobot kriteria seleksi untuk jalur '.$type->label().' harus 100, saat ini '.$totalWeight.'.',
            ]);
        }

        $strategy = $this->resolver->resolve($type);
        $values = $strategy->values($profile, $application);

        return DB::transaction(function () use ($application, $criteria, $values, $scorerId, $type): Selection {
            $criterionIds = $criteria->modelKeys();
            $application->scores()->whereNotIn('criterion_id', $criterionIds)->delete();

            $total = 0.0;

            foreach ($criteria as $criterion) {
                if (! array_key_exists($criterion->code, $values)) {
                    throw ValidationException::withMessages([
                        'criteria' => 'Kriteria "'.$criterion->code.'" tidak dikenal oleh strategi skoring jalur '.$type->label().'.',
                    ]);
                }

                $value = $values[$criterion->code];
                $normalized = (float) $value['normalized'];
                $weighted = $normalized * ((float) $criterion->weight / 100);
                $total += $weighted;

                $application->scores()->updateOrCreate(
                    ['criterion_id' => $criterion->id],
                    [
                        'scorer_id' => $scorerId,
                        'raw_value' => is_numeric($value['raw']) ? (float) $value['raw'] : 0,
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
        $ranks = [];

        DB::transaction(function () use ($types, $kabupatenId, $period, &$ranks): void {
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
                    ->each(function (Collection $countySelections) use (&$ranks): void {
                        $countySelections
                            ->sort(function (Selection $left, Selection $right): int {
                                return (float) $right->final_score <=> (float) $left->final_score
                                    ?: $left->application_id <=> $right->application_id;
                            })
                            ->values()
                            ->each(function (Selection $selection, int $index) use (&$ranks): void {
                                // application_id & final_score disertakan karena upsert pada
                                // SQLite mengisi ulang seluruh kolom NOT NULL tanpa default.
                                $ranks[] = [
                                    'id' => $selection->id,
                                    'application_id' => $selection->application_id,
                                    'final_score' => $selection->final_score,
                                    'rank' => $index + 1,
                                ];
                            });
                    });
            }

            // ponytail: upsert melewati event Eloquent sehingga pergeseran rank tidak teraudit;
            // jika jejak audit per-rank kelak dibutuhkan, kembalikan ke update per-baris.
            if ($ranks !== []) {
                Selection::query()->upsert($ranks, ['id'], ['rank']);
            }
        });

        return Selection::query()
            ->whereKey(array_column($ranks, 'id'))
            ->orderBy('rank')
            ->get();
    }
}
