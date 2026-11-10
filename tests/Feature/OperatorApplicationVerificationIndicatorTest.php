<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorApplicationVerificationIndicatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->travelTo('2026-11-11');
    }

    public function test_application_verified_by_another_verifier_in_the_same_agency_is_marked_as_verified(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $otherDukcapilVerifier = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL, 'Verifikator Dukcapil Lain');
        $application = $this->applicationFor($this->student(), ApplicationStatus::VERIFIKASI_DINAS);

        $this->agencyDecision($application, $otherDukcapilVerifier, UserRole::OPERATOR_DUKCAPIL, VerificationDecision::MS);

        $this->actingAs($dukcapil)
            ->get(route('operator.applications.index'))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertSee('Sudah diverifikasi')
            ->assertDontSee('Belum diverifikasi');
    }

    public function test_application_without_agency_verification_is_marked_as_not_verified(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $application = $this->applicationFor($this->student(), ApplicationStatus::VERIFIKASI_DINAS);

        $this->actingAs($dukcapil)
            ->get(route('operator.applications.index'))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertSee('Belum diverifikasi')
            ->assertDontSee('Sudah diverifikasi');
    }

    public function test_verification_from_another_agency_does_not_mark_the_current_agency_as_verified(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $pendidikan = $this->agencyOperator(UserRole::OPERATOR_PENDIDIKAN);
        $application = $this->applicationFor($this->student(), ApplicationStatus::VERIFIKASI_DINAS);

        $this->agencyDecision($application, $pendidikan, UserRole::OPERATOR_PENDIDIKAN, VerificationDecision::MS);

        $this->actingAs($dukcapil)
            ->get(route('operator.applications.index'))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertSee('Belum diverifikasi')
            ->assertDontSee('Sudah diverifikasi');
    }

    public function test_non_agency_operator_does_not_see_the_agency_verification_column(): void
    {
        $kabupaten = $this->agencyOperator(UserRole::OPERATOR_KABUPATEN);
        $application = $this->applicationFor($this->student(), ApplicationStatus::SELEKSI_KABUPATEN);

        $this->actingAs($kabupaten)
            ->get(route('operator.applications.index'))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertDontSee('Status Verifikasi')
            ->assertDontSee('Sudah diverifikasi')
            ->assertDontSee('Belum diverifikasi');
    }

    private function mainKabupaten(): int
    {
        return (int) Village::query()->firstOrFail()->kabupaten_id;
    }

    private function agencyOperator(UserRole $role, ?string $name = null): User
    {
        $operator = User::factory()->create([
            'name' => $name ?? 'Operator '.$role->label(),
            'role' => $role,
            'kabupaten_id' => $this->mainKabupaten(),
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function student(string $name = 'Mahasiswa Verifikasi'): User
    {
        $village = Village::query()
            ->where('kabupaten_id', $this->mainKabupaten())
            ->firstOrFail();

        $student = User::factory()->create([
            'name' => $name,
            'role' => UserRole::MAHASISWA,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212123456789'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT),
            'nim' => 'KHM-VER-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jalan Pengujian',
        ]);

        return $student;
    }

    private function applicationFor(User $student, ApplicationStatus $status): Application
    {
        $periode = Periode::query()->create([
            'tahun' => 2026,
            'nama' => config('kartu_hebat.current_period'),
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'KAT-VER-'.$student->id,
            'application_type' => ApplicationType::AKADEMIK,
            'nama' => 'Kategori Pengujian',
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-REG-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'status' => 'verification',
            'submitted_at' => now(),
        ]);

        return Application::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $pendaftaran->nomor_pendaftaran,
            'periode' => config('kartu_hebat.current_period'),
            'status' => $status,
            'application_type' => ApplicationType::AKADEMIK,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }

    private function agencyDecision(
        Application $application,
        User $verifier,
        UserRole $role,
        VerificationDecision $decision,
    ): AgencyVerification {
        return AgencyVerification::query()->create([
            'application_id' => $application->id,
            'verifier_id' => $verifier->id,
            'agency' => $role->agencyCode(),
            'decision' => $decision,
            'notes' => 'Dokumen sesuai.',
            'verified_at' => now(),
        ]);
    }
}
