<?php

namespace App\Enums;

enum MasaSanggahStatus: string
{
    case BELUM_DIJADWALKAN = 'BELUM_DIJADWALKAN';
    case BELUM_DIMULAI = 'BELUM_DIMULAI';
    case SEDANG_BERLANGSUNG = 'SEDANG_BERLANGSUNG';
    case TELAH_BERAKHIR = 'TELAH_BERAKHIR';

    public function label(): string
    {
        return match ($this) {
            self::BELUM_DIJADWALKAN => 'Belum dijadwalkan',
            self::BELUM_DIMULAI => 'Belum dimulai',
            self::SEDANG_BERLANGSUNG => 'Sedang berlangsung',
            self::TELAH_BERAKHIR => 'Telah berakhir',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::BELUM_DIJADWALKAN => 'warning',
            self::BELUM_DIMULAI => 'info',
            self::SEDANG_BERLANGSUNG => 'success',
            self::TELAH_BERAKHIR => 'neutral',
        };
    }

    public function isScheduled(): bool
    {
        return $this !== self::BELUM_DIJADWALKAN;
    }

    /**
     * Kalimat penjelas yang ditampilkan di bawah jadwal masa sanggah.
     */
    public function notice(): ?string
    {
        return match ($this) {
            self::BELUM_DIMULAI => 'Masa sanggah belum dimulai. Pengajuan sanggahan baru dapat dilakukan pada tanggal mulai di atas.',
            self::SEDANG_BERLANGSUNG => 'Masa sanggah sedang berlangsung sampai tanggal batas akhir di atas.',
            self::TELAH_BERAKHIR => 'Masa sanggah telah berakhir.',
            self::BELUM_DIJADWALKAN => null,
        };
    }
}
