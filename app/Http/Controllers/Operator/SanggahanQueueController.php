<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Periode;
use App\Services\AgencyVerificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SanggahanQueueController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $agency = $user->role->agencyCode();

        abort_if($agency === null, 403);

        $baseQuery = Application::query()
            ->visibleTo($user)
            ->whereAwaitingReverificationBy($user)
            ->when(
                $request->filled('periode_id'),
                fn (Builder $query) => $query->whereHas(
                    'sanggahan',
                    fn (Builder $sanggahan) => $sanggahan
                        ->where('periode_id', $request->integer('periode_id')),
                ),
            )
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('q')->trim()->toString().'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('applications.nomor_pengajuan', 'like', $search)
                        ->orWhereHas('mahasiswa', fn (Builder $student) => $student
                            ->where('name', 'like', $search))
                        ->orWhereHas('mahasiswa.profile', fn (Builder $profile) => $profile
                            ->where('nik', 'like', $search)
                            ->orWhere('nim', 'like', $search));
                });
            });

        $applications = (clone $baseQuery)
            ->with([
                'mahasiswa.profile.village.kecamatan',
                'pendaftaran.periode',
                'pendaftaran.jalurBeasiswa',
                'sanggahan.decidedBy',
                'sanggahan.periode',
                'verificationLogs' => fn ($query) => $query
                    ->where('action', 'sanggahan_granted'),
            ])
            ->latest('applications.updated_at')
            ->paginate(15)
            ->withQueryString();

        $annulledVerifications = $applications->getCollection()
            ->mapWithKeys(function (Application $application) use ($agency): array {
                $metadata = $application->verificationLogs->first()?->metadata ?? [];

                $annulled = collect($metadata['annulled_verifications'] ?? [])
                    ->firstWhere('agency', $agency);

                if ($annulled === null && in_array($agency, $metadata['annulled_agencies'] ?? [], true)) {
                    $annulled = ['agency' => $agency, 'notes' => null];
                }

                return [$application->getKey() => $annulled];
            });

        return view('operator.sanggahan-queue.index', [
            'applications' => $applications,
            'annulledVerifications' => $annulledVerifications,
            'agencyLabel' => AgencyVerificationService::stageLabel($agency),
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'periodes' => (clone $baseQuery)
                    ->join('sanggahans', 'sanggahans.application_id', '=', 'applications.id')
                    ->distinct()
                    ->count('sanggahans.periode_id'),
            ],
            'periodes' => Periode::query()
                ->orderByDesc('tahun')
                ->orderByDesc('tanggal_mulai')
                ->get(),
        ]);
    }
}
