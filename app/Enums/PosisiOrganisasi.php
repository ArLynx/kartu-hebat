<?php

namespace App\Enums;

enum PosisiOrganisasi: string
{
    case KETUA = 'ketua';
    case WAKIL_KETUA = 'wakil_ketua';
    case SEKRETARIS = 'sekretaris';
    case BENDAHARA = 'bendahara';
    case KOORDINATOR = 'koordinator';
    case ANGGOTA = 'anggota';

    public function label(): string
    {
        return match ($this) {
            self::KETUA => 'Ketua',
            self::WAKIL_KETUA => 'Wakil Ketua',
            self::SEKRETARIS => 'Sekretaris',
            self::BENDAHARA => 'Bendahara',
            self::KOORDINATOR => 'Koordinator Bidang',
            self::ANGGOTA => 'Anggota Pengurus',
        };
    }

    /**
     * Semakin kecil nilainya, semakin tinggi prioritasnya pada pemeringkatan.
     */
    public function rank(): int
    {
        return match ($this) {
            self::KETUA => 1,
            self::WAKIL_KETUA => 2,
            self::SEKRETARIS => 3,
            self::BENDAHARA => 4,
            self::KOORDINATOR => 5,
            self::ANGGOTA => 6,
        };
    }

    public function isInti(): bool
    {
        return $this !== self::ANGGOTA;
    }
}
