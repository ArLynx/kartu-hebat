<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'from_status' => ApplicationStatus::class,
            'to_status' => ApplicationStatus::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'submitted' => 'Pengajuan dikirim',
            'sanggahan_submitted' => 'Sanggahan diajukan',
            'verification_MS' => 'Keputusan MS',
            'verification_TMS' => 'Keputusan TMS',
            'agency_verification_MS' => 'Penilaian dinas MS',
            'agency_verification_TMS' => 'Penilaian dinas TMS',
            'selection_decision_recorded' => 'Keputusan seleksi disimpan',
            'selection_result_published' => 'Hasil seleksi dipublikasikan',
            default => str($this->action)->replace('_', ' ')->title()->toString(),
        };
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
