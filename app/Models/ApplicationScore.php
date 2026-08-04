<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationScore extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'raw_value' => 'decimal:2',
            'normalized_score' => 'decimal:4',
            'weighted_score' => 'decimal:4',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
