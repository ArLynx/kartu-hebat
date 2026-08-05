<?php

namespace App\Services;

use App\Enums\ApplicationType;
use App\Services\Scoring\ScoringStrategy;
use InvalidArgumentException;

class ScoringStrategyResolver
{
    /**
     * @var array<string, ScoringStrategy>
     */
    private array $cache = [];

    public function resolve(ApplicationType $type): ScoringStrategy
    {
        if (isset($this->cache[$type->value])) {
            return $this->cache[$type->value];
        }

        $class = $type->scoringStrategyClass();

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Scoring strategy [{$class}] untuk jalur {$type->value} tidak ditemukan.");
        }

        $strategy = app($class);

        if (! $strategy instanceof ScoringStrategy) {
            throw new InvalidArgumentException("Class [{$class}] harus mengimplementasikan ScoringStrategy.");
        }

        return $this->cache[$type->value] = $strategy;
    }
}
