<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orang_tuas', function (Blueprint $table): void {
            if (! Schema::hasColumn('orang_tuas', 'sk_jabatan_ayah')) {
                $table->string('sk_jabatan_ayah')->nullable()->after('jabatan_ayah');
            }
            if (! Schema::hasColumn('orang_tuas', 'sk_jabatan_ibu')) {
                $table->string('sk_jabatan_ibu')->nullable()->after('jabatan_ibu');
            }
            if (! Schema::hasColumn('orang_tuas', 'sk_jabatan_wali')) {
                $table->string('sk_jabatan_wali')->nullable()->after('jabatan_wali');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToDrop = array_values(array_filter([
            'sk_jabatan_ayah',
            'sk_jabatan_ibu',
            'sk_jabatan_wali',
        ], fn (string $column): bool => Schema::hasColumn('orang_tuas', $column)));

        if ($columnsToDrop !== []) {
            Schema::table('orang_tuas', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
