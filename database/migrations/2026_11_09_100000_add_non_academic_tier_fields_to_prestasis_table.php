<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prestasis')) {
            return;
        }

        Schema::table('prestasis', function (Blueprint $table) {
            if (! Schema::hasColumn('prestasis', 'kategori_prestasi')) {
                $table->string('kategori_prestasi', 20)->nullable()->after('jenis');
            }
            if (! Schema::hasColumn('prestasis', 'tingkat_organisasi')) {
                $table->string('tingkat_organisasi', 20)->nullable()->after('is_pengurus_inti_ormawa');
            }
            if (! Schema::hasColumn('prestasis', 'posisi_organisasi')) {
                $table->string('posisi_organisasi', 20)->nullable()->after('tingkat_organisasi');
            }
        });

        $this->backfill();
    }

    public function down(): void
    {
        if (! Schema::hasTable('prestasis')) {
            return;
        }

        Schema::table('prestasis', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['kategori_prestasi', 'tingkat_organisasi', 'posisi_organisasi'],
                fn (string $column): bool => Schema::hasColumn('prestasis', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function backfill(): void
    {
        DB::table('prestasis')
            ->select(['id', 'nama_prestasi', 'jabatan_ormawa', 'is_pengurus_inti_ormawa'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    if (! (bool) $row->is_pengurus_inti_ormawa) {
                        DB::table('prestasis')->where('id', $row->id)->update([
                            'kategori_prestasi' => 'lomba',
                        ]);

                        continue;
                    }

                    DB::table('prestasis')->where('id', $row->id)->update([
                        'kategori_prestasi' => 'organisasi',
                        'posisi_organisasi' => $this->mapPosisi($row->jabatan_ormawa),
                        'tingkat_organisasi' => str_contains(strtolower((string) $row->nama_prestasi), 'fakultas')
                            ? 'fakultas'
                            : 'universitas',
                    ]);
                }
            });
    }

    private function mapPosisi(?string $jabatan): string
    {
        $text = strtolower(trim((string) $jabatan));
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        if ($text === '') {
            return 'anggota';
        }

        if (str_contains($text, 'wakil ketua')) {
            return 'wakil_ketua';
        }

        if (str_contains($text, 'wakil')) {
            return 'anggota';
        }

        if (str_contains($text, 'ketua')) {
            return 'ketua';
        }

        if (str_contains($text, 'sekretaris')) {
            return 'sekretaris';
        }

        if (str_contains($text, 'bendahara')) {
            return 'bendahara';
        }

        if (
            str_contains($text, 'koordinator')
            || str_contains($text, 'kadiv')
            || str_contains($text, 'kepala divisi')
            || str_contains($text, 'kepala bidang')
            || str_contains($text, 'kepala departemen')
        ) {
            return 'koordinator';
        }

        return 'anggota';
    }
};
