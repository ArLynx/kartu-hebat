<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orang_tuas', function (Blueprint $table) {
            $table->enum('status_ayah', [
                'hidup',
                'meninggal_dunia',
            ])
                ->default('hidup')
                ->after('nama_ayah');

            $table->enum('status_ibu', [
                'hidup',
                'meninggal_dunia',
            ])
                ->default('hidup')
                ->after('nama_ibu');
        });
    }

    public function down(): void
    {
        Schema::table('orang_tuas', function (Blueprint $table) {
            $table->dropColumn([
                'status_ayah',
                'status_ibu',
            ]);
        });
    }
};
