<?php

namespace App\Enums;

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
            self::AKADEMIK => \App\Services\Scoring\AcademicScoring::class,
            self::TIDAK_MAMPU => \App\Services\Scoring\DesilScoring::class,
            self::DISABILITAS => \App\Services\Scoring\DisabilityScoring::class,
            self::NON_AKADEMIK => \App\Services\Scoring\PrestasiScoring::class,
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
