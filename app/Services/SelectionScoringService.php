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
        $application->loadMissing([
            'mahasiswa.profile',
            'pendaftaran.pendidikan',
            'pendaftaran.dataPribadi',
            'pendaftaran.prestasis',
            'agencyVerifications',
        ]);
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
                    ->with([
                        'application.mahasiswa.profile.village',
                        'application.pendaftaran.pendidikan',
                        'application.pendaftaran.prestasis',
                        'application.agencyVerifications',
                    ])
                    ->get();

                $selections
                    ->groupBy(function (Selection $selection): string {
                        $kabupaten = $selection->application->mahasiswa->profile?->village?->kabupaten_id ?? '0';
                        $jalur = $selection->application->pendaftaran?->jalur_beasiswa_id ?? '0';

                        return "{$kabupaten}-{$jalur}";
                    })
                    ->each(function (Collection $countySelections) use (&$ranks, $type): void {
                        $countySelections
                            ->sort(function (Selection $left, Selection $right) use ($type): int {
                                return $this->compareSelections($left, $right, $type);
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

    public function compareSelections(Selection $left, Selection $right, ApplicationType $type): int
    {
        $appL = $left->application;
        $appR = $right->application;

        $penL = $appL->pendaftaran?->pendidikan;
        $penR = $appR->pendaftaran?->pendidikan;

        $profL = $appL->mahasiswa?->profile;
        $profR = $appR->mahasiswa?->profile;

        $ipkL = (float) ($penL?->ipk ?? $profL?->ipk ?? 0);
        $ipkR = (float) ($penR?->ipk ?? $profR?->ipk ?? 0);

        $tahunL = (int) ($penL?->tahun_masuk ?? 9999);
        $tahunR = (int) ($penR?->tahun_masuk ?? 9999);
        if ($tahunL <= 0) {
            $tahunL = 9999;
        }
        if ($tahunR <= 0) {
            $tahunR = 9999;
        }

        $akreditasiL = $this->getAkreditasiScore($penL?->akreditasi_perguruan_tinggi);
        $akreditasiR = $this->getAkreditasiScore($penR?->akreditasi_perguruan_tinggi);

        switch ($type) {
            case ApplicationType::TIDAK_MAMPU:
                // 1. desil 1 sampai dengan desil 5 pada DTSEN (ASC: 1 > 2 > 3 > 4 > 5 ...)
                $desilL = $this->resolveDesil($appL);
                $desilR = $this->resolveDesil($appR);

                // 3. nilai raport kelas XII (dua belas) semester genap (DESC)
                $raportL = (float) ($penL?->nilai_raport ?? 0);
                $raportR = (float) ($penR?->nilai_raport ?? 0);

                return ($desilL <=> $desilR)
                    ?: ($ipkR <=> $ipkL)
                    ?: ($raportR <=> $raportL)
                    ?: ($tahunL <=> $tahunR)
                    ?: ($akreditasiR <=> $akreditasiL)
                    ?: ($left->application_id <=> $right->application_id);

            case ApplicationType::AKADEMIK:
                // 1. nilai indeks prestasi total/kumulatif (DESC)
                // 2. tahun angkatan masuk perguruan tinggi yang lebih awal (ASC)
                // 3. akreditasi Perguruan Tinggi (DESC)
                return ($ipkR <=> $ipkL)
                    ?: ($tahunL <=> $tahunR)
                    ?: ($akreditasiR <=> $akreditasiL)
                    ?: ($left->application_id <=> $right->application_id);

            case ApplicationType::NON_AKADEMIK:
                // 1. tingkatan juara 1, 2, atau 3 kompetisi non akademik (DESC)
                $prestasisL = $appL->pendaftaran?->prestasis ?? collect();
                $prestasisR = $appR->pendaftaran?->prestasis ?? collect();

                $lombaL = $this->getBestNonAcademicCompetitionScore($prestasisL);
                $lombaR = $this->getBestNonAcademicCompetitionScore($prestasisR);

                // 2. kepengurusan inti organisasi mahasiswa tingkat universitas (DESC)
                $ormawaL = (int) $prestasisL->contains(fn ($p) => (bool) $p->is_pengurus_inti_ormawa);
                $ormawaR = (int) $prestasisR->contains(fn ($p) => (bool) $p->is_pengurus_inti_ormawa);

                // 3. tahun angkatan masuk perguruan tinggi yang lebih awal (ASC)
                // 4. akreditasi Perguruan Tinggi (DESC)
                return ($lombaR <=> $lombaL)
                    ?: ($ormawaR <=> $ormawaL)
                    ?: ($tahunL <=> $tahunR)
                    ?: ($akreditasiR <=> $akreditasiL)
                    ?: ($left->application_id <=> $right->application_id);

            case ApplicationType::DISABILITAS:
                // 1. tahun angkatan masuk perguruan tinggi yang lebih awal (ASC)
                // 2. akreditasi Perguruan Tinggi (DESC)
                return ($tahunL <=> $tahunR)
                    ?: ($akreditasiR <=> $akreditasiL)
                    ?: ($left->application_id <=> $right->application_id);

            default:
                return (float) $right->final_score <=> (float) $left->final_score
                    ?: ($left->application_id <=> $right->application_id);
        }
    }

    public function resolveDesil(Application $application): float
    {
        $application->loadMissing(['agencyVerifications', 'mahasiswa.profile']);

        $agencyDesils = $application->agencyVerifications
            ->pluck('metadata.desil')
            ->filter(fn ($desil) => $desil !== null && is_numeric($desil))
            ->map(fn ($desil) => (float) $desil);

        $profile = $application->mahasiswa?->profile;
        $profileDesils = collect([$profile?->desil_sosial])
            ->filter(fn ($desil) => $desil !== null && is_numeric($desil))
            ->map(fn ($desil) => (float) $desil);

        $available = $agencyDesils->isNotEmpty() ? $agencyDesils : $profileDesils;

        if ($available->isEmpty()) {
            return 999.0;
        }

        return (float) $available->average();
    }

    public function getAkreditasiScore(?string $akreditasi): int
    {
        if (! $akreditasi) {
            return 1;
        }

        $normalized = strtoupper(trim($akreditasi));

        if (str_contains($normalized, 'UNGGUL') || $normalized === 'A') {
            return 4;
        }

        if (str_contains($normalized, 'BAIK SEKALI') || $normalized === 'B') {
            return 3;
        }

        if (str_contains($normalized, 'BAIK') || $normalized === 'C') {
            return 2;
        }

        return 1;
    }

    public function calculateTingkatScore(?string $tingkat): float
    {
        $key = strtolower(trim((string) $tingkat));

        return match ($key) {
            'internasional' => 100.0,
            'nasional' => 80.0,
            'provinsi' => 60.0,
            'kabupaten', 'kabupaten/kota', 'kota' => 40.0,
            'kampus', 'universitas' => 20.0,
            default => 10.0,
        };
    }

    public function calculatePeringkatScore(?string $peringkat): float
    {
        $normalized = strtolower(trim((string) $peringkat));
        $normalized = preg_replace('/\s+/', '_', $normalized) ?? '';

        if (str_contains($normalized, 'juara_1') || str_contains($normalized, 'juara_i') || str_contains($normalized, 'emas') || str_contains($normalized, 'pertama') || $normalized === '1') {
            return 100.0;
        }

        if (str_contains($normalized, 'juara_2') || str_contains($normalized, 'juara_ii') || str_contains($normalized, 'perak') || str_contains($normalized, 'kedua') || $normalized === '2') {
            return 85.0;
        }

        if (str_contains($normalized, 'juara_3') || str_contains($normalized, 'juara_iii') || str_contains($normalized, 'perunggu') || str_contains($normalized, 'ketiga') || $normalized === '3') {
            return 70.0;
        }

        if (str_contains($normalized, 'favorit') || str_contains($normalized, 'harapan')) {
            return 50.0;
        }

        return 20.0;
    }

    public function getBestNonAcademicCompetitionScore(Collection $prestasis): float
    {
        if ($prestasis->isEmpty()) {
            return 0.0;
        }

        return (float) $prestasis
            ->filter(fn ($p) => $p->jenis === 'non_akademik' || ! (bool) $p->is_pengurus_inti_ormawa)
            ->map(function ($p) {
                $tingkat = $this->calculateTingkatScore($p->tingkat);
                $peringkat = $this->calculatePeringkatScore($p->peringkat);

                return ($tingkat * 1000) + $peringkat;
            })
            ->max() ?? 0.0;
    }
}
