<?php

namespace App\Services\Scoring;

final readonly class NonAcademicTierProfile
{
    public function __construct(
        public int $tier,
        public int $posisiRank = 99,
        public int $tingkatOrganisasiRank = 99,
        public int $tingkatLombaRank = 99,
        public int $peringkatRank = 99,
    ) {}

    public static function empty(): self
    {
        return new self(tier: NonAcademicTierResolver::TIER_TIDAK_ADA);
    }

    /**
     * Semakin kecil hasilnya, semakin tinggi prioritas kandidat.
     */
    public function compareTo(self $other): int
    {
        return ($this->tier <=> $other->tier)
            ?: ($this->posisiRank <=> $other->posisiRank)
            ?: ($this->tingkatOrganisasiRank <=> $other->tingkatOrganisasiRank)
            ?: ($this->tingkatLombaRank <=> $other->tingkatLombaRank)
            ?: ($this->peringkatRank <=> $other->peringkatRank);
    }
}
