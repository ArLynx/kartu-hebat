<?php

namespace App\Enums;

use App\Services\Scoring\AcademicScoring;
use App\Services\Scoring\DesilScoring;
use App\Services\Scoring\DisabilityScoring;
use App\Services\Scoring\PrestasiScoring;

enum ApplicationType: string
{
    case AKADEMIK = 'AKADEMIK';
    case TIDAK_MAMPU = 'TIDAK_MAMPU';
    case DISABILITAS = 'DISABILITAS';
    case NON_AKADEMIK = 'NON_AKADEMIK';

    public function label(): string
    {
        return match ($this) {
            self::AKADEMIK => 'Akademik',
            self::TIDAK_MAMPU => 'Tidak Mampu',
            self::DISABILITAS => 'Disabilitas',
            self::NON_AKADEMIK => 'Non-Akademik / Prestasi',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AKADEMIK => 'Seleksi berdasarkan IPK dan semester aktif.',
            self::TIDAK_MAMPU => 'Seleksi berdasarkan desil sosial ekonomi terverifikasi.',
            self::DISABILITAS => 'Seleksi afirmasi untuk mahasiswa penyandang disabilitas, dengan bobot IPK, semester, dan jenis disabilitas.',
            self::NON_AKADEMIK => 'Seleksi afirmasi untuk mahasiswa berprestasi (MTQ, olahraga, seni, wirausaha, dsb.) berdasarkan tingkat dan peringkat kejuaraan.',
        };
    }

    public function quota(): int
    {
        return (int) config('kartu_hebat.quotas.'.$this->value, 0);
    }

    public function scoringStrategyClass(): string
    {
        return match ($this) {
            self::AKADEMIK => AcademicScoring::class,
            self::TIDAK_MAMPU => DesilScoring::class,
            self::DISABILITAS => DisabilityScoring::class,
            self::NON_AKADEMIK => PrestasiScoring::class,
        };
    }

    public function isAffirmation(): bool
    {
        return match ($this) {
            self::AKADEMIK => false,
            self::TIDAK_MAMPU, self::DISABILITAS, self::NON_AKADEMIK => true,
        };
    }
}
