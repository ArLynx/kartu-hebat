<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verifier_id')->constrained('users')->restrictOnDelete();
            $table->string('stage'); // desa | kecamatan | dukcapil | sosial | pendidikan
            $table->unsignedInteger('round')->default(1);
            $table->string('result', 20); // belum_dinilai | memenuhi | tidak_memenuhi
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'stage', 'round'], 'document_verifications_unique_row');
            $table->index(['application_id', 'stage', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_verifications');
    }
};
