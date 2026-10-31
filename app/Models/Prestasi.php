<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prestasi extends Model
{
    public const TINGKAT_OPTIONS = [
        'kampus' => 'Kampus / Perguruan Tinggi',
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
        'nama_prestasi',
        'tingkat',
        'peringkat',
        'is_pengurus_inti_ormawa',
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

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
