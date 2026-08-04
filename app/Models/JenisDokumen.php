<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisDokumen extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'format_file',
        'maksimal_ukuran',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'maksimal_ukuran' => 'integer',
        ];
    }

    public function kategoriBeasiswas(): HasMany
    {
        return $this->hasMany(KategoriBeasiswaDokumen::class);
    }

    public function dokumens(): HasMany
    {
        return $this->hasMany(Dokumen::class);
    }
}
