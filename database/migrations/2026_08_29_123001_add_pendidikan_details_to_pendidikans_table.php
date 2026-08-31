<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendidikans', function (Blueprint $table) {
            $table->string('jurusan')->nullable()->after('fakultas');

            $table->enum('status_perguruan_tinggi', [
                'negeri',
                'swasta',
            ])->nullable()->after('universitas');

            $table->string('akreditasi_perguruan_tinggi')->nullable()
                ->after('status_perguruan_tinggi');

            $table->string('akreditasi_program_studi')->nullable()
                ->after('akreditasi_perguruan_tinggi');

            $table->string('nama_ketua_prodi')->nullable()
                ->after('akreditasi_program_studi');

            $table->string('nama_ketua_jurusan')->nullable()
                ->after('nama_ketua_prodi');

            $table->string('nama_direktur')->nullable()
                ->after('nama_ketua_jurusan');

            $table->string('nama_rektor')->nullable()
                ->after('nama_direktur');

            $table->text('alamat_perguruan_tinggi')->nullable()
                ->after('nama_rektor');

            $table->string('no_telp_perguruan_tinggi', 30)->nullable()
                ->after('alamat_perguruan_tinggi');
        });
    }

    public function down(): void
    {
        Schema::table('pendidikans', function (Blueprint $table) {
            $table->dropColumn([
                'jurusan',
                'status_perguruan_tinggi',
                'akreditasi_perguruan_tinggi',
                'akreditasi_program_studi',
                'nama_ketua_prodi',
                'nama_ketua_jurusan',
                'nama_direktur',
                'nama_rektor',
                'alamat_perguruan_tinggi',
                'no_telp_perguruan_tinggi',
            ]);
        });
    }
};