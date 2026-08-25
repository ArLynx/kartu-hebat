<?php

namespace App\Http\Controllers\Operator;

use App\Enums\DocumentVerificationResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentVerificationRequest;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentVerification;
use App\Services\DocumentVerificationService;
use Illuminate\Http\Request;

class DocumentVerificationController extends Controller
{
    public function store(
        DocumentVerificationRequest $request,
        Application $application,
        Document $document,
        DocumentVerificationService $service,
    ) {
        $this->authorize('view', $application);

        abort_unless((int) $document->application_id === (int) $application->id, 404);

        $service->save(
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
    ) {
        $this->authorize('view', $application);

        abort_unless((int) $document->application_id === (int) $application->id, 404);

        DocumentVerification::query()
            ->where('document_id', $document->id)
            ->where('stage', DocumentVerificationService::stageFor($request->user()))
            ->where('round', DocumentVerificationService::currentRound($application))
            ->delete();

        return back()->with('success', 'Penilaian dokumen berhasil dibatalkan.');
    }
}
