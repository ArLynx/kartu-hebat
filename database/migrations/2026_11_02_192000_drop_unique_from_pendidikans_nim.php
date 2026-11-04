<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendidikans', function (Blueprint $table) {
            $table->dropUnique('pendidikans_nim_unique');
            $table->index('nim', 'pendidikans_nim_index');
        });
    }

    public function down(): void
    {
        Schema::table('pendidikans', function (Blueprint $table) {
            $table->dropIndex('pendidikans_nim_index');
            $table->unique('nim', 'pendidikans_nim_unique');
        });
    }
};
