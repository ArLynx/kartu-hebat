<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ResetAndSeedApprovalQueuesSeeder extends Seeder
{
    public function run(): void
    {
        $period = Periode::query()->aktif()->orderByDesc('tanggal_mulai')->first()
            ?? Periode::query()->latest('id')->firstOrFail();
        $village = Village::query()->with('kecamatan')->firstOrFail();
        $category = KategoriBeasiswa::query()
            ->where('periode_id', $period->id)
            ->where('aktif', true)
            ->whereNotNull('application_type')
            ->first()
            ?? KategoriBeasiswa::query()->where('periode_id', $period->id)->firstOrFail();
        $applicationType = $category->application_type instanceof ApplicationType
            ? $category->application_type
            : ApplicationType::AKADEMIK;

        DB::transaction(function () use ($period, $village, $category, $applicationType): void {
            $this->deleteApplicationsAndWorkflowData();

            $queues = [
                [UserRole::OPERATOR_DESA, ApplicationStatus::VERIFIKASI_DESA],
                [UserRole::OPERATOR_KECAMATAN, ApplicationStatus::VERIFIKASI_KECAMATAN],
                [UserRole::OPERATOR_DUKCAPIL, ApplicationStatus::VERIFIKASI_DINAS],
                [UserRole::OPERATOR_SOSIAL, ApplicationStatus::VERIFIKASI_DINAS],
                [UserRole::OPERATOR_PENDIDIKAN, ApplicationStatus::VERIFIKASI_DINAS],
                [UserRole::OPERATOR_KABUPATEN, ApplicationStatus::SELEKSI_KABUPATEN],
            ];

            foreach ($queues as $index => [$role, $status]) {
                $student = User::query()->updateOrCreate(
                    ['email' => 'antrian.'.strtolower($role->value).'@kartuhebat.test'],
                    [
                        'name' => 'Mahasiswa Antrian '.($index + 1),
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
                    ['user_id' => $student->id],
                    [
                        'nik' => '629999000000'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                        'nim' => 'ANTRIAN'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                        'phone' => '08129999000'.($index + 1),
                        'universitas' => 'Universitas Demo Murung Raya',
                        'program_studi' => 'Teknik Informatika',
                        'semester' => 5,
                        'ipk' => 3.50,
                        'alamat' => 'Alamat demo antrian persetujuan',
                        'village_id' => $village->id,
                        'status_kependudukan' => 'sesuai',
                        'penghasilan_keluarga' => 2500000,
                        'jumlah_tanggungan' => 4,
                    ],
                );

                $number = sprintf('KHM-%s-QUEUE-%02d', $period->tahun, $index + 1);
                $registration = Pendaftaran::query()->create([
                    'user_id' => $student->id,
                    'periode_id' => $period->id,
                    'kategori_beasiswa_id' => $category->id,
                    'nomor_pendaftaran' => $number,
                    'status' => 'verification',
                    'submitted_at' => now()->subHour(),
                ]);

                Application::query()->create([
                    'pendaftaran_id' => $registration->id,
                    'nomor_pengajuan' => $number,
                    'mahasiswa_id' => $student->id,
                    'periode' => $period->nama ?: (string) $period->tahun,
                    'application_type' => $applicationType,
                    'status' => $status,
                    'submitted_at' => now()->subHour(),
                    'locked_at' => now(),
                    'catatan' => 'Pengajuan demo menunggu pemeriksaan '.strtolower($role->label()).'.',
                ]);
            }
        });

        Storage::disk(config('kartu_hebat.document_disk', 'local'))->deleteDirectory('applications');
    }

    private function deleteApplicationsAndWorkflowData(): void
    {
        DB::table('documents')->delete();
        DB::table('village_verifications')->delete();
        DB::table('district_verifications')->delete();
        DB::table('agency_verifications')->delete();
        DB::table('verification_logs')->delete();
        DB::table('application_scores')->delete();
        DB::table('selections')->delete();
        DB::table('applications')->delete();
        DB::table('dokumens')->delete();
        DB::table('prestasis')->delete();
        DB::table('orang_tuas')->delete();
        DB::table('pendidikans')->delete();
        DB::table('data_pribadis')->delete();
        DB::table('pendaftarans')->delete();
    }
}
