<?php

namespace App\Enums;

enum SanggahanStatus: string
{
    case DIAJUKAN = 'DIAJUKAN';
    case DIKABULKAN = 'DIKABULKAN';
    case DITOLAK = 'DITOLAK';

    public function label(): string
    {
        return match ($this) {
            self::DIAJUKAN => 'Diajukan',
            self::DIKABULKAN => 'Dikabulkan',
            self::DITOLAK => 'Ditolak',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::DIAJUKAN => 'warning',
            self::DIKABULKAN => 'success',
            self::DITOLAK => 'danger',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DIAJUKAN => 'Menunggu keputusan operator.',
            self::DIKABULKAN => 'Sanggahan diterima, dinas menilai ulang.',
            self::DITOLAK => 'Keputusan TMS dipertahankan.',
        };
    }

    public function isDecided(): bool
    {
        return $this !== self::DIAJUKAN;
    }
}
