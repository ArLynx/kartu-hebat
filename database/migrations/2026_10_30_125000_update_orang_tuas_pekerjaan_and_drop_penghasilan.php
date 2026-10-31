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
            if (! Schema::hasColumn('orang_tuas', 'jabatan_ayah')) {
                $table->string('jabatan_ayah', 150)->nullable()->after('pekerjaan_ayah');
            }
            if (! Schema::hasColumn('orang_tuas', 'jabatan_ibu')) {
                $table->string('jabatan_ibu', 150)->nullable()->after('pekerjaan_ibu');
            }
            if (! Schema::hasColumn('orang_tuas', 'jabatan_wali')) {
                $table->string('jabatan_wali', 150)->nullable()->after('pekerjaan_wali');
            }
        });

        $columnsToDrop = array_values(array_filter([
            'penghasilan_ayah',
            'penghasilan_ibu',
            'penghasilan_wali',
        ], fn (string $column): bool => Schema::hasColumn('orang_tuas', $column)));

        if ($columnsToDrop !== []) {
            Schema::table('orang_tuas', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orang_tuas', function (Blueprint $table): void {
            if (! Schema::hasColumn('orang_tuas', 'penghasilan_ayah')) {
                $table->decimal('penghasilan_ayah', 15, 2)->nullable()->after('pekerjaan_ayah');
            }
            if (! Schema::hasColumn('orang_tuas', 'penghasilan_ibu')) {
                $table->decimal('penghasilan_ibu', 15, 2)->nullable()->after('pekerjaan_ibu');
            }
            if (! Schema::hasColumn('orang_tuas', 'penghasilan_wali')) {
                $table->decimal('penghasilan_wali', 15, 2)->nullable()->after('pekerjaan_wali');
            }
        });

        $columnsToDrop = array_values(array_filter([
            'jabatan_ayah',
            'jabatan_ibu',
            'jabatan_wali',
        ], fn (string $column): bool => Schema::hasColumn('orang_tuas', $column)));

        if ($columnsToDrop !== []) {
            Schema::table('orang_tuas', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
