<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\DataPribadi;
use App\Models\DocumentType;
use App\Models\DocumentVerification;
use App\Models\JalurBeasiswa;
use App\Models\Kabupaten;
use App\Models\KategoriBeasiswa;
use App\Models\Kecamatan;
use App\Models\MahasiswaProfile;
use App\Models\OrangTua;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\Prestasi;
use App\Models\User;
use App\Models\VerificationLog;
use App\Models\Village;
use App\Services\AgencyVerificationService;
use App\Services\SelectionScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function __construct(private readonly SelectionScoringService $scoring) {}

    public function run(): void
    {
        $periode = Periode::query()->aktif()->orderByDesc('tanggal_mulai')->first()
            ?? Periode::query()->latest('id')->firstOrFail();
        $kabupaten = Kabupaten::query()->firstOrFail();
        $kecamatan = Kecamatan::query()->where('kabupaten_id', $kabupaten->id)->firstOrFail();
        $village = Village::query()->where('kecamatan_id', $kecamatan->id)->firstOrFail();

        $this->seedSuperadmin($kabupaten);

        $operators = $this->seedOperators($village, $kecamatan, $kabupaten);
        $kabupatenOperator = $operators[UserRole::OPERATOR_KABUPATEN->value];

        $agencyOperators = [];
        foreach ($operators as $roleValue => $operator) {
            $role = UserRole::tryFrom($roleValue);

            if ($role?->isAgency()) {
                $agencyOperators[$role->agencyCode()] = $operator;
            }
        }

        $jalurIds = JalurBeasiswa::query()->orderBy('urutan')->pluck('id')->all();

        $payloads = array_merge(
            $this->featuredStudents($jalurIds[0] ?? null, $village, $periode),
            $this->generatedStudents($jalurIds, $periode),
        );

        foreach ($payloads as $payload) {
            $this->seedStudent($payload, $periode, $agencyOperators, $kabupatenOperator);
        }

        $this->scoring->recalculateRanking($kabupaten->id, $this->periodName($periode));

        $this->seedAnnouncements($kabupatenOperator);

        $this->command?->info('Data demo siap: '.count($payloads).' pendaftaran mahasiswa pada periode '.$this->periodName($periode).'.');
    }

    private function seedSuperadmin(Kabupaten $kabupaten): void
    {
        $superadmin = User::query()->firstOrNew(['email' => 'superadmin@kartuhebat.test']);

        $superadmin->forceFill([
            'name' => 'Superadmin Kartu Hebat',
            'email' => 'superadmin@kartuhebat.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => UserRole::SUPERADMIN,
            'status' => 'active',
            'kabupaten_id' => $kabupaten->id,
        ])->save();
    }

    /**
     * @return array<string, User> operator dikunci dengan nilai UserRole
     */
    private function seedOperators(Village $village, Kecamatan $kecamatan, Kabupaten $kabupaten): array
    {
        $definitions = [
            [UserRole::OPERATOR_DESA, 'Operator Desa', 'desa@kartuhebat.test', $village->id, $kecamatan->id],
            [UserRole::OPERATOR_KECAMATAN, 'Operator Kecamatan', 'kecamatan@kartuhebat.test', null, $kecamatan->id],
            [UserRole::OPERATOR_DUKCAPIL, 'Operator Dukcapil', 'dukcapil@kartuhebat.test', null, null],
            [UserRole::OPERATOR_SOSIAL, 'Operator Dinas Sosial', 'sosial@kartuhebat.test', null, null],
            [UserRole::OPERATOR_PENDIDIKAN, 'Operator Dinas Pendidikan', 'pendidikan@kartuhebat.test', null, null],
            [UserRole::OPERATOR_BKPSDM, 'Operator BKPSDM', 'bkpsdm@kartuhebat.test', null, null],
            [UserRole::OPERATOR_DINKES, 'Operator Dinas Kesehatan', 'dinkes@kartuhebat.test', null, null],
            [UserRole::OPERATOR_PARSEPOR, 'Operator Dinas Parsepor', 'parsepor@kartuhebat.test', null, null],
            [UserRole::OPERATOR_KABUPATEN, 'Operator Kabupaten', 'kabupaten@kartuhebat.test', null, null],
        ];

        $operators = [];

        foreach ($definitions as [$role, $name, $email, $villageId, $kecamatanId]) {
            $operator = User::query()->firstOrNew(['email' => $email]);

            $operator->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => $role,
                'status' => 'active',
                'village_id' => $villageId,
                'kecamatan_id' => $kecamatanId,
                'kabupaten_id' => $kabupaten->id,
            ])->save();

            $operators[$role->value] = $operator;
        }

        return $operators;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, User>  $agencyOperators
     */
    private function seedStudent(
        array $payload,
        Periode $periode,
        array $agencyOperators,
        User $kabupatenOperator,
    ): void {
        $type = $payload['application_type'];
        $status = $payload['status'];
        $submittedAt = $payload['submitted_at'];
        $studentVillage = $payload['village'];

        $student = User::query()->firstOrNew(['email' => $payload['email']]);

        $student->forceFill([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => UserRole::MAHASISWA,
            'status' => 'active',
            'village_id' => $studentVillage->id,
            'kecamatan_id' => $studentVillage->kecamatan_id,
            'kabupaten_id' => $studentVillage->kabupaten_id,
        ])->save();

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $student->id],
            array_merge($payload['profile'], [
                'user_id' => $student->id,
                'village_id' => $studentVillage->id,
            ]),
        );

        Application::query()
            ->where('mahasiswa_id', $student->id)
            ->whereNull('pendaftaran_id')
            ->delete();

        $kategori = KategoriBeasiswa::query()
            ->where('periode_id', $periode->id)
            ->where('application_type', $type->value)
            ->firstOrFail();

        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            ['user_id' => $student->id, 'periode_id' => $periode->id],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'jalur_beasiswa_id' => $payload['jalur_beasiswa_id'],
                'nomor_pendaftaran' => $payload['nomor_pendaftaran'],
                'status' => $this->pendaftaranStatus($status),
                'submitted_at' => $submittedAt,
                'review_dikonfirmasi_at' => $submittedAt,
                'prestasi_dikonfirmasi_at' => $type === ApplicationType::NON_AKADEMIK ? $submittedAt : null,
            ],
        );

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            array_merge($payload['data_pribadi'], [
                'pendaftaran_id' => $pendaftaran->id,
                'village_id' => $studentVillage->id,
            ]),
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            array_merge($payload['pendidikan'], ['pendaftaran_id' => $pendaftaran->id]),
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            array_merge($payload['orang_tua'], ['pendaftaran_id' => $pendaftaran->id]),
        );

        Prestasi::query()->where('pendaftaran_id', $pendaftaran->id)->delete();

        foreach ($payload['prestasis'] as $prestasi) {
            Prestasi::query()->create(array_merge($prestasi, ['pendaftaran_id' => $pendaftaran->id]));
        }

        $application = Application::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $student->id,
                'nomor_pengajuan' => $payload['nomor_pendaftaran'],
                'periode' => $this->periodName($periode),
                'application_type' => $type,
                'status' => $status,
                'catatan' => null,
                'submitted_at' => $submittedAt,
                'locked_at' => $submittedAt,
            ],
        );

        $this->seedDocuments($application, $pendaftaran, $kategori, $student);

        $this->resetWorkflowArtifacts($application);

        $this->seedWorkflow(
            $application->fresh(),
            $status,
            $agencyOperators,
            $kabupatenOperator,
            $payload['verified_agency_count'],
        );
    }

    /**
     * Membersihkan jejak alur sebelumnya agar seeder tetap idempoten saat
     * dijalankan berulang pada database yang sama.
     */
    private function resetWorkflowArtifacts(Application $application): void
    {
        VerificationLog::query()->where('application_id', $application->id)->delete();
        AgencyVerification::query()->where('application_id', $application->id)->delete();
        DocumentVerification::query()->where('application_id', $application->id)->delete();
        $application->scores()->delete();
        $application->selection()->delete();
    }

    private function seedDocuments(
        Application $application,
        Pendaftaran $pendaftaran,
        KategoriBeasiswa $kategori,
        User $student,
    ): void {
        $disk = Storage::disk(config('kartu_hebat.document_disk'));

        $jenisDokumens = $kategori->jenisDokumens()->get()->keyBy('kode');
        $documentTypes = DocumentType::query()
            ->whereIn('code', $jenisDokumens->keys())
            ->get()
            ->keyBy('code');

        foreach ($jenisDokumens as $code => $jenis) {
            $type = $documentTypes->get($code);

            if (! $type) {
                $this->command?->warn("DocumentType code={$code} tidak ditemukan, dilewati.");

                continue;
            }

            $path = 'applications/'.$application->id.'/'.$code.'/demo-'.$code.'.pdf';
            $contents = "%PDF-1.4\n% Dokumen demo {$type->name}\n";
            $disk->put($path, $contents);

            $application->documents()->updateOrCreate(
                ['document_type_id' => $type->id],
                [
                    'uploaded_by' => $student->id,
                    'path' => $path,
                    'original_name' => Str::lower($code).'-'.$student->id.'.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => strlen($contents),
                    'checksum' => hash('sha256', $contents),
                    'version' => 1,
                ],
            );

            $pendaftaran->dokumens()->updateOrCreate(
                ['jenis_dokumen_id' => $jenis->id],
                [
                    'file_path' => $path,
                    'nama_file_asli' => Str::lower($code).'-'.$student->id.'.pdf',
                    'mime_type' => 'application/pdf',
                    'ukuran_file' => strlen($contents),
                    'status' => 'uploaded',
                    'catatan' => null,
                ],
            );
        }
    }

    /**
     * Menirukan alur AgencyVerificationService: log submit, penilaian dokumen
     * per dinas, keputusan MS/TMS, lalu skoring saat masuk seleksi kabupaten.
     * Keputusan penerimaan sengaja belum diisi agar operator kabupaten masih
     * memiliki antrean seleksi yang bisa diputuskan.
     *
     * @param  array<string, User>  $agencyOperators
     */
    private function seedWorkflow(
        Application $application,
        ApplicationStatus $status,
        array $agencyOperators,
        User $kabupatenOperator,
        int $verifiedAgencyCount,
    ): void {
        if ($status === ApplicationStatus::DRAFT) {
            return;
        }

        $application->loadMissing(['documents', 'mahasiswa.profile']);

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $application->mahasiswa_id,
            'from_status' => ApplicationStatus::DRAFT->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'submitted',
            'notes' => 'Pengajuan dikirim oleh mahasiswa.',
            'created_at' => $application->submitted_at ?? now(),
        ]);

        $requiredAgencies = AgencyVerificationService::requiredAgencies($application);
        $decisions = $this->agencyDecisions($requiredAgencies, $status, $verifiedAgencyCount);

        $decidedCount = 0;

        foreach ($requiredAgencies as $agency) {
            $operator = $agencyOperators[$agency] ?? null;

            if (! isset($decisions[$agency]) || $operator === null) {
                continue;
            }

            $this->assessDocuments($application, $operator, $agency);
            $this->recordAgencyVerification($application, $operator, $agency, $decisions[$agency]);
            $this->logAgencyDecision($application, $operator, $agency, $decisions[$agency]);

            $decidedCount++;
        }

        if ($decidedCount < count($requiredAgencies)) {
            return;
        }

        $this->scoring->calculate($application, $kabupatenOperator->id);
        $this->logFinalDecision($application, $kabupatenOperator, ApplicationStatus::SELEKSI_KABUPATEN, VerificationDecision::MS);
    }

    /**
     * @param  array<int, string>  $requiredAgencies
     * @return array<string, VerificationDecision>
     */
    private function agencyDecisions(array $requiredAgencies, ApplicationStatus $status, int $verifiedAgencyCount): array
    {
        if ($status === ApplicationStatus::VERIFIKASI_DINAS) {
            $limit = max(0, min($verifiedAgencyCount, count($requiredAgencies) - 1));

            return array_fill_keys(array_slice($requiredAgencies, 0, $limit), VerificationDecision::MS);
        }

        return array_fill_keys($requiredAgencies, VerificationDecision::MS);
    }

    private function assessDocuments(Application $application, User $operator, string $stage): void
    {
        $round = AgencyVerificationService::currentRound($application);

        foreach ($application->documents as $document) {
            DocumentVerification::query()->updateOrCreate(
                [
                    'document_id' => $document->id,
                    'stage' => $stage,
                    'round' => $round,
                ],
                [
                    'application_id' => $application->id,
                    'verifier_id' => $operator->id,
                    'result' => DocumentVerificationResult::MEMENUHI,
                    'notes' => null,
                    'verified_at' => now()->subDay(),
                ],
            );
        }
    }

    private function recordAgencyVerification(
        Application $application,
        User $operator,
        string $agency,
        VerificationDecision $decision,
    ): void {
        $desil = $agency === 'sosial'
            ? $application->mahasiswa->profile?->desil_sosial
            : null;

        AgencyVerification::query()->updateOrCreate(
            ['application_id' => $application->id, 'agency' => $agency],
            [
                'verifier_id' => $operator->id,
                'decision' => $decision,
                'score' => $decision === VerificationDecision::MS ? fake()->numberBetween(70, 95) : null,
                'notes' => $decision === VerificationDecision::MS
                    ? 'Hasil verifikasi instansi sesuai.'
                    : 'Data tidak sesuai dengan dokumen yang diajukan.',
                'metadata' => $desil === null ? null : ['desil' => $desil],
                'verified_at' => now()->subDay(),
            ],
        );
    }

    private function logAgencyDecision(
        Application $application,
        User $operator,
        string $agency,
        VerificationDecision $decision,
    ): void {
        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $operator->id,
            'from_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'agency_verification_'.$decision->value,
            'notes' => null,
            'metadata' => ['agency' => $agency],
            'created_at' => now()->subDay(),
        ]);
    }

    private function logFinalDecision(
        Application $application,
        User $operator,
        ApplicationStatus $target,
        VerificationDecision $decision,
    ): void {
        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $operator->id,
            'from_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'to_status' => $target->value,
            'action' => 'verification_'.$decision->value,
            'notes' => $decision === VerificationDecision::TMS
                ? 'Salah satu dinas menyatakan tidak memenuhi syarat.'
                : 'Seluruh dinas menyatakan memenuhi syarat.',
            'created_at' => now()->subDay(),
        ]);
    }

    private function seedAnnouncements(User $kabupatenOperator): void
    {
        $announcements = [
            ['Periode Pendaftaran Semester Ganjil Dibuka', 'informasi', 'Pendaftaran Kartu Hebat Mahasiswa telah dibuka.', 'Mahasiswa dapat membuat akun, melengkapi profil, dan mengunggah dokumen sesuai persyaratan.'],
            ['Panduan Pengunggahan Dokumen KHS', 'panduan', 'Pastikan dokumen terbaca dengan jelas.', 'Gunakan berkas PDF atau gambar dengan ukuran maksimal 2 MB untuk setiap dokumen.'],
            ['Hasil Seleksi Administrasi Gelombang I', 'hasil', 'Hasil verifikasi administrasi dapat diperiksa melalui dashboard.', 'Masuk ke akun masing-masing untuk melihat status dan catatan petugas.'],
        ];

        foreach ($announcements as $index => [$title, $type, $excerpt, $body]) {
            Announcement::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'type' => $type,
                    'excerpt' => $excerpt,
                    'body' => $body,
                    'is_active' => true,
                    'published_at' => now()->subDays($index + 1),
                    'created_by' => $kabupatenOperator->id,
                ],
            );
        }
    }

    private function pendaftaranStatus(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::DRAFT => 'draft',
            ApplicationStatus::DITERIMA => 'approved',
            ApplicationStatus::DITOLAK, ApplicationStatus::TMS => 'rejected',
            default => 'verification',
        };
    }

    private function periodName(Periode $periode): string
    {
        return $periode->nama ?: (string) $periode->tahun;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function featuredStudents(?int $jalurBeasiswaId, Village $village, Periode $periode): array
    {
        return [
            [
                'email' => 'mahasiswa@kartuhebat.test',
                'name' => 'Andi Saputra',
                'nomor_pendaftaran' => 'KHM-'.$periode->tahun.'-000128',
                'application_type' => ApplicationType::AKADEMIK,
                'status' => ApplicationStatus::VERIFIKASI_DINAS,
                'submitted_at' => now()->subDays(4),
                'verified_agency_count' => 1,
                'village' => $village,
                'jalur_beasiswa_id' => $jalurBeasiswaId,
                'profile' => [
                    'nik' => '6212010101010001',
                    'nim' => 'KHM20260001',
                    'phone' => '081234567890',
                    'universitas' => 'Universitas Palangka Raya',
                    'program_studi' => 'Teknik Informatika',
                    'semester' => 5,
                    'ipk' => 3.62,
                    'alamat' => 'Jl. Pendidikan, Kabupaten Murung Raya',
                    'status_kependudukan' => 'sesuai',
                    'penghasilan_keluarga' => null,
                    'jumlah_tanggungan' => null,
                    'desil_sosial' => null,
                    'prestasi' => 'Finalis lomba inovasi teknologi tingkat provinsi.',
                ],
                'data_pribadi' => [
                    'nik' => '6212010101010001',
                    'nomor_kk' => '6212010101019001',
                    'nomor_rekening' => '6212000001280',
                    'nama_lengkap' => 'Andi Saputra',
                    'tempat_lahir' => 'Puruk Cahu',
                    'tanggal_lahir' => '2004-02-11',
                    'jenis_kelamin' => 'L',
                    'agama' => 'Islam',
                    'alamat' => 'Jl. Pendidikan, Kabupaten Murung Raya',
                    'provinsi' => 'Kalimantan Tengah',
                    'kabupaten' => 'Murung Raya',
                    'kecamatan' => 'Murung',
                    'desa' => 'Beriwit',
                    'kode_pos' => '73911',
                    'no_hp' => '081234567890',
                ],
                'pendidikan' => [
                    'nim' => 'KHM20260001',
                    'universitas' => 'Universitas Palangka Raya',
                    'fakultas' => 'Fakultas Teknik',
                    'jurusan' => 'Teknik Informatika',
                    'program_studi' => 'Teknik Informatika',
                    'jenjang' => 'S1',
                    'semester' => 5,
                    'ipk' => 3.62,
                    'nominal_ukt' => 3500000,
                    'tahun_masuk' => 2024,
                    'status_perguruan_tinggi' => 'negeri',
                    'akreditasi_perguruan_tinggi' => 'Baik Sekali',
                    'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya, Kalimantan Tengah',
                ],
                'orang_tua' => [
                    'nama_ayah' => 'Saputra Wijaya',
                    'status_ayah' => 'hidup',
                    'nik_ayah' => '6212010101700001',
                    'pekerjaan_ayah' => 'ASN',
                    'jabatan_ayah' => 'Kepala Bidang Pelayanan dan Mutasi (Eselon III)',
                    'nama_ibu' => 'Sumiati',
                    'status_ibu' => 'hidup',
                    'nik_ibu' => '6212014101720002',
                    'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                    'jabatan_ibu' => null,
                    'memiliki_wali' => false,
                    'pekerjaan_wali' => null,
                    'jabatan_wali' => null,
                ],
                'prestasis' => [],
            ],
            [
                'email' => 'mahasiswa.disabilitas@kartuhebat.test',
                'name' => 'Rina Kusuma',
                'nomor_pendaftaran' => 'KHM-'.$periode->tahun.'-000129',
                'application_type' => ApplicationType::DISABILITAS,
                'status' => ApplicationStatus::SELEKSI_KABUPATEN,
                'submitted_at' => now()->subDays(3),
                'verified_agency_count' => 0,
                'village' => $village,
                'jalur_beasiswa_id' => $jalurBeasiswaId,
                'profile' => [
                    'nik' => '6212010101010002',
                    'nim' => 'KHM20260002',
                    'phone' => '081234567891',
                    'universitas' => 'Universitas Palangka Raya',
                    'program_studi' => 'Pendidikan Luar Biasa',
                    'semester' => 4,
                    'ipk' => 3.55,
                    'disability_type' => 'TUNANETRA',
                    'disability_grade' => 'SEDANG',
                    'disability_document_number' => 'DOK-DIS-2024-00123',
                    'alamat' => 'Jl. G. Obos No. 45, Kabupaten Murung Raya',
                    'status_kependudukan' => 'sesuai',
                    'penghasilan_keluarga' => null,
                    'jumlah_tanggungan' => null,
                    'desil_sosial' => null,
                    'prestasi' => null,
                ],
                'data_pribadi' => [
                    'nik' => '6212010101010002',
                    'nomor_kk' => '6212010101019002',
                    'nomor_rekening' => '6212000001290',
                    'nama_lengkap' => 'Rina Kusuma',
                    'tempat_lahir' => 'Puruk Cahu',
                    'tanggal_lahir' => '2004-06-23',
                    'jenis_kelamin' => 'P',
                    'agama' => 'Kristen',
                    'alamat' => 'Jl. G. Obos No. 45, Kabupaten Murung Raya',
                    'provinsi' => 'Kalimantan Tengah',
                    'kabupaten' => 'Murung Raya',
                    'kecamatan' => 'Murung',
                    'desa' => 'Beriwit',
                    'kode_pos' => '73911',
                    'no_hp' => '081234567891',
                ],
                'pendidikan' => [
                    'nim' => 'KHM20260002',
                    'universitas' => 'Universitas Palangka Raya',
                    'fakultas' => 'Fakultas Keguruan dan Ilmu Pendidikan',
                    'jurusan' => 'Pendidikan Luar Biasa',
                    'program_studi' => 'Pendidikan Luar Biasa',
                    'jenjang' => 'S1',
                    'semester' => 4,
                    'ipk' => 3.55,
                    'nominal_ukt' => 2400000,
                    'tahun_masuk' => 2024,
                    'status_perguruan_tinggi' => 'negeri',
                    'akreditasi_perguruan_tinggi' => 'Baik Sekali',
                    'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya, Kalimantan Tengah',
                ],
                'orang_tua' => [
                    'nama_ayah' => 'Kusuma Adi',
                    'status_ayah' => 'hidup',
                    'nik_ayah' => '6212010101700003',
                    'pekerjaan_ayah' => 'ASN',
                    'jabatan_ayah' => 'Staf Pelaksana',
                    'nama_ibu' => 'Lestari',
                    'status_ibu' => 'hidup',
                    'nik_ibu' => '6212014101720004',
                    'pekerjaan_ibu' => 'Penjahit',
                    'jabatan_ibu' => null,
                    'memiliki_wali' => false,
                    'pekerjaan_wali' => null,
                    'jabatan_wali' => null,
                ],
                'prestasis' => [],
            ],
            [
                'email' => 'mahasiswa.prestasi@kartuhebat.test',
                'name' => 'Budi Hartono',
                'nomor_pendaftaran' => 'KHM-'.$periode->tahun.'-000130',
                'application_type' => ApplicationType::NON_AKADEMIK,
                'status' => ApplicationStatus::VERIFIKASI_DINAS,
                'submitted_at' => now()->subDays(2),
                'verified_agency_count' => 0,
                'village' => $village,
                'jalur_beasiswa_id' => $jalurBeasiswaId,
                'profile' => [
                    'nik' => '6212010101010004',
                    'nim' => 'KHM20260004',
                    'phone' => '081234567894',
                    'universitas' => 'Universitas Palangka Raya',
                    'program_studi' => 'Ilmu Komunikasi',
                    'semester' => 5,
                    'ipk' => 3.61,
                    'alamat' => 'Jl. Yos Sudarso No. 88, Kabupaten Murung Raya',
                    'status_kependudukan' => 'sesuai',
                    'penghasilan_keluarga' => null,
                    'jumlah_tanggungan' => null,
                    'desil_sosial' => null,
                    'prestasi' => 'Juara 1 Debat Bahasa Indonesia Tingkat Nasional (2025)',
                ],
                'data_pribadi' => [
                    'nik' => '6212010101010004',
                    'nomor_kk' => '6212010101019001',
                    'nomor_rekening' => '6212000001300',
                    'nama_lengkap' => 'Budi Hartono',
                    'tempat_lahir' => 'Puruk Cahu',
                    'tanggal_lahir' => '2003-11-05',
                    'jenis_kelamin' => 'L',
                    'agama' => 'Islam',
                    'alamat' => 'Jl. Yos Sudarso No. 88, Kabupaten Murung Raya',
                    'provinsi' => 'Kalimantan Tengah',
                    'kabupaten' => 'Murung Raya',
                    'kecamatan' => 'Murung',
                    'desa' => 'Beriwit',
                    'kode_pos' => '73911',
                    'no_hp' => '081234567894',
                ],
                'pendidikan' => [
                    'nim' => 'KHM20260004',
                    'universitas' => 'Universitas Palangka Raya',
                    'fakultas' => 'Fakultas Ilmu Sosial dan Ilmu Politik',
                    'jurusan' => 'Ilmu Komunikasi',
                    'program_studi' => 'Ilmu Komunikasi',
                    'jenjang' => 'S1',
                    'semester' => 5,
                    'ipk' => 3.61,
                    'nominal_ukt' => 3200000,
                    'tahun_masuk' => 2024,
                    'status_perguruan_tinggi' => 'negeri',
                    'akreditasi_perguruan_tinggi' => 'Baik Sekali',
                    'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya, Kalimantan Tengah',
                ],
                'orang_tua' => [
                    'nama_ayah' => 'Saputra Wijaya',
                    'status_ayah' => 'hidup',
                    'nik_ayah' => '6212010101700001',
                    'pekerjaan_ayah' => 'ASN',
                    'jabatan_ayah' => 'Kepala Bidang Pelayanan dan Mutasi (Eselon III)',
                    'nama_ibu' => 'Sumiati',
                    'status_ibu' => 'hidup',
                    'nik_ibu' => '6212014101720002',
                    'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                    'jabatan_ibu' => null,
                    'memiliki_wali' => false,
                    'pekerjaan_wali' => null,
                    'jabatan_wali' => null,
                ],
                'prestasis' => [
                    [
                        'jenis' => 'non_akademik',
                        'nama_prestasi' => 'Juara 1 Debat Bahasa Indonesia Tingkat Nasional',
                        'tingkat' => 'nasional',
                        'peringkat' => 'juara 1',
                        'is_pengurus_inti_ormawa' => false,
                        'jabatan_ormawa' => null,
                        'penyelenggara' => 'Kementerian Pendidikan',
                        'tahun' => 2025,
                        'dokumen_prestasi' => null,
                        'keterangan' => 'Final nasional debat mahasiswa.',
                    ],
                ],
            ],
            [
                'email' => 'mahasiswa.tidakmampu@kartuhebat.test',
                'name' => 'Siti Aminah',
                'nomor_pendaftaran' => 'KHM-'.$periode->tahun.'-000131',
                'application_type' => ApplicationType::TIDAK_MAMPU,
                'status' => ApplicationStatus::VERIFIKASI_DINAS,
                'submitted_at' => now()->subDays(2),
                'verified_agency_count' => 0,
                'village' => $village,
                'jalur_beasiswa_id' => $jalurBeasiswaId,
                'profile' => [
                    'nik' => '6212014101010005',
                    'nim' => 'KHM20260005',
                    'phone' => '081234567895',
                    'universitas' => 'Universitas Palangka Raya',
                    'program_studi' => 'Pendidikan Guru Sekolah Dasar',
                    'semester' => 3,
                    'ipk' => 3.48,
                    'alamat' => 'Jl. Veteran No. 12, Kabupaten Murung Raya',
                    'status_kependudukan' => 'sesuai',
                    'penghasilan_keluarga' => null,
                    'jumlah_tanggungan' => null,
                    'desil_sosial' => 2,
                    'prestasi' => null,
                ],
                'data_pribadi' => [
                    'nik' => '6212014101010005',
                    'nomor_kk' => '6212010101019005',
                    'nomor_rekening' => '6212000001310',
                    'nama_lengkap' => 'Siti Aminah',
                    'tempat_lahir' => 'Puruk Cahu',
                    'tanggal_lahir' => '2005-04-15',
                    'jenis_kelamin' => 'P',
                    'agama' => 'Islam',
                    'alamat' => 'Jl. Veteran No. 12, Kabupaten Murung Raya',
                    'provinsi' => 'Kalimantan Tengah',
                    'kabupaten' => 'Murung Raya',
                    'kecamatan' => 'Murung',
                    'desa' => 'Beriwit',
                    'kode_pos' => '73911',
                    'no_hp' => '081234567895',
                ],
                'pendidikan' => [
                    'nim' => 'KHM20260005',
                    'universitas' => 'Universitas Palangka Raya',
                    'fakultas' => 'Fakultas Keguruan dan Ilmu Pendidikan',
                    'jurusan' => 'Pendidikan Guru Sekolah Dasar',
                    'program_studi' => 'Pendidikan Guru Sekolah Dasar',
                    'jenjang' => 'S1',
                    'semester' => 3,
                    'ipk' => 3.48,
                    'nominal_ukt' => 2000000,
                    'tahun_masuk' => 2024,
                    'status_perguruan_tinggi' => 'negeri',
                    'akreditasi_perguruan_tinggi' => 'Baik',
                    'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya, Kalimantan Tengah',
                ],
                'orang_tua' => [
                    'nama_ayah' => 'Supriadi',
                    'status_ayah' => 'hidup',
                    'nik_ayah' => '6212010101700007',
                    'pekerjaan_ayah' => 'Petani',
                    'jabatan_ayah' => null,
                    'nama_ibu' => 'Aminah',
                    'status_ibu' => 'hidup',
                    'nik_ibu' => '6212014101720008',
                    'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                    'jabatan_ibu' => null,
                    'memiliki_wali' => false,
                    'pekerjaan_wali' => null,
                    'jabatan_wali' => null,
                ],
                'prestasis' => [],
            ],
        ];
    }

    /**
     * @param  array<int, int>  $jalurIds
     * @return array<int, array<string, mixed>>
     */
    private function generatedStudents(array $jalurIds, Periode $periode): array
    {
        $villages = Village::query()->with('kecamatan')->orderBy('id')->get();
        $types = ApplicationType::cases();
        $universities = [
            'Universitas Palangka Raya',
            'Universitas Lambung Mangkurat',
            'Institut Teknologi Kalimantan',
            'Universitas Muhammadiyah Palangka Raya',
        ];
        $programs = ['Teknik Informatika', 'Ekonomi', 'Pendidikan', 'Kesehatan Masyarakat', 'Akuntansi'];

        $students = [];
        $typeCounters = [];

        foreach (range(1, 28) as $index) {
            $type = $types[$index % count($types)];
            $typeCounters[$type->value] = ($typeCounters[$type->value] ?? 0) + 1;
            $typeCount = $typeCounters[$type->value];

            // 2 mahasiswa pertama untuk setiap jalur berada pada VERIFIKASI_DINAS (ada di antrean operator dinas),
            // sisanya pada SELEKSI_KABUPATEN (siap untuk operator kabupaten)
            $status = $typeCount <= 2
                ? ApplicationStatus::VERIFIKASI_DINAS
                : ApplicationStatus::SELEKSI_KABUPATEN;
            $village = $villages[$index % $villages->count()];
            $isDisability = $type === ApplicationType::DISABILITAS;
            $isPrestasi = $type === ApplicationType::NON_AKADEMIK;
            $semester = fake()->numberBetween(2, 8);
            $ipk = fake()->randomFloat(2, 2.75, 3.95);
            $desil = $type === ApplicationType::TIDAK_MAMPU ? fake()->numberBetween(1, 8) : null;
            $universitas = fake()->randomElement($universities);
            $programStudi = fake()->randomElement($programs);
            $nik = '62'.str_pad((string) $index, 14, '0', STR_PAD_LEFT);
            $nomor = sprintf('KHM-%s-%06d', $periode->tahun, 200 + $index);
            $isOrmawa = $index % 5 === 0;

            $pekerjaanOptions = [
                'Wirausaha',
                'ASN',
                'BUMN / BUMD',
                'TNI / POLRI',
                'Petani',
                'Buruh',
                'Pedagang',
                'Karyawan Swasta',
            ];

            $pekerjaanAyah = fake()->randomElement($pekerjaanOptions);
            $pekerjaanIbu = fake()->randomElement($pekerjaanOptions);

            $verifiedAgencyCount = $status === ApplicationStatus::VERIFIKASI_DINAS
                ? ($typeCount === 1 ? 0 : 1)
                : 0;

            $students[] = [
                'email' => 'mahasiswa'.$index.'@example.test',
                'name' => fake()->name(),
                'nomor_pendaftaran' => $nomor,
                'application_type' => $type,
                'status' => $status,
                'submitted_at' => now()->subDays(fake()->numberBetween(2, 35)),
                'verified_agency_count' => $verifiedAgencyCount,
                'village' => $village,
                'jalur_beasiswa_id' => $jalurIds === [] ? null : $jalurIds[$index % count($jalurIds)],
                'profile' => [
                    'nik' => $nik,
                    'nim' => 'KHM2026'.str_pad((string) ($index + 100), 5, '0', STR_PAD_LEFT),
                    'phone' => '0812'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                    'universitas' => $universitas,
                    'program_studi' => $programStudi,
                    'semester' => $semester,
                    'ipk' => $ipk,
                    'disability_type' => $isDisability ? fake()->randomElement(MahasiswaProfile::DISABILITY_TYPES) : null,
                    'disability_grade' => $isDisability ? fake()->randomElement(MahasiswaProfile::DISABILITY_GRADES) : null,
                    'disability_document_number' => $isDisability
                        ? 'DOK-DIS-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT)
                        : null,
                    'alamat' => fake()->streetAddress(),
                    'status_kependudukan' => 'sesuai',
                    'penghasilan_keluarga' => null,
                    'jumlah_tanggungan' => null,
                    'desil_sosial' => $desil,
                    'prestasi' => $isPrestasi ? 'Juara kejuaraan non-akademik tingkat provinsi.' : null,
                ],
                'data_pribadi' => [
                    'nik' => $nik,
                    'nomor_kk' => '62'.str_pad((string) (5000 + $index), 14, '0', STR_PAD_LEFT),
                    'nomor_rekening' => '6212'.str_pad((string) (7000000 + $index), 10, '0', STR_PAD_LEFT),
                    'nama_lengkap' => fake()->name(),
                    'tempat_lahir' => 'Puruk Cahu',
                    'tanggal_lahir' => fake()->dateTimeBetween('-24 years', '-18 years')->format('Y-m-d'),
                    'jenis_kelamin' => $index % 2 === 0 ? 'L' : 'P',
                    'agama' => fake()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu']),
                    'alamat' => fake()->streetAddress(),
                    'provinsi' => 'Kalimantan Tengah',
                    'kabupaten' => 'Murung Raya',
                    'kecamatan' => $village->kecamatan?->name ?? 'Murung',
                    'desa' => $village->name,
                    'kode_pos' => '73911',
                    'no_hp' => '0812'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                ],
                'pendidikan' => [
                    'nim' => 'KHM2026'.str_pad((string) ($index + 100), 5, '0', STR_PAD_LEFT),
                    'universitas' => $universitas,
                    'fakultas' => 'Fakultas '.$programStudi,
                    'jurusan' => $programStudi,
                    'program_studi' => $programStudi,
                    'jenjang' => 'S1',
                    'semester' => $semester,
                    'ipk' => $ipk,
                    'nominal_ukt' => fake()->numberBetween(1000000, 8000000),
                    'tahun_masuk' => 2026 - (int) ceil($semester / 2),
                    'status_perguruan_tinggi' => $index % 2 === 0 ? 'negeri' : 'swasta',
                    'akreditasi_perguruan_tinggi' => fake()->randomElement(['Unggul', 'Baik Sekali', 'Baik']),
                    'alamat_perguruan_tinggi' => fake()->streetAddress().', Palangka Raya, Kalimantan Tengah',
                ],
                'orang_tua' => [
                    'nama_ayah' => fake()->name('male'),
                    'status_ayah' => 'hidup',
                    'nik_ayah' => '62120101'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                    'pekerjaan_ayah' => $pekerjaanAyah,
                    'jabatan_ayah' => $pekerjaanAyah === 'ASN'
                        ? fake()->randomElement(['Staf Pelaksana', 'Kepala Bidang', 'Sekretaris Dinas', 'Guru'])
                        : null,
                    'nama_ibu' => fake()->name('female'),
                    'status_ibu' => 'hidup',
                    'nik_ibu' => '62120102'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                    'pekerjaan_ibu' => $pekerjaanIbu,
                    'jabatan_ibu' => $pekerjaanIbu === 'ASN'
                        ? fake()->randomElement(['Staf Pelaksana', 'Kepala Bidang', 'Sekretaris Dinas', 'Guru'])
                        : null,
                    'memiliki_wali' => false,
                    'pekerjaan_wali' => null,
                    'jabatan_wali' => null,
                ],
                'prestasis' => $isPrestasi ? [
                    [
                        'jenis' => 'non_akademik',
                        'nama_prestasi' => $isOrmawa
                            ? fake()->randomElement(['Badan Eksekutif Mahasiswa', 'Himpunan Mahasiswa Jurusan', 'Unit Kegiatan Mahasiswa'])
                            : fake()->randomElement(['Juara Lomba Debat', 'Juara Lomba MTQ', 'Juara Lomba Futsal', 'Juara Lomba Karya Tulis']),
                        'tingkat' => $isOrmawa
                            ? 'kampus'
                            : fake()->randomElement(['kampus', 'kabupaten', 'provinsi', 'nasional']),
                        'peringkat' => $isOrmawa
                            ? 'Pengurus Organisasi'
                            : fake()->randomElement(['juara 1', 'juara 2', 'juara 3', 'harapan', 'favorit', 'peserta']),
                        'is_pengurus_inti_ormawa' => $isOrmawa,
                        'jabatan_ormawa' => $isOrmawa
                            ? fake()->randomElement(['Ketua', 'Sekretaris', 'Bendahara'])
                            : null,
                        'penyelenggara' => $isOrmawa ? $universitas : 'Penyelenggara Demo',
                        'tahun' => 2025,
                        'dokumen_prestasi' => null,
                        'keterangan' => null,
                    ],
                ] : [],
            ];
        }

        return $students;
    }
}
