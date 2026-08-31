<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formulir_pendaftarans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pendaftaran_id')
                ->unique()
                ->constrained('pendaftarans')
                ->cascadeOnDelete();

            $table->enum('jenis_form', [
                'A',
                'B',
            ])->nullable();

            $table->string('surat_permohonan')->nullable();

            $table->string('pakta_integritas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formulir_pendaftarans');
    }
};