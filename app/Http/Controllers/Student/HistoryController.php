<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ApplicationWorkflowService;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request, ApplicationWorkflowService $workflow)
    {
        $application = $workflow->getOrCreateCurrent($request->user());
        $application->load([
            'verificationLogs.actor',
            'villageVerification.verifier',
            'districtVerification.verifier',
            'agencyVerifications.verifier',
            'selection',
        ]);

        return view('student.history', compact('application'));
    }
}
