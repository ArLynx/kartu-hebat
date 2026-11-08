<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Periode;
use App\Models\Sanggahan;
use App\Services\SanggahanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SanggahanController extends Controller
{
    public function __construct(private readonly SanggahanService $sanggahanService) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $sanggahans = Sanggahan::query()
            ->where('submitted_by', $user->getKey())
            ->with(['application', 'periode', 'decidedBy'])
            ->latest('id')
            ->get();

        $tmsApplications = Application::query()
            ->where('mahasiswa_id', $user->getKey())
            ->where('status', ApplicationStatus::TMS)
            ->with(['pendaftaran.periode', 'agencyVerifications.verifier', 'sanggahan'])
            ->latest('id')
            ->get();

        $eligibilities = $tmsApplications->mapWithKeys(function (Application $application) use ($user): array {
            $reason = $this->sanggahanService->ineligibilityReason($application, $user);

            return [$application->id => ['can_submit' => $reason === null, 'reason' => $reason]];
        });

        $eligibleApplications = $tmsApplications
            ->filter(fn (Application $application): bool => $eligibilities[$application->id]['can_submit'])
            ->values();

        $periode = $eligibleApplications->first()?->resolvedPeriode()
            ?? $tmsApplications->first()?->resolvedPeriode()
            ?? Periode::latestAktif();

        return view('mahasiswa.sanggahan.index', compact(
            'sanggahans',
            'tmsApplications',
            'eligibilities',
            'periode',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['uraian' => trim((string) $request->input('uraian', ''))]);

        $validated = $request->validate([
            'application_id' => ['required', 'integer', Rule::exists('applications', 'id')],
            'uraian' => ['required', 'string', 'min:40', 'max:2000'],
            'pernyataan_kebenaran' => ['accepted'],
        ], [
            'application_id.required' => 'Pengajuan yang akan disanggah wajib dipilih.',
            'application_id.exists' => 'Pengajuan yang akan disanggah tidak ditemukan.',
            'uraian.required' => 'Uraian sanggahan wajib diisi.',
            'uraian.min' => 'Uraian sanggahan minimal 40 karakter.',
            'uraian.max' => 'Uraian sanggahan maksimal 2000 karakter.',
            'pernyataan_kebenaran.accepted' => 'Pernyataan kebenaran sanggahan wajib disetujui.',
        ]);

        $application = Application::query()->findOrFail($validated['application_id']);

        $this->authorize('view', $application);

        $reason = $this->sanggahanService->ineligibilityReason($application, $request->user());

        if ($reason !== null) {
            return back()
                ->with('error', $reason)
                ->withInput();
        }

        $this->sanggahanService->submit($application, $request->user(), $validated['uraian']);

        return redirect()
            ->route('mahasiswa.sanggahan.index')
            ->with('success', 'Sanggahan berhasil diajukan dan tidak dapat diubah lagi.');
    }
}
