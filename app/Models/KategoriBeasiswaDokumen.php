<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KategoriBeasiswaDokumen extends Model
{
    protected $fillable = [
        'kategori_beasiswa_id',
        'jenis_dokumen_id',
        'urutan',
    ];

    public function kategoriBeasiswa(): BelongsTo
    {
        return $this->belongsTo(KategoriBeasiswa::class);
    }

    public function jenisDokumen(): BelongsTo
    {
        return $this->belongsTo(JenisDokumen::class);
    }
}
