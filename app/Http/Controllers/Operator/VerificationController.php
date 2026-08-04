<?php

namespace App\Http\Controllers\Operator;

use App\Enums\VerificationDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerificationRequest;
use App\Models\Application;
use App\Services\ApplicationWorkflowService;

class VerificationController extends Controller
{
    public function store(
        VerificationRequest $request,
        Application $application,
        ApplicationWorkflowService $workflow,
    ) {
        $this->authorize('verify', $application);
        $data = $request->validated();

        $workflow->verify(
            $application,
            $request->user(),
            VerificationDecision::from($data['decision']),
            $data['notes'] ?? null,
            isset($data['score']) ? (float) $data['score'] : null,
            isset($data['desil']) ? (int) $data['desil'] : null,
        );

        return redirect()
            ->route('operator.applications.index')
            ->with('success', 'Keputusan verifikasi berhasil disimpan.');
    }
}
