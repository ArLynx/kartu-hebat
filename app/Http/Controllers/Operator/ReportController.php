<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Exports\ApplicationsRecapExport;
use App\Exports\CandidatesExport;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function pdf(Request $request)
    {
        $type = $this->requestedType($request);
        $applications = $this->applications($request, $type);

        return Pdf::loadView('reports.candidates-pdf', [
            'applications' => $applications,
            'generatedAt' => now(),
            'reportTitle' => 'Rekap Seleksi Kartu Hebat Mahasiswa'.($type ? ' - Jalur '.$type->label() : ''),
        ])->setPaper('a4', 'landscape')
            ->download('laporan-kartu-hebat-mahasiswa'.($type ? '-'.strtolower($type->value) : '').'.pdf');
    }

    public function recipientsPdf(Request $request)
    {
        $type = $this->requestedType($request);
        $applications = Application::query()
            ->visibleTo($request->user())
            ->where('periode', config('kartu_hebat.current_period'))
            ->when($type, fn ($query) => $query->where('application_type', $type->value))
            ->with(['mahasiswa.profile.village.kecamatan', 'selection', 'pendaftaran.jalurBeasiswa'])
            ->where('status', ApplicationStatus::DITERIMA->value)
            ->whereHas('selection', fn ($selection) => $selection->whereNotNull('published_at'))
            ->get()
            ->sortBy(fn (Application $application) => sprintf(
                '%s-%s-%09d',
                $application->application_type?->value ?? 'ZZZ',
                $application->pendaftaran?->jalur_beasiswa_id ?? '0',
                $application->selection?->rank ?? PHP_INT_MAX,
            ));

        return Pdf::loadView('reports.candidates-pdf', [
            'applications' => $applications,
            'generatedAt' => now(),
            'reportTitle' => 'Daftar Penerima Kartu Hebat Mahasiswa'.($type ? ' - Jalur '.$type->label() : ''),
        ])->setPaper('a4', 'landscape')
            ->download('daftar-penerima-kartu-hebat-mahasiswa'.($type ? '-'.strtolower($type->value) : '').'.pdf');
    }

    public function excel(Request $request)
    {
        $type = $this->requestedType($request);

        return Excel::download(
            new CandidatesExport($request->user()->kabupaten_id, $type),
            'rekap-kartu-hebat-mahasiswa'.($type ? '-'.strtolower($type->value) : '').'.xlsx',
        );
    }

    public function recap(Request $request)
    {
        return Excel::download(
            ApplicationsRecapExport::forUser($request->user()),
            'rekap-pengajuan-kartu-hebat-mahasiswa.xlsx',
        );
    }

    private function applications(Request $request, ?ApplicationType $type = null)
    {
        return Application::query()
            ->visibleTo($request->user())
            ->where('periode', config('kartu_hebat.current_period'))
            ->when($type, fn ($query) => $query->where('application_type', $type->value))
            ->with(['mahasiswa.profile.village.kecamatan', 'selection', 'pendaftaran.jalurBeasiswa'])
            ->whereIn('status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])
            ->get()
            ->sortBy(fn (Application $application) => sprintf(
                '%s-%s-%09d',
                $application->application_type?->value ?? 'ZZZ',
                $application->pendaftaran?->jalur_beasiswa_id ?? '0',
                $application->selection?->rank ?? PHP_INT_MAX,
            ));
    }

    private function requestedType(Request $request): ?ApplicationType
    {
        return ApplicationType::tryFrom($request->string('application_type')->toString());
    }
}
