<?php

namespace App\Enums;

enum KategoriPrestasi: string
{
    case LOMBA = 'lomba';
    case ORGANISASI = 'organisasi';

    public function label(): string
    {
        return match ($this) {
            self::LOMBA => 'Kompetisi / Lomba',
            self::ORGANISASI => 'Pengurus Organisasi Mahasiswa',
        };
    }
}
