<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case MAHASISWA = 'mahasiswa';
    case OPERATOR_DESA = 'operator_desa';
    case OPERATOR_KECAMATAN = 'operator_kecamatan';
    case OPERATOR_DUKCAPIL = 'operator_dukcapil';
    case OPERATOR_SOSIAL = 'operator_sosial';
    case OPERATOR_PENDIDIKAN = 'operator_pendidikan';
    case OPERATOR_KABUPATEN = 'operator_kabupaten';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Superadmin',
            self::MAHASISWA => 'Mahasiswa',
            self::OPERATOR_DESA => 'Operator Desa/Kelurahan',
            self::OPERATOR_KECAMATAN => 'Operator Kecamatan',
            self::OPERATOR_DUKCAPIL => 'Operator Dinas Dukcapil',
            self::OPERATOR_SOSIAL => 'Operator Dinas Sosial',
            self::OPERATOR_PENDIDIKAN => 'Operator Dinas Pendidikan dan Kebudayaan',
            self::OPERATOR_KABUPATEN => 'Operator Kabupaten',
        };
    }

    public function requiresTwoFactor(): bool
    {
        return $this !== self::MAHASISWA;
    }

    public function isAgency(): bool
    {
        return in_array($this, [
            self::OPERATOR_DUKCAPIL,
            self::OPERATOR_SOSIAL,
            self::OPERATOR_PENDIDIKAN,
        ], true);
    }

    public function agencyCode(): ?string
    {
        return match ($this) {
            self::OPERATOR_DUKCAPIL => 'dukcapil',
            self::OPERATOR_SOSIAL => 'sosial',
            self::OPERATOR_PENDIDIKAN => 'pendidikan',
            default => null,
        };
    }

    public static function operatorValues(): array
    {
        return array_values(array_map(
            fn (self $role) => $role->value,
            array_filter(
                self::cases(),
                fn (self $role) => ! in_array($role, [self::SUPERADMIN, self::MAHASISWA], true),
            ),
        ));
    }
}
