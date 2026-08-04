<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => static::writeAudit($model, 'created', [], $model->getAttributes()));

        static::updated(function (Model $model): void {
            static::writeAudit(
                $model,
                'updated',
                array_intersect_key($model->getOriginal(), $model->getChanges()),
                $model->getChanges(),
            );
        });

        static::deleted(fn (Model $model) => static::writeAudit($model, 'deleted', $model->getOriginal(), []));
    }

    private static function writeAudit(Model $model, string $event, array $oldValues, array $newValues): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        $actorId = auth()->id();

        if ($event === 'deleted' && $model instanceof \App\Models\User && $actorId === $model->getKey()) {
            $actorId = null;
        }

        AuditLog::query()->create([
            'user_id' => $actorId,
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => static::sanitizeAuditValues($oldValues),
            'new_values' => static::sanitizeAuditValues($newValues),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    private static function sanitizeAuditValues(array $values): array
    {
        return collect($values)
            ->except(['password', 'two_factor_secret', 'two_factor_recovery_codes'])
            ->all();
    }
}
