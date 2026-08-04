<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case VERIFIKASI_DESA = 'VERIFIKASI_DESA';
    case BTL_DESA = 'BTL_DESA';
    case MS_DESA = 'MS_DESA';
    case VERIFIKASI_KECAMATAN = 'VERIFIKASI_KECAMATAN';
    case BTL_KECAMATAN = 'BTL_KECAMATAN';
    case MS = 'MS';
    case TMS = 'TMS';
    case VERIFIKASI_DINAS = 'VERIFIKASI_DINAS';
    case SELEKSI_KABUPATEN = 'SELEKSI_KABUPATEN';
    case DITERIMA = 'DITERIMA';
    case DITOLAK = 'DITOLAK';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf',
            self::SUBMITTED => 'Sudah Dikirim',
            self::VERIFIKASI_DESA => 'Verifikasi Desa/Kelurahan',
            self::BTL_DESA => 'Butuh Perbaikan dari Desa',
            self::MS_DESA => 'Memenuhi Syarat Desa',
            self::VERIFIKASI_KECAMATAN => 'Verifikasi Kecamatan',
            self::BTL_KECAMATAN => 'Butuh Perbaikan dari Kecamatan/Dinas',
            self::MS => 'Memenuhi Syarat',
            self::TMS => 'Tidak Memenuhi Syarat',
            self::VERIFIKASI_DINAS => 'Verifikasi Lintas Dinas',
            self::SELEKSI_KABUPATEN => 'Seleksi Kabupaten',
            self::DITERIMA => 'Diterima',
            self::DITOLAK => 'Ditolak',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::DITERIMA, self::MS, self::MS_DESA => 'success',
            self::BTL_DESA, self::BTL_KECAMATAN => 'warning',
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
            self::SUBMITTED, self::VERIFIKASI_DESA, self::BTL_DESA => 35,
            self::MS_DESA, self::VERIFIKASI_KECAMATAN, self::BTL_KECAMATAN => 55,
            self::VERIFIKASI_DINAS => 75,
            self::MS, self::SELEKSI_KABUPATEN => 90,
            self::DITERIMA, self::DITOLAK, self::TMS => 100,
        };
    }

    public function isEditableByStudent(): bool
    {
        return in_array($this, [self::DRAFT, self::BTL_DESA, self::BTL_KECAMATAN], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::DITERIMA, self::DITOLAK, self::TMS], true);
    }
}
