<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MahasiswaProfile extends Model
{
    use Auditable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'ipk' => 'decimal:2',
            'penghasilan_keluarga' => 'integer',
            'jumlah_tanggungan' => 'integer',
            'desil_sosial' => 'integer',
            'desil_pendidikan' => 'integer',
        ];
    }

    public const DISABILITY_TYPES = [
        'TUNANETRA',
        'TUNARUNGU',
        'TUNAWICARA',
        'TUNADAKSA',
        'TUNAGRAHITA',
        'DISABILITAS_GANDA',
        'LAINNYA',
    ];

    public const DISABILITY_GRADES = ['RINGAN', 'SEDANG', 'BERAT'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function hasDisabilityData(): bool
    {
        return ! empty($this->disability_type);
    }
}
