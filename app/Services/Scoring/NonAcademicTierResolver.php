<?php

namespace App\Services\Scoring;

use App\Enums\KategoriPrestasi;
use App\Enums\PosisiOrganisasi;
use App\Enums\TingkatOrganisasi;
use App\Models\Prestasi;
use Illuminate\Support\Collection;

class NonAcademicTierResolver
{
    public const TIER_PENGURUS_INTI = 1;

    public const TIER_JUARA = 2;

    public const TIER_ANGGOTA_ORGANISASI = 3;

    public const TIER_PESERTA = 4;

    public const TIER_TIDAK_ADA = 99;

    /**
     * Urutan tingkat lomba sesuai Perbup: Internasional > Nasional > Provinsi > Universitas > Kabupaten.
     *
     * @var array<string, int>
     */
    private const TINGKAT_LOMBA_RANK = [
        'internasional' => 1,
        'nasional' => 2,
        'provinsi' => 3,
        'kampus' => 4,
        'universitas' => 4,
        'kabupaten' => 5,
        'kabupaten/kota' => 5,
        'kota' => 5,
    ];

    /**
     * Peringkat juara 1, 2, dan 3 berada di tier juara. Harapan, favorit, dan peserta adalah non-juara.
     *
     * @var array<string, int>
     */
    private const PERINGKAT_RANK = [
        'juara 1' => 1,
        'juara_1' => 1,
        'juara i' => 1,
        'emas' => 1,
        'juara 2' => 2,
        'juara_2' => 2,
        'juara ii' => 2,
        'perak' => 2,
        'juara 3' => 3,
        'juara_3' => 3,
        'juara iii' => 3,
        'perunggu' => 3,
        'harapan' => 4,
        'favorit' => 5,
        'peserta' => 6,
    ];

    public function profile(Collection $prestasis): NonAcademicTierProfile
    {
        $best = $this->bestRecord($prestasis);

        return $best !== null ? $this->profileFor($best) : NonAcademicTierProfile::empty();
    }

    public function bestRecord(Collection $prestasis): ?Prestasi
    {
        $best = null;
        $bestProfile = null;

        foreach ($prestasis as $prestasi) {
            $profile = $this->profileFor($prestasi);

            if ($bestProfile === null || $profile->compareTo($bestProfile) < 0) {
                $best = $prestasi;
                $bestProfile = $profile;
            }
        }

        return $best;
    }

    public function bestRecordOfCategory(Collection $prestasis, KategoriPrestasi $kategori): ?Prestasi
    {
        return $this->bestRecord(
            $prestasis->filter(fn (Prestasi $p): bool => $p->kategoriEnum() === $kategori)->values(),
        );
    }

    public function profileFor(Prestasi $prestasi): NonAcademicTierProfile
    {
        if ($prestasi->kategoriEnum() === KategoriPrestasi::ORGANISASI) {
            return $this->organisationProfile($prestasi);
        }

        return $this->competitionProfile($prestasi);
    }

    private function organisationProfile(Prestasi $prestasi): NonAcademicTierProfile
    {
        $posisi = $prestasi->posisiEnum() ?? self::posisiFromJabatan($prestasi->jabatan_ormawa);
        $tingkat = $prestasi->tingkatOrganisasiEnum() ?? self::tingkatOrganisasiFromNama($prestasi->nama_prestasi);

        return new NonAcademicTierProfile(
            tier: $posisi->isInti() ? self::TIER_PENGURUS_INTI : self::TIER_ANGGOTA_ORGANISASI,
            posisiRank: $posisi->rank(),
            tingkatOrganisasiRank: $tingkat->rank(),
        );
    }

    private function competitionProfile(Prestasi $prestasi): NonAcademicTierProfile
    {
        $peringkatRank = self::peringkatRank($prestasi->peringkat);

        // Juara 1-3, harapan, dan favorit masih dianggap juara (tier 2); hanya peserta yang turun ke tier 4.
        $isWinner = $peringkatRank <= 5;

        return new NonAcademicTierProfile(
            tier: $isWinner ? self::TIER_JUARA : self::TIER_PESERTA,
            tingkatLombaRank: self::tingkatLombaRank($prestasi->tingkat),
            peringkatRank: $peringkatRank,
        );
    }

    public static function posisiFromJabatan(?string $jabatan): PosisiOrganisasi
    {
        $text = strtolower(trim((string) $jabatan));
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        if ($text === '') {
            return PosisiOrganisasi::ANGGOTA;
        }

        if (str_contains($text, 'wakil ketua')) {
            return PosisiOrganisasi::WAKIL_KETUA;
        }

        if (str_contains($text, 'wakil')) {
            return PosisiOrganisasi::ANGGOTA;
        }

        if (str_contains($text, 'ketua') || str_contains($text, 'presiden')) {
            return PosisiOrganisasi::KETUA;
        }

        if (str_contains($text, 'sekretaris')) {
            return PosisiOrganisasi::SEKRETARIS;
        }

        if (str_contains($text, 'bendahara')) {
            return PosisiOrganisasi::BENDAHARA;
        }

        if (
            str_contains($text, 'koordinator')
            || str_contains($text, 'kadiv')
            || str_contains($text, 'kepala divisi')
            || str_contains($text, 'kepala bidang')
            || str_contains($text, 'kepala departemen')
        ) {
            return PosisiOrganisasi::KOORDINATOR;
        }

        return PosisiOrganisasi::ANGGOTA;
    }

    public static function tingkatOrganisasiFromNama(?string $nama): TingkatOrganisasi
    {
        $text = strtolower(trim((string) $nama));

        return str_contains($text, 'fakultas')
            ? TingkatOrganisasi::FAKULTAS
            : TingkatOrganisasi::UNIVERSITAS;
    }

    public static function tingkatLombaRank(?string $tingkat): int
    {
        $key = strtolower(trim((string) $tingkat));
        $key = preg_replace('/\s+/', ' ', $key) ?? '';

        return self::TINGKAT_LOMBA_RANK[$key] ?? 99;
    }

    public static function peringkatRank(?string $peringkat): int
    {
        $key = strtolower(trim((string) $peringkat));
        $key = preg_replace('/\s+/', '_', $key) ?? '';

        return self::PERINGKAT_RANK[$key] ?? 99;
    }
}
