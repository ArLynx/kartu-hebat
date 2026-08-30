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
    case OPERATOR_DINKES = 'operator_dinkes';
    case OPERATOR_PARSEPOR = 'operator_parsepor';
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
            self::OPERATOR_DINKES => 'Operator Dinas Kesehatan',
            self::OPERATOR_PARSEPOR => 'Operator Dinas Parsepor',
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
            self::OPERATOR_DINKES,
            self::OPERATOR_PARSEPOR,
        ], true);
    }

    public function agencyCode(): ?string
    {
        return match ($this) {
            self::OPERATOR_DUKCAPIL => 'dukcapil',
            self::OPERATOR_SOSIAL => 'sosial',
            self::OPERATOR_PENDIDIKAN => 'pendidikan',
            self::OPERATOR_DINKES => 'kesehatan',
            self::OPERATOR_PARSEPOR => 'parsepor',
            default => null,
        };
    }

    public function verifiedTrack(): ?ApplicationType
    {
        return match ($this) {
            self::OPERATOR_DINKES => ApplicationType::DISABILITAS,
            self::OPERATOR_PARSEPOR => ApplicationType::NON_AKADEMIK,
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
