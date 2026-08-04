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
        'penyelenggara',
        'tahun',
        'dokumen_prestasi',
        'keterangan',
    ];

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
