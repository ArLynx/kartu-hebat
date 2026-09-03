<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pendaftaran extends Model
{
    protected $fillable = [
        'user_id',
        'periode_id',
        'kategori_beasiswa_id',
        'nomor_pendaftaran',
        'status',
        'submitted_at',
        'prestasi_dikonfirmasi_at',
        'review_dikonfirmasi_at',
        'jalur_beasiswa_id',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Pendaftaran $pendaftaran): void {
            $pendaftaran->application()->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'prestasi_dikonfirmasi_at' => 'datetime',
            'review_dikonfirmasi_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBeasiswa::class, 'kategori_beasiswa_id');
    }

    public function kategoriBeasiswa(): BelongsTo
    {
        return $this->kategori();
    }

    public function dataPribadi(): HasOne
    {
        return $this->hasOne(DataPribadi::class);
    }

    public function pendidikan(): HasOne
    {
        return $this->hasOne(Pendidikan::class);
    }

    public function prestasis(): HasMany
    {
        return $this->hasMany(Prestasi::class);
    }

    public function orangTua(): HasOne
    {
        return $this->hasOne(OrangTua::class);
    }

    public function dokumens(): HasMany
    {
        return $this->hasMany(Dokumen::class);
    }

    public function application(): HasOne
    {
        return $this->hasOne(Application::class);
    }

    public function jalurBeasiswa(): BelongsTo
    {
        return $this->belongsTo(JalurBeasiswa::class);
    }

    public function laporanPertanggungjawaban(): HasOne
    {
        return $this->hasOne(LaporanPertanggungjawaban::class);
    }

    public function blacklists(): HasMany
    {
        return $this->hasMany(Blacklist::class);
    }

    public function formulirPendaftaran(): HasOne
    {
        return $this->hasOne(FormulirPendaftaran::class);
    }
}
