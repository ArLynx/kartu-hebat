<?php

namespace App\Models;

use App\Enums\ApplicationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'application_type' => ApplicationType::class,
            'weight' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ApplicationScore::class);
    }
}
