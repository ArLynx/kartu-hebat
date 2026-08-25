<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use App\Services\SelectionScoringService;
use Illuminate\Console\Command;

class SelectionRescoreCommand extends Command
{
    protected $signature = 'selection:rescore {--kabupaten= : Filter kabupaten_id} {--type= : Filter application_type (AKADEMIK|TIDAK_MAMPU|DISABILITAS|NON_AKADEMIK)} {--period= : Periode, default config kartu_hebat.current_period}';

    protected $description = 'Backfill skor yang terlewat dan hitung ulang ranking — jaring pengaman untuk data seleksi (jangan dijalankan di GET).';

    public function handle(SelectionScoringService $scoring): int
    {
        $period = $this->option('period') ?: config('kartu_hebat.current_period');
        $kabupatenId = $this->option('kabupaten') !== null ? (int) $this->option('kabupaten') : null;
        $type = $this->option('type') ? ApplicationType::tryFrom((string) $this->option('type')) : null;

        if ($this->option('type') && ! $type) {
            $this->error('Nilai --type tidak valid. Gunakan: '.implode('|', array_map(fn ($c) => $c->value, ApplicationType::cases())));

            return self::FAILURE;
        }

        $query = Application::query()
            ->where('periode', $period)
            ->whereIn('status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])
            ->whereNotNull('application_type')
            ->whereDoesntHave('selection', fn ($s) => $s->whereNotNull('final_score'));

        if ($kabupatenId !== null) {
            $query->whereHas('mahasiswa.profile.village', fn ($v) => $v->where('kabupaten_id', $kabupatenId));
        }

        if ($type) {
            $query->where('application_type', $type->value);
        }

        $missing = $query->with('mahasiswa.profile')->get();
        $scored = 0;

        foreach ($missing as $application) {
            $scoring->calculate($application);
            $scored++;
        }

        $scoring->recalculateRanking($kabupatenId, $period, $type);

        $this->info("Scored {$scored} application(s), ranking recalculated for period {$period}".($kabupatenId ? " kabupaten {$kabupatenId}" : '').($type ? " type {$type->value}" : '').'.');

        return self::SUCCESS;
    }
}
