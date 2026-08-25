<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait Auditable
{
    private static ?bool $tableExists = null;

    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            if (! $model->exists) {
                return;
            }

            static::writeAudit($model, 'created', [], $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            if (! $model->exists) {
                return;
            }

            static::writeAudit(
                $model,
                'updated',
                array_intersect_key($model->getOriginal(), $model->getChanges()),
                $model->getChanges(),
            );
        });

        static::deleted(function (Model $model): void {
            if ($model->exists) {
                return;
            }

            static::writeAudit($model, 'deleted', $model->getOriginal(), []);
        });
    }

    private static function writeAudit(Model $model, string $event, array $oldValues, array $newValues): void
    {
        self::$tableExists ??= Schema::hasTable('audit_logs');

        if (! self::$tableExists) {
            return;
        }

        // Guard hanya untuk kasus user memperbarui baris dirinya sendiri yang
        // mungkin sudah dihapus di tempat lain (FK audit_logs.user_id ke users.id,
        // mis. update remember_token setelah akun dihapus). Untuk penulisan lain
        // baris pasti ada karena baru saja ditulis oleh instance yang sama.
        if (
            $event !== 'deleted'
            && $model instanceof User
            && (string) auth()->id() === (string) $model->getKey()
            && User::query()->whereKey($model->getKey())->doesntExist()
        ) {
            return;
        }

        $actorId = auth()->id();

        if ($event === 'deleted' && $model instanceof User && $actorId === $model->getKey()) {
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
