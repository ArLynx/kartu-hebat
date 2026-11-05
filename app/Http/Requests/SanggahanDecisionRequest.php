<?php

namespace App\Http\Requests;

use App\Enums\SanggahanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SanggahanDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                Rule::in([SanggahanStatus::DIKABULKAN->value, SanggahanStatus::DITOLAK->value]),
            ],
            'catatan_keputusan' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan sanggahan wajib dipilih.',
            'decision.in' => 'Keputusan sanggahan harus Kabulkan atau Tolak.',
            'catatan_keputusan.required' => 'Catatan keputusan wajib diisi.',
            'catatan_keputusan.string' => 'Catatan keputusan harus berupa teks.',
            'catatan_keputusan.max' => 'Catatan keputusan maksimal 2000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'catatan_keputusan' => trim((string) $this->input('catatan_keputusan', '')),
        ]);
    }
}
