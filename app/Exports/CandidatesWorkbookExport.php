<?php

namespace App\Exports;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\JalurBeasiswa;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CandidatesWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $kabupatenId,
        private readonly ?ApplicationType $applicationType = null,
        private readonly ?int $jalurBeasiswaId = null,
    ) {}

    /**
     * @return array<int, CandidatesExport>
     */
    public function sheets(): array
    {
        $jalurNames = JalurBeasiswa::query()->pluck('nama', 'id');
        $combinations = $this->combinations();

        if ($combinations->isEmpty()) {
            return [new CandidatesExport(
                kabupatenId: $this->kabupatenId,
                applicationType: $this->applicationType,
                jalurBeasiswaId: $this->jalurBeasiswaId,
                sheetTitle: 'Rekap Seleksi',
            )];
        }

        return $combinations
            ->map(function (object $row) use ($jalurNames): CandidatesExport {
                $type = $row->application_type instanceof ApplicationType
                    ? $row->application_type
                    : ApplicationType::tryFrom((string) $row->application_type);
                $jalurId = $row->jalur_beasiswa_id !== null ? (int) $row->jalur_beasiswa_id : null;

                return new CandidatesExport(
                    kabupatenId: $this->kabupatenId,
                    applicationType: $type,
                    jalurBeasiswaId: $jalurId,
                    onlyWithoutJalur: $jalurId === null,
                    sheetTitle: $this->makeSheetTitle($type, $jalurId, $jalurNames),
                );
            })
            ->all();
    }

    private function combinations(): Collection
    {
        return Application::query()
            ->where('applications.periode', config('kartu_hebat.current_period'))
            ->when($this->applicationType, fn ($query) => $query->where('applications.application_type', $this->applicationType->value))
            ->when($this->jalurBeasiswaId, fn ($query) => $query->whereHas(
                'pendaftaran',
                fn ($pendaftaran) => $pendaftaran->where('jalur_beasiswa_id', $this->jalurBeasiswaId),
            ))
            ->whereHas('mahasiswa.profile.village', fn ($query) => $query->where('kabupaten_id', $this->kabupatenId))
            ->whereIn('applications.status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])
            ->leftJoin('pendaftarans', 'pendaftarans.id', '=', 'applications.pendaftaran_id')
            ->selectRaw('applications.application_type, pendaftarans.jalur_beasiswa_id')
            ->groupBy('applications.application_type', 'pendaftarans.jalur_beasiswa_id')
            ->orderBy('applications.application_type')
            ->orderBy('pendaftarans.jalur_beasiswa_id')
            ->get();
    }

    private function makeSheetTitle(?ApplicationType $type, ?int $jalurId, Collection $jalurNames): string
    {
        $typeLabel = match ($type) {
            ApplicationType::NON_AKADEMIK => 'Non Akademik',
            null => 'Semua Jalur',
            default => $type->label(),
        };

        $jalurLabel = $jalurId !== null ? (string) ($jalurNames->get($jalurId) ?? 'Kategori '.$jalurId) : 'Tanpa Kategori';

        return mb_substr($typeLabel.' - '.$jalurLabel, 0, 31);
    }
}
