<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prestasi extends Model
{
    protected $fillable = [
        'pendaftaran_id',
        'jenis',
        'nama_prestasi',
        'tingkat',
        'peringkat',
        'is_pengurus_inti_ormawa',
        'jabatan_ormawa',
        'penyelenggara',
        'tahun',
        'dokumen_prestasi',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'is_pengurus_inti_ormawa' => 'boolean',
        ];
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
