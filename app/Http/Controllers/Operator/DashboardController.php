<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $base = Application::query()
            ->visibleTo($user)
            ->where('periode', config('kartu_hebat.current_period'));

        if ($track = $user->role->verifiedTrack()) {
            $base->where('application_type', $track->value);
        }

        $queueStatuses = match ($user->role) {
            UserRole::OPERATOR_DUKCAPIL,
            UserRole::OPERATOR_SOSIAL,
            UserRole::OPERATOR_PENDIDIKAN,
            UserRole::OPERATOR_BKPSDM,
            UserRole::OPERATOR_DINKES,
            UserRole::OPERATOR_PARSEPOR => [ApplicationStatus::VERIFIKASI_DINAS->value],
            UserRole::OPERATOR_KABUPATEN => [ApplicationStatus::SELEKSI_KABUPATEN->value],
            default => [],
        };

        $stats = [
            'total' => (clone $base)->where('applications.status', '!=', ApplicationStatus::DRAFT->value)->count(),
            'queue' => (clone $base)->whereIn('status', $queueStatuses)->count(),
            'revision' => (clone $base)->where('applications.status', '=', ApplicationStatus::DRAFT->value)->count(),
            'completed' => (clone $base)->whereIn('status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])->count(),
        ];

        $recent = (clone $base)
            ->with(['mahasiswa.profile.village.kecamatan', 'pendaftaran.jalurBeasiswa'])
            ->where('applications.status', '!=', ApplicationStatus::DRAFT->value)
            ->latest('updated_at')
            ->limit(8)
            ->get();

        $byDistrict = Application::query()
            ->visibleTo($user)
            ->where('applications.periode', config('kartu_hebat.current_period'))
            ->where('applications.status', '!=', ApplicationStatus::DRAFT->value)
            ->selectRaw('kecamatans.name as district_name, count(*) as total')
            ->join('users as students', 'students.id', '=', 'applications.mahasiswa_id')
            ->join('mahasiswa_profiles', 'mahasiswa_profiles.user_id', '=', 'students.id')
            ->join('villages', 'villages.id', '=', 'mahasiswa_profiles.village_id')
            ->join('kecamatans', 'kecamatans.id', '=', 'villages.kecamatan_id')
            ->groupBy('kecamatans.id', 'kecamatans.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $agencyCoverage = $user->role === UserRole::OPERATOR_KABUPATEN
            ? $this->agencyCoverage($user)
            : collect();

        return view('operator.dashboard', compact('stats', 'recent', 'byDistrict', 'queueStatuses', 'agencyCoverage'));
    }

    /**
     * Ringkasan cakupan verifikasi tiap SOPD pada periode aktif.
     *
     * @return Collection<int, array{
     *     code: string,
     *     name: string,
     *     requirement: string,
     *     total: int,
     *     verified: int,
     *     remaining: int,
     *     percent_verified: float|null
     * }>
     */
    private function agencyCoverage(User $user): Collection
    {
        $base = Application::query()
            ->visibleTo($user)
            ->where('applications.periode', config('kartu_hebat.current_period'))
            ->where('applications.status', '!=', ApplicationStatus::DRAFT->value);

        $roles = collect(UserRole::cases())
            ->filter(fn (UserRole $role) => $role->isAgency())
            ->keyBy(fn (UserRole $role) => $role->agencyCode());

        $sopdNames = [
            'dukcapil' => 'Dinas Kependudukan dan Pencatatan Sipil',
            'pendidikan' => 'Dinas Pendidikan dan Kebudayaan',
            'bkpsdm' => 'BKPSDM',
            'sosial' => 'Dinas Sosial',
            'kesehatan' => 'Dinas Kesehatan',
            'parsepor' => 'Dinas Parsepor',
        ];

        return collect($sopdNames)
            ->map(function (string $name, string $code) use ($base, $roles): array {
                $track = $roles->get($code)->verifiedTrack();

                $coverage = (clone $base)
                    ->when($track, fn (Builder $query) => $query->where('applications.application_type', $track->value))
                    ->leftJoin('agency_verifications as agency_coverage', function (JoinClause $join) use ($code): void {
                        $join->on('agency_coverage.application_id', '=', 'applications.id')
                            ->where('agency_coverage.agency', '=', $code);
                    })
                    ->selectRaw('COUNT(*) as total, COUNT(agency_coverage.id) as verified')
                    ->first();

                $total = (int) $coverage->total;
                $verified = (int) $coverage->verified;

                return [
                    'code' => $code,
                    'name' => $name,
                    'requirement' => match ($track) {
                        null => 'Semua jalur',
                        ApplicationType::AKADEMIK => 'Jalur Akademik',
                        ApplicationType::TIDAK_MAMPU => 'Jalur Tidak Mampu',
                        ApplicationType::DISABILITAS => 'Jalur Disabilitas',
                        ApplicationType::NON_AKADEMIK => 'Jalur Non-Akademik',
                    },
                    'total' => $total,
                    'verified' => $verified,
                    'remaining' => $total - $verified,
                    'percent_verified' => $total > 0 ? round(100 * $verified / $total, 1) : null,
                ];
            })
            ->values();
    }
}
