<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrangTua extends Model
{
    protected $fillable = [
        'pendaftaran_id',

        // Ayah
        'nama_ayah',
        'status_ayah',
        'nik_ayah',
        'pekerjaan_ayah',
        'penghasilan_ayah',

        // Ibu
        'nama_ibu',
        'status_ibu',
        'nik_ibu',
        'pekerjaan_ibu',
        'penghasilan_ibu',

        // Wali
        'memiliki_wali',
        'nama_wali',
        'nik_wali',
        'pekerjaan_wali',
        'penghasilan_wali',
    ];

    protected function casts(): array
    {
        return [
            'memiliki_wali' => 'boolean',
            'penghasilan_ayah' => 'decimal:2',
            'penghasilan_ibu' => 'decimal:2',
            'penghasilan_wali' => 'decimal:2',
        ];
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}