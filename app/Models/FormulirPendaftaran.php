<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormulirPendaftaran extends Model
{
    protected $table = 'formulir_pendaftarans';

    protected $fillable = [
        'pendaftaran_id',
        'jenis_form',
        'surat_permohonan',
        'pakta_integritas',
    ];

    /**
     * Formulir ini milik satu pendaftaran.
     */
    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
