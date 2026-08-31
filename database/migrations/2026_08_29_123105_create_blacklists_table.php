<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blacklists', function (Blueprint $table) {
            $table->id();

            $table->string('nik', 20)->index();

            $table->year('tahun_berlaku');

            $table->foreignId('pendaftaran_id')
                ->nullable()
                ->constrained('pendaftarans')
                ->nullOnDelete();

            $table->text('alasan');

            $table->boolean('aktif')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'nik',
                'tahun_berlaku',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklists');
    }
};