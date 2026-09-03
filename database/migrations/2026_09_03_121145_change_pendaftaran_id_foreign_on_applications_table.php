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
        // Bersihkan aplikasi yatim (orphaned) yang tidak memiliki pendaftaran valid
        DB::table('applications')
            ->whereNull('pendaftaran_id')
            ->orWhereNotIn('pendaftaran_id', DB::table('pendaftarans')->select('id'))
            ->delete();

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('applications', function (Blueprint $table): void {
                $table->dropForeign(['pendaftaran_id']);
                $table->foreign('pendaftaran_id')
                    ->references('id')
                    ->on('pendaftarans')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('applications', function (Blueprint $table): void {
                $table->dropForeign(['pendaftaran_id']);
                $table->foreign('pendaftaran_id')
                    ->references('id')
                    ->on('pendaftarans')
                    ->nullOnDelete();
            });
        }
    }
};
