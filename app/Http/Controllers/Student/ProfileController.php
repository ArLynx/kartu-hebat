<?php

namespace App\Http\Controllers\Student;

use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Village;
use App\Services\ApplicationWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(ProfileUpdateRequest $request, ApplicationWorkflowService $workflow)
    {
        $user = $request->user();
        $application = $workflow->getOrCreateCurrent($user);
        $this->authorize('update', $application);

        $data = $request->validated();
        $applicationType = ApplicationType::from($data['application_type']);
        unset($data['application_type']);

        $village = Village::query()
            ->with('kecamatan')
            ->whereHas('kabupaten', fn ($query) => $query->where('code', config('kartu_hebat.kabupaten_code')))
            ->findOrFail($data['village_id']);

        DB::transaction(function () use ($user, $application, $applicationType, $data, $village): void {
            $typeChanged = $application->application_type !== $applicationType;

            $user->update([
                'name' => $data['name'],
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kabupaten_id,
            ]);

            unset($data['name']);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $data,
            );

            $application->update(['application_type' => $applicationType]);

            if ($typeChanged) {
                $irrelevantDocuments = $application->documents()
                    ->whereHas('type', fn ($query) => $query
                        ->whereNotNull('application_type')
                        ->where('application_type', '!=', $applicationType->value))
                    ->get();

                foreach ($irrelevantDocuments as $document) {
                    Storage::disk(config('kartu_hebat.document_disk'))->delete($document->path);
                    $document->delete();
                }

                $application->scores()->delete();
                $application->selection()->delete();
            }
        });

        return back()->with('success', 'Data mahasiswa dan jalur pengajuan berhasil disimpan.');
    }
}
