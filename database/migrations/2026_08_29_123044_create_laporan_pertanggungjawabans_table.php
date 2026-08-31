<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_pertanggungjawabans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pendaftaran_id')
                ->unique()
                ->constrained('pendaftarans')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('file_path');

            $table->string('original_name')->nullable();

            $table->string('mime_type', 100)->nullable();

            $table->unsignedBigInteger('size')->nullable();

            $table->enum('status', [
                'belum_upload',
                'menunggu_verifikasi',
                'disetujui',
                'ditolak',
            ])->default('belum_upload');

            $table->text('catatan')->nullable();

            $table->date('batas_pengumpulan')->nullable();

            $table->timestamp('uploaded_at')->nullable();

            $table->timestamp('verified_at')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_pertanggungjawabans');
    }
};
