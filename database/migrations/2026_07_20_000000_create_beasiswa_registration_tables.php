<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('periodes')) {
            Schema::create('periodes', function (Blueprint $table): void {
                $table->id();
                $table->year('tahun');
                $table->string('nama')->nullable();
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai');
                $table->enum('status', ['draft', 'aktif', 'ditutup'])->default('draft');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('kategori_beasiswas')) {
            Schema::create('kategori_beasiswas', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('periode_id')->constrained('periodes')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('kode', 50)->unique();
                $table->string('nama');
                $table->text('deskripsi')->nullable();
                $table->integer('kuota')->default(0);
                $table->boolean('aktif')->default(true);
                $table->unsignedTinyInteger('urutan')->default(1);
                $table->string('icon', 50)->nullable();
                $table->string('warna', 30)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('kategori_beasiswas')) {
            Schema::table('kategori_beasiswas', function (Blueprint $table): void {
                if (! Schema::hasColumn('kategori_beasiswas', 'urutan')) {
                    $table->unsignedTinyInteger('urutan')->default(1);
                }
                if (! Schema::hasColumn('kategori_beasiswas', 'icon')) {
                    $table->string('icon', 50)->nullable();
                }
                if (! Schema::hasColumn('kategori_beasiswas', 'warna')) {
                    $table->string('warna', 30)->nullable();
                }
            });
        }

        if (! Schema::hasTable('pendaftarans')) {
            Schema::create('pendaftarans', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('periode_id')->constrained('periodes')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('kategori_beasiswa_id')->constrained('kategori_beasiswas')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('nomor_pendaftaran')->unique()->nullable();
                $table->enum('status', ['draft', 'submitted', 'verification', 'revision', 'approved', 'rejected'])->default('draft');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'periode_id']);
            });
        }

        if (! Schema::hasTable('data_pribadis')) {
            Schema::create('data_pribadis', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pendaftaran_id')->unique()->constrained('pendaftarans')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('nik', 20)->unique();
                $table->string('nisn', 20)->nullable();
                $table->string('nama_lengkap', 150)->nullable();
                $table->string('tempat_lahir');
                $table->date('tanggal_lahir');
                $table->enum('jenis_kelamin', ['L', 'P']);
                $table->string('agama', 30)->nullable();
                $table->text('alamat');
                $table->string('provinsi');
                $table->string('kabupaten');
                $table->string('kecamatan');
                $table->string('desa');
                $table->string('kode_pos', 10)->nullable();
                $table->string('no_hp', 25);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('data_pribadis')) {
            Schema::table('data_pribadis', function (Blueprint $table): void {
                if (! Schema::hasColumn('data_pribadis', 'nisn')) {
                    $table->string('nisn', 20)->nullable();
                }
                if (! Schema::hasColumn('data_pribadis', 'nama_lengkap')) {
                    $table->string('nama_lengkap', 150)->nullable();
                }
                if (! Schema::hasColumn('data_pribadis', 'agama')) {
                    $table->string('agama', 30)->nullable();
                }
            });
        }

        if (! Schema::hasTable('pendidikans')) {
            Schema::create('pendidikans', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pendaftaran_id')->unique()->constrained('pendaftarans')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('nim')->unique();
                $table->string('universitas')->nullable();
                $table->string('fakultas')->nullable();
                $table->string('program_studi')->nullable();
                $table->string('jenjang', 20)->nullable();
                $table->unsignedTinyInteger('semester')->nullable();
                $table->decimal('ipk', 3, 2)->nullable();
                $table->year('tahun_masuk')->nullable();
                $table->year('tahun_lulus')->nullable();
                $table->enum('status_mahasiswa', ['aktif', 'cuti', 'lulus', 'drop_out', 'nonaktif'])->nullable();
                $table->timestamp('pddikti_verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('pendidikans') && ! Schema::hasColumn('pendidikans', 'jenjang')) {
            Schema::table('pendidikans', function (Blueprint $table): void {
                $table->string('jenjang', 20)->nullable();
            });
        }

        if (! Schema::hasTable('prestasis')) {
            Schema::create('prestasis', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pendaftaran_id')->constrained('pendaftarans')->cascadeOnUpdate()->cascadeOnDelete();
                $table->enum('jenis', ['akademik', 'non_akademik']);
                $table->string('nama_prestasi');
                $table->enum('tingkat', ['kampus', 'kabupaten', 'provinsi', 'nasional', 'internasional']);
                $table->string('peringkat');
                $table->string('penyelenggara');
                $table->year('tahun');
                $table->string('dokumen_prestasi')->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('orang_tuas')) {
            Schema::create('orang_tuas', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pendaftaran_id')->unique()->constrained('pendaftarans')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('nama_ayah');
                $table->string('nik_ayah', 20)->nullable();
                $table->string('pekerjaan_ayah')->nullable();
                $table->decimal('penghasilan_ayah', 15, 2)->nullable();
                $table->string('nama_ibu');
                $table->string('nik_ibu', 20)->nullable();
                $table->string('pekerjaan_ibu')->nullable();
                $table->decimal('penghasilan_ibu', 15, 2)->nullable();
                $table->boolean('memiliki_wali')->default(false);
                $table->string('nama_wali')->nullable();
                $table->string('nik_wali', 20)->nullable();
                $table->string('pekerjaan_wali')->nullable();
                $table->decimal('penghasilan_wali', 15, 2)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('jenis_dokumens')) {
            Schema::create('jenis_dokumens', function (Blueprint $table): void {
                $table->id();
                $table->string('kode', 50)->unique();
                $table->string('nama');
                $table->text('deskripsi')->nullable();
                $table->string('format_file')->default('pdf');
                $table->integer('maksimal_ukuran')->default(2048);
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('kategori_beasiswa_dokumens')) {
            Schema::create('kategori_beasiswa_dokumens', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('kategori_beasiswa_id')->constrained('kategori_beasiswas')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('jenis_dokumen_id')->constrained('jenis_dokumens')->cascadeOnUpdate()->cascadeOnDelete();
                $table->unsignedSmallInteger('urutan')->default(1);
                $table->timestamps();
                $table->unique(['kategori_beasiswa_id', 'jenis_dokumen_id'], 'kategori_dokumen_unique');
            });
        }

        if (! Schema::hasTable('dokumens')) {
            Schema::create('dokumens', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('pendaftaran_id')->constrained('pendaftarans')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('jenis_dokumen_id')->constrained('jenis_dokumens')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('file_path');
                $table->enum('status', ['uploaded', 'verified', 'rejected'])->default('uploaded');
                $table->text('catatan')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->unique(['pendaftaran_id', 'jenis_dokumen_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumens');
        Schema::dropIfExists('kategori_beasiswa_dokumens');
        Schema::dropIfExists('jenis_dokumens');
        Schema::dropIfExists('orang_tuas');
        Schema::dropIfExists('prestasis');
        Schema::dropIfExists('pendidikans');
        Schema::dropIfExists('data_pribadis');
        Schema::dropIfExists('pendaftarans');
        Schema::dropIfExists('kategori_beasiswas');
        Schema::dropIfExists('periodes');
    }
};
