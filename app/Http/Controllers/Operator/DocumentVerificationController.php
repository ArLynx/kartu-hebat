<?php

namespace App\Http\Controllers\Operator;

use App\Enums\DocumentVerificationResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentVerificationRequest;
use App\Models\Application;
use App\Models\Document;
use App\Services\AgencyVerificationService;
use Illuminate\Http\Request;

class DocumentVerificationController extends Controller
{
    public function store(
        DocumentVerificationRequest $request,
        Application $application,
        Document $document,
        AgencyVerificationService $service,
    ) {
        $this->authorize('view', $application);

        abort_unless((int) $document->application_id === (int) $application->id, 404);

        $service->assessDocument(
            $application,
            $document,
            $request->user(),
            DocumentVerificationResult::from($request->validated('result')),
            $request->validated('notes'),
        );

        return back()->with('success', 'Penilaian dokumen berhasil disimpan.');
    }

    public function destroy(
        Request $request,
        Application $application,
        Document $document,
        AgencyVerificationService $service,
    ) {
        $this->authorize('view', $application);

        abort_unless((int) $document->application_id === (int) $application->id, 404);

        $service->cancelDocumentAssessment($application, $document, $request->user());

        return back()->with('success', 'Penilaian dokumen berhasil dibatalkan.');
    }
}
