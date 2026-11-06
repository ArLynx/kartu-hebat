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
use App\Models\Selection;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicResultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->travelTo('2026-11-11');
    }

    public function test_published_result_from_closed_period_is_found_by_application_number(): void
    {
        $periode = $this->periode([
            'tahun' => 2024,
            'nama' => 'Periode Beasiswa 2024',
            'tanggal_mulai' => '2024-01-01',
            'tanggal_selesai' => '2024-12-31',
            'status' => 'ditutup',
        ]);

        $application = $this->applicationFor(
            $this->student(),
            $periode,
            ApplicationStatus::DITERIMA,
            'KHM-2024-000001',
        );
        $this->publish($application, '2025-01-10 09:00:00');

        $this->get(route('public.results', ['nomor' => $application->nomor_pengajuan]))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertSee('Diterima');
    }

    public function test_published_result_from_closed_period_is_found_by_nik(): void
    {
        $periode = $this->periode([
            'tahun' => 2024,
            'nama' => 'Periode Beasiswa 2024',
            'tanggal_mulai' => '2024-01-01',
            'tanggal_selesai' => '2024-12-31',
            'status' => 'ditutup',
        ]);

        $nik = '6212123456789012';
        $application = $this->applicationFor(
            $this->student($nik),
            $periode,
            ApplicationStatus::DITOLAK,
            'KHM-2024-000002',
        );
        $this->publish($application, '2025-01-10 09:00:00');

        $this->get(route('public.results', ['nomor' => $nik]))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertSee('Ditolak');
    }

    public function test_most_recently_published_result_is_shown_when_multiple_results_match(): void
    {
        $student = $this->student('6212123456789012');

        $oldPeriode = $this->periode([
            'tahun' => 2024,
            'nama' => 'Periode Beasiswa 2024',
            'tanggal_mulai' => '2024-01-01',
            'tanggal_selesai' => '2024-12-31',
            'status' => 'ditutup',
        ]);
        $oldApplication = $this->applicationFor(
            $student,
            $oldPeriode,
            ApplicationStatus::DITOLAK,
            'KHM-2024-000003',
        );
        $this->publish($oldApplication, '2025-01-10 09:00:00');

        $newPeriode = $this->periode([
            'tahun' => 2025,
            'nama' => 'Periode Beasiswa 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'status' => 'ditutup',
        ]);
        $newApplication = $this->applicationFor(
            $student,
            $newPeriode,
            ApplicationStatus::DITERIMA,
            'KHM-2025-000003',
        );
        $this->publish($newApplication, '2026-02-10 09:00:00');

        $this->get(route('public.results', ['nomor' => '6212123456789012']))
            ->assertOk()
            ->assertSee($newApplication->nomor_pengajuan)
            ->assertDontSee($oldApplication->nomor_pengajuan)
            ->assertSee('Diterima')
            ->assertDontSee('Ditolak');
    }

    public function test_unpublished_result_does_not_shadow_an_older_published_result(): void
    {
        $student = $this->student('6212123456789012');

        $publishedPeriode = $this->periode([
            'tahun' => 2024,
            'nama' => 'Periode Beasiswa 2024',
            'tanggal_mulai' => '2024-01-01',
            'tanggal_selesai' => '2024-12-31',
            'status' => 'ditutup',
        ]);
        $published = $this->applicationFor(
            $student,
            $publishedPeriode,
            ApplicationStatus::DITERIMA,
            'KHM-2024-000005',
        );
        $this->publish($published, '2025-01-10 09:00:00');

        $draftPeriode = $this->periode([
            'tahun' => 2026,
            'nama' => 'Periode Beasiswa 2026',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);
        $unpublished = $this->applicationFor(
            $student,
            $draftPeriode,
            ApplicationStatus::SELEKSI_KABUPATEN,
            'KHM-2026-000005',
        );
        Selection::query()->create([
            'application_id' => $unpublished->id,
            'final_score' => 90.0,
            'rank' => 1,
        ]);

        $this->get(route('public.results', ['nomor' => '6212123456789012']))
            ->assertOk()
            ->assertSee($published->nomor_pengajuan)
            ->assertDontSee($unpublished->nomor_pengajuan);
    }

    public function test_schedule_panel_shows_belum_dijadwalkan_when_dates_are_empty(): void
    {
        $this->periode(['status' => 'aktif']);

        $this->get(route('public.results'))
            ->assertOk()
            ->assertSee('Belum dijadwalkan')
            ->assertSee('Jadwal masa sanggah belum diatur oleh pengelola.')
            ->assertDontSee('Belum dimulai')
            ->assertDontSee('Sedang berlangsung')
            ->assertDontSee('Telah berakhir');
    }

    public function test_schedule_panel_still_appears_when_there_is_no_period_at_all(): void
    {
        $this->get(route('public.results'))
            ->assertOk()
            ->assertSee('Masa Sanggah')
            ->assertSee('Belum dijadwalkan')
            ->assertSee('Belum ada periode aktif')
            ->assertSee('Jadwal masa sanggah belum diatur oleh pengelola.');
    }

    public function test_schedule_panel_shows_belum_dimulai_before_the_start_date(): void
    {
        $this->periode([
            'status' => 'aktif',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        $this->travelTo('2026-11-09');

        $this->get(route('public.results'))
            ->assertOk()
            ->assertSee('Belum dimulai')
            ->assertSee('Masa sanggah belum dimulai')
            ->assertDontSee('Sedang berlangsung')
            ->assertDontSee('Masa sanggah telah berakhir');
    }

    public function test_schedule_panel_shows_sedang_berlangsung_within_the_date_range(): void
    {
        $this->periode([
            'status' => 'aktif',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        $this->travelTo('2026-11-11');

        $this->get(route('public.results'))
            ->assertOk()
            ->assertSee('Sedang berlangsung')
            ->assertSee('10 Nov 2026')
            ->assertSee('12 Nov 2026')
            ->assertSee('Masa sanggah sedang berlangsung')
            ->assertDontSee('Masa sanggah telah berakhir');
    }

    public function test_schedule_panel_shows_masa_sanggah_telah_berakhir_after_the_end_date(): void
    {
        $this->periode([
            'status' => 'aktif',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        $this->travelTo('2026-11-13');

        $this->get(route('public.results'))
            ->assertOk()
            ->assertSee('Telah berakhir')
            ->assertSee('Masa sanggah telah berakhir')
            ->assertDontSee('Sedang berlangsung')
            ->assertDontSee('Belum dimulai');
    }

    public function test_schedule_panel_uses_the_latest_active_period_when_no_result_is_searched(): void
    {
        $this->periode([
            'nama' => 'Periode Aktif Lama',
            'tanggal_mulai_sanggah' => '2026-10-01',
            'tanggal_selesai_sanggah' => '2026-10-31',
        ]);
        $this->periode([
            'nama' => 'Periode Aktif Terbaru',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        $this->get(route('public.results'))
            ->assertOk()
            ->assertSee('Periode Aktif Terbaru')
            ->assertSee('10 Nov 2026')
            ->assertSee('12 Nov 2026')
            ->assertDontSee('Periode Aktif Lama')
            ->assertDontSee('01 Oct 2026');
    }

    public function test_schedule_panel_follows_the_period_of_the_found_result(): void
    {
        $this->periode([
            'nama' => 'Periode Aktif 2026',
            'status' => 'aktif',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        $closedPeriode = $this->periode([
            'tahun' => 2025,
            'nama' => 'Periode Tertutup 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'status' => 'ditutup',
            'tanggal_mulai_sanggah' => '2025-11-01',
            'tanggal_selesai_sanggah' => '2025-11-05',
        ]);

        $application = $this->applicationFor(
            $this->student(),
            $closedPeriode,
            ApplicationStatus::DITOLAK,
            'KHM-2025-000004',
        );
        $this->publish($application, '2026-01-10 09:00:00');

        $this->get(route('public.results', ['nomor' => $application->nomor_pengajuan]))
            ->assertOk()
            ->assertSee('Periode Tertutup 2025')
            ->assertSee('01 Nov 2025')
            ->assertSee('05 Nov 2025')
            ->assertSee('Masa sanggah telah berakhir')
            ->assertDontSee('Periode Aktif 2026')
            ->assertDontSee('10 Nov 2026')
            ->assertDontSee('12 Nov 2026')
            ->assertDontSee('Sedang berlangsung');
    }

    public function test_schedule_panel_does_not_borrow_the_active_period_when_the_result_period_is_missing(): void
    {
        $this->periode([
            'nama' => 'Periode Aktif 2026',
            'status' => 'aktif',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        $legacyPeriode = $this->periode([
            'tahun' => 2024,
            'nama' => 'Periode Lama Tanpa Pendaftaran',
            'tanggal_mulai' => '2024-01-01',
            'tanggal_selesai' => '2024-12-31',
            'status' => 'ditutup',
        ]);

        $application = $this->legacyApplicationWithoutPendaftaran(
            $this->student(),
            'Periode Yang Sudah Tidak Ada',
            'KHM-2024-000006',
        );
        $this->publish($application, '2025-01-10 09:00:00');

        $this->get(route('public.results', ['nomor' => $application->nomor_pengajuan]))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertSee('Periode hasil tidak ditemukan')
            ->assertSee('Belum dijadwalkan')
            ->assertDontSee('Periode Aktif 2026')
            ->assertDontSee('10 Nov 2026')
            ->assertDontSee('12 Nov 2026')
            ->assertDontSee('Periode Lama Tanpa Pendaftaran');

        $this->assertSame('ditutup', $legacyPeriode->fresh()->status);
    }

    public function test_sanggahan_button_always_links_to_the_login_portal(): void
    {
        $scenarios = [
            'belum dijadwalkan' => [null, null, '2026-11-11'],
            'belum dimulai' => ['2026-11-10', '2026-11-12', '2026-11-09'],
            'sedang berlangsung' => ['2026-11-10', '2026-11-12', '2026-11-11'],
            'telah berakhir' => ['2026-11-10', '2026-11-12', '2026-11-13'],
        ];

        foreach ($scenarios as $label => [$mulai, $selesai, $today]) {
            Periode::query()->delete();

            $this->periode([
                'status' => 'aktif',
                'tanggal_mulai_sanggah' => $mulai,
                'tanggal_selesai_sanggah' => $selesai,
            ]);

            $this->travelTo($today);

            $response = $this->get(route('public.results'));

            $response->assertOk();
            $response->assertSee('Ajukan Sanggahan');
            $response->assertDontSee(route('mahasiswa.sanggahan.index'));

            $sanggahanLinks = $this->linksContaining($response->getContent(), 'Ajukan Sanggahan');

            $this->assertCount(1, $sanggahanLinks, "Tombol sanggahan hilang pada keadaan {$label}.");
            $this->assertSame(route('login'), $sanggahanLinks->first()->getAttribute('href'));
        }
    }

    public function test_sanggahan_button_still_links_to_login_when_a_closed_period_result_is_found(): void
    {
        $closedPeriode = $this->periode([
            'tahun' => 2025,
            'nama' => 'Periode Tertutup 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'status' => 'ditutup',
            'tanggal_mulai_sanggah' => '2025-11-01',
            'tanggal_selesai_sanggah' => '2025-11-05',
        ]);

        $application = $this->applicationFor(
            $this->student(),
            $closedPeriode,
            ApplicationStatus::DITOLAK,
            'KHM-2025-000007',
        );
        $this->publish($application, '2026-01-10 09:00:00');

        $response = $this->get(route('public.results', ['nomor' => $application->nomor_pengajuan]));

        $response->assertOk();
        $response->assertSee('Masa sanggah telah berakhir');

        $sanggahanLinks = $this->linksContaining($response->getContent(), 'Ajukan Sanggahan');

        $this->assertCount(1, $sanggahanLinks);
        $this->assertSame(route('login'), $sanggahanLinks->first()->getAttribute('href'));
    }

    public function test_panel_no_longer_shows_simulated_data_notice_or_hardcoded_dates(): void
    {
        $this->periode([
            'status' => 'aktif',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
        ]);

        $this->get(route('public.results'))
            ->assertOk()
            ->assertDontSee('data simulasi')
            ->assertDontSee('10 November 2026')
            ->assertDontSee('12 November 2026')
            ->assertDontSee('Pukul 08.00 WIB')
            ->assertDontSee('Pukul 23.59 WIB');
    }

    private function periode(array $attributes = []): Periode
    {
        return Periode::query()->create(array_merge([
            'tahun' => 2026,
            'nama' => 'Periode Beasiswa '.Str::random(8),
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ], $attributes));
    }

    private function student(?string $nik = null): User
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => Village::query()->firstOrFail()->id,
            'nik' => $nik ?? fake()->unique()->numerify('6212############'),
            'nim' => 'KHM-PUB-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jalan Pengujian',
        ]);

        return $student;
    }

    private function applicationFor(
        User $student,
        Periode $periode,
        ApplicationStatus $status,
        string $nomorPengajuan,
    ): Application {
        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'KAT-'.Str::random(10),
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
            'nomor_pendaftaran' => 'REG-'.Str::random(12),
            'status' => 'verification',
            'submitted_at' => now(),
        ]);

        return Application::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $nomorPengajuan,
            'periode' => $periode->nama,
            'status' => $status,
            'application_type' => ApplicationType::AKADEMIK,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }

    private function legacyApplicationWithoutPendaftaran(
        User $student,
        string $periodeName,
        string $nomorPengajuan,
    ): Application {
        return Application::query()->create([
            'pendaftaran_id' => null,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $nomorPengajuan,
            'periode' => $periodeName,
            'status' => ApplicationStatus::DITERIMA,
            'application_type' => ApplicationType::AKADEMIK,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, \DOMElement>
     */
    private function linksContaining(string $html, string $text): Collection
    {
        $document = new \DOMDocument;
        $previousInternalErrors = libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrors);

        return collect($document->getElementsByTagName('a'))
            ->filter(fn (\DOMElement $anchor): bool => str_contains($anchor->textContent, $text))
            ->values();
    }

    private function publish(Application $application, string $publishedAt): void
    {
        Selection::query()->create([
            'application_id' => $application->id,
            'final_score' => 85.0,
            'rank' => 1,
            'published_at' => $publishedAt,
        ]);
    }
}
