<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\DocumentVerificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Application::query()
            ->visibleTo($user)
            ->where('periode', config('kartu_hebat.current_period'))
            ->with(['mahasiswa.profile.village.kecamatan', 'selection'])
            ->whereNot('status', ApplicationStatus::DRAFT->value);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        } else {
            $query->whereIn('status', $this->defaultQueueStatuses($user->role));
        }

        if ($request->filled('application_type')) {
            $query->where('application_type', $request->string('application_type')->toString());
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('nomor_pengajuan', 'like', $search)
                    ->orWhereHas('mahasiswa', fn (Builder $student) => $student->where('name', 'like', $search))
                    ->orWhereHas('mahasiswa.profile', fn (Builder $profile) => $profile
                        ->where('nik', 'like', $search)
                        ->orWhere('nim', 'like', $search));
            });
        }

        return view('operator.applications.index', [
            'applications' => $query->latest('updated_at')->paginate(15)->withQueryString(),
            'statuses' => ApplicationStatus::cases(),
            'applicationTypes' => ApplicationType::cases(),
        ]);
    }

    public function show(Request $request, Application $application)
    {
        $this->authorize('view', $application);

        $user = $request->user();
        $currentRound = DocumentVerificationService::currentRound($application);

        $application->load([
            'mahasiswa.profile.village.kecamatan',
            'documents.type',
            'documents.verifications' => fn ($query) => $query
                ->where('round', $currentRound)
                ->with('verifier'),
            'villageVerification.verifier',
            'districtVerification.verifier',
            'agencyVerifications.verifier',
            'verificationLogs.actor',
            'scores.criterion',
            'selection',
            'pendaftaran.prestasis',
        ]);

        $verifications = $application->documentVerifications;
        $stage = null;
        $canEditChecklist = false;

        if ($user->isOperator() && $user->role !== UserRole::OPERATOR_KABUPATEN) {
            $stage = DocumentVerificationService::stageFor($user);
            $canEditChecklist = DocumentVerificationService::canVerifyStage($application, $stage);
        }

        return view('operator.applications.show', [
            'application' => $application,
            'canVerify' => $user->can('verify', $application),
            'docVerifications' => $verifications,
            'docVerificationStage' => $stage,
            'canEditChecklist' => $canEditChecklist,
            'docVerificationRound' => $currentRound,
        ]);
    }

    private function defaultQueueStatuses(UserRole $role): array
    {
        return match ($role) {
            UserRole::OPERATOR_DESA => [
                ApplicationStatus::SUBMITTED->value,
                ApplicationStatus::VERIFIKASI_DESA->value,
            ],
            UserRole::OPERATOR_KECAMATAN => [
                ApplicationStatus::VERIFIKASI_KECAMATAN->value,
            ],
            UserRole::OPERATOR_DUKCAPIL,
            UserRole::OPERATOR_SOSIAL,
            UserRole::OPERATOR_PENDIDIKAN => [ApplicationStatus::VERIFIKASI_DINAS->value],
            UserRole::OPERATOR_KABUPATEN => [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ],
            default => [],
        };
    }
}
