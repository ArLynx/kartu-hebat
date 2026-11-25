<?php

namespace App\Exports;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SanggahanStatus;
use App\Enums\VerificationDecision;
use App\Models\Application;
use App\Services\AgencyVerificationService;
use Illuminate\Database\Eloquent\Builder;
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

class TmsCandidatesExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithTitle
{
    private int $rowNumber = 0;

    /** @var array<int, string>|null */
    private ?array $textColumns = null;

    public function __construct(
        private readonly int $kabupatenId,
        private readonly ?string $periode = null,
        private readonly ?ApplicationType $applicationType = null,
        private readonly ?int $jalurBeasiswaId = null,
        private readonly ?string $sheetTitle = null,
    ) {}

    public function collection(): Collection
    {
        $periode = $this->periode ?: (string) config('kartu_hebat.current_period');

        return Application::query()
            ->with([
                'mahasiswa.profile.village.kecamatan',
                'pendaftaran.jalurBeasiswa',
                'pendaftaran.pendidikan',
                'pendaftaran.dataPribadi',
                'agencyVerifications.verifier',
                'sanggahan',
                'verificationLogs' => fn ($query) => $query->where('action', 'sanggahan_granted'),
            ])
            ->where('periode', $periode)
            ->where(function (Builder $query): void {
                $query->where('applications.status', ApplicationStatus::TMS->value)
                    ->orWhere(function (Builder $subQuery): void {
                        $subQuery->where('applications.status', ApplicationStatus::VERIFIKASI_DINAS->value)
                            ->whereHas('sanggahan', fn (Builder $s) => $s->where('status', SanggahanStatus::DIKABULKAN->value));
                    });
            })
            ->when($this->applicationType, fn ($query) => $query->where('application_type', $this->applicationType->value))
            ->when($this->jalurBeasiswaId, fn ($query) => $query->whereHas('pendaftaran', fn ($q) => $q->where('jalur_beasiswa_id', $this->jalurBeasiswaId)))
            ->whereHas('mahasiswa.profile.village', fn ($query) => $query->where('kabupaten_id', $this->kabupatenId))
            ->latest('updated_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Pengajuan',
            'Tanggal Pengajuan',
            'Nama Mahasiswa',
            'NIK',
            'NIM',
            'Universitas',
            'Program Studi',
            'Semester',
            'UKT',
            'Nomor Rekening',
            'Jalur Beasiswa',
            'Kategori Mahasiswa',
            'Kecamatan',
            'Desa/Kelurahan',
            'Dinas Penolak (TMS)',
            'Alasan / Catatan Penolakan Dinas',
            'Status Sanggahan',
            'Catatan / Keputusan Sanggahan',
        ];
    }

    public function map($application): array
    {
        $this->rowNumber++;

        $tmsVerifications = $application->agencyVerifications
            ->where('decision', VerificationDecision::TMS);

        $grantedLog = $application->status === ApplicationStatus::VERIFIKASI_DINAS
            ? $application->verificationLogs->firstWhere('action', 'sanggahan_granted')
            : null;
        $metadata = $grantedLog?->metadata ?? [];
        $annulledAgencies = collect($metadata['annulled_agencies'] ?? []);
        $annulled = collect($metadata['annulled_verifications'] ?? []);

        if ($annulled->isEmpty() && $annulledAgencies->isNotEmpty()) {
            $annulled = $annulledAgencies->map(fn ($ag) => [
                'agency' => $ag,
                'agency_label' => AgencyVerificationService::stageLabel($ag),
                'notes' => $application->sanggahan?->catatan_keputusan ?? 'Keputusan TMS dianulir lewat sanggahan.',
            ]);
        }

        if ($tmsVerifications->isNotEmpty()) {
            $dinasPenolak = $tmsVerifications
                ->map(fn ($v) => AgencyVerificationService::stageLabel($v->agency))
                ->unique()
                ->implode(', ');

            $catatanPenolakan = $tmsVerifications
                ->filter(fn ($v) => filled($v->notes))
                ->map(fn ($v) => AgencyVerificationService::stageLabel($v->agency).': '.$v->notes)
                ->implode('; ');
        } elseif ($annulled->isNotEmpty()) {
            $dinasPenolak = $annulled
                ->map(fn ($v) => $v['agency_label'] ?? AgencyVerificationService::stageLabel($v['agency']))
                ->unique()
                ->implode(', ');

            $catatanPenolakan = $annulled
                ->filter(fn ($v) => filled($v['notes'] ?? null))
                ->map(fn ($v) => ($v['agency_label'] ?? AgencyVerificationService::stageLabel($v['agency'])).': '.$v['notes'])
                ->implode('; ');
        } else {
            $dinasPenolak = '-';
            $catatanPenolakan = '-';
        }

        if (empty($catatanPenolakan) && filled($application->catatan)) {
            $catatanPenolakan = $application->catatan;
        }

        $sanggahanStatus = 'Belum Ada Sanggahan';
        $sanggahanCatatan = '-';
        if ($application->sanggahan) {
            $sanggahanStatus = $application->sanggahan->status->label();
            if ($application->sanggahan->status === SanggahanStatus::DIKABULKAN && $application->status === ApplicationStatus::VERIFIKASI_DINAS) {
                $sanggahanStatus .= ' (Menunggu dinas menilai ulang)';
            }
            $sanggahanCatatan = $application->sanggahan->catatan_keputusan ?? $application->sanggahan->uraian;
        }

        return [
            $this->rowNumber,
            $application->nomor_pengajuan,
            $application->submitted_at?->format('d/m/Y H:i') ?? '-',
            $application->mahasiswa->name,
            $application->mahasiswa->profile?->nik,
            $application->mahasiswa->profile?->nim,
            $application->mahasiswa->profile?->universitas,
            $application->mahasiswa->profile?->program_studi,
            $application->mahasiswa->profile?->semester,
            $application->pendaftaran?->pendidikan?->nominal_ukt !== null
                ? (float) $application->pendaftaran->pendidikan->nominal_ukt
                : '-',
            $application->pendaftaran?->dataPribadi?->nomor_rekening ?: '-',
            $application->application_type?->label(),
            $application->pendaftaran?->jalurBeasiswa?->nama ?? '-',
            $application->mahasiswa->profile?->village?->kecamatan?->name ?? '-',
            $application->mahasiswa->profile?->village?->display_name ?? '-',
            $dinasPenolak ?: '-',
            $catatanPenolakan ?: '-',
            $sanggahanStatus,
            $sanggahanCatatan,
        ];
    }

    public function title(): string
    {
        return $this->sheetTitle ?? 'Rekap Berkas TMS';
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
}
