<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SelectionRequest;
use App\Models\Application;
use App\Models\JalurBeasiswa;
use App\Models\Periode;
use App\Models\Selection;
use App\Services\SelectionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SelectionController extends Controller
{
    public function __construct(
        private readonly SelectionWorkflowService $workflow,
    ) {}

    public function index(Request $request): View
    {
        $masterPeriodes = Periode::query()
            ->orderByDesc('tahun')
            ->orderByDesc('id')
            ->get();

        $availablePeriodes = $masterPeriodes->toBase()->map(function ($p) {
            return $p->nama ?: (string) $p->tahun;
        })->merge(
            Application::query()->visibleTo($request->user())->distinct()->pluck('periode')
        )->filter()->unique()->values();

        $activeMasterPeriode = $masterPeriodes->firstWhere('status', 'aktif');
        $activePeriodName = $activeMasterPeriode
            ? ($activeMasterPeriode->nama ?: (string) $activeMasterPeriode->tahun)
            : (string) config('kartu_hebat.current_period');

        $defaultPeriode = $activeMasterPeriode
            ? $activePeriodName
            : ($availablePeriodes->first() ?? config('kartu_hebat.current_period'));

        $selectedPeriode = $request->string('periode')->trim()->toString() ?: $defaultPeriode;
        $isCurrentPeriod = ($selectedPeriode === $activePeriodName);

        $selectedType = ApplicationType::tryFrom($request->string('application_type')->toString())
            ?? ApplicationType::AKADEMIK;

        $selectedJalurId = $request->filled('jalur_beasiswa_id') ? $request->integer('jalur_beasiswa_id') : null;
        $statusFilter = $request->string('status_filter')->trim()->toString();

        $jalurBeasiswas = JalurBeasiswa::query()->where('aktif', true)->orderBy('urutan')->get();

        $query = Application::query()
            ->visibleTo($request->user())
            ->where('applications.periode', $selectedPeriode)
            ->where('applications.application_type', $selectedType->value)
            ->with([
                'mahasiswa.profile.village.kecamatan',
                'selection',
                'scores.criterion',
                'pendaftaran.jalurBeasiswa',
                'pendaftaran.pendidikan',
                'pendaftaran.prestasis',
            ])
            ->whereIn('applications.status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ]);

        if ($statusFilter === 'diterima') {
            $query->where('applications.status', ApplicationStatus::DITERIMA->value);
        } elseif ($statusFilter === 'ditolak') {
            $query->where('applications.status', ApplicationStatus::DITOLAK->value);
        } elseif ($statusFilter === 'kandidat') {
            $query->where('applications.status', ApplicationStatus::SELEKSI_KABUPATEN->value);
        }

        if ($selectedJalurId) {
            $query->whereHas('pendaftaran', function ($pendaftaranQuery) use ($selectedJalurId): void {
                $pendaftaranQuery->where('jalur_beasiswa_id', $selectedJalurId);
            });
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('applications.nomor_pengajuan', 'like', $search)
                    ->orWhereHas('mahasiswa', fn ($student) => $student->where('name', 'like', $search));
            });
        }

        $countySelections = Selection::query()
            ->whereHas('application', function ($appQuery) use ($request, $selectedType, $selectedJalurId, $selectedPeriode): void {
                $appQuery
                    ->visibleTo($request->user())
                    ->where('periode', $selectedPeriode)
                    ->where('application_type', $selectedType->value);

                if ($selectedJalurId) {
                    $appQuery->whereHas('pendaftaran', fn ($p) => $p->where('jalur_beasiswa_id', $selectedJalurId));
                }
            });

        $rawCounts = Application::query()
            ->visibleTo($request->user())
            ->where('applications.periode', $selectedPeriode)
            ->whereIn('applications.status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])
            ->leftJoin('pendaftarans', 'pendaftarans.id', '=', 'applications.pendaftaran_id')
            ->leftJoin('pendidikans', 'pendidikans.pendaftaran_id', '=', 'pendaftarans.id')
            ->selectRaw('applications.application_type, pendaftarans.jalur_beasiswa_id, count(*) as total, SUM(pendidikans.nominal_ukt) as total_ukt')
            ->groupBy('applications.application_type', 'pendaftarans.jalur_beasiswa_id')
            ->get();

        $typeUktTotals = $rawCounts
            ->groupBy('application_type')
            ->map(fn ($rows): float => (float) $rows->sum('total_ukt'));

        $typeCounts = collect(ApplicationType::cases())->mapWithKeys(
            fn (ApplicationType $type): array => [
                $type->value => (int) $rawCounts->where('application_type', $type->value)->sum('total'),
            ],
        );

        $jalurCounts = $rawCounts
            ->where('application_type', $selectedType->value)
            ->filter(fn ($row) => $row->jalur_beasiswa_id !== null)
            ->pluck('total', 'jalur_beasiswa_id');

        $jalurUktTotals = $rawCounts
            ->where('application_type', $selectedType->value)
            ->filter(fn ($row) => $row->jalur_beasiswa_id !== null)
            ->pluck('total_ukt', 'jalur_beasiswa_id')
            ->map(fn ($total): float => (float) $total);

        $selectedJalur = $selectedJalurId ? $jalurBeasiswas->firstWhere('id', $selectedJalurId) : null;

        return view('operator.selection', [
            'applications' => $query
                ->leftJoin('selections', 'selections.application_id', '=', 'applications.id')
                ->leftJoin('pendaftarans', 'pendaftarans.id', '=', 'applications.pendaftaran_id')
                ->select('applications.*')
                ->orderByRaw('COALESCE(pendaftarans.jalur_beasiswa_id, 0), COALESCE(selections.rank, 999999)')
                ->paginate(20)
                ->withQueryString(),
            'applicationTypes' => ApplicationType::cases(),
            'selectedType' => $selectedType,
            'selectedJalurId' => $selectedJalurId,
            'typeCounts' => $typeCounts,
            'jalurCounts' => $jalurCounts,
            'typeUktTotals' => $typeUktTotals,
            'jalurUktTotals' => $jalurUktTotals,
            'selectedJalur' => $selectedJalur,
            'totalCurrentTypeUkt' => $selectedJalurId
                ? (float) ($jalurUktTotals[$selectedJalurId] ?? 0)
                : (float) ($typeUktTotals[$selectedType->value] ?? 0),
            'totalCurrentTypeCount' => (int) ($typeCounts[$selectedType->value] ?? 0),
            'quota' => $selectedType->quota(),
            'jalurBeasiswas' => $jalurBeasiswas,
            'acceptedCount' => (clone $countySelections)
                ->where(function ($q) {
                    $q->where('manual_decision', ApplicationStatus::DITERIMA->value)
                        ->orWhereHas('application', fn ($aq) => $aq->where('status', ApplicationStatus::DITERIMA));
                })
                ->count(),
            'publishedCount' => (clone $countySelections)
                ->whereNotNull('published_at')
                ->count(),
            'availablePeriodes' => $availablePeriodes,
            'selectedPeriode' => $selectedPeriode,
            'statusFilter' => $statusFilter,
            'isCurrentPeriod' => $isCurrentPeriod,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $selectedType = ApplicationType::tryFrom($request->string('application_type')->toString());
        $selectedJalurId = $request->filled('jalur_beasiswa_id') ? $request->integer('jalur_beasiswa_id') : null;
        $jalur = $selectedJalurId ? JalurBeasiswa::find($selectedJalurId) : null;

        $filename = 'rekap-seleksi-kartu-hebat-'
            .($selectedType ? strtolower($selectedType->value) : 'semua-jalur')
            .($jalur ? '-'.strtolower($jalur->kode) : '')
            .'.xlsx';

        $export = $selectedType
            ? $this->workflow->exportCandidates($request->user(), $selectedType, $selectedJalurId)
            : $this->workflow->exportCandidatesWorkbook($request->user());

        return Excel::download($export, $filename);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'excel_file' => ['required', File::types(['xlsx', 'xls', 'csv'])->max(10 * 1024)],
        ], [
            'excel_file.required' => 'Pilih berkas Excel hasil ACC pimpinan terlebih dahulu.',
            'excel_file.mimes' => 'Berkas daftar ACC pimpinan harus berformat Excel (.xlsx, .xls, .csv).',
            'excel_file.max' => 'Ukuran berkas Excel maksimal 10 MB.',
        ]);

        $count = $this->workflow->importDecisionsFromExcel(
            $request->user(),
            $request->file('excel_file'),
        );

        return back()->with('success', "Keputusan ACC dari {$count} data kandidat berhasil diimpor ke sistem internal. Silakan periksa kembali status di tabel sebelum melakukan publikasi resmi.");
    }

    public function store(SelectionRequest $request, Application $application): RedirectResponse
    {
        if ($application->periode !== config('kartu_hebat.current_period')) {
            return back()->with('error', 'Penetapan penerima untuk periode lampau telah dikunci dan tidak dapat diubah.');
        }

        $this->authorize('select', $application);
        $data = $request->validated();
        $target = ApplicationStatus::from($data['decision']);

        $this->workflow->recordDecision(
            $application,
            $target,
            $request->user(),
            $data['notes'] ?? null,
        );

        return back()->with('success', 'Keputusan internal kandidat berhasil disimpan.');
    }

    public function publish(Request $request): RedirectResponse
    {
        $request->validate([
            'sk_file' => ['required', File::types(['pdf'])->max(10 * 1024)],
            'title' => ['nullable', 'string', 'max:255'],
        ], [
            'sk_file.required' => 'Surat Keputusan (SK) Penetapan format PDF wajib diunggah untuk memublikasikan hasil seleksi.',
            'sk_file.mimes' => 'Berkas SK harus berformat PDF.',
            'sk_file.max' => 'Ukuran berkas SK maksimal 10 MB.',
        ]);

        $title = $request->string('title')->trim()->toString();
        $title = $title !== '' ? $title : null;

        $count = $this->workflow->publishDecisions(
            $request->user(),
            $request->file('sk_file'),
            $title,
        );

        return back()->with('success', "{$count} hasil seleksi berhasil dipublikasikan bersama Surat Keputusan (SK) resmi.");
    }
}
