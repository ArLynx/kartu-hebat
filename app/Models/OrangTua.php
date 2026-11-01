<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrangTua extends Model
{
    public const PEKERJAAN_OPTIONS = [
        'Wirausaha',
        'ASN',
        'BUMN / BUMD',
        'TNI / POLRI',
        'Lainnya',
    ];

    protected $fillable = [
        'pendaftaran_id',

        // Ayah
        'nama_ayah',
        'status_ayah',
        'nik_ayah',
        'pekerjaan_ayah',
        'jabatan_ayah',

        // Ibu
        'nama_ibu',
        'status_ibu',
        'nik_ibu',
        'pekerjaan_ibu',
        'jabatan_ibu',

        // Wali
        'memiliki_wali',
        'nama_wali',
        'nik_wali',
        'pekerjaan_wali',
        'jabatan_wali',
    ];

    protected function casts(): array
    {
        return [
            'memiliki_wali' => 'boolean',
        ];
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
