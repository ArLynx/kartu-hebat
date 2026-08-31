<?php

namespace App\Exports;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApplicationsRecapExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly string $role,
        private readonly ?int $villageId,
        private readonly ?int $kecamatanId,
        private readonly ?int $kabupatenId,
    ) {}

    public static function forUser(User $user): self
    {
        return new self(
            $user->role->value,
            $user->village_id,
            $user->kecamatan_id,
            $user->kabupaten_id,
        );
    }

    public function collection(): Collection
    {
        $query = Application::query()
            ->with(['mahasiswa.profile.village.kecamatan'])
            ->where('periode', config('kartu_hebat.current_period'))
            ->where('status', '!=', ApplicationStatus::DRAFT->value);

        $this->applyRegionScope($query);

        return $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nomor Pengajuan',
            'Jalur Pengajuan',
            'Periode',
            'Nama Mahasiswa',
            'NIK',
            'NIM',
            'Universitas',
            'Program Studi',
            'Kecamatan',
            'Desa/Kelurahan',
            'Status',
            'Tanggal Dikirim',
            'Terakhir Diperbarui',
        ];
    }

    public function map($application): array
    {
        return [
            $application->nomor_pengajuan,
            $application->application_type?->label(),
            $application->periode,
            $application->mahasiswa->name,
            $application->mahasiswa->profile?->nik,
            $application->mahasiswa->profile?->nim,
            $application->mahasiswa->profile?->universitas,
            $application->mahasiswa->profile?->program_studi,
            $application->mahasiswa->profile?->village?->kecamatan?->name,
            $application->mahasiswa->profile?->village?->display_name,
            $application->status->label(),
            $application->submitted_at?->format('Y-m-d H:i:s'),
            $application->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function applyRegionScope(Builder $query): void
    {
        match (UserRole::from($this->role)) {
            UserRole::OPERATOR_DESA => $query->whereHas(
                'mahasiswa.profile',
                fn (Builder $profile) => $profile->where('village_id', $this->villageId),
            ),
            UserRole::OPERATOR_KECAMATAN => $query->whereHas(
                'mahasiswa.profile.village',
                fn (Builder $village) => $village->where('kecamatan_id', $this->kecamatanId),
            ),
            default => $query->whereHas(
                'mahasiswa.profile.village',
                fn (Builder $village) => $village->where('kabupaten_id', $this->kabupatenId),
            ),
        };
    }
}
