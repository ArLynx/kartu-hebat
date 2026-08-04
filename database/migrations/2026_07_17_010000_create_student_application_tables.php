<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nik', 16)->unique();
            $table->string('nim')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('universitas');
            $table->string('program_studi');
            $table->unsignedTinyInteger('semester');
            $table->decimal('ipk', 3, 2)->nullable();
            $table->text('alamat');
            $table->foreignId('village_id')->constrained('villages')->restrictOnDelete();
            $table->string('status_kependudukan')->default('belum_diverifikasi');
            $table->unsignedBigInteger('penghasilan_keluarga')->nullable();
            $table->unsignedTinyInteger('jumlah_tanggungan')->nullable();
            $table->unsignedTinyInteger('desil_sosial')->nullable();
            $table->unsignedTinyInteger('desil_pendidikan')->nullable();
            $table->text('prestasi')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pengajuan')->unique();
            $table->foreignId('mahasiswa_id')->constrained('users')->cascadeOnDelete();
            $table->string('periode');
            $table->string('status')->default('DRAFT')->index();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->text('catatan')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['mahasiswa_id', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
        Schema::dropIfExists('mahasiswa_profiles');
    }
};
