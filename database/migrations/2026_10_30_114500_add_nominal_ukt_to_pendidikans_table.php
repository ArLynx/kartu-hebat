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
        Schema::table('pendidikans', function (Blueprint $table): void {
            if (! Schema::hasColumn('pendidikans', 'nominal_ukt')) {
                $table->decimal('nominal_ukt', 15, 2)->nullable()->after('ipk');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendidikans', function (Blueprint $table): void {
            if (Schema::hasColumn('pendidikans', 'nominal_ukt')) {
                $table->dropColumn('nominal_ukt');
            }
        });
    }
};
