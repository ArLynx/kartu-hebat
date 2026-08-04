<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('operator_kabupaten') ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['DITERIMA', 'DITOLAK'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
