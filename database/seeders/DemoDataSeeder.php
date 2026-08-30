<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\DocumentType;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\MahasiswaProfile;
use App\Models\User;
use App\Models\Village;
use App\Services\DocumentVerificationService;
use App\Services\SelectionScoringService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $kabupaten = Kabupaten::query()->firstOrFail();
        $kecamatan = Kecamatan::query()->firstOrFail();
        $village = Village::query()->where('kecamatan_id', $kecamatan->id)->firstOrFail();

        $operators = [
            [UserRole::OPERATOR_DESA, 'Operator Desa', 'desa@kartuhebat.test', $village->id, $kecamatan->id],
            [UserRole::OPERATOR_KECAMATAN, 'Operator Kecamatan', 'kecamatan@kartuhebat.test', null, $kecamatan->id],
            [UserRole::OPERATOR_DUKCAPIL, 'Operator Dukcapil', 'dukcapil@kartuhebat.test', null, null],
            [UserRole::OPERATOR_SOSIAL, 'Operator Dinas Sosial', 'sosial@kartuhebat.test', null, null],
            [UserRole::OPERATOR_PENDIDIKAN, 'Operator Dinas Pendidikan', 'pendidikan@kartuhebat.test', null, null],
            [UserRole::OPERATOR_DINKES, 'Operator Dinas Kesehatan', 'dinkes@kartuhebat.test', null, null],
            [UserRole::OPERATOR_PARSEPOR, 'Operator Dinas Parsepor', 'parsepor@kartuhebat.test', null, null],
            [UserRole::OPERATOR_KABUPATEN, 'Operator Kabupaten', 'kabupaten@kartuhebat.test', null, null],
        ];

        User::query()->updateOrCreate(
            ['email' => 'superadmin@kartuhebat.test'],
            [
                'name' => 'Superadmin Kartu Hebat',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::SUPERADMIN,
                'status' => 'active',
                'kabupaten_id' => $kabupaten->id,
            ],
        );

        $operatorUsers = [];

        foreach ($operators as [$role, $name, $email, $villageId, $kecamatanId]) {
            $operatorUsers[$role->value] = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'role' => $role,
                    'status' => 'active',
                    'village_id' => $villageId,
                    'kecamatan_id' => $kecamatanId,
                    'kabupaten_id' => $kabupaten->id,
                ],
            );
        }

        $demo = User::query()->updateOrCreate(
            ['email' => 'mahasiswa@kartuhebat.test'],
            [
                'name' => 'Andi Saputra',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $kecamatan->id,
                'kabupaten_id' => $kabupaten->id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $demo->id],
            [
                'nik' => '6212010101010001',
                'nim' => 'KHM20260001',
                'phone' => '081234567890',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Teknik Informatika',
                'semester' => 5,
                'ipk' => 3.62,
                'alamat' => 'Jl. Pendidikan, Kabupaten Murung Raya',
                'village_id' => $village->id,
                'status_kependudukan' => 'sesuai',
                'penghasilan_keluarga' => 2500000,
                'jumlah_tanggungan' => 4,
                'prestasi' => 'Finalis lomba inovasi teknologi tingkat provinsi.',
            ],
        );

        $demoApplication = Application::query()->updateOrCreate(
            ['mahasiswa_id' => $demo->id, 'periode' => config('kartu_hebat.current_period')],
            [
                'nomor_pengajuan' => 'KHM-'.now()->format('Y').'-000128',
                'application_type' => ApplicationType::AKADEMIK,
                'status' => ApplicationStatus::VERIFIKASI_DINAS,
                'catatan' => null,
                'submitted_at' => now()->subDays(4),
            ],
        );

        $this->seedDocuments($demoApplication, $demo);

        $disabilityDemo = User::query()->updateOrCreate(
            ['email' => 'mahasiswa.disabilitas@kartuhebat.test'],
            [
                'name' => 'Rina Kusuma',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $disabilityDemo->id],
            [
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
                'village_id' => $village->id,
                'status_kependudukan' => 'sesuai',
                'penghasilan_keluarga' => 3500000,
                'jumlah_tanggungan' => 3,
                'desil_sosial' => null,
                'desil_pendidikan' => null,
            ],
        );

        $disabilityApplication = Application::query()->updateOrCreate(
            ['mahasiswa_id' => $disabilityDemo->id, 'periode' => config('kartu_hebat.current_period')],
            [
                'nomor_pengajuan' => 'KHM-'.now()->format('Y').'-000129',
                'application_type' => ApplicationType::DISABILITAS,
                'status' => ApplicationStatus::VERIFIKASI_DINAS,
                'catatan' => null,
                'submitted_at' => now()->subDays(3),
            ],
        );

        $this->seedDocuments($disabilityApplication, $disabilityDemo);

        $prestasiDemo = User::query()->updateOrCreate(
            ['email' => 'mahasiswa.prestasi@kartuhebat.test'],
            [
                'name' => 'Budi Hartono',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => UserRole::MAHASISWA,
                'status' => 'active',
                'village_id' => $village->id,
                'kecamatan_id' => $village->kecamatan_id,
                'kabupaten_id' => $village->kabupaten_id,
            ],
        );

        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $prestasiDemo->id],
            [
                'nik' => '6212010101010004',
                'nim' => 'KHM20260004',
                'phone' => '081234567894',
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Ilmu Komunikasi',
                'semester' => 5,
                'ipk' => 3.61,
                'alamat' => 'Jl. Yos Sudarso No. 88, Kabupaten Murung Raya',
                'village_id' => $village->id,
                'status_kependudukan' => 'sesuai',
                'penghasilan_keluarga' => 5000000,
                'jumlah_tanggungan' => 3,
                'prestasi' => 'Juara 1 Debat Bahasa Indonesia Tingkat Nasional (2025)',
            ],
        );

        $prestasiApplication = Application::query()->updateOrCreate(
            ['mahasiswa_id' => $prestasiDemo->id, 'periode' => config('kartu_hebat.current_period')],
            [
                'nomor_pengajuan' => 'KHM-'.now()->format('Y').'-000130',
                'application_type' => ApplicationType::NON_AKADEMIK,
                'status' => ApplicationStatus::VERIFIKASI_DINAS,
                'catatan' => null,
                'submitted_at' => now()->subDays(2),
            ],
        );

        $this->seedDocuments($prestasiApplication, $prestasiDemo);

        $statuses = [
            ApplicationStatus::VERIFIKASI_DINAS,
            ApplicationStatus::SELEKSI_KABUPATEN,
            ApplicationStatus::DITERIMA,
            ApplicationStatus::DITOLAK,
            ApplicationStatus::TMS,
        ];

        $villages = Village::query()->with('kecamatan')->get();

        for ($i = 1; $i <= 28; $i++) {
            $studentVillage = $villages[$i % $villages->count()];
            $status = $statuses[$i % count($statuses)];
            $applicationType = $i % 2 === 0 ? ApplicationType::AKADEMIK : ApplicationType::TIDAK_MAMPU;
            $desilSosial = fake()->numberBetween(1, 10);
            $desilPendidikan = min(10, max(1, $desilSosial + fake()->numberBetween(-1, 1)));

            $student = User::factory()->create([
                'name' => fake()->name(),
                'email' => 'mahasiswa'.$i.'@example.test',
                'role' => UserRole::MAHASISWA,
                'village_id' => $studentVillage->id,
                'kecamatan_id' => $studentVillage->kecamatan_id,
                'kabupaten_id' => $studentVillage->kabupaten_id,
            ]);

            MahasiswaProfile::query()->create([
                'user_id' => $student->id,
                'nik' => '62'.str_pad((string) $i, 14, '0', STR_PAD_LEFT),
                'nim' => 'KHM'.now()->format('Y').str_pad((string) ($i + 100), 5, '0', STR_PAD_LEFT),
                'phone' => '0812'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'universitas' => fake()->randomElement([
                    'Universitas Palangka Raya',
                    'Universitas Lambung Mangkurat',
                    'Institut Teknologi Kalimantan',
                ]),
                'program_studi' => fake()->randomElement(['Teknik Informatika', 'Ekonomi', 'Pendidikan', 'Kesehatan Masyarakat']),
                'semester' => fake()->numberBetween(2, 8),
                'ipk' => fake()->randomFloat(2, 2.75, 3.95),
                'alamat' => fake()->streetAddress(),
                'village_id' => $studentVillage->id,
                'status_kependudukan' => 'sesuai',
                'penghasilan_keluarga' => fake()->numberBetween(1200000, 6500000),
                'jumlah_tanggungan' => fake()->numberBetween(2, 7),
                'desil_sosial' => $desilSosial,
                'desil_pendidikan' => $desilPendidikan,
                'prestasi' => $i % 3 === 0 ? 'Prestasi akademik/nonakademik terverifikasi.' : null,
            ]);

            $application = Application::query()->create([
                'nomor_pengajuan' => sprintf('KHM-%s-%06d', now()->format('Y'), 200 + $i),
                'mahasiswa_id' => $student->id,
                'periode' => config('kartu_hebat.current_period'),
                'application_type' => $applicationType,
                'status' => $status,
                'submitted_at' => now()->subDays(fake()->numberBetween(2, 35)),
                'locked_at' => now(),
            ]);

            $this->seedVerificationRecords($application, $status, $operatorUsers);

            if (in_array($status, [
                ApplicationStatus::SELEKSI_KABUPATEN,
                ApplicationStatus::DITERIMA,
                ApplicationStatus::DITOLAK,
            ], true)) {
                app(SelectionScoringService::class)->calculate($application, $operatorUsers[UserRole::OPERATOR_KABUPATEN->value]->id);

                if ($status !== ApplicationStatus::SELEKSI_KABUPATEN) {
                    $application->selection()->update([
                        'manual_decision' => $status->value,
                        'decided_by' => $operatorUsers[UserRole::OPERATOR_KABUPATEN->value]->id,
                        'decided_at' => now()->subDay(),
                        'published_at' => now()->subHours(3),
                    ]);
                }
            }
        }

        app(SelectionScoringService::class)->recalculateRanking();

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
                    'created_by' => $operatorUsers[UserRole::OPERATOR_KABUPATEN->value]->id,
                ],
            );
        }
    }

    private function seedDocuments(Application $application, User $student): void
    {
        foreach (DocumentType::query()
            ->where('is_required', true)
            ->where(function ($query) use ($application): void {
                $query->whereNull('application_type');

                if ($application->application_type) {
                    $query->orWhere('application_type', $application->application_type->value);
                }
            })
            ->get() as $type) {
            $path = 'applications/'.$application->id.'/'.$type->code.'/demo-'.$type->code.'.pdf';
            $contents = "%PDF-1.4\n% Dokumen demo {$type->name}\n";
            Storage::disk(config('kartu_hebat.document_disk'))->put($path, $contents);

            $application->documents()->updateOrCreate(
                ['document_type_id' => $type->id],
                [
                    'uploaded_by' => $student->id,
                    'path' => $path,
                    'original_name' => strtolower($type->code).'-andi-saputra.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => strlen($contents),
                    'checksum' => hash('sha256', $contents),
                    'version' => 1,
                ],
            );
        }
    }

    private function seedVerificationRecords(Application $application, ApplicationStatus $status, array $operators): void
    {
        if ($status->progress() < ApplicationStatus::SELEKSI_KABUPATEN->progress()) {
            return;
        }

        $required = DocumentVerificationService::requiredAgencies($application);

        foreach ($operators as $roleValue => $operator) {
            $role = UserRole::tryFrom($roleValue);
            if (! $role?->isAgency() || ! in_array($role->agencyCode(), $required, true)) {
                continue;
            }

            $desil = match ($role) {
                UserRole::OPERATOR_SOSIAL => $application->mahasiswa->profile?->desil_sosial,
                UserRole::OPERATOR_PENDIDIKAN => $application->mahasiswa->profile?->desil_pendidikan,
                default => null,
            };

            AgencyVerification::query()->create([
                'application_id' => $application->id,
                'verifier_id' => $operator->id,
                'agency' => $role->agencyCode(),
                'decision' => VerificationDecision::MS,
                'score' => fake()->numberBetween(70, 95),
                'notes' => 'Hasil verifikasi instansi sesuai.',
                'metadata' => $desil === null ? [] : ['desil' => $desil],
                'verified_at' => now()->subDay(),
            ]);
        }
    }
}
