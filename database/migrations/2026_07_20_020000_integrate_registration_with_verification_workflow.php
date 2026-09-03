<?php

use App\Enums\ApplicationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kategori_beasiswas') && ! Schema::hasColumn('kategori_beasiswas', 'application_type')) {
            Schema::table('kategori_beasiswas', function (Blueprint $table): void {
                $table->string('application_type')->nullable()->after('kode')->index();
            });

            DB::table('kategori_beasiswas')
                ->whereNull('application_type')
                ->update(['application_type' => ApplicationType::AKADEMIK->value]);
        }

        if (Schema::hasTable('data_pribadis') && ! Schema::hasColumn('data_pribadis', 'village_id')) {
            Schema::table('data_pribadis', function (Blueprint $table): void {
                $table->foreignId('village_id')
                    ->nullable()
                    ->after('alamat')
                    ->constrained('villages')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasTable('applications') && ! Schema::hasColumn('applications', 'pendaftaran_id')) {
            Schema::table('applications', function (Blueprint $table): void {
                $table->foreignId('pendaftaran_id')
                    ->nullable()
                    ->after('id')
                    ->unique()
                    ->constrained('pendaftarans')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applications') && Schema::hasColumn('applications', 'pendaftaran_id')) {
            Schema::table('applications', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('pendaftaran_id');
            });
        }

        if (Schema::hasTable('data_pribadis') && Schema::hasColumn('data_pribadis', 'village_id')) {
            Schema::table('data_pribadis', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('village_id');
            });
        }

        if (Schema::hasTable('kategori_beasiswas') && Schema::hasColumn('kategori_beasiswas', 'application_type')) {
            Schema::table('kategori_beasiswas', function (Blueprint $table): void {
                $table->dropColumn('application_type');
            });
        }
    }
};
