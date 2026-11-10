<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JalurBeasiswa;
use App\Models\Prestasi;
use App\Services\AgencyVerificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $agencyCode = $user->role->agencyCode();
        $query = Application::query()
            ->visibleTo($user)
            ->whereOpenForVerification()
            ->when($user->role->isAgency(), fn (Builder $query) => $query->whereNotAwaitingReverification())
            ->with(['mahasiswa.profile.village.kecamatan', 'selection', 'pendaftaran.jalurBeasiswa'])
            ->when($agencyCode, fn (Builder $query) => $query->with([
                'agencyVerifications' => fn ($relation) => $relation->where('agency', $agencyCode),
            ]))
            ->whereNot('status', ApplicationStatus::DRAFT->value);

        if ($track = $user->role->verifiedTrack()) {
            $query->where('application_type', $track->value);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        } else {
            $query->whereIn('status', $this->defaultQueueStatuses($user->role));
        }

        if ($request->filled('application_type')) {
            $query->where('application_type', $request->string('application_type')->toString());
        }

        if ($request->filled('jalur_beasiswa_id')) {
            $query->whereHas('pendaftaran', function (Builder $builder) use ($request): void {
                $builder->where('jalur_beasiswa_id', $request->integer('jalur_beasiswa_id'));
            });
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

        $baseCountQuery = Application::query()
            ->visibleTo($user)
            ->whereOpenForVerification()
            ->when($user->role->isAgency(), fn (Builder $query) => $query->whereNotAwaitingReverification())
            ->whereNot('applications.status', ApplicationStatus::DRAFT->value);

        if ($track = $user->role->verifiedTrack()) {
            $baseCountQuery->where('applications.application_type', $track->value);
        }

        if ($request->filled('status')) {
            $baseCountQuery->where('applications.status', $request->string('status')->toString());
        } else {
            $baseCountQuery->whereIn('applications.status', $this->defaultQueueStatuses($user->role));
        }

        if ($request->filled('application_type')) {
            $baseCountQuery->where('applications.application_type', $request->string('application_type')->toString());
        }

        $rawJalurCounts = (clone $baseCountQuery)
            ->leftJoin('pendaftarans', 'pendaftarans.id', '=', 'applications.pendaftaran_id')
            ->selectRaw('pendaftarans.jalur_beasiswa_id, count(*) as total')
            ->groupBy('pendaftarans.jalur_beasiswa_id')
            ->pluck('total', 'jalur_beasiswa_id');

        return view('operator.applications.index', [
            'applications' => $query->latest('updated_at')->paginate(15)->withQueryString(),
            'statuses' => ApplicationStatus::cases(),
            'applicationTypes' => ApplicationType::cases(),
            'jalurBeasiswas' => JalurBeasiswa::query()->where('aktif', true)->orderBy('urutan')->get(),
            'jalurCounts' => $rawJalurCounts,
            'totalQueueCount' => $rawJalurCounts->sum(),
            'sanggahanQueueCount' => $user->role->isAgency()
                ? Application::query()->visibleTo($user)->whereAwaitingReverificationBy($user)->count()
                : 0,
        ]);
    }

    public function show(Request $request, Application $application)
    {
        $this->authorize('view', $application);

        $user = $request->user();
        $currentRound = AgencyVerificationService::currentRound($application);

        $application->load([
            'mahasiswa.profile.village.kecamatan',
            'documents.type',
            'documents.verifications' => fn ($query) => $query
                ->where('round', $currentRound)
                ->with('verifier'),
            'agencyVerifications.verifier',
            'verificationLogs.actor',
            'scores.criterion',
            'selection',
            'sanggahan.decidedBy',
            'pendaftaran.prestasis',
            'pendaftaran.jalurBeasiswa',
            'pendaftaran.formulirPendaftaran',
            'pendaftaran.dataPribadi',
            'pendaftaran.orangTua',
        ]);

        $verifications = $application->documentVerifications;
        $verificationDocuments = AgencyVerificationService::documentsVisibleTo($application, $user);
        $canAssessDocuments = $user->role->isAgency()
            && $user->role->assessableDocumentCodes() !== [];
        $stage = null;
        $canEditChecklist = false;

        if ($user->role->isAgency()) {
            $stage = AgencyVerificationService::stageFor($user);
            $canEditChecklist = $canAssessDocuments
                && AgencyVerificationService::canVerifyStage($application, $stage);
        }

        $siblingApplications = collect();
        if ($user->role === UserRole::OPERATOR_BKPSDM) {
            $nomorKk = $application->pendaftaran?->dataPribadi?->nomor_kk;
            if ($nomorKk) {
                $periodeId = $application->pendaftaran?->periode_id;
                $siblingApplications = Application::query()
                    ->where('applications.id', '!=', $application->id)
                    ->whereHas('pendaftaran', function ($pq) use ($nomorKk, $periodeId) {
                        if ($periodeId) {
                            $pq->where('periode_id', $periodeId);
                        }
                        $pq->whereHas('dataPribadi', fn ($dq) => $dq->where('nomor_kk', $nomorKk));
                    })
                    ->with([
                        'mahasiswa',
                        'pendaftaran.dataPribadi',
                        'pendaftaran.orangTua',
                        'pendaftaran.jalurBeasiswa',
                    ])
                    ->get();
            }
        }

        return view('operator.applications.show', [
            'application' => $application,
            'canVerify' => $user->can('verify', $application),
            'docVerifications' => $verifications,
            'verificationDocuments' => $verificationDocuments,
            'canAssessDocuments' => $canAssessDocuments,
            'docVerificationStage' => $stage,
            'canEditChecklist' => $canEditChecklist,
            'docVerificationRound' => $currentRound,
            'siblingApplications' => $siblingApplications,
            'prestasiDocumentMimes' => $this->prestasiDocumentMimes($application),
        ]);
    }

    /**
     * @return array<int, string|null>
     */
    private function prestasiDocumentMimes(Application $application): array
    {
        $prestasis = $application->pendaftaran?->prestasis;

        if (! $prestasis) {
            return [];
        }

        $disk = Storage::disk(config('kartu_hebat.document_disk'));

        return $prestasis
            ->mapWithKeys(function (Prestasi $prestasi) use ($disk): array {
                $path = $prestasi->dokumen_prestasi;

                if (blank($path) || ! $disk->exists($path)) {
                    return [$prestasi->id => null];
                }

                return [$prestasi->id => $disk->mimeType($path)];
            })
            ->all();
    }

    private function defaultQueueStatuses(UserRole $role): array
    {
        return match ($role) {
            UserRole::OPERATOR_DUKCAPIL,
            UserRole::OPERATOR_SOSIAL,
            UserRole::OPERATOR_PENDIDIKAN,
            UserRole::OPERATOR_BKPSDM,
            UserRole::OPERATOR_DINKES,
            UserRole::OPERATOR_PARSEPOR => [ApplicationStatus::VERIFIKASI_DINAS->value],
            UserRole::OPERATOR_KABUPATEN => [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ],
            default => [],
        };
    }
}
