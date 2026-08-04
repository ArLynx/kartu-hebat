<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pendaftarans')) {
            Schema::table('pendaftarans', function (Blueprint $table): void {
                if (! Schema::hasColumn('pendaftarans', 'prestasi_dikonfirmasi_at')) {
                    $table->timestamp('prestasi_dikonfirmasi_at')->nullable();
                }
                if (! Schema::hasColumn('pendaftarans', 'review_dikonfirmasi_at')) {
                    $table->timestamp('review_dikonfirmasi_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('dokumens')) {
            Schema::table('dokumens', function (Blueprint $table): void {
                if (! Schema::hasColumn('dokumens', 'nama_file_asli')) {
                    $table->string('nama_file_asli')->nullable();
                }
                if (! Schema::hasColumn('dokumens', 'mime_type')) {
                    $table->string('mime_type', 120)->nullable();
                }
                if (! Schema::hasColumn('dokumens', 'ukuran_file')) {
                    $table->unsignedBigInteger('ukuran_file')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dokumens')) {
            Schema::table('dokumens', function (Blueprint $table): void {
                $columns = collect(['nama_file_asli', 'mime_type', 'ukuran_file'])
                    ->filter(fn (string $column): bool => Schema::hasColumn('dokumens', $column))
                    ->all();

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('pendaftarans')) {
            Schema::table('pendaftarans', function (Blueprint $table): void {
                $columns = collect(['prestasi_dikonfirmasi_at', 'review_dikonfirmasi_at'])
                    ->filter(fn (string $column): bool => Schema::hasColumn('pendaftarans', $column))
                    ->all();

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
