<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\DataPribadi;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Dokumen;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\KategoriBeasiswaDokumen;
use App\Models\MahasiswaProfile;
use App\Models\OrangTua;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\Prestasi;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CompleteStudentSeeder extends Seeder
{
    public function run(): void
    {
        $periode = Periode::query()->where('status', 'aktif')->first();

        if (! $periode) {
            $this->command?->warn('Tidak ada periode aktif. Jalankan BeasiswaMasterSeeder terlebih dahulu.');

            return;
        }

        DocumentType::query()
            ->where('code', 'SKD')
            ->whereNull('application_type')
            ->update(['application_type' => ApplicationType::DISABILITAS->value]);

        $this->syncKategoriDokumen();

        $students = [
            $this->academicStudent(),
            $this->unableStudent(),
            $this->disabilityStudent(),
            $this->nonAcademicStudent(),
        ];

        foreach ($students as $student) {
            $this->createCompleteStudent($student, $periode);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createCompleteStudent(array $payload, Periode $periode): void
    {
        $village = Village::query()->first();

        $user = User::query()->updateOrCreate(
            ['email' => $payload['email']],
            [
                'name' => $payload['name'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village?->id,
                'kecamatan_id' => $village?->kecamatan_id,
                'kabupaten_id' => $village?->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $payload['profile'],
        );

        $kategori = KategoriBeasiswa::query()
            ->where('application_type', $payload['application_type'])
            ->where('periode_id', $periode->id)
            ->first();

        if (! $kategori) {
            $this->command?->warn("Kategori {$payload['application_type']->label()} belum ter-seed.");

            return;
        }

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'periode_id' => $periode->id,
            ],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => $payload['nomor_pendaftaran'],
                'status' => 'draft',
                'submitted_at' => null,
                'review_dikonfirmasi_at' => now(),
                'prestasi_dikonfirmasi_at' => $payload['application_type'] === ApplicationType::NON_AKADEMIK
                    ? now()
                    : null,
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            array_merge(
                ['pendaftaran_id' => $pendaftaran->id, 'village_id' => $village?->id],
                $payload['data_pribadi'],
            ),
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            array_merge(['pendaftaran_id' => $pendaftaran->id], $payload['pendidikan']),
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            array_merge(['pendaftaran_id' => $pendaftaran->id], $payload['orang_tua']),
        );

        Prestasi::query()->where('pendaftaran_id', $pendaftaran->id)->delete();
        foreach ($payload['prestasis'] as $prestasi) {
            Prestasi::query()->create(
                array_merge(['pendaftaran_id' => $pendaftaran->id], $prestasi),
            );
        }

        $application = $pendaftaran->application()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $user->id,
                'nomor_pengajuan' => $payload['nomor_pendaftaran'],
                'periode' => $periode->nama,
                'application_type' => $payload['application_type'],
                'status' => ApplicationStatus::DRAFT,
                'submitted_at' => null,
                'locked_at' => null,
            ],
        );

        Document::query()->where('application_id', $application->id)->delete();
        Dokumen::query()->where('pendaftaran_id', $pendaftaran->id)->delete();
        foreach ($payload['documents'] as $docCode) {
            $type = DocumentType::query()->where('code', $docCode)->first();
            $jenis = JenisDokumen::query()->where('kode', $docCode)->first();

            if (! $type) {
                $this->command?->warn("DocumentType code={$docCode} tidak ditemukan, dilewati.");

                continue;
            }

            $filename = "{$docCode}-{$user->id}.pdf";
            $path = "demo/{$filename}";

            Document::query()->create([
                'application_id' => $application->id,
                'document_type_id' => $type->id,
                'uploaded_by' => $user->id,
                'path' => $path,
                'original_name' => $filename,
                'mime_type' => 'application/pdf',
                'size' => 102400,
                'checksum' => md5($path),
                'version' => 1,
                'verified_at' => null,
            ]);

            if ($jenis) {
                Dokumen::query()->create([
                    'pendaftaran_id' => $pendaftaran->id,
                    'jenis_dokumen_id' => $jenis->id,
                    'file_path' => $path,
                    'nama_file_asli' => $filename,
                    'mime_type' => 'application/pdf',
                    'ukuran_file' => 102400,
                    'status' => 'uploaded',
                    'catatan' => null,
                    'verified_at' => null,
                ]);
            }

            Storage::disk('local')->put(
                $path,
                "Demo dokumen {$docCode} untuk {$user->name}",
            );
        }

        $this->command?->info("✓ Akun {$user->email} siap di langkah terakhir (Submit). Jalur: {$payload['application_type']->label()}");
    }

    private function syncKategoriDokumen(): void
    {
        $requiredGlobal = ['UKT', 'TRANSKRIP'];
        $requiredByType = [
            ApplicationType::DISABILITAS->value => ['SKD'],
        ];

        $byCode = JenisDokumen::query()
            ->whereIn('kode', array_merge($requiredGlobal, array_merge(...array_values($requiredByType))))
            ->get()
            ->keyBy('kode');

        $categories = KategoriBeasiswa::query()
            ->whereIn('application_type', array_keys($requiredByType))
            ->orWhereIn('application_type', [
                ApplicationType::AKADEMIK->value,
                ApplicationType::TIDAK_MAMPU->value,
                ApplicationType::NON_AKADEMIK->value,
            ])
            ->get();

        foreach ($categories as $category) {
            $codes = array_merge($requiredGlobal, $requiredByType[$category->application_type->value] ?? []);

            foreach ($codes as $code) {
                $jd = $byCode[$code] ?? null;
                if (! $jd) {
                    continue;
                }

                $exists = KategoriBeasiswaDokumen::query()
                    ->where('kategori_beasiswa_id', $category->id)
                    ->where('jenis_dokumen_id', $jd->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $max = KategoriBeasiswaDokumen::query()
                    ->where('kategori_beasiswa_id', $category->id)
                    ->max('urutan');

                KategoriBeasiswaDokumen::query()->create([
                    'kategori_beasiswa_id' => $category->id,
                    'jenis_dokumen_id' => $jd->id,
                    'urutan' => ($max ?? 0) + 1,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function academicStudent(): array
    {
        return [
            'email' => 'lengkap.akademik@kartuhebat.test',
            'name' => 'Andini Putri Maharani',
            'nomor_pendaftaran' => 'KH-2026-AKAD-001',
            'application_type' => ApplicationType::AKADEMIK,
            'profile' => [
                'nik' => '6212012345670001',
                'nim' => '2000012345',
                'phone' => '081250001001',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Teknik Informatika',
                'semester' => 5,
                'ipk' => 3.78,
                'disability_type' => null,
                'disability_grade' => null,
                'disability_document_number' => null,
                'alamat' => 'Jl. Cilik Riwut Km. 5, Gang Mawar No. 12',
                'village_id' => Village::query()->where('name', 'Beriwit')->value('id'),
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 4500000,
                'jumlah_tanggungan' => 4,
                'desil_sosial' => null,
                'desil_pendidikan' => null,
                'prestasi' => 'Juara 1 LKTI Tingkat Universitas (2024)',
            ],
            'data_pribadi' => [
                'nik' => '6212012345670001',
                'nisn' => '1234567890',
                'nama_lengkap' => 'Andini Putri Maharani',
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2004-03-15',
                'jenis_kelamin' => 'P',
                'agama' => 'Islam',
                'alamat' => 'Jl. Cilik Riwut Km. 5, Gang Mawar No. 12',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '081250001001',
            ],
            'pendidikan' => [
                'nim' => '2000012345',
                'universitas' => 'Universitas Palangka Raya',
                'fakultas' => 'Fakultas Teknik',
                'program_studi' => 'Teknik Informatika',
                'jenjang' => 'S1',
                'semester' => 5,
                'ipk' => 3.78,
                'tahun_masuk' => 2022,
                'status_mahasiswa' => 'aktif',
            ],
            'orang_tua' => [
                'nama_ayah' => 'Mahrani Saputra',
                'nik_ayah' => '6212010101700001',
                'pekerjaan_ayah' => 'Petani',
                'penghasilan_ayah' => 2500000,
                'nama_ibu' => 'Sumiati',
                'nik_ibu' => '6212014101720002',
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'penghasilan_ibu' => 0,
                'memiliki_wali' => false,
            ],
            'prestasis' => [
                [
                    'jenis' => 'akademik',
                    'nama_prestasi' => 'Juara 1 Lomba Karya Tulis Ilmiah Tingkat Universitas',
                    'tingkat' => 'kampus',
                    'peringkat' => 'juara_1',
                    'penyelenggara' => 'Universitas Palangka Raya',
                    'tahun' => 2024,
                    'dokumen_prestasi' => null,
                    'keterangan' => 'Finalis nasional LKTI.',
                ],
            ],
            'documents' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'UKT', 'TRANSKRIP', 'KHS'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unableStudent(): array
    {
        return [
            'email' => 'lengkap.tidakmampu@kartuhebat.test',
            'name' => 'Rio Saputra',
            'nomor_pendaftaran' => 'KH-2026-TM-001',
            'application_type' => ApplicationType::TIDAK_MAMPU,
            'profile' => [
                'nik' => '6212022345670002',
                'nim' => '2100098765',
                'phone' => '081250001002',
                'universitas' => 'Universitas Muhammadiyah Palangka Raya',
                'program_studi' => 'Akuntansi',
                'semester' => 6,
                'ipk' => 3.42,
                'disability_type' => null,
                'disability_grade' => null,
                'disability_document_number' => null,
                'alamat' => 'Jl. RTA Milono Km. 2, Gang Anggrek No. 7',
                'village_id' => Village::query()->where('name', 'Saripoi')->value('id'),
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 1800000,
                'jumlah_tanggungan' => 5,
                'desil_sosial' => 6,
                'desil_pendidikan' => 7,
                'prestasi' => null,
            ],
            'data_pribadi' => [
                'nik' => '6212022345670002',
                'nisn' => '2345678901',
                'nama_lengkap' => 'Rio Saputra',
                'tempat_lahir' => 'Muara Teweh',
                'tanggal_lahir' => '2002-09-21',
                'jenis_kelamin' => 'L',
                'agama' => 'Kristen',
                'alamat' => 'Jl. RTA Milono Km. 2, Gang Anggrek No. 7',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Tanah Siang',
                'desa' => 'Saripoi',
                'kode_pos' => '73912',
                'no_hp' => '081250001002',
            ],
            'pendidikan' => [
                'nim' => '2100098765',
                'universitas' => 'Universitas Muhammadiyah Palangka Raya',
                'fakultas' => 'Fakultas Ekonomi dan Bisnis',
                'program_studi' => 'Akuntansi',
                'jenjang' => 'S1',
                'semester' => 6,
                'ipk' => 3.42,
                'tahun_masuk' => 2021,
                'status_mahasiswa' => 'aktif',
            ],
            'orang_tua' => [
                'nama_ayah' => 'Yansen Saputra',
                'nik_ayah' => '6212020101680003',
                'pekerjaan_ayah' => 'Buruh Harian Lepas',
                'penghasilan_ayah' => 1200000,
                'nama_ibu' => 'Marta Lina',
                'nik_ibu' => '6212024101730004',
                'pekerjaan_ibu' => 'Penjual Sayur',
                'penghasilan_ibu' => 600000,
                'memiliki_wali' => false,
            ],
            'prestasis' => [],
            'documents' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'UKT', 'TRANSKRIP', 'SKTM'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function disabilityStudent(): array
    {
        return [
            'email' => 'lengkap.disabilitas@kartuhebat.test',
            'name' => 'Putri Wulandari',
            'nomor_pendaftaran' => 'KH-2026-DIS-001',
            'application_type' => ApplicationType::DISABILITAS,
            'profile' => [
                'nik' => '6212032345670003',
                'nim' => '2200077777',
                'phone' => '081250001003',
                'universitas' => 'Institut Agama Islam Negeri Palangka Raya',
                'program_studi' => 'Pendidikan Bahasa Arab',
                'semester' => 4,
                'ipk' => 3.55,
                'disability_type' => 'TUNANETRA',
                'disability_grade' => 'SEDANG',
                'disability_document_number' => 'DOK-DIS-2024-00123',
                'alamat' => 'Jl. G. Obos No. 45, RT 02/RW 01',
                'village_id' => Village::query()->where('name', 'Muara Laung I')->value('id'),
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 3500000,
                'jumlah_tanggungan' => 3,
                'desil_sosial' => null,
                'desil_pendidikan' => null,
                'prestasi' => 'Juara 2 MTQ Tingkat Provinsi Kalteng (2025)',
            ],
            'data_pribadi' => [
                'nik' => '6212032345670003',
                'nisn' => '3456789012',
                'nama_lengkap' => 'Putri Wulandari',
                'tempat_lahir' => 'Tamiang Layang',
                'tanggal_lahir' => '2004-08-09',
                'jenis_kelamin' => 'P',
                'agama' => 'Islam',
                'alamat' => 'Jl. G. Obos No. 45, RT 02/RW 01',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Laung Tuhup',
                'desa' => 'Muara Laung I',
                'kode_pos' => '73913',
                'no_hp' => '081250001003',
            ],
            'pendidikan' => [
                'nim' => '2200077777',
                'universitas' => 'Institut Agama Islam Negeri Palangka Raya',
                'fakultas' => 'Fakultas Tarbiyah dan Keguruan',
                'program_studi' => 'Pendidikan Bahasa Arab',
                'jenjang' => 'S1',
                'semester' => 4,
                'ipk' => 3.55,
                'tahun_masuk' => 2023,
                'status_mahasiswa' => 'aktif',
            ],
            'orang_tua' => [
                'nama_ayah' => 'Bambang Wulandari',
                'nik_ayah' => '6212030101710005',
                'pekerjaan_ayah' => 'PNS',
                'penghasilan_ayah' => 2500000,
                'nama_ibu' => 'Suryani',
                'nik_ibu' => '6212034101750006',
                'pekerjaan_ibu' => 'Guru Honorer',
                'penghasilan_ibu' => 1000000,
                'memiliki_wali' => false,
            ],
            'prestasis' => [
                [
                    'jenis' => 'non_akademik',
                    'nama_prestasi' => 'Juara 2 MTQ Tingkat Provinsi Kalimantan Tengah',
                    'tingkat' => 'provinsi',
                    'peringkat' => 'juara_2',
                    'penyelenggara' => 'LPTQ Kalimantan Tengah',
                    'tahun' => 2025,
                    'dokumen_prestasi' => null,
                    'keterangan' => 'Cabang Tilawah Dewasa.',
                ],
            ],
            'documents' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'UKT', 'TRANSKRIP', 'KHS', 'SURAT-DISABILITAS', 'SKD'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nonAcademicStudent(): array
    {
        return [
            'email' => 'lengkap.prestasi@kartuhebat.test',
            'name' => 'Bagas Kurniawan',
            'nomor_pendaftaran' => 'KH-2026-PRESTASI-001',
            'application_type' => ApplicationType::NON_AKADEMIK,
            'profile' => [
                'nik' => '6212042345670004',
                'nim' => '2300055555',
                'phone' => '081250001004',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Ilmu Komunikasi',
                'semester' => 5,
                'ipk' => 3.61,
                'disability_type' => null,
                'disability_grade' => null,
                'disability_document_number' => null,
                'alamat' => 'Jl. Yos Sudarso No. 88, RT 03/RW 02',
                'village_id' => Village::query()->where('name', 'Tumbang Lahung')->value('id'),
                'status_kependudukan' => 'aktif',
                'penghasilan_keluarga' => 5000000,
                'jumlah_tanggungan' => 3,
                'desil_sosial' => null,
                'desil_pendidikan' => null,
                'prestasi' => 'Juara 1 Debat Bahasa Indonesia Tingkat Nasional (2025); Juara 3 Pencak Silat Tingkat Provinsi (2024)',
            ],
            'data_pribadi' => [
                'nik' => '6212042345670004',
                'nisn' => '4567890123',
                'nama_lengkap' => 'Bagas Kurniawan',
                'tempat_lahir' => 'Sampit',
                'tanggal_lahir' => '2003-12-02',
                'jenis_kelamin' => 'L',
                'agama' => 'Islam',
                'alamat' => 'Jl. Yos Sudarso No. 88, RT 03/RW 02',
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Permata Intan',
                'desa' => 'Tumbang Lahung',
                'kode_pos' => '73914',
                'no_hp' => '081250001004',
            ],
            'pendidikan' => [
                'nim' => '2300055555',
                'universitas' => 'Universitas Palangka Raya',
                'fakultas' => 'Fakultas Ilmu Sosial dan Ilmu Politik',
                'program_studi' => 'Ilmu Komunikasi',
                'jenjang' => 'S1',
                'semester' => 5,
                'ipk' => 3.61,
                'tahun_masuk' => 2022,
                'status_mahasiswa' => 'aktif',
            ],
            'orang_tua' => [
                'nama_ayah' => 'Eko Kurniawan',
                'nik_ayah' => '6212040101700007',
                'pekerjaan_ayah' => 'Wiraswasta',
                'penghasilan_ayah' => 3000000,
                'nama_ibu' => 'Dewi Anggraini',
                'nik_ibu' => '6212044101720008',
                'pekerjaan_ibu' => 'Karyawan Swasta',
                'penghasilan_ibu' => 2000000,
                'memiliki_wali' => false,
            ],
            'prestasis' => [
                [
                    'jenis' => 'non_akademik',
                    'nama_prestasi' => 'Juara 1 Debat Bahasa Indonesia Tingkat Nasional',
                    'tingkat' => 'nasional',
                    'peringkat' => 'juara_1',
                    'penyelenggara' => 'Kementerian Pendidikan',
                    'tahun' => 2025,
                    'dokumen_prestasi' => null,
                    'keterangan' => 'Finalis terbaik nasional.',
                ],
                [
                    'jenis' => 'non_akademik',
                    'nama_prestasi' => 'Juara 3 Kejuaraan Pencak Silat Tingkat Provinsi',
                    'tingkat' => 'provinsi',
                    'peringkat' => 'juara_3',
                    'penyelenggara' => 'KONI Kalimantan Tengah',
                    'tahun' => 2024,
                    'dokumen_prestasi' => null,
                    'keterangan' => 'Kategori tanding kelas B.',
                ],
            ],
            'documents' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'UKT', 'TRANSKRIP', 'KHS', 'SERTIFIKAT-PRESTASI'],
        ];
    }
}
