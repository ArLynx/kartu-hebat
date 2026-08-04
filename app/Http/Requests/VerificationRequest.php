<?php

namespace App\Http\Requests;

use App\Enums\ApplicationType;
use App\Enums\VerificationDecision;
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
}
