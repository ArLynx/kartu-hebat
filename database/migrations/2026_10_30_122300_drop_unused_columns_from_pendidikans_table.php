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
        $columnsToDrop = array_values(array_filter([
            'status_mahasiswa',
            'nilai_raport',
            'tahun_lulus',
            'akreditasi_program_studi',
            'nama_ketua_prodi',
            'nama_ketua_jurusan',
            'nama_direktur',
            'nama_rektor',
            'no_telp_perguruan_tinggi',
        ], fn (string $column): bool => Schema::hasColumn('pendidikans', $column)));

        if ($columnsToDrop !== []) {
            Schema::table('pendidikans', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendidikans', function (Blueprint $table): void {
            if (! Schema::hasColumn('pendidikans', 'nilai_raport')) {
                $table->decimal('nilai_raport', 5, 2)->nullable()->after('ipk');
            }
            if (! Schema::hasColumn('pendidikans', 'tahun_lulus')) {
                $table->year('tahun_lulus')->nullable()->after('tahun_masuk');
            }
            if (! Schema::hasColumn('pendidikans', 'status_mahasiswa')) {
                $table->enum('status_mahasiswa', ['aktif', 'cuti', 'lulus', 'drop_out', 'nonaktif'])->nullable()->after('tahun_masuk');
            }
            if (! Schema::hasColumn('pendidikans', 'akreditasi_program_studi')) {
                $table->string('akreditasi_program_studi')->nullable()->after('akreditasi_perguruan_tinggi');
            }
            if (! Schema::hasColumn('pendidikans', 'nama_ketua_prodi')) {
                $table->string('nama_ketua_prodi')->nullable();
            }
            if (! Schema::hasColumn('pendidikans', 'nama_ketua_jurusan')) {
                $table->string('nama_ketua_jurusan')->nullable();
            }
            if (! Schema::hasColumn('pendidikans', 'nama_direktur')) {
                $table->string('nama_direktur')->nullable();
            }
            if (! Schema::hasColumn('pendidikans', 'nama_rektor')) {
                $table->string('nama_rektor')->nullable();
            }
            if (! Schema::hasColumn('pendidikans', 'no_telp_perguruan_tinggi')) {
                $table->string('no_telp_perguruan_tinggi', 30)->nullable();
            }
        });
    }
};
