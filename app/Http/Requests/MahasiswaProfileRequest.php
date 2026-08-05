<?php

namespace App\Http\Requests;

use App\Enums\ApplicationType;
use App\Models\MahasiswaProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MahasiswaProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $base = [
            'nik' => ['required', 'string', 'size:16'],
            'nim' => ['required', 'string', 'max:50'],
            'universitas' => ['required', 'string', 'max:150'],
            'program_studi' => ['required', 'string', 'max:150'],
            'semester' => ['required', 'integer', 'between:1,14'],
            'ipk' => ['required', 'numeric', 'between:0,4'],
            'alamat' => ['required', 'string'],
            'village_id' => ['required', 'integer', 'exists:villages,id'],
        ];

        $pendaftaran = $this->user()?->pendaftarans()
            ->with('kategoriBeasiswa')
            ->whereHas('periode', fn ($q) => $q->where('status', 'aktif'))
            ->latest('id')
            ->first();
        $type = $pendaftaran?->kategoriBeasiswa?->application_type;

        if ($type === ApplicationType::DISABILITAS) {
            $base['disability_type'] = ['required', Rule::in(MahasiswaProfile::DISABILITY_TYPES)];
            $base['disability_grade'] = ['required', Rule::in(MahasiswaProfile::DISABILITY_GRADES)];
            $base['disability_document_number'] = ['nullable', 'string', 'max:100'];
        } else {
            $base['disability_type'] = ['nullable', 'string'];
            $base['disability_grade'] = ['nullable', 'string'];
            $base['disability_document_number'] = ['nullable', 'string'];
        }

        return $base;
    }

    public function messages(): array
    {
        return [
            'disability_type.required' => 'Jenis disabilitas wajib diisi untuk jalur Disabilitas.',
            'disability_type.in' => 'Jenis disabilitas tidak dikenal.',
            'disability_grade.required' => 'Tingkat disabilitas wajib diisi untuk jalur Disabilitas.',
            'disability_grade.in' => 'Tingkat disabilitas harus RINGAN, SEDANG, atau BERAT.',
        ];
    }
}
