<?php

namespace App\Models;

use App\Enums\KategoriPrestasi;
use App\Enums\PosisiOrganisasi;
use App\Enums\TingkatOrganisasi;
use App\Services\Scoring\NonAcademicTierResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prestasi extends Model
{
    public const TINGKAT_OPTIONS = [
        'kampus' => 'Universitas',
        'kabupaten' => 'Kabupaten / Kota',
        'provinsi' => 'Provinsi',
        'nasional' => 'Nasional',
        'internasional' => 'Internasional',
    ];

    public const PERINGKAT_OPTIONS = [
        'juara 1' => 'Juara 1 / Medali Emas',
        'juara 2' => 'Juara 2 / Medali Perak',
        'juara 3' => 'Juara 3 / Medali Perunggu',
        'harapan' => 'Juara Harapan',
        'favorit' => 'Kategori Khusus / Juara Favorit',
        'peserta' => 'Finalis / Peserta / Kontingen Kegiatan',
    ];

    protected $fillable = [
        'pendaftaran_id',
        'jenis',
        'kategori_prestasi',
        'nama_prestasi',
        'tingkat',
        'peringkat',
        'is_pengurus_inti_ormawa',
        'tingkat_organisasi',
        'posisi_organisasi',
        'jabatan_ormawa',
        'penyelenggara',
        'tahun',
        'dokumen_prestasi',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'is_pengurus_inti_ormawa' => 'boolean',
        ];
    }

    public function getPeringkatLabelAttribute(): string
    {
        return self::PERINGKAT_OPTIONS[$this->peringkat] ?? $this->peringkat;
    }

    public function kategoriEnum(): KategoriPrestasi
    {
        if ($this->kategori_prestasi !== null) {
            return KategoriPrestasi::tryFrom($this->kategori_prestasi) ?? KategoriPrestasi::LOMBA;
        }

        if ($this->is_pengurus_inti_ormawa || $this->posisi_organisasi !== null || $this->tingkat_organisasi !== null) {
            return KategoriPrestasi::ORGANISASI;
        }

        return KategoriPrestasi::LOMBA;
    }

    public function posisiEnum(): ?PosisiOrganisasi
    {
        if ($this->posisi_organisasi !== null) {
            return PosisiOrganisasi::tryFrom($this->posisi_organisasi);
        }

        if ($this->kategoriEnum() !== KategoriPrestasi::ORGANISASI) {
            return null;
        }

        return NonAcademicTierResolver::posisiFromJabatan($this->jabatan_ormawa);
    }

    public function tingkatOrganisasiEnum(): ?TingkatOrganisasi
    {
        if ($this->tingkat_organisasi !== null) {
            return TingkatOrganisasi::tryFrom($this->tingkat_organisasi);
        }

        if ($this->kategoriEnum() !== KategoriPrestasi::ORGANISASI) {
            return null;
        }

        return NonAcademicTierResolver::tingkatOrganisasiFromNama($this->nama_prestasi);
    }

    public function isPengurusInti(): bool
    {
        if ($this->kategoriEnum() !== KategoriPrestasi::ORGANISASI) {
            return false;
        }

        return $this->posisiEnum()?->isInti() ?? false;
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
