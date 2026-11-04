<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pribadis', function (Blueprint $table) {
            $table->dropUnique('data_pribadis_nik_unique');
            $table->index('nik', 'data_pribadis_nik_index');
        });
    }

    public function down(): void
    {
        Schema::table('data_pribadis', function (Blueprint $table) {
            $table->dropIndex('data_pribadis_nik_index');
            $table->unique('nik', 'data_pribadis_nik_unique');
        });
    }
};
