<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pribadis', function (Blueprint $table) {
            if (Schema::hasColumn('data_pribadis', 'nisn')) {
                $table->dropColumn('nisn');
            }

            if (! Schema::hasColumn('data_pribadis', 'nomor_rekening')) {
                $table->string('nomor_rekening', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('data_pribadis', function (Blueprint $table) {
            if (! Schema::hasColumn('data_pribadis', 'nisn')) {
                $table->string('nisn', 20)->nullable();
            }

            if (Schema::hasColumn('data_pribadis', 'nomor_rekening')) {
                $table->dropColumn('nomor_rekening');
            }
        });
    }
};
