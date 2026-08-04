<?php

namespace App\Http\Requests;

use App\Enums\ApplicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        $profileId = $this->user()?->profile?->id;
        $academic = fn (): bool => $this->input('application_type') === ApplicationType::AKADEMIK->value;

        return [
            'application_type' => ['required', Rule::enum(ApplicationType::class)],
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', Rule::unique('mahasiswa_profiles', 'nik')->ignore($profileId)],
            'nim' => ['required', 'string', 'max:50', Rule::unique('mahasiswa_profiles', 'nim')->ignore($profileId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'universitas' => ['required', 'string', 'max:255'],
            'program_studi' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'ipk' => ['nullable', Rule::requiredIf($academic), 'numeric', 'min:0', 'max:4'],
            'alamat' => ['required', 'string', 'max:2000'],
            'village_id' => ['required', 'exists:villages,id'],
            'penghasilan_keluarga' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'jumlah_tanggungan' => ['nullable', 'integer', 'min:0', 'max:30'],
            'prestasi' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
