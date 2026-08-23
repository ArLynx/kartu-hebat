<?php

namespace App\Enums;

enum DocumentVerificationResult: string
{
    case BELUM_DINILAI = 'belum_dinilai';
    case MEMENUHI = 'memenuhi';
    case TIDAK_MEMENUHI = 'tidak_memenuhi';

    public function label(): string
    {
        return match ($this) {
            self::BELUM_DINILAI => 'Belum Dinilai',
            self::MEMENUHI => 'Memenuhi Syarat',
            self::TIDAK_MEMENUHI => 'Tidak Memenuhi Syarat',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::BELUM_DINILAI => 'neutral',
            self::MEMENUHI => 'success',
            self::TIDAK_MEMENUHI => 'danger',
        };
    }
}
