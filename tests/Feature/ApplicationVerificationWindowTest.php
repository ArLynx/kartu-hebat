<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\KategoriBeasiswa;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Sanggahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApplicationVerificationWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_verification_override_keeps_current_period_open_after_registration_closes(): void
    {
        Carbon::setTestNow('2026-11-28 10:00:00');

        $periode = $this->periode();
        $application = $this->applicationFor($periode);

        $this->setVerificationOverride($periode, '2026-11-30');

        $this->assertTrue($application->isVerificationWindowOpen());
        $this->assertFalse(Periode::query()->aktif()->whereKey($periode->id)->exists());
    }

    public function test_verification_override_closes_after_its_deadline(): void
    {
        Carbon::setTestNow('2026-12-01 00:00:00');

        $periode = $this->periode();
        $application = $this->applicationFor($periode);

        $this->setVerificationOverride($periode, '2026-11-30');

        $this->assertFalse($application->isVerificationWindowOpen());
    }

    public function test_without_override_verification_closes_with_registration(): void
    {
        Carbon::setTestNow('2026-11-28 10:00:00');

        $periode = $this->periode();
        $application = $this->applicationFor($periode);

        $this->assertFalse($application->isVerificationWindowOpen());
    }

    public function test_override_only_applies_to_the_configured_period(): void
    {
        Carbon::setTestNow('2026-11-28 10:00:00');

        $targetPeriode = $this->periode(['nama' => 'Periode Target']);
        $otherPeriode = $this->periode(['nama' => 'Periode Lain']);

        $targetApplication = $this->applicationFor($targetPeriode);
        $otherApplication = $this->applicationFor($otherPeriode);

        $this->setVerificationOverride($targetPeriode, '2026-11-30');

        $this->assertTrue($targetApplication->isVerificationWindowOpen());
        $this->assertFalse($otherApplication->isVerificationWindowOpen());
    }

    public function test_granted_sanggahan_remains_verifiable_after_override_deadline(): void
    {
        Carbon::setTestNow('2026-12-01 00:00:00');

        $periode = $this->periode();
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        $application = $this->applicationFor($periode, $student);

        Sanggahan::query()->create([
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'Sanggahan pengujian.',
            'catatan_keputusan' => 'Sanggahan dikabulkan.',
            'decided_at' => now(),
        ]);

        $this->setVerificationOverride($periode, '2026-11-30');

        $this->assertTrue($application->fresh()->isVerificationWindowOpen());
    }

    private function setVerificationOverride(Periode $periode, string $until): void
    {
        config([
            'kartu_hebat.verification_override' => [
                'periode_id' => $periode->id,
                'until' => $until,
            ],
        ]);
    }

    private function periode(array $attributes = []): Periode
    {
        return Periode::query()->create(array_merge([
            'tahun' => 2026,
            'nama' => 'Periode Uji '.fake()->unique()->numerify('####'),
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-11-27',
            'status' => 'aktif',
        ], $attributes));
    }

    private function applicationFor(Periode $periode, ?User $student = null): Application
    {
        $student ??= User::factory()->create(['role' => UserRole::MAHASISWA]);

        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'KAT-'.fake()->unique()->numerify('####'),
            'nama' => 'Kategori Uji',
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-TEST-'.fake()->unique()->numerify('######'),
            'status' => 'verification',
            'submitted_at' => now(),
        ]);

        return Application::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $pendaftaran->nomor_pendaftaran,
            'periode' => $periode->nama,
            'application_type' => ApplicationType::AKADEMIK,
            'status' => ApplicationStatus::VERIFIKASI_DINAS,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }
}
