<?php

namespace App\Http\Requests;

use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\VerificationDecision;
use App\Services\AgencyVerificationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        $isDesilAgency = $this->user()?->hasRole('operator_sosial') ?? false;
        $application = $this->route('application');
        $isUnableTrack = $application?->application_type === ApplicationType::TIDAK_MAMPU;
        $requiresDesil = fn (): bool => $isDesilAgency
            && $isUnableTrack
            && $this->input('decision') === VerificationDecision::MS->value;

        return [
            'decision' => ['required', Rule::enum(VerificationDecision::class)],
            'notes' => ['nullable', 'required_unless:decision,MS', 'string', 'max:3000'],
            'desil' => $isDesilAgency && $isUnableTrack
                ? ['nullable', Rule::requiredIf($requiresDesil), 'prohibited_unless:decision,MS', 'integer', 'min:1', 'max:10']
                : ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required_unless' => 'Catatan petugas wajib diisi untuk keputusan Tidak Memenuhi Syarat (TMS).',
            'notes.string' => 'Catatan petugas harus berupa teks.',
            'notes.max' => 'Catatan petugas maksimal 3000 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $decision = $this->input('decision');

            if (! in_array($decision, [VerificationDecision::MS->value, VerificationDecision::TMS->value], true)) {
                return;
            }

            $application = $this->route('application');
            $user = $this->user();

            if (! $application || ! $user || ! $user->role->isAgency()) {
                return;
            }

            $stage = AgencyVerificationService::stageFor($user);
            $round = AgencyVerificationService::currentRound($application);
            $application->load([
                'documents.type',
                'documents.verifications' => fn ($q) => $q->where('stage', $stage)->where('round', $round),
            ]);
            $documents = AgencyVerificationService::documentsAssessableBy($application, $user);

            if ($documents->isEmpty()) {
                return;
            }

            $rejectedDocuments = $documents->filter(fn ($document): bool => $document->verifications
                ->where('stage', $stage)
                ->where('round', $round)
                ->contains('result', DocumentVerificationResult::TIDAK_MEMENUHI));

            if ($decision === VerificationDecision::MS->value && $rejectedDocuments->isNotEmpty()) {
                $validator->errors()->add(
                    'decision',
                    'Keputusan Memenuhi Syarat (MS) tidak dapat dipilih karena masih ada dokumen yang dinilai Tidak Memenuhi Syarat (TMS): '
                    .$rejectedDocuments->pluck('type.name')->implode(', ').'.'
                );

                return;
            }

            if ($decision !== VerificationDecision::TMS->value) {
                return;
            }

            $allMs = $documents->every(fn ($document): bool => $document->verifications
                ->where('stage', $stage)
                ->where('round', $round)
                ->contains('result', DocumentVerificationResult::MEMENUHI));

            if ($allMs) {
                $validator->errors()->add(
                    'decision',
                    'Keputusan Tidak Memenuhi Syarat (TMS) tidak dapat dipilih karena seluruh dokumen sudah dinilai Memenuhi Syarat (MS).'
                );
            }
        });
    }
}
