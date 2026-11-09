<?php

namespace App\Enums;

enum TingkatOrganisasi: string
{
    case UNIVERSITAS = 'universitas';
    case FAKULTAS = 'fakultas';

    public function label(): string
    {
        return match ($this) {
            self::UNIVERSITAS => 'Universitas',
            self::FAKULTAS => 'Fakultas',
        };
    }

    /**
     * Semakin kecil nilainya, semakin tinggi prioritasnya pada pemeringkatan.
     */
    public function rank(): int
    {
        return match ($this) {
            self::UNIVERSITAS => 1,
            self::FAKULTAS => 2,
        };
    }
}
