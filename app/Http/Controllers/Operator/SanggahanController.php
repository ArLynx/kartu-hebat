<?php

namespace App\Http\Controllers\Operator;

use App\Enums\DocumentVerificationResult;
use App\Enums\SanggahanStatus;
use App\Http\Controllers\Controller;
use App\Models\Periode;
use App\Models\Sanggahan;
use App\Services\AgencyVerificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SanggahanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $baseQuery = Sanggahan::query()
            ->whereHas('application', fn (Builder $application) => $application->visibleTo($user))
            ->when(
                $request->filled('periode_id'),
                fn (Builder $query) => $query->where('periode_id', $request->integer('periode_id')),
            )
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('q')->trim()->toString().'%';

                $query->whereHas('application', function (Builder $application) use ($search): void {
                    $application
                        ->where('nomor_pengajuan', 'like', $search)
                        ->orWhereHas('mahasiswa', fn (Builder $student) => $student->where('name', 'like', $search));
                });
            });

        $summary = (clone $baseQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($total, $status): array => [(string) $status => (int) $total])
            ->all();

        $statusFilter = SanggahanStatus::tryFrom($request->string('status')->trim()->toString());

        $sanggahans = (clone $baseQuery)
            ->when(
                $statusFilter !== null,
                fn (Builder $query) => $query->where('status', $statusFilter->value),
            )
            ->with([
                'application.mahasiswa.profile.village.kecamatan',
                'application.pendaftaran.jalurBeasiswa',
                'periode',
                'decidedBy',
            ])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('operator.sanggahan.index', [
            'sanggahans' => $sanggahans,
            'summary' => array_merge(
                array_fill_keys(array_map(fn (SanggahanStatus $status) => $status->value, SanggahanStatus::cases()), 0),
                $summary,
            ),
            'statuses' => SanggahanStatus::cases(),
            'periodes' => Periode::query()
                ->orderByDesc('tahun')
                ->orderByDesc('tanggal_mulai')
                ->get(),
        ]);
    }

    public function show(Sanggahan $sanggahan): View
    {
        $sanggahan->loadMissing('application');

        $this->authorize('view', $sanggahan->application);

        $currentRound = AgencyVerificationService::currentRound($sanggahan->application);

        $sanggahan->load([
            'application.mahasiswa.profile.village.kecamatan',
            'application.pendaftaran.jalurBeasiswa',
            'application.agencyVerifications',
            'application.documentVerifications' => fn ($query) => $query
                ->where('round', $currentRound)
                ->with(['document.type']),
            'application.verificationLogs.actor',
            'periode',
            'submittedBy',
            'decidedBy',
        ]);

        $rejectedDocuments = $sanggahan->application->documentVerifications
            ->filter(fn ($verification) => $verification->result === DocumentVerificationResult::TIDAK_MEMENUHI)
            ->values();

        return view('operator.sanggahan.show', [
            'sanggahan' => $sanggahan,
            'application' => $sanggahan->application,
            'rejectedDocuments' => $rejectedDocuments,
            'documentVerificationRound' => $currentRound,
        ]);
    }
}
