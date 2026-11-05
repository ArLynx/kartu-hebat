<?php

namespace App\Models;

use App\Enums\MasaSanggahStatus;
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
        'tanggal_mulai_sanggah',
        'tanggal_selesai_sanggah',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'tanggal_mulai_lpj' => 'date',
            'tanggal_selesai_lpj' => 'date',
            'tanggal_mulai_sanggah' => 'date',
            'tanggal_selesai_sanggah' => 'date',
        ];
    }

    public function masaSanggahStatus(): MasaSanggahStatus
    {
        if (! $this->tanggal_mulai_sanggah || ! $this->tanggal_selesai_sanggah) {
            return MasaSanggahStatus::BELUM_DIJADWALKAN;
        }

        if (today()->betweenIncluded($this->tanggal_mulai_sanggah, $this->tanggal_selesai_sanggah)) {
            return MasaSanggahStatus::SEDANG_BERLANGSUNG;
        }

        return today()->lt($this->tanggal_mulai_sanggah)
            ? MasaSanggahStatus::BELUM_DIMULAI
            : MasaSanggahStatus::TELAH_BERAKHIR;
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
