<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periode extends Model
{
    protected $fillable = [
        'tahun',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_mulai_lpj',
        'tanggal_selesai_lpj',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tanggal_mulai_lpj' => 'date',
            'tanggal_selesai_lpj' => 'date',
        ];
    }

    public function isLpjOpen(): bool
    {
        if (! $this->tanggal_mulai_lpj || ! $this->tanggal_selesai_lpj) {
            return false;
        }

        return today()->betweenIncluded($this->tanggal_mulai_lpj, $this->tanggal_selesai_lpj);
    }

    public function isLpjPastDeadline(): bool
    {
        if (! $this->tanggal_selesai_lpj) {
            return false;
        }

        return today()->greaterThan($this->tanggal_selesai_lpj);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query
            ->where('status', 'aktif')
            ->whereDate('tanggal_mulai', '<=', today())
            ->whereDate('tanggal_selesai', '>=', today());
    }

    public function kategoriBeasiswas(): HasMany
    {
        return $this->hasMany(KategoriBeasiswa::class);
    }

    public function pendaftarans(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }
}
