<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jalur_beasiswas', function (Blueprint $table) {
            $table->id();

            $table->string('kode', 30)->unique();

            $table->string('nama');

            $table->text('deskripsi')->nullable();

            $table->boolean('aktif')->default(true);

            $table->unsignedTinyInteger('urutan')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jalur_beasiswas');
    }
};
