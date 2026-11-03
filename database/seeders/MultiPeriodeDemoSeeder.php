<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\Blacklist;
use App\Models\DataPribadi;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Dokumen;
use App\Models\FormulirPendaftaran;
use App\Models\JenisDokumen;
use App\Models\Kabupaten;
use App\Models\KategoriBeasiswa;
use App\Models\KategoriBeasiswaDokumen;
use App\Models\Kecamatan;
use App\Models\LaporanPertanggungjawaban;
use App\Models\MahasiswaProfile;
use App\Models\OrangTua;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\Selection;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class MultiPeriodeDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inisialisasi Wilayah & Master Dokumen
        $village = $this->ensureRegions();
        $this->ensureDocumentDefinitions();

        // 2. Setup 2 Periode Resmi: 2026/2027 Ganjil (Ditutup) & 2027/2028 Ganjil (Aktif)
        $periode2026 = Periode::query()->updateOrCreate(
            ['nama' => '2026/2027 Ganjil'],
            [
                'tahun' => 2026,
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-12-31',
                'status' => 'ditutup',
            ],
        );

        $periode2027 = Periode::query()->updateOrCreate(
            ['nama' => '2027/2028 Ganjil'],
            [
                'tahun' => 2027,
                'tanggal_mulai' => '2027-07-01',
                'tanggal_selesai' => '2027-12-31',
                'status' => 'aktif',
            ],
        );

        // Auto-migrasi data lama: jika ada data bertuliskan '2027/2028 Genap'
        $genapPeriod = Periode::query()->where('nama', '2027/2028 Genap')->first();
        if ($genapPeriod) {
            Pendaftaran::query()->where('periode_id', $genapPeriod->id)->update([
                'periode_id' => $periode2026->id,
            ]);
            $genapPeriod->delete();
        }

        // SEMUA data teman KHM-2026-% dari DemoDataSeeder secara alami adalah data arsip tahun 2026!
        Application::query()->where('nomor_pengajuan', 'LIKE', 'KHM-2026-%')->update([
            'periode' => '2026/2027 Ganjil',
        ]);
        Application::query()->where('periode', '2027/2028 Genap')->update([
            'periode' => '2026/2027 Ganjil',
        ]);

        // Pastikan hanya periode 2027/2028 Ganjil yang berstatus aktif di seluruh sistem
        Periode::query()->where('id', '!=', $periode2027->id)->where('status', 'aktif')->update([
            'status' => 'ditutup',
        ]);

        // Pastikan konfigurasi periode aktif sinkron di memori saat seeder berjalan
        config(['kartu_hebat.current_period' => '2027/2028 Ganjil']);

        // 3. Setup Kategori Beasiswa untuk kedua periode
        $categories2026 = $this->ensureCategoriesForPeriod($periode2026);
        $categories2027 = $this->ensureCategoriesForPeriod($periode2027);

        // 4. Inisialisasi / Amankan Akun Operator & Superadmin
        $operators = $this->ensureOperators($village);

        // 5. Data Periode 2026/2027 Ganjil (Mode Arsip / Lampau)
        // Mahasiswa 1: Rizky Pratama (Penerima Lulus + LPJ Disetujui + Berkas Lengkap)
        $this->seedRizkyPratama2026($periode2026, $categories2026[ApplicationType::AKADEMIK->value], $village, $operators);

        // Mahasiswa 2: Fajar Ramadhan (Penerima 2026 yang LPJ-nya DIBLOKIR / Kena Blacklist)
        $this->seedFajarRamadhan2026Block($periode2026, $categories2026[ApplicationType::AKADEMIK->value], $village, $operators);

        // Mahasiswa 3: Ahmad Fauzi (Arsip Pendaftar Ditolak di 2026)
        $this->seedAhmadFauzi2026($periode2026, $categories2026[ApplicationType::TIDAK_MAMPU->value], $village, $operators);

        // Mahasiswa Demo 2026: Sinkronisasi seluruh penerima DITERIMA dari DemoDataSeeder ke tabel pendaftaran & monitoring LPJ
        $this->syncDemoRecipientsToPendaftaran($periode2026, $categories2026, $village, $operators);

        // 6. Data Periode 2027/2028 Ganjil (Periode Berjalan / Aktif)
        // Mahasiswa 4: Dimas Pratama (Jalur Akademik - Tahap Seleksi Kabupaten: Menunggu Penetapan)
        $this->seedDimasPratama2027($periode2027, $categories2027[ApplicationType::AKADEMIK->value], $village, $operators);

        // Mahasiswa 5: Putri Handayani (Jalur Akademik - Tahap Seleksi Kabupaten: Internal DITERIMA Hijau)
        $this->seedPutriHandayani2027($periode2027, $categories2027[ApplicationType::AKADEMIK->value], $village, $operators);

        // Mahasiswa 6: Hendra Wijaya (Jalur Akademik - Tahap Seleksi Kabupaten: Internal DITOLAK Merah)
        $this->seedHendraWijaya2027($periode2027, $categories2027[ApplicationType::AKADEMIK->value], $village, $operators);

        // Mahasiswa 7: Siti Aisyah (Pendaftar Baru 2027 - Tahap Verifikasi Dinas)
        $this->seedSitiAisyah2027($periode2027, $categories2027[ApplicationType::AKADEMIK->value], $village, $operators);

        $this->command?->info('✓ MultiPeriodeDemoSeeder berhasil dijalankan!');
        $this->command?->info('  - Periode Lampau : 2026/2027 Ganjil (ditutup / arsip)');
        $this->command?->info('  - Periode Aktif  : 2027/2028 Ganjil (aktif)');
    }

    private function ensureRegions(): Village
    {
        $kabupaten = Kabupaten::query()->first()
            ?? Kabupaten::query()->firstOrCreate(
                ['code' => '6212'],
                ['name' => 'KABUPATEN MURUNG RAYA'],
            );

        $kecamatan = Kecamatan::query()->where('kabupaten_id', $kabupaten->id)->first()
            ?? Kecamatan::query()->firstOrCreate(
                ['code' => '621201'],
                ['name' => 'MURUNG', 'kabupaten_id' => $kabupaten->id],
            );

        return Village::query()->where('kecamatan_id', $kecamatan->id)->first()
            ?? Village::query()->firstOrCreate(
                ['code' => '6212011001'],
                [
                    'name' => 'BERIWIT',
                    'kabupaten_id' => $kabupaten->id,
                    'kecamatan_id' => $kecamatan->id,
                ],
            );
    }

    private function ensureDocumentDefinitions(): void
    {
        $docs = [
            'KTP' => ['Kartu Tanda Penduduk', 'pdf,jpg,jpeg,png', null, true],
            'KK' => ['Kartu Keluarga', 'pdf,jpg,jpeg,png', null, true],
            'KTM' => ['Kartu Tanda Mahasiswa', 'pdf,jpg,jpeg,png', null, true],
            'SURAT-AKTIF' => ['Surat Keterangan Aktif Kuliah', 'pdf', null, true],
            'TRANSKRIP' => ['Transkrip Nilai / KHS', 'pdf,jpg,jpeg,png', null, true],
            'KHS' => ['Kartu Hasil Studi Terakhir', 'pdf,jpg,jpeg,png', null, true],
            'UKT' => ['Bukti Pembayaran UKT / SPP', 'pdf,jpg,jpeg,png', null, false],
            'SKTM' => ['Surat Keterangan Tidak Mampu', 'pdf,jpg,jpeg,png', ApplicationType::TIDAK_MAMPU->value, false],
            'SURAT-DISABILITAS' => ['Surat Keterangan Disabilitas', 'pdf', ApplicationType::DISABILITAS->value, false],
            'SERTIFIKAT-PRESTASI' => ['Sertifikat Prestasi / Ormawa', 'pdf,jpg,jpeg,png', ApplicationType::NON_AKADEMIK->value, false],
        ];

        foreach ($docs as $code => [$name, $format, $appType, $required]) {
            JenisDokumen::query()->updateOrCreate(
                ['kode' => $code],
                [
                    'nama' => $name,
                    'deskripsi' => "Dokumen persyaratan {$name}",
                    'format_file' => $format,
                    'maksimal_ukuran' => 2048,
                    'aktif' => true,
                ],
            );

            DocumentType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'application_type' => $appType,
                    'description' => "Dokumen persyaratan {$name}",
                    'allowed_mimes' => explode(',', $format),
                    'max_size_kb' => 2048,
                    'is_required' => $required,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<string, KategoriBeasiswa>
     */
    private function ensureCategoriesForPeriod(Periode $periode): array
    {
        $categoriesData = [
            ApplicationType::AKADEMIK->value => [
                'kode' => 'BEASISWA-AKADEMIK-'.$periode->id,
                'nama' => 'Beasiswa Prestasi Akademik',
                'deskripsi' => 'Beasiswa berdasarkan prestasi akademik IPK mahasiswa.',
                'urutan' => 1,
                'docs' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'TRANSKRIP', 'KHS', 'UKT'],
            ],
            ApplicationType::TIDAK_MAMPU->value => [
                'kode' => 'BEASISWA-TIDAK-MAMPU-'.$periode->id,
                'nama' => 'Beasiswa Tidak Mampu',
                'deskripsi' => 'Beasiswa bagi mahasiswa dari keluarga ekonomi kurang mampu.',
                'urutan' => 2,
                'docs' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'TRANSKRIP', 'KHS', 'UKT', 'SKTM'],
            ],
            ApplicationType::DISABILITAS->value => [
                'kode' => 'BEASISWA-DISABILITAS-'.$periode->id,
                'nama' => 'Beasiswa Mahasiswa Disabilitas',
                'deskripsi' => 'Beasiswa khusus bagi mahasiswa penyandang disabilitas.',
                'urutan' => 3,
                'docs' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'TRANSKRIP', 'KHS', 'UKT', 'SURAT-DISABILITAS'],
            ],
            ApplicationType::NON_AKADEMIK->value => [
                'kode' => 'BEASISWA-NON-AKADEMIK-'.$periode->id,
                'nama' => 'Beasiswa Prestasi Non Akademik',
                'deskripsi' => 'Beasiswa prestasi kejuaraan, seni budaya, atau pengurus organisasi.',
                'urutan' => 4,
                'docs' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'TRANSKRIP', 'KHS', 'UKT', 'SERTIFIKAT-PRESTASI'],
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $typeKey => $data) {
            $cat = KategoriBeasiswa::query()->updateOrCreate(
                [
                    'periode_id' => $periode->id,
                    'application_type' => $typeKey,
                ],
                [
                    'kode' => $data['kode'],
                    'nama' => $data['nama'],
                    'deskripsi' => $data['deskripsi'],
                    'urutan' => $data['urutan'],
                    'aktif' => true,
                ],
            );

            foreach ($data['docs'] as $idx => $docCode) {
                $jd = JenisDokumen::query()->where('kode', $docCode)->first();
                if ($jd) {
                    KategoriBeasiswaDokumen::query()->updateOrCreate(
                        [
                            'kategori_beasiswa_id' => $cat->id,
                            'jenis_dokumen_id' => $jd->id,
                        ],
                        [
                            'urutan' => $idx + 1,
                        ],
                    );
                }
            }

            $categories[$typeKey] = $cat;
        }

        return $categories;
    }

    /**
     * @return array<string, User>
     */
    private function ensureOperators(Village $village): array
    {
        $operators = [
            [UserRole::SUPERADMIN, 'Superadmin Kartu Hebat', 'superadmin@kartuhebat.test', null, null],
            [UserRole::OPERATOR_DESA, 'Operator Desa', 'desa@kartuhebat.test', $village->id, $village->kecamatan_id],
            [UserRole::OPERATOR_KECAMATAN, 'Operator Kecamatan', 'kecamatan@kartuhebat.test', null, $village->kecamatan_id],
            [UserRole::OPERATOR_DUKCAPIL, 'Operator Dukcapil', 'dukcapil@kartuhebat.test', null, null],
            [UserRole::OPERATOR_SOSIAL, 'Operator Dinas Sosial', 'sosial@kartuhebat.test', null, null],
            [UserRole::OPERATOR_PENDIDIKAN, 'Operator Dinas Pendidikan', 'pendidikan@kartuhebat.test', null, null],
            [UserRole::OPERATOR_BKPSDM, 'Operator BKPSDM', 'bkpsdm@kartuhebat.test', null, null],
            [UserRole::OPERATOR_DINKES, 'Operator Dinas Kesehatan', 'dinkes@kartuhebat.test', null, null],
            [UserRole::OPERATOR_PARSEPOR, 'Operator Dinas Parsepor', 'parsepor@kartuhebat.test', null, null],
            [UserRole::OPERATOR_KABUPATEN, 'Operator Kabupaten', 'kabupaten@kartuhebat.test', null, null],
        ];

        $users = [];
        foreach ($operators as [$role, $name, $email, $vId, $kId]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'two_factor_confirmed_at' => now(),
                    'role' => $role,
                    'status' => 'active',
                    'village_id' => $vId,
                    'kecamatan_id' => $kId,
                    'kabupaten_id' => $village->kecamatan?->kabupaten_id,
                ],
            );
            $users[$role->value] = $user;
        }

        return $users;
    }

    /**
     * @param array<string, User> $operators
     */
    private function seedRizkyPratama2026(Periode $periode, KategoriBeasiswa $kategori, Village $village, array $operators): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'penerima.beasiswa@kartuhebat.test'],
            [
                'name' => 'Rizky Pratama',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kecamatan?->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => '6212052345670005',
                'nim' => '2100011223',
                'phone' => '081250001005',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Teknik Sipil',
                'semester' => 6,
                'ipk' => 3.85,
                'alamat' => 'Jl. Jenderal Sudirman No. 10, Puruk Cahu',
                'village_id' => $village->id,
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 3800000,
                'jumlah_tanggungan' => 3,
            ],
        );

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'periode_id' => $periode->id,
            ],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => 'KH-2026-LULUS-001',
                'status' => 'approved',
                'submitted_at' => '2026-08-10 09:30:00',
                'review_dikonfirmasi_at' => '2026-08-10 09:30:00',
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'village_id' => $village->id,
                'nik' => '6212052345670005',
                'nomor_kk' => '6212052345679005',
                'nomor_rekening' => '5678901234',
                'nama_lengkap' => 'Rizky Pratama',
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2003-05-12',
                'jenis_kelamin' => 'L',
                'agama' => 'Islam',
                'alamat' => 'Jl. Jenderal Sudirman No. 10, Puruk Cahu',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '081250001005',
            ],
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nim' => '2100011223',
                'universitas' => 'Universitas Palangka Raya',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Fakultas Teknik',
                'jurusan' => 'Teknik Sipil',
                'program_studi' => 'Teknik Sipil',
                'jenjang' => 'S1',
                'semester' => 6,
                'ipk' => 3.85,
                'nominal_ukt' => 3500000,
                'tahun_masuk' => 2021,
                'akreditasi_perguruan_tinggi' => 'Baik Sekali',
                'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            ],
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama_ayah' => 'Hendra Pratama',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212050101700009',
                'pekerjaan_ayah' => 'Wirausaha',
                'nama_ibu' => 'Siti Rahmah',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212054101720010',
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'memiliki_wali' => false,
            ],
        );

        $application = $pendaftaran->application()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $user->id,
                'nomor_pengajuan' => 'KH-2026-LULUS-001',
                'periode' => $periode->nama,
                'application_type' => ApplicationType::AKADEMIK,
                'status' => ApplicationStatus::DITERIMA,
                'submitted_at' => '2026-08-10 09:30:00',
                'locked_at' => '2026-08-20 15:00:00',
            ],
        );

        // Dokumen Persyaratan
        $docCodes = ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'TRANSKRIP', 'KHS', 'UKT'];
        foreach ($docCodes as $docCode) {
            $path = "demo/{$docCode}-rizky-2026.pdf";
            $this->putValidPdf($path, "Dokumen Persyaratan {$docCode} Rizky Pratama (2026)");

            $type = DocumentType::query()->where('code', $docCode)->first();
            if ($type) {
                Document::query()->updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'document_type_id' => $type->id,
                    ],
                    [
                        'uploaded_by' => $user->id,
                        'path' => $path,
                        'original_name' => strtolower($docCode).'-rizky.pdf',
                        'mime_type' => 'application/pdf',
                        'size' => 10240,
                        'checksum' => md5($path),
                        'version' => 1,
                    ],
                );
            }

            $jenis = JenisDokumen::query()->where('kode', $docCode)->first();
            if ($jenis) {
                Dokumen::query()->updateOrCreate(
                    [
                        'pendaftaran_id' => $pendaftaran->id,
                        'jenis_dokumen_id' => $jenis->id,
                    ],
                    [
                        'file_path' => $path,
                        'nama_file_asli' => strtolower($docCode).'-rizky.pdf',
                        'mime_type' => 'application/pdf',
                        'ukuran_file' => 10240,
                        'status' => 'uploaded',
                    ],
                );
            }
        }

        // Formulir Pendaftaran (Surat Permohonan & Pakta Integritas)
        $suratPath = "pendaftarans/{$pendaftaran->id}/formulir/surat_permohonan.pdf";
        $paktaPath = "pendaftarans/{$pendaftaran->id}/formulir/pakta_integritas.pdf";
        $this->putValidPdf($suratPath, 'Surat Permohonan Beasiswa Rizky Pratama 2026');
        $this->putValidPdf($paktaPath, 'Pakta Integritas Penerima Beasiswa Rizky Pratama 2026');

        FormulirPendaftaran::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'surat_permohonan' => $suratPath,
                'pakta_integritas' => $paktaPath,
                'jenis_form' => 'A',
            ],
        );

        // LPJ Disetujui
        $lpjPath = "lpj/{$pendaftaran->id}/laporan_beasiswa_2026.pdf";
        $this->putValidPdf($lpjPath, 'Laporan Pertanggungjawaban Beasiswa Rizky Pratama Semester Ganjil 2026');

        $operatorKabupaten = $operators[UserRole::OPERATOR_KABUPATEN->value] ?? null;

        LaporanPertanggungjawaban::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'file_path' => $lpjPath,
                'original_name' => 'Laporan_Pertanggungjawaban_Beasiswa_2026.pdf',
                'mime_type' => 'application/pdf',
                'size' => 15360,
                'status' => 'disetujui',
                'catatan' => 'Laporan pertanggungjawaban penggunaan dana beasiswa semester ganjil 2026 telah diverifikasi lengkap dan disetujui.',
                'uploaded_at' => '2026-11-15 10:00:00',
                'verified_at' => '2026-11-20 14:00:00',
                'verified_by' => $operatorKabupaten?->id,
            ],
        );

        // Hasil Seleksi Kabupaten
        Selection::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'final_score' => 93.50,
                'rank' => 1,
                'manual_decision' => 'diterima',
                'notes' => 'Memenuhi semua persyaratan akademik dan berkas administrasi lengkap.',
                'decided_by' => $operatorKabupaten?->id,
                'decided_at' => '2026-08-20 14:00:00',
                'published_at' => '2026-08-20 15:00:00',
            ],
        );
    }

    

    /**
     * @param array<string, User> $operators
     */
    private function seedAhmadFauzi2026(Periode $periode, KategoriBeasiswa $kategori, Village $village, array $operators): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'ahmad.arsip@kartuhebat.test'],
            [
                'name' => 'Ahmad Fauzi',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kecamatan?->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => '6212052345670006',
                'nim' => '2100011224',
                'phone' => '081250001006',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Ilmu Administrasi',
                'semester' => 6,
                'ipk' => 2.90,
                'alamat' => 'Jl. Bhayangkara No. 5, Puruk Cahu',
                'village_id' => $village->id,
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 4500000,
                'jumlah_tanggungan' => 2,
            ],
        );

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'periode_id' => $periode->id,
            ],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => 'KH-2026-TOLAK-002',
                'status' => 'rejected',
                'submitted_at' => '2026-08-11 11:00:00',
                'review_dikonfirmasi_at' => '2026-08-11 11:00:00',
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'village_id' => $village->id,
                'nik' => '6212052345670006',
                'nomor_kk' => '6212052345679006',
                'nomor_rekening' => '5678901235',
                'nama_lengkap' => 'Ahmad Fauzi',
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2003-02-18',
                'jenis_kelamin' => 'L',
                'agama' => 'Islam',
                'alamat' => 'Jl. Bhayangkara No. 5, Puruk Cahu',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '081250001006',
            ],
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nim' => '2100011224',
                'universitas' => 'Universitas Palangka Raya',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Fakultas Ilmu Sosial dan Politik',
                'jurusan' => 'Administrasi Publik',
                'program_studi' => 'Ilmu Administrasi',
                'jenjang' => 'S1',
                'semester' => 6,
                'ipk' => 2.90,
                'nominal_ukt' => 2500000,
                'tahun_masuk' => 2021,
                'akreditasi_perguruan_tinggi' => 'Baik',
                'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            ],
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama_ayah' => 'Fauzan',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212050101680001',
                'pekerjaan_ayah' => 'Petani',
                'nama_ibu' => 'Halimah',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212054101700002',
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'memiliki_wali' => false,
            ],
        );

        $application = $pendaftaran->application()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $user->id,
                'nomor_pengajuan' => 'KH-2026-TOLAK-002',
                'periode' => $periode->nama,
                'application_type' => ApplicationType::TIDAK_MAMPU,
                'status' => ApplicationStatus::DITOLAK,
                'submitted_at' => '2026-08-11 11:00:00',
                'locked_at' => '2026-08-20 15:00:00',
            ],
        );

        $operatorKabupaten = $operators[UserRole::OPERATOR_KABUPATEN->value] ?? null;

        Selection::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'final_score' => 64.20,
                'rank' => 45,
                'manual_decision' => 'ditolak',
                'notes' => 'Kuota penerima telah terpenuhi pada batas ranking kelulusan.',
                'decided_by' => $operatorKabupaten?->id,
                'decided_at' => '2026-08-20 14:00:00',
                'published_at' => '2026-08-20 15:00:00',
            ],
        );
    }

    

    /**
     * @param array<string, User> $operators
     */
    private function seedDimasPratama2027(Periode $periode, KategoriBeasiswa $kategori, Village $village, array $operators): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'dimas.seleksi@kartuhebat.test'],
            [
                'name' => 'Dimas Pratama',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kecamatan?->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => '6212052345670007',
                'nim' => '2200011335',
                'phone' => '081250001007',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Pendidikan Matematika',
                'semester' => 4,
                'ipk' => 3.70,
                'alamat' => 'Jl. Veteran No. 12, Puruk Cahu',
                'village_id' => $village->id,
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 2000000,
                'jumlah_tanggungan' => 4,
                'desil_sosial' => 2,
            ],
        );

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'periode_id' => $periode->id,
            ],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => 'KH-2027-SELEKSI-001',
                'status' => 'verification',
                'submitted_at' => '2027-07-15 10:00:00',
                'review_dikonfirmasi_at' => '2027-07-15 10:00:00',
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'village_id' => $village->id,
                'nik' => '6212052345670007',
                'nomor_kk' => '6212052345679007',
                'nomor_rekening' => '5678901236',
                'nama_lengkap' => 'Dimas Pratama',
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2004-03-10',
                'jenis_kelamin' => 'L',
                'agama' => 'Islam',
                'alamat' => 'Jl. Veteran No. 12, Puruk Cahu',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '081250001007',
            ],
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nim' => '2200011335',
                'universitas' => 'Universitas Palangka Raya',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Keguruan dan Ilmu Pendidikan',
                'jurusan' => 'Pendidikan MIPA',
                'program_studi' => 'Pendidikan Matematika',
                'jenjang' => 'S1',
                'semester' => 4,
                'ipk' => 3.70,
                'nominal_ukt' => 2000000,
                'tahun_masuk' => 2022,
                'akreditasi_perguruan_tinggi' => 'Baik Sekali',
                'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            ],
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama_ayah' => 'Suyanto',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212050101650003',
                'pekerjaan_ayah' => 'Buruh Harian Lepas',
                'nama_ibu' => 'Suprihatin',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212054101670004',
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'memiliki_wali' => false,
            ],
        );

        $application = $pendaftaran->application()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $user->id,
                'nomor_pengajuan' => 'KH-2027-SELEKSI-001',
                'periode' => $periode->nama,
                'application_type' => ApplicationType::AKADEMIK,
                'status' => ApplicationStatus::SELEKSI_KABUPATEN,
                'submitted_at' => '2027-07-15 10:00:00',
                'locked_at' => null,
            ],
        );

        // Upload Dokumen Demo 2027
        foreach (['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'TRANSKRIP', 'KHS', 'UKT', 'SKTM'] as $docCode) {
            $path = "demo/{$docCode}-dimas-2027.pdf";
            $this->putValidPdf($path, "Dokumen {$docCode} Dimas Pratama 2027");

            $type = DocumentType::query()->where('code', $docCode)->first();
            if ($type) {
                Document::query()->updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'document_type_id' => $type->id,
                    ],
                    [
                        'uploaded_by' => $user->id,
                        'path' => $path,
                        'original_name' => strtolower($docCode).'-dimas.pdf',
                        'mime_type' => 'application/pdf',
                        'size' => 10240,
                        'checksum' => md5($path),
                        'version' => 1,
                    ],
                );
            }
        }

        // Verifikasi Instansi (Semua MS)
        $agencyRoles = [
            UserRole::OPERATOR_DUKCAPIL,
            UserRole::OPERATOR_SOSIAL,
            UserRole::OPERATOR_PENDIDIKAN,
        ];

        foreach ($agencyRoles as $role) {
            $operator = $operators[$role->value] ?? null;
            if ($operator) {
                AgencyVerification::query()->updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'agency' => $role->agencyCode(),
                    ],
                    [
                        'verifier_id' => $operator->id,
                        'decision' => VerificationDecision::MS,
                        'score' => 90.00,
                        'notes' => 'Verifikasi berkas persyaratan instansi telah memenuhi syarat.',
                        'verified_at' => '2027-07-18 10:00:00',
                    ],
                );
            }
        }

        // Skor Seleksi Siap Diputuskan di Halaman Operator Kabupaten
        Selection::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'final_score' => 88.75,
                'rank' => 1,
                'manual_decision' => null, // Belum diputuskan, siap untuk diuji klik di halaman seleksi!
                'notes' => null,
            ],
        );
    }

    

    /**
     * @param array<string, User> $operators
     */
    private function seedSitiAisyah2027(Periode $periode, KategoriBeasiswa $kategori, Village $village, array $operators): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'siti.aisyah@kartuhebat.test'],
            [
                'name' => 'Siti Aisyah',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kecamatan?->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => '6212052345670008',
                'nim' => '2300011440',
                'phone' => '081250001008',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Farmasi',
                'semester' => 2,
                'ipk' => 3.92,
                'alamat' => 'Jl. Merdeka No. 8, Puruk Cahu',
                'village_id' => $village->id,
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 3000000,
                'jumlah_tanggungan' => 3,
            ],
        );

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'periode_id' => $periode->id,
            ],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => 'KH-2027-DINAS-002',
                'status' => 'verification',
                'submitted_at' => '2027-07-20 14:00:00',
                'review_dikonfirmasi_at' => '2027-07-20 14:00:00',
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'village_id' => $village->id,
                'nik' => '6212052345670008',
                'nomor_kk' => '6212052345679008',
                'nomor_rekening' => '5678901237',
                'nama_lengkap' => 'Siti Aisyah',
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2005-08-22',
                'jenis_kelamin' => 'P',
                'agama' => 'Islam',
                'alamat' => 'Jl. Merdeka No. 8, Puruk Cahu',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '081250001008',
            ],
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nim' => '2300011440',
                'universitas' => 'Universitas Palangka Raya',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Fakultas Kedokteran',
                'jurusan' => 'Farmasi',
                'program_studi' => 'Farmasi',
                'jenjang' => 'S1',
                'semester' => 2,
                'ipk' => 3.92,
                'nominal_ukt' => 4500000,
                'tahun_masuk' => 2023,
                'akreditasi_perguruan_tinggi' => 'Unggul',
                'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            ],
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama_ayah' => 'Rahmat Hidayat',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212050101630005',
                'pekerjaan_ayah' => 'Pedagang',
                'nama_ibu' => 'Aminah',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212054101650006',
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'memiliki_wali' => false,
            ],
        );

        $application = $pendaftaran->application()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $user->id,
                'nomor_pengajuan' => 'KH-2027-DINAS-002',
                'periode' => $periode->nama,
                'application_type' => ApplicationType::AKADEMIK,
                'status' => ApplicationStatus::VERIFIKASI_DINAS,
                'submitted_at' => '2027-07-20 14:00:00',
                'locked_at' => null,
            ],
        );

        // Upload Dokumen Demo 2027
        foreach (['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'TRANSKRIP', 'KHS', 'UKT'] as $docCode) {
            $path = "demo/{$docCode}-siti-2027.pdf";
            $this->putValidPdf($path, "Dokumen {$docCode} Siti Aisyah 2027");

            $type = DocumentType::query()->where('code', $docCode)->first();
            if ($type) {
                Document::query()->updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'document_type_id' => $type->id,
                    ],
                    [
                        'uploaded_by' => $user->id,
                        'path' => $path,
                        'original_name' => strtolower($docCode).'-siti.pdf',
                        'mime_type' => 'application/pdf',
                        'size' => 10240,
                        'checksum' => md5($path),
                        'version' => 1,
                    ],
                );
            }
        }

        // Dukcapil sudah verifikasi MS, Dinas lain masih pending
        $dukcapil = $operators[UserRole::OPERATOR_DUKCAPIL->value] ?? null;
        if ($dukcapil) {
            AgencyVerification::query()->updateOrCreate(
                [
                    'application_id' => $application->id,
                    'agency' => 'dukcapil',
                ],
                [
                    'verifier_id' => $dukcapil->id,
                    'decision' => VerificationDecision::MS,
                    'score' => 95.00,
                    'notes' => 'Data KTP dan Kartu Keluarga terverifikasi valid di database Dukcapil.',
                    'verified_at' => '2027-07-21 09:00:00',
                ],
            );
        }
    }

    /**
     * @param array<string, User> $operators
     */
    private function seedFajarRamadhan2026Block(Periode $periode, KategoriBeasiswa $kategori, Village $village, array $operators): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'fajar.lpj.blokir@kartuhebat.test'],
            [
                'name' => 'Fajar Ramadhan',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kecamatan?->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => '6212052345670009',
                'nim' => '2100011999',
                'phone' => '081250001009',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Ilmu Hukum',
                'semester' => 6,
                'ipk' => 3.65,
                'alamat' => 'Jl. Jenderal Sudirman No. 44, Puruk Cahu',
                'village_id' => $village->id,
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 3200000,
                'jumlah_tanggungan' => 3,
            ],
        );

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'periode_id' => $periode->id,
            ],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => 'KH-2026-LULUS-003',
                'status' => 'approved',
                'submitted_at' => '2026-08-12 10:00:00',
                'review_dikonfirmasi_at' => '2026-08-12 10:00:00',
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'village_id' => $village->id,
                'nik' => '6212052345670009',
                'nomor_kk' => '6212052345679009',
                'nomor_rekening' => '5678901239',
                'nama_lengkap' => 'Fajar Ramadhan',
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2003-07-15',
                'jenis_kelamin' => 'L',
                'agama' => 'Islam',
                'alamat' => 'Jl. Jenderal Sudirman No. 44, Puruk Cahu',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '081250001009',
            ],
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nim' => '2100011999',
                'universitas' => 'Universitas Palangka Raya',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Fakultas Hukum',
                'jurusan' => 'Ilmu Hukum',
                'program_studi' => 'Ilmu Hukum',
                'jenjang' => 'S1',
                'semester' => 6,
                'ipk' => 3.65,
                'nominal_ukt' => 3000000,
                'tahun_masuk' => 2021,
                'akreditasi_perguruan_tinggi' => 'Baik Sekali',
                'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            ],
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama_ayah' => 'Mansyur',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212050101670009',
                'pekerjaan_ayah' => 'Wiraswasta',
                'nama_ibu' => 'Faridah',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212054101690010',
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'memiliki_wali' => false,
            ],
        );

        $application = $pendaftaran->application()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $user->id,
                'nomor_pengajuan' => 'KH-2026-LULUS-003',
                'periode' => $periode->nama,
                'application_type' => ApplicationType::AKADEMIK,
                'status' => ApplicationStatus::DITERIMA,
                'submitted_at' => '2026-08-12 10:00:00',
                'locked_at' => '2026-08-20 15:00:00',
            ],
        );

        $lpjPath = "lpj/{$pendaftaran->id}/laporan_beasiswa_2026_fajar.pdf";
        $this->putValidPdf($lpjPath, 'Laporan Pertanggungjawaban Fajar Ramadhan 2026 (Perlu Perbaikan)');

        $operatorKabupaten = $operators[UserRole::OPERATOR_KABUPATEN->value] ?? null;

        LaporanPertanggungjawaban::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'file_path' => $lpjPath,
                'original_name' => 'Laporan_LPJ_Fajar_2026.pdf',
                'mime_type' => 'application/pdf',
                'size' => 12288,
                'status' => 'ditolak',
                'catatan' => 'Kuitansi pembayaran UKT dan bukti transfer tidak valid. Harap perbaiki berkas LPJ Anda.',
                'uploaded_at' => '2026-11-10 09:00:00',
                'verified_at' => '2026-11-12 14:00:00',
                'verified_by' => $operatorKabupaten?->id,
            ],
        );

        // Kena Blacklist aktif!
        Blacklist::query()->updateOrCreate(
            ['nik' => '6212052345670009'],
            [
                'tahun_berlaku' => $periode->tahun,
                'pendaftaran_id' => $pendaftaran->id,
                'alasan' => 'Kuitansi pembayaran UKT dan bukti transfer tidak valid. Harap perbaiki berkas LPJ Anda.',
                'aktif' => true,
                'created_by' => $operatorKabupaten?->id,
            ]
        );
    }

    /**
     * @param array<string, User> $operators
     */
    private function seedPutriHandayani2027(Periode $periode, KategoriBeasiswa $kategori, Village $village, array $operators): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'putri.diterima@kartuhebat.test'],
            [
                'name' => 'Putri Handayani',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kecamatan?->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => '6212052345670010',
                'nim' => '2200011401',
                'phone' => '081250001010',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Teknik Sipil',
                'semester' => 4,
                'ipk' => 3.90,
                'alamat' => 'Jl. Jenderal Ahmad Yani No. 15, Puruk Cahu',
                'village_id' => $village->id,
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 2800000,
                'jumlah_tanggungan' => 3,
            ],
        );

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'periode_id' => $periode->id,
            ],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => 'KH-2027-SELEKSI-002',
                'status' => 'verification',
                'submitted_at' => '2027-07-16 09:00:00',
                'review_dikonfirmasi_at' => '2027-07-16 09:00:00',
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'village_id' => $village->id,
                'nik' => '6212052345670010',
                'nomor_kk' => '6212052345679010',
                'nomor_rekening' => '5678901240',
                'nama_lengkap' => 'Putri Handayani',
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2004-06-20',
                'jenis_kelamin' => 'P',
                'agama' => 'Islam',
                'alamat' => 'Jl. Jenderal Ahmad Yani No. 15, Puruk Cahu',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '081250001010',
            ],
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nim' => '2200011401',
                'universitas' => 'Universitas Palangka Raya',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Fakultas Teknik',
                'jurusan' => 'Teknik Sipil',
                'program_studi' => 'Teknik Sipil',
                'jenjang' => 'S1',
                'semester' => 4,
                'ipk' => 3.90,
                'nominal_ukt' => 3200000,
                'tahun_masuk' => 2022,
                'akreditasi_perguruan_tinggi' => 'Baik Sekali',
                'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            ],
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama_ayah' => 'Darmawan',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212050101660010',
                'pekerjaan_ayah' => 'PNS',
                'nama_ibu' => 'Hartini',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212054101680011',
                'pekerjaan_ibu' => 'Guru',
                'memiliki_wali' => false,
            ],
        );

        $application = $pendaftaran->application()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $user->id,
                'nomor_pengajuan' => 'KH-2027-SELEKSI-002',
                'periode' => $periode->nama,
                'application_type' => ApplicationType::AKADEMIK,
                'status' => ApplicationStatus::SELEKSI_KABUPATEN,
                'submitted_at' => '2027-07-16 09:00:00',
                'locked_at' => null,
            ],
        );

        $operatorKabupaten = $operators[UserRole::OPERATOR_KABUPATEN->value] ?? null;

        // Seleksi: Sudah Ditetapkan DITERIMA (Internal Hijau) tetapi belum dipublikasikan SK
        Selection::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'final_score' => 91.20,
                'rank' => 1,
                'manual_decision' => 'DITERIMA',
                'notes' => 'IPK tertinggi dengan berkas verifikasi dinas sangat lengkap.',
                'decided_by' => $operatorKabupaten?->id,
                'decided_at' => '2027-07-22 10:00:00',
                'published_at' => null,
            ],
        );
    }

    

    /**
     * @param array<string, User> $operators
     */
    private function seedHendraWijaya2027(Periode $periode, KategoriBeasiswa $kategori, Village $village, array $operators): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'hendra.ditolak@kartuhebat.test'],
            [
                'name' => 'Hendra Wijaya',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kecamatan?->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => '6212052345670011',
                'nim' => '2200011402',
                'phone' => '081250001011',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Ilmu Ekonomi',
                'semester' => 4,
                'ipk' => 2.95,
                'alamat' => 'Jl. Sudirman No. 89, Puruk Cahu',
                'village_id' => $village->id,
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 4500000,
                'jumlah_tanggungan' => 2,
            ],
        );

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'periode_id' => $periode->id,
            ],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => 'KH-2027-SELEKSI-003',
                'status' => 'verification',
                'submitted_at' => '2027-07-16 11:00:00',
                'review_dikonfirmasi_at' => '2027-07-16 11:00:00',
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'village_id' => $village->id,
                'nik' => '6212052345670011',
                'nomor_kk' => '6212052345679011',
                'nomor_rekening' => '5678901241',
                'nama_lengkap' => 'Hendra Wijaya',
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2004-09-05',
                'jenis_kelamin' => 'L',
                'agama' => 'Islam',
                'alamat' => 'Jl. Sudirman No. 89, Puruk Cahu',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '081250001011',
            ],
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nim' => '2200011402',
                'universitas' => 'Universitas Palangka Raya',
                'status_perguruan_tinggi' => 'negeri',
                'fakultas' => 'Fakultas Ekonomi dan Bisnis',
                'jurusan' => 'Ilmu Ekonomi',
                'program_studi' => 'Ilmu Ekonomi',
                'jenjang' => 'S1',
                'semester' => 4,
                'ipk' => 2.95,
                'nominal_ukt' => 2500000,
                'tahun_masuk' => 2022,
                'akreditasi_perguruan_tinggi' => 'Baik',
                'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
            ],
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama_ayah' => 'Kurniawan',
                'status_ayah' => 'hidup',
                'nik_ayah' => '6212050101650011',
                'pekerjaan_ayah' => 'Pedagang',
                'nama_ibu' => 'Suryati',
                'status_ibu' => 'hidup',
                'nik_ibu' => '6212054101670012',
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'memiliki_wali' => false,
            ],
        );

        $application = $pendaftaran->application()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $user->id,
                'nomor_pengajuan' => 'KH-2027-SELEKSI-003',
                'periode' => $periode->nama,
                'application_type' => ApplicationType::AKADEMIK,
                'status' => ApplicationStatus::SELEKSI_KABUPATEN,
                'submitted_at' => '2027-07-16 11:00:00',
                'locked_at' => null,
            ],
        );

        $operatorKabupaten = $operators[UserRole::OPERATOR_KABUPATEN->value] ?? null;

        // Seleksi: Sudah Ditetapkan DITOLAK (Internal Merah) tetapi belum dipublikasikan SK
        Selection::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'final_score' => 62.10,
                'rank' => 48,
                'manual_decision' => 'DITOLAK',
                'notes' => 'Peringkat berada di luar batas kuota penerima beasiswa.',
                'decided_by' => $operatorKabupaten?->id,
                'decided_at' => '2027-07-22 10:00:00',
                'published_at' => null,
            ],
        );
    }

    

    /**
     * Membuat berkas PDF dummy valid single-page ke storage lokal
     */
    private function putValidPdf(string $path, string $title): void
    {
        $content = "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\n"
            ."xref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000053 00000 n \n0000000102 00000 n \n"
            ."trailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n"
            ."% {$title}\n";

        Storage::disk(config('kartu_hebat.document_disk', 'local'))->put($path, $content);
    }

    /**
     * @param array<string, KategoriBeasiswa> $categories2026
     * @param array<string, User> $operators
     */
    private function syncDemoRecipientsToPendaftaran(Periode $periode2026, array $categories2026, Village $village, array $operators): void
    {
        $acceptedApplications = Application::query()
            ->where('periode', '2026/2027 Ganjil')
            ->where('status', ApplicationStatus::DITERIMA->value)
            ->where(function ($q) {
                $q->whereNull('pendaftaran_id')
                    ->orWhereDoesntHave('pendaftaran.dataPribadi');
            })
            ->with(['mahasiswa.profile.village.kecamatan'])
            ->get();

        $operatorKabupaten = $operators[UserRole::OPERATOR_KABUPATEN->value] ?? null;

        foreach ($acceptedApplications as $index => $app) {
            $user = $app->mahasiswa;
            if (! $user) {
                continue;
            }

            $profile = $user->profile;
            $typeKey = $app->application_type instanceof ApplicationType
                ? $app->application_type->value
                : (string) ($app->application_type ?? ApplicationType::AKADEMIK->value);

            $cat = $categories2026[$typeKey] ?? $categories2026[ApplicationType::AKADEMIK->value];

            $nik = $profile?->nik ?? ('6212'.str_pad((string) $user->id, 12, '0', STR_PAD_LEFT));
            $nim = $profile?->nim ?? ('NIM2026'.$user->id);

            $pendaftaran = Pendaftaran::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'periode_id' => $periode2026->id,
                ],
                [
                    'nomor_pendaftaran' => $app->nomor_pengajuan,
                    'kategori_beasiswa_id' => $cat->id,
                    'jalur_beasiswa_id' => $cat->jalur_beasiswa_id ?? \App\Models\JalurBeasiswa::query()->value('id'),
                    'status' => 'approved',
                    'submitted_at' => $app->submitted_at ?? now()->subMonths(5),
                ]
            );

            $app->update(['pendaftaran_id' => $pendaftaran->id]);

            DataPribadi::query()->updateOrCreate(
                ['pendaftaran_id' => $pendaftaran->id],
                [
                    'village_id' => $profile?->village_id ?? $village->id,
                    'nik' => $nik,
                    'nomor_kk' => '6212052345679001',
                    'nomor_rekening' => '5678901234',
                    'nama_lengkap' => $user->name,
                    'tempat_lahir' => 'Puruk Cahu',
                    'tanggal_lahir' => '2004-03-12',
                    'jenis_kelamin' => 'L',
                    'agama' => 'Islam',
                    'alamat' => $profile?->alamat ?? 'Jl. Jenderal Sudirman No. 12, Murung Raya',
                    'provinsi' => 'Kalimantan Tengah',
                    'kabupaten' => 'Murung Raya',
                    'kecamatan' => $village->kecamatan?->name ?? 'Murung',
                    'desa' => $village->name ?? 'Beriwit',
                    'kode_pos' => '73911',
                    'no_hp' => $profile?->phone ?? '081234567890',
                ]
            );

            Pendidikan::query()->updateOrCreate(
                ['pendaftaran_id' => $pendaftaran->id],
                [
                    'universitas' => $profile?->universitas ?? 'Universitas Palangka Raya',
                    'program_studi' => $profile?->program_studi ?? 'Ilmu Administrasi Negara',
                    'jenjang' => 'S1',
                    'semester' => $profile?->semester ?? 6,
                    'nim' => $nim,
                    'ipk' => $profile?->ipk ?? 3.55,
                ]
            );

            // Variasi status LPJ untuk mahasiswa penerima demo 2026:
            // Index genap -> Belum Mengumpulkan LPJ (Terblokir otomatis karena lewat batas waktu)
            // Index ganjil -> Menunggu Verifikasi (Sudah upload berkas bukti UKT, menunggu dinas cek)
            $isBelumUpload = ($index % 2 === 0);

            if ($isBelumUpload) {
                Blacklist::query()->updateOrCreate(
                    [
                        'nik' => $nik,
                        'tahun_berlaku' => 2026,
                    ],
                    [
                        'pendaftaran_id' => $pendaftaran->id,
                        'alasan' => 'Belum mengunggah berkas Laporan Pertanggungjawaban (LPJ) hingga batas waktu berakhir.',
                        'aktif' => true,
                        'created_by' => $operatorKabupaten?->id,
                    ]
                );
            } else {
                $lpjPath = "lpj/{$pendaftaran->id}/bukti_pembayaran_ukt_2026.pdf";
                $this->putValidPdf($lpjPath, "Bukti Pembayaran UKT {$user->name} Semester Ganjil 2026");

                LaporanPertanggungjawaban::query()->updateOrCreate(
                    ['pendaftaran_id' => $pendaftaran->id],
                    [
                        'file_path' => $lpjPath,
                        'original_name' => 'Bukti_Pembayaran_UKT_2026.pdf',
                        'mime_type' => 'application/pdf',
                        'size' => 14336,
                        'status' => 'menunggu_verifikasi',
                        'uploaded_at' => now()->subDays(4),
                    ]
                );

                Blacklist::query()->updateOrCreate(
                    [
                        'nik' => $nik,
                        'tahun_berlaku' => 2026,
                    ],
                    [
                        'pendaftaran_id' => $pendaftaran->id,
                        'alasan' => 'Berkas LPJ telah diunggah dan sedang dalam antrean verifikasi oleh Dinas.',
                        'aktif' => true,
                        'created_by' => $operatorKabupaten?->id,
                    ]
                );
            }
        }
    }
}