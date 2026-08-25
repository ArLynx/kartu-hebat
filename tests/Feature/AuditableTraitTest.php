<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditableTraitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetTableExistsCache();
    }

    public function test_update_writes_audit_without_extra_queries_per_write(): void
    {
        $schemaProbes = fn (iterable $queries) => $queries->filter(
            fn ($query) => str_contains($query, 'information_schema') || str_contains($query, 'sqlite_master'),
        )->count();
        $selectUsers = fn (iterable $queries) => $queries->filter(
            fn ($query) => str_contains($query, 'select') && str_contains($query, 'users'),
        )->count();

        DB::enableQueryLog();

        DB::flushQueryLog();
        $user = User::factory()->create();
        $this->assertSame(1, $schemaProbes(collect(DB::getQueryLog())->pluck('query')));

        DB::flushQueryLog();
        $user->update(['name' => 'Nama Pertama']);
        $queries = collect(DB::getQueryLog())->pluck('query');
        $this->assertSame(0, $schemaProbes($queries));
        $this->assertSame(0, $selectUsers($queries));

        DB::flushQueryLog();
        $user->update(['name' => 'Nama Kedua']);
        $this->assertSame(0, $schemaProbes(collect(DB::getQueryLog())->pluck('query')));

        DB::disableQueryLog();

        $this->assertSame(
            2,
            AuditLog::query()->where('event', 'updated')->where('auditable_id', $user->id)->count(),
        );
    }

    public function test_stale_own_user_row_update_skips_audit_without_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        User::query()->whereKey($user->getKey())->delete();

        $user->setRememberToken(Str::random(10));
        $user->save();

        $this->assertSame(
            0,
            AuditLog::query()->where('auditable_id', $user->id)->where('event', 'updated')->count(),
        );
    }

    private function resetTableExistsCache(): void
    {
        if (! property_exists(User::class, 'tableExists')) {
            return;
        }

        $property = new \ReflectionProperty(User::class, 'tableExists');
        $property->setValue(null, null);
    }
}
