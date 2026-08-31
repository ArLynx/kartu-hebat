<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPertanggungjawaban extends Model
{
    use HasFactory;

    protected $table = 'laporan_pertanggungjawabans';

    protected $fillable = [
        'pendaftaran_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'status',
        'catatan',
        'batas_pengumpulan',
        'uploaded_at',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'batas_pengumpulan' => 'date',
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
