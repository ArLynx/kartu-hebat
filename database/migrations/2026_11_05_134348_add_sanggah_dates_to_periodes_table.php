<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodes', function (Blueprint $table) {
            $table->date('tanggal_mulai_sanggah')->nullable()->after('tanggal_selesai_lpj');
            $table->date('tanggal_selesai_sanggah')->nullable()->after('tanggal_mulai_sanggah');
        });
    }

    public function down(): void
    {
        Schema::table('periodes', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai_sanggah', 'tanggal_selesai_sanggah']);
        });
    }
};
