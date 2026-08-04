<?php

namespace App\Http\Controllers\Student;

use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Village;
use App\Services\ApplicationWorkflowService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request, ApplicationWorkflowService $workflow)
    {
        $application = $workflow->getOrCreateCurrent($request->user());
        $application->load(['documents.type', 'mahasiswa.profile.village.kecamatan']);

        $documentTypes = DocumentType::query()
            ->where('is_active', true)
            ->where(function ($query) use ($application): void {
                $query->whereNull('application_type');

                if ($application->application_type) {
                    $query->orWhere('application_type', $application->application_type->value);
                } else {
                    $query->orWhereIn('application_type', array_map(fn (ApplicationType $type) => $type->value, ApplicationType::cases()));
                }
            })
            ->orderBy('sort_order')
            ->get();

        return view('student.application', [
            'application' => $application,
            'profile' => $request->user()->profile,
            'applicationTypes' => ApplicationType::cases(),
            'documentTypes' => $documentTypes,
            'villages' => Village::query()
                ->with('kecamatan')
                ->whereHas('kabupaten', fn ($query) => $query->where('code', config('kartu_hebat.kabupaten_code')))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function submit(Request $request, ApplicationWorkflowService $workflow)
    {
        $application = $workflow->getOrCreateCurrent($request->user());
        $this->authorize('submit', $application);

        $workflow->submit($application, $request->user());

        return redirect()
            ->route('student.history')
            ->with('success', 'Pengajuan jalur '.$application->application_type->label().' berhasil dikirim dan masuk ke antrean verifikasi.');
    }
}
