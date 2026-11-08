<?php

namespace App\Exports;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class CandidatesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
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
            'Tahun Masuk (Angkatan)',
            'Akreditasi PT',
            'Pengurus Ormawa Univ',
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
        $hasOrmawa = $application->pendaftaran?->prestasis?->contains(fn ($p) => (bool) $p->is_pengurus_inti_ormawa);

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
            $pendidikan?->tahun_masuk ?? '-',
            $pendidikan?->akreditasi_perguruan_tinggi ?? '-',
            $hasOrmawa ? 'Ya' : 'Tidak',
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
