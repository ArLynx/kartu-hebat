<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

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
            ->with(['mahasiswa.profile.village.kecamatan'])
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

        return view('operator.dashboard', compact('stats', 'recent', 'byDistrict', 'queueStatuses'));
    }
}
