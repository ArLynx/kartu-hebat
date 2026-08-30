<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case DRAFT = 'DRAFT';
    case VERIFIKASI_DINAS = 'VERIFIKASI_DINAS';
    case SELEKSI_KABUPATEN = 'SELEKSI_KABUPATEN';
    case TMS = 'TMS';
    case DITERIMA = 'DITERIMA';
    case DITOLAK = 'DITOLAK';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::VERIFIKASI_DINAS => 'Verifikasi Lintas Dinas',
            self::SELEKSI_KABUPATEN => 'Seleksi Kabupaten',
            self::TMS => 'Tidak Memenuhi Syarat',
            self::DITERIMA => 'Diterima',
            self::DITOLAK => 'Ditolak',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::DITERIMA => 'success',
            self::TMS, self::DITOLAK => 'danger',
            self::SELEKSI_KABUPATEN, self::VERIFIKASI_DINAS => 'purple',
            self::DRAFT => 'neutral',
            default => 'info',
        };
    }

    public function progress(): int
    {
        return match ($this) {
            self::DRAFT => 15,
            self::VERIFIKASI_DINAS => 50,
            self::SELEKSI_KABUPATEN => 85,
            self::DITERIMA, self::DITOLAK, self::TMS => 100,
        };
    }

    public function isEditableByStudent(): bool
    {
        return $this === self::DRAFT;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::DITERIMA, self::DITOLAK, self::TMS], true);
    }
}
