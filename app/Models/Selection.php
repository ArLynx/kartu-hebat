<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Selection extends Model
{
    use Auditable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:4',
            'decided_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Keputusan internal yang tersimpan boleh berupa huruf kecil dari impor
     * lama; baca tanpa membedakan huruf besar/kecil agar tidak jatuh ke DITOLAK.
     */
    public function manualDecisionStatus(): ?ApplicationStatus
    {
        $value = strtoupper(trim((string) $this->manual_decision));

        if ($value === '') {
            return null;
        }

        return ApplicationStatus::tryFrom($value);
    }

    /**
     * Filter keputusan internal tanpa membedakan huruf besar/kecil.
     */
    public function scopeWhereManualDecision(Builder $query, ApplicationStatus $decision): Builder
    {
        return $query->whereRaw('UPPER(TRIM(manual_decision)) = ?', [$decision->value]);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
