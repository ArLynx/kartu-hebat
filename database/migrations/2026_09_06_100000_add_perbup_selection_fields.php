<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pendidikans')) {
            Schema::table('pendidikans', function (Blueprint $table) {
                if (! Schema::hasColumn('pendidikans', 'nilai_raport')) {
                    $table->decimal('nilai_raport', 5, 2)->nullable()->after('ipk');
                }
            });
        }

        if (Schema::hasTable('prestasis')) {
            Schema::table('prestasis', function (Blueprint $table) {
                if (! Schema::hasColumn('prestasis', 'is_pengurus_inti_ormawa')) {
                    $table->boolean('is_pengurus_inti_ormawa')->default(false)->after('peringkat');
                }
                if (! Schema::hasColumn('prestasis', 'jabatan_ormawa')) {
                    $table->string('jabatan_ormawa')->nullable()->after('is_pengurus_inti_ormawa');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('prestasis')) {
            Schema::table('prestasis', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('prestasis', 'jabatan_ormawa')) {
                    $columns[] = 'jabatan_ormawa';
                }
                if (Schema::hasColumn('prestasis', 'is_pengurus_inti_ormawa')) {
                    $columns[] = 'is_pengurus_inti_ormawa';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('pendidikans')) {
            Schema::table('pendidikans', function (Blueprint $table) {
                if (Schema::hasColumn('pendidikans', 'nilai_raport')) {
                    $table->dropColumn('nilai_raport');
                }
            });
        }
    }
};
