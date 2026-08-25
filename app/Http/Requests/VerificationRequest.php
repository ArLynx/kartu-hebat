<?php

namespace App\Http\Requests;

use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\VerificationDecision;
use App\Services\DocumentVerificationService;
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
        $isAgency = $this->user()?->role->isAgency() ?? false;
        $isDesilAgency = $this->user()?->hasRole('operator_sosial', 'operator_pendidikan') ?? false;
        $application = $this->route('application');
        $isUnableTrack = $application?->application_type === ApplicationType::TIDAK_MAMPU;
        $requiresDesil = fn (): bool => $isDesilAgency
            && $isUnableTrack
            && $this->input('decision') === VerificationDecision::MS->value;

        return [
            'decision' => ['required', Rule::enum(VerificationDecision::class)],
            'notes' => ['nullable', 'required_unless:decision,MS', 'string', 'max:3000'],
            'score' => $isAgency
                ? ['nullable', 'numeric', 'min:0', 'max:100']
                : ['prohibited'],
            'desil' => $isDesilAgency && $isUnableTrack
                ? ['nullable', Rule::requiredIf($requiresDesil), 'prohibited_unless:decision,MS', 'integer', 'min:1', 'max:10']
                : ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required_unless' => 'Catatan petugas wajib diisi untuk keputusan Butuh Perbaikan (BTL) atau Tidak Memenuhi Syarat (TMS).',
            'notes.string' => 'Catatan petugas harus berupa teks.',
            'notes.max' => 'Catatan petugas maksimal 3000 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! in_array($this->input('decision'), [VerificationDecision::BTL->value, VerificationDecision::TMS->value], true)) {
                return;
            }

            $application = $this->route('application');
            if (! $application) {
                return;
            }

            $stage = DocumentVerificationService::stageFor($this->user());
            $round = DocumentVerificationService::currentRound($application);
            $application->load([
                'documents.verifications' => fn ($q) => $q->where('stage', $stage)->where('round', $round),
            ]);
            $documents = $application->documents;

            if ($documents->isEmpty()) {
                return;
            }

            $allMs = $documents->every(function ($doc) use ($stage, $round) {
                return $doc->verifications
                    ->where('stage', $stage)
                    ->where('round', $round)
                    ->contains('result', DocumentVerificationResult::MEMENUHI);
            });

            if ($allMs) {
                $decision = VerificationDecision::from($this->input('decision'));
                $validator->errors()->add(
                    'decision',
                    "Keputusan {$decision->label()} tidak dapat dipilih karena seluruh dokumen sudah dinilai Memenuhi Syarat (MS)."
                );
            }
        });
    }
}
