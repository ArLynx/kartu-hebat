<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pendidikan extends Model
{
    protected $fillable = [
        'pendaftaran_id',
        'nim',
        'universitas',
        'fakultas',
        'program_studi',
        'jenjang',
        'semester',
        'ipk',
        'tahun_masuk',
        'tahun_lulus',
        'status_mahasiswa',
        'pddikti_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'ipk' => 'decimal:2',
            'pddikti_verified_at' => 'datetime',
        ];
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
