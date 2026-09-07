<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mahasiswa_profiles') && Schema::hasColumn('mahasiswa_profiles', 'desil_pendidikan')) {
            Schema::table('mahasiswa_profiles', function (Blueprint $table) {
                $table->dropColumn('desil_pendidikan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mahasiswa_profiles') && ! Schema::hasColumn('mahasiswa_profiles', 'desil_pendidikan')) {
            Schema::table('mahasiswa_profiles', function (Blueprint $table) {
                $table->unsignedTinyInteger('desil_pendidikan')->nullable()->after('desil_sosial');
            });
        }
    }
};
