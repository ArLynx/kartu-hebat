<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Prestasi;
use App\Models\User;
use App\Models\Village;
use App\Services\SelectionScoringService;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\PrestasiTrackSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PrestasiScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);
        $this->seed(PrestasiTrackSeeder::class);
    }

    public function test_prestasi_track_weights_ipk_level_and_rank(): void
    {
        $application = $this->application(
            profileOverrides: ['ipk' => 3.20],
            tingkat: 'nasional',
            peringkat: 'juara 1',
        );

        $selection = app(SelectionScoringService::class)->calculate($application);

        $codes = $application->scores()->with('criterion')->get()->pluck('criterion.code')->sort()->values()->all();
        $this->assertSame(['achievement_level', 'achievement_rank', 'ipk'], $codes);

        $expectedIpk = (3.20 / 4) * 100 * 0.25;
        $expectedLevel = 80 * 0.45;
        $expectedRank = 100 * 0.30;
        $expectedTotal = $expectedIpk + $expectedLevel + $expectedRank;

        $this->assertEqualsWithDelta($expectedTotal, (float) $selection->final_score, 0.0001);
    }

    public function test_higher_tingkat_outranks_lower_with_same_rank(): void
    {
        $internasional = $this->application(
            profileOverrides: ['ipk' => 3.0],
            tingkat: 'internasional',
            peringkat: 'juara 2',
        );
        $kabupaten = $this->application(
            profileOverrides: ['ipk' => 3.0],
            tingkat: 'kabupaten',
            peringkat: 'juara 1',
        );

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($internasional);
        $scoring->calculate($kabupaten);
        $scoring->recalculateRanking();

        $this->assertSame(1, $internasional->selection()->firstOrFail()->rank);
        $this->assertSame(2, $kabupaten->selection()->firstOrFail()->rank);
    }

    public function test_tingkat_internasional_mengalahkan_juara_3_kabupaten(): void
    {
        $juara3Kabupaten = $this->application(
            profileOverrides: ['ipk' => 3.0],
            tingkat: 'kabupaten',
            peringkat: 'juara 3',
        );
        $pesertaInternasional = $this->application(
            profileOverrides: ['ipk' => 3.0],
            tingkat: 'internasional',
            peringkat: 'peserta',
        );

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($juara3Kabupaten);
        $scoring->calculate($pesertaInternasional);
        $scoring->recalculateRanking();

        $this->assertSame(1, $pesertaInternasional->selection()->firstOrFail()->rank);
        $this->assertSame(2, $juara3Kabupaten->selection()->firstOrFail()->rank);
    }

    public function test_missing_prestasi_throws_validation_exception(): void
    {
        $application = $this->application(
            profileOverrides: ['ipk' => 3.0],
            skipPrestasi: true,
        );

        $this->expectException(ValidationException::class);
        app(SelectionScoringService::class)->calculate($application);
    }

    public function test_prestasi_track_ranked_separately_from_akademik(): void
    {
        $nonAkademik = $this->application([
            'ipk' => 3.5,
            'tingkat' => 'nasional',
            'peringkat' => 'juara 1',
        ]);
        $akademik = $this->application(
            profileOverrides: ['ipk' => 3.5, 'semester' => 6],
            applicationType: ApplicationType::AKADEMIK,
        );

        $scoring = app(SelectionScoringService::class);
        $scoring->calculate($nonAkademik);
        $scoring->calculate($akademik);
        $scoring->recalculateRanking();

        $this->assertSame(1, $nonAkademik->selection()->firstOrFail()->rank);
        $this->assertSame(1, $akademik->selection()->firstOrFail()->rank);
    }

    private function application(
        array $profileOverrides = [],
        ?ApplicationType $applicationType = null,
        bool $skipPrestasi = false,
        ?string $tingkat = null,
        ?string $peringkat = null,
    ): Application {
        $type = $applicationType ?? ApplicationType::NON_AKADEMIK;
        $village = Village::query()->firstOrFail();

        $student = User::factory()->create([
            'role' => UserRole::MAHASISWA,
            'village_id' => $village->id,
            'kecamatan_id' => $village->kecamatan_id,
            'kabupaten_id' => $village->kabupaten_id,
        ]);

        $profileData = array_merge([
            'user_id' => $student->id,
            'nik' => fake()->unique()->numerify('6212############'),
            'nim' => fake()->unique()->bothify('KHM-NON-#####'),
            'universitas' => 'Universitas Uji',
            'program_studi' => 'Ilmu Komputer',
            'semester' => 4,
            'ipk' => 3.00,
            'alamat' => 'Alamat pengujian non-akademik',
            'village_id' => $village->id,
        ], $profileOverrides);

        unset($profileData['tingkat'], $profileData['peringkat'], $profileData['skip_prestasi']);

        MahasiswaProfile::query()->create($profileData);

        $application = Application::query()->create([
            'nomor_pengajuan' => 'KHM-NON-'.fake()->unique()->numerify('######'),
            'mahasiswa_id' => $student->id,
            'periode' => config('kartu_hebat.current_period'),
            'application_type' => $type,
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);

        if (! $skipPrestasi) {
            $periode = Periode::query()->firstOrCreate(
                ['nama' => config('kartu_hebat.current_period')],
                [
                    'tahun' => (int) now()->format('Y'),
                    'tanggal_mulai' => now()->startOfYear(),
                    'tanggal_selesai' => now()->endOfYear(),
                    'status' => 'aktif',
                ],
            );

            $pendaftaran = Pendaftaran::query()->create([
                'user_id' => $student->id,
                'periode_id' => $periode->id,
                'kategori_beasiswa_id' => KategoriBeasiswa::query()->where('application_type', $type->value)->firstOrFail()->id,
                'nomor_pendaftaran' => 'KHM-REG-'.fake()->unique()->numerify('######'),
                'status' => 'verification',
                'submitted_at' => now(),
            ]);

            Prestasi::query()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'jenis' => 'non_akademik',
                'nama_prestasi' => 'Juara MTQ Tingkat Nasional',
                'tingkat' => $tingkat ?? 'kabupaten',
                'peringkat' => $peringkat ?? 'juara 1',
                'penyelenggara' => 'KEMENAG',
                'tahun' => (int) now()->format('Y'),
            ]);

            $application->update(['pendaftaran_id' => $pendaftaran->id]);
        }

        return $application->fresh(['pendaftaran.prestasis']);
    }
}
