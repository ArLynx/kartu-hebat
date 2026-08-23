<?php

namespace App\Http\Requests;

use App\Enums\DocumentVerificationResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'result' => ['required', Rule::enum(DocumentVerificationResult::class)],
            'notes' => ['nullable', 'required_if:result,tidak_memenuhi', 'string', 'max:3000'],
        ];
    }
}
