<?php

namespace App\Exports;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\KategoriPrestasi;
use App\Models\Application;
use App\Services\Scoring\NonAcademicTierResolver;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CandidatesExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    /** @var array<int, string>|null */
    private ?array $textColumns = null;

    public function __construct(
        private readonly int $kabupatenId,
        private readonly ?ApplicationType $applicationType = null,
        private readonly ?int $jalurBeasiswaId = null,
        private readonly bool $onlyWithoutJalur = false,
        private readonly ?string $sheetTitle = null,
    ) {}

    public function collection(): Collection
    {
        return Application::query()
            ->with([
                'mahasiswa.profile.village.kecamatan',
                'selection',
                'pendaftaran.jalurBeasiswa',
                'pendaftaran.pendidikan',
                'pendaftaran.dataPribadi',
                'pendaftaran.prestasis',
            ])
            ->where('periode', config('kartu_hebat.current_period'))
            ->when($this->applicationType, fn ($query) => $query->where('application_type', $this->applicationType->value))
            ->when($this->onlyWithoutJalur, fn ($query) => $query->whereDoesntHave(
                'pendaftaran',
                fn ($pendaftaran) => $pendaftaran->whereNotNull('jalur_beasiswa_id'),
            ))
            ->when($this->jalurBeasiswaId, fn ($query) => $query->whereHas('pendaftaran', fn ($q) => $q->where('jalur_beasiswa_id', $this->jalurBeasiswaId)))
            ->whereHas('mahasiswa.profile.village', fn ($query) => $query->where('kabupaten_id', $this->kabupatenId))
            ->whereIn('status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])
            ->get()
            ->sortBy(fn (Application $application) => sprintf(
                '%s-%s-%09d',
                $application->application_type?->value ?? 'ZZZ',
                $application->pendaftaran?->jalur_beasiswa_id ?? '0',
                $application->selection?->rank ?? PHP_INT_MAX,
            ))
            ->values();
    }

    public function headings(): array
    {
        return [
            'Jalur Pengajuan',
            'Kategori Mahasiswa',
            'Peringkat Jalur',
            'Nomor Pengajuan',
            'Nama Mahasiswa',
            'NIK',
            'NIM',
            'Universitas',
            'Program Studi',
            'Semester',
            'IPK',
            'UKT',
            'Nomor Rekening',
            'Tahun Masuk (Angkatan)',
            'Akreditasi PT',
            'Pengurus Ormawa',
            'Tier Prestasi Non-Akademik',
            'Desil Sosial',
            'Kecamatan',
            'Desa/Kelurahan',
            'Skor Akhir',
            'Keputusan',
            'Catatan Internal',
        ];
    }

    public function map($application): array
    {
        $pendidikan = $application->pendaftaran?->pendidikan;
        $prestasis = $application->pendaftaran?->prestasis ?? collect();
        $tierResolver = app(NonAcademicTierResolver::class);
        $ormawa = $tierResolver->bestRecordOfCategory($prestasis, KategoriPrestasi::ORGANISASI);
        $tierProfile = $tierResolver->profile($prestasis);

        return [
            $application->application_type?->label(),
            $application->pendaftaran?->jalurBeasiswa?->nama ?? '-',
            $application->selection?->rank,
            $application->nomor_pengajuan,
            $application->mahasiswa->name,
            $application->mahasiswa->profile?->nik,
            $application->mahasiswa->profile?->nim,
            $application->mahasiswa->profile?->universitas,
            $application->mahasiswa->profile?->program_studi,
            $application->mahasiswa->profile?->semester,
            $application->mahasiswa->profile?->ipk,
            $pendidikan?->nominal_ukt !== null ? (float) $pendidikan->nominal_ukt : '-',
            $application->pendaftaran?->dataPribadi?->nomor_rekening ?: '-',
            $pendidikan?->tahun_masuk ?? '-',
            $pendidikan?->akreditasi_perguruan_tinggi ?? '-',
            $ormawa !== null
                ? $ormawa->posisiEnum()?->label().' · '.($ormawa->tingkatOrganisasiEnum()?->label() ?? 'Universitas')
                : 'Tidak',
            match ($tierProfile->tier) {
                NonAcademicTierResolver::TIER_PENGURUS_INTI => 'Tier 1 - Pengurus Inti',
                NonAcademicTierResolver::TIER_JUARA => 'Tier 2 - Juara Lomba',
                NonAcademicTierResolver::TIER_ANGGOTA_ORGANISASI => 'Tier 3 - Anggota Pengurus',
                NonAcademicTierResolver::TIER_PESERTA => 'Tier 4 - Peserta Lomba',
                default => 'Tanpa Prestasi',
            },
            $application->mahasiswa->profile?->desil_sosial,
            $application->mahasiswa->profile?->village?->kecamatan?->name,
            $application->mahasiswa->profile?->village?->display_name,
            $application->selection?->final_score,
            $this->decisionValue($application),
            $application->selection?->notes ?? '',
        ];
    }

    public function title(): string
    {
        return $this->sheetTitle ?? 'Rekap Seleksi';
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->textColumns() as $column) {
            $formats[$column] = NumberFormat::FORMAT_TEXT;
        }

        return $formats;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (in_array($cell->getColumn(), $this->textColumns(), true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder)->bindValue($cell, $value);
    }

    /**
     * @return array<int, string>
     */
    private function textColumns(): array
    {
        if ($this->textColumns !== null) {
            return $this->textColumns;
        }

        $this->textColumns = [];

        foreach (['NIK', 'Nomor Rekening'] as $heading) {
            $index = array_search($heading, $this->headings(), true);

            if ($index !== false) {
                $this->textColumns[] = Coordinate::stringFromColumnIndex($index + 1);
            }
        }

        return $this->textColumns;
    }

    private function decisionValue(Application $application): string
    {
        if ($application->selection?->published_at) {
            return $application->status->value;
        }

        $manualDecision = $application->selection?->manualDecisionStatus();

        if ($manualDecision !== null) {
            return $manualDecision->value;
        }

        $rank = $application->selection?->rank;
        $quota = $application->application_type?->quota() ?? 0;

        return ($rank !== null && $rank <= $quota)
            ? ApplicationStatus::DITERIMA->value
            : ApplicationStatus::DITOLAK->value;
    }
}
