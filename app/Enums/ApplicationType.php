<?php

namespace App\Enums;

enum ApplicationType: string
{
    case AKADEMIK = 'AKADEMIK';
    case TIDAK_MAMPU = 'TIDAK_MAMPU';

    public function label(): string
    {
        return match ($this) {
            self::AKADEMIK => 'Akademik',
            self::TIDAK_MAMPU => 'Tidak Mampu',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AKADEMIK => 'Seleksi berdasarkan IPK dan semester aktif.',
            self::TIDAK_MAMPU => 'Seleksi berdasarkan desil sosial ekonomi terverifikasi.',
        };
    }

    public function quota(): int
    {
        return (int) config('kartu_hebat.quotas.'.$this->value, 0);
    }
}
