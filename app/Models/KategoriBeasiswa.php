<?php

namespace App\Models;

use App\Enums\ApplicationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriBeasiswa extends Model
{
    protected $fillable = [
        'periode_id',
        'kode',
        'application_type',
        'nama',
        'deskripsi',
        'kuota',
        'aktif',
        'urutan',
        'icon',
        'warna',
    ];

    protected function casts(): array
    {
        return [
            'application_type' => ApplicationType::class,
            'aktif' => 'boolean',
            'kuota' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function pendaftarans(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function dokumenPersyaratan(): HasMany
    {
        return $this->hasMany(KategoriBeasiswaDokumen::class)
            ->orderBy('urutan');
    }

    public function jenisDokumens(): BelongsToMany
    {
        return $this->belongsToMany(
            JenisDokumen::class,
            'kategori_beasiswa_dokumens',
            'kategori_beasiswa_id',
            'jenis_dokumen_id',
        )->withPivot('urutan')->withTimestamps()->orderByPivot('urutan');
    }
}
