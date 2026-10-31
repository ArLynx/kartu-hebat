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
        'status_perguruan_tinggi',
        'fakultas',
        'jurusan',
        'program_studi',
        'jenjang',
        'semester',
        'ipk',
        'nominal_ukt',
        'tahun_masuk',
        'akreditasi_perguruan_tinggi',
        'alamat_perguruan_tinggi',
        'pddikti_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'ipk' => 'decimal:2',
            'nominal_ukt' => 'decimal:2',
            'pddikti_verified_at' => 'datetime',
        ];
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
