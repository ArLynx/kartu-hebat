<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $selectedType = ApplicationType::tryFrom($request->string('application_type')->toString());
        $baseQuery = Application::query()
            ->visibleTo($request->user())
            ->where('periode', config('kartu_hebat.current_period'))
            ->when($selectedType, fn ($query) => $query->where('application_type', $selectedType->value));

        $applications = (clone $baseQuery)
            ->with(['mahasiswa.profile.village.kecamatan', 'agencyVerifications.verifier'])
            ->whereIn('status', [
                ApplicationStatus::VERIFIKASI_DINAS->value,
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::TMS->value,
                ApplicationStatus::BTL_KECAMATAN->value,
            ])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => $applications->total(),
            'complete' => (clone $baseQuery)
                ->has('agencyVerifications', '=', count(config('kartu_hebat.agencies')))
                ->count(),
            'ready' => (clone $baseQuery)
                ->where('status', ApplicationStatus::SELEKSI_KABUPATEN->value)
                ->count(),
            'problem' => (clone $baseQuery)
                ->whereIn('status', [
                    ApplicationStatus::TMS->value,
                    ApplicationStatus::BTL_KECAMATAN->value,
                ])
                ->count(),
        ];

        return view('operator.reconciliation', [
            'applications' => $applications,
            'summary' => $summary,
            'applicationTypes' => ApplicationType::cases(),
        ]);
    }
}
