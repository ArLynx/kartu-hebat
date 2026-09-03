<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\DocumentVerificationService;
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
            ->with(['mahasiswa.profile.village.kecamatan', 'agencyVerifications.verifier', 'pendaftaran.jalurBeasiswa'])
            ->whereIn('status', [
                ApplicationStatus::VERIFIKASI_DINAS->value,
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::TMS->value,
            ])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => $applications->total(),
            'complete' => (clone $baseQuery)
                ->whereIn('status', [
                    ApplicationStatus::VERIFIKASI_DINAS->value,
                    ApplicationStatus::SELEKSI_KABUPATEN->value,
                    ApplicationStatus::TMS->value,
                ])
                ->get()
                ->filter(fn (Application $application) => $this->isComplete($application))
                ->count(),
            'ready' => (clone $baseQuery)
                ->where('status', ApplicationStatus::SELEKSI_KABUPATEN->value)
                ->count(),
            'problem' => (clone $baseQuery)
                ->where('status', ApplicationStatus::TMS->value)
                ->count(),
        ];

        return view('operator.reconciliation', [
            'applications' => $applications,
            'summary' => $summary,
            'applicationTypes' => ApplicationType::cases(),
            'agencies' => array_keys(config('kartu_hebat.agencies')),
        ]);
    }

    private function isComplete(Application $application): bool
    {
        $required = DocumentVerificationService::requiredAgencies($application);

        return count($required) > 0
            && count($application->agencyVerifications) >= count($required);
    }
}
