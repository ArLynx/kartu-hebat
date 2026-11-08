<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\Kabupaten;
use App\Models\KategoriBeasiswa;
use App\Models\Kecamatan;
use App\Models\MahasiswaProfile;
use App\Models\Pendaftaran;
use App\Models\Periode;
use App\Models\Sanggahan;
use App\Models\User;
use App\Models\VerificationLog;
use App\Models\Village;
use App\Services\AgencyVerificationService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperatorSanggahanReverificationQueueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, User>
     */
    private array $agencyOperators = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
        $this->travelTo('2026-11-11');
    }

    public function test_agency_operator_sees_sanggahan_queue_menu_below_the_application_queue(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($dukcapil)
            ->get(route('operator.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Antrean Pengajuan', 'Antrean Sanggahan'])
            ->assertSee(route('operator.sanggahan-queue.index'));
    }

    public function test_kabupaten_operator_keeps_the_sanggahan_decision_menu_without_the_agency_queue(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());

        $this->actingAs($operator)
            ->get(route('operator.dashboard'))
            ->assertOk()
            ->assertDontSee('Antrean Sanggahan')
            ->assertSee(route('operator.sanggahan.index'));
    }

    public function test_queue_only_lists_reopened_applications_from_the_operator_region_and_agency(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $periode = $this->periode();

        $reopened = $this->reopenedApplication($this->student(), $periode, UserRole::OPERATOR_DUKCAPIL);

        $alreadyDecided = $this->reopenedApplication($this->student('Sudah Menilai'), $periode, UserRole::OPERATOR_DUKCAPIL);
        $this->agencyDecision($alreadyDecided, UserRole::OPERATOR_DUKCAPIL, VerificationDecision::MS);

        $otherAgency = $this->reopenedApplication($this->student('Dinas Lain'), $periode, UserRole::OPERATOR_SOSIAL, ApplicationType::TIDAK_MAMPU);

        $otherRegion = $this->reopenedApplication(
            $this->student('Wilayah Lain', $this->otherKabupaten()),
            $periode,
            UserRole::OPERATOR_DUKCAPIL,
        );

        $normal = $this->normalApplication($this->student('Verifikasi Biasa'), $periode);

        $response = $this->actingAs($dukcapil)->get(route('operator.sanggahan-queue.index'));

        $response->assertOk();
        $response->assertSee($reopened->nomor_pengajuan);
        $response->assertDontSee($alreadyDecided->nomor_pengajuan);
        $response->assertDontSee($otherAgency->nomor_pengajuan);
        $response->assertDontSee($otherRegion->nomor_pengajuan);
        $response->assertDontSee($normal->nomor_pengajuan);
        $response->assertSee('Menunggu Penilaian Ulang');
    }

    public function test_reopened_application_is_hidden_from_the_regular_queue_and_leaves_the_sanggahan_queue_after_the_agency_decides(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $periode = $this->periode();
        $application = $this->reopenedApplication($this->student(), $periode, UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($dukcapil)
            ->get(route('operator.applications.index'))
            ->assertOk()
            ->assertDontSee($application->nomor_pengajuan)
            ->assertSee('Buka Antrean Sanggahan')
            ->assertSee('1 pengajuan hasil sanggahan menunggu dinas Anda');

        $this->agencyDecision($application, UserRole::OPERATOR_DUKCAPIL, VerificationDecision::MS);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertOk()
            ->assertDontSee($application->nomor_pengajuan);

        $this->actingAs($dukcapil)
            ->get(route('operator.applications.index'))
            ->assertOk()
            ->assertDontSee($application->nomor_pengajuan)
            ->assertDontSee('Buka Antrean Sanggahan');
    }

    public function test_queue_accepts_a_reopened_application_from_a_historical_period(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $periode = $this->periode([
            'tahun' => 2025,
            'nama' => 'Periode Beasiswa 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-10-31',
            'tanggal_mulai_sanggah' => '2025-11-10',
            'tanggal_selesai_sanggah' => '2025-11-12',
        ]);

        $application = $this->reopenedApplication($this->student(), $periode, UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertOk()
            ->assertSee($application->nomor_pengajuan)
            ->assertSee('Periode Beasiswa 2025');
    }

    public function test_queue_can_be_filtered_by_periode_and_searched_by_nomor_name_nik_or_nim(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $periodeA = $this->periode(['nama' => 'Periode Beasiswa 2026']);
        $periodeB = $this->periode(['tahun' => 2025, 'nama' => 'Periode Beasiswa 2025']);

        $studentA = $this->student('Fajar Pratama');
        $studentB = $this->student('Siti Aminah');

        $applicationA = $this->reopenedApplication($studentA, $periodeA, UserRole::OPERATOR_DUKCAPIL);
        $applicationB = $this->reopenedApplication($studentB, $periodeB, UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index', ['periode_id' => $periodeA->id]))
            ->assertOk()
            ->assertSee($applicationA->nomor_pengajuan)
            ->assertDontSee($applicationB->nomor_pengajuan);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index', ['q' => 'Fajar']))
            ->assertOk()
            ->assertSee($applicationA->nomor_pengajuan)
            ->assertDontSee($applicationB->nomor_pengajuan);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index', ['q' => $studentB->profile->nik]))
            ->assertOk()
            ->assertSee($applicationB->nomor_pengajuan)
            ->assertDontSee($applicationA->nomor_pengajuan);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index', ['q' => $studentA->profile->nim]))
            ->assertOk()
            ->assertSee($applicationA->nomor_pengajuan)
            ->assertDontSee($applicationB->nomor_pengajuan);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index', ['q' => $applicationB->nomor_pengajuan]))
            ->assertOk()
            ->assertSee($applicationB->nomor_pengajuan)
            ->assertDontSee($applicationA->nomor_pengajuan);
    }

    public function test_queue_explains_the_sanggahan_context_and_links_the_application_for_reverification(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $periode = $this->periode();
        $application = $this->reopenedApplication(
            $this->student(),
            $periode,
            UserRole::OPERATOR_DUKCAPIL,
            uraian: 'NIK pada KTP sudah benar, mohon dinilai ulang.',
            catatanKeputusan: 'KTP terbukti sesuai, TMS Dukcapil dianulir.',
        );

        $response = $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index'));

        $response->assertOk();
        $response->assertSee('NIK pada KTP sudah benar, mohon dinilai ulang.');
        $response->assertSee('KTP terbukti sesuai, TMS Dukcapil dianulir.');
        $response->assertSee('NIK tidak sesuai dengan dokumen kependudukan.');
        $response->assertSee(route('operator.applications.show', $application));
        $response->assertSee('Nilai Ulang');
    }

    public function test_application_detail_shows_the_annulled_tms_context_and_links_back_to_the_queue(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);
        $periode = $this->periode();
        $application = $this->reopenedApplication($this->student(), $periode, UserRole::OPERATOR_DUKCAPIL);

        $response = $this->actingAs($dukcapil)
            ->get(route('operator.applications.show', $application));

        $response->assertOk();
        $response->assertSee('Pengajuan dibuka kembali lewat sanggahan');
        $response->assertSee('Uraian Sanggahan');
        $response->assertSee('Catatan Keputusan Kabupaten');
        $response->assertSee('TMS yang Dianulir');
        $response->assertSee('NIK tidak sesuai dengan dokumen kependudukan.');
        $response->assertSee(route('operator.sanggahan-queue.index'));
    }

    public function test_track_bound_agency_only_receives_its_own_application_track(): void
    {
        $sosial = $this->agencyOperator(UserRole::OPERATOR_SOSIAL);
        $periode = $this->periode();

        $tidakMampu = $this->reopenedApplication(
            $this->student('Jalur Tidak Mampu'),
            $periode,
            UserRole::OPERATOR_SOSIAL,
            ApplicationType::TIDAK_MAMPU,
        );

        $akademik = $this->reopenedApplication(
            $this->student('Jalur Akademik'),
            $periode,
            UserRole::OPERATOR_DUKCAPIL,
            ApplicationType::AKADEMIK,
        );

        $this->actingAs($sosial)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertOk()
            ->assertSee($tidakMampu->nomor_pengajuan)
            ->assertDontSee($akademik->nomor_pengajuan);
    }

    public function test_only_agency_operators_can_open_the_sanggahan_queue(): void
    {
        $periode = $this->periode();
        $application = $this->reopenedApplication($this->student(), $periode, UserRole::OPERATOR_DUKCAPIL);

        $kabupaten = $this->kabupatenOperator($this->mainKabupaten());
        $student = $this->student('Mahasiswa Lain');
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($kabupaten)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertForbidden();

        $this->assertDatabaseHas('applications', ['id' => $application->id]);
    }

    public function test_queue_empty_state_is_shown_when_nothing_waits_for_reverification(): void
    {
        $dukcapil = $this->agencyOperator(UserRole::OPERATOR_DUKCAPIL);

        $this->actingAs($dukcapil)
            ->get(route('operator.sanggahan-queue.index'))
            ->assertOk()
            ->assertSee('Tidak ada pengajuan hasil sanggahan yang menunggu penilaian ulang dinas Anda.');
    }

    private function mainKabupaten(): int
    {
        return (int) Village::query()->with('kecamatan')->firstOrFail()->kabupaten_id;
    }

    private function otherKabupaten(): int
    {
        $kabupaten = Kabupaten::query()->create([
            'code' => '9999',
            'name' => 'Kabupaten Lain',
        ]);

        $kecamatan = Kecamatan::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'code' => '999901',
            'name' => 'Kecamatan Lain',
        ]);

        Village::query()->create([
            'kabupaten_id' => $kabupaten->id,
            'kecamatan_id' => $kecamatan->id,
            'code' => '9999010001',
            'name' => 'Desa Lain',
            'type' => 'desa',
        ]);

        return $kabupaten->id;
    }

    private function kabupatenOperator(int $kabupatenId): User
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $kabupatenId,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function agencyOperator(UserRole $role): User
    {
        if (! isset($this->agencyOperators[$role->value])) {
            $operator = User::factory()->create([
                'role' => $role,
                'kabupaten_id' => $this->mainKabupaten(),
            ]);
            $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

            $this->agencyOperators[$role->value] = $operator;
        }

        return $this->agencyOperators[$role->value];
    }

    private function student(string $name = 'Fajar Pratama', ?int $kabupatenId = null): User
    {
        $village = Village::query()
            ->where('kabupaten_id', $kabupatenId ?? $this->mainKabupaten())
            ->firstOrFail();

        $student = User::factory()->create([
            'name' => $name,
            'role' => UserRole::MAHASISWA,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212123456789'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT),
            'nim' => 'KHM-RVF-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jalan Pengujian',
        ]);

        return $student;
    }

    private function periode(array $attributes = []): Periode
    {
        return Periode::query()->create(array_merge([
            'tahun' => 2026,
            'nama' => 'Periode Beasiswa '.Str::random(8),
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'tanggal_mulai_sanggah' => '2026-11-10',
            'tanggal_selesai_sanggah' => '2026-11-12',
            'status' => 'aktif',
        ], $attributes));
    }

    private function normalApplication(User $student, Periode $periode): Application
    {
        return $this->applicationFor($student, $periode, ApplicationStatus::VERIFIKASI_DINAS);
    }

    private function reopenedApplication(
        User $student,
        Periode $periode,
        UserRole $tmsAgencyRole,
        ApplicationType $type = ApplicationType::AKADEMIK,
        string $uraian = 'Dokumen yang saya unggah sudah sesuai, mohon keputusan TMS ditinjau kembali.',
        ?string $catatanKeputusan = 'Sanggahan dikabulkan, dinas dapat menilai ulang.',
    ): Application {
        $application = $this->applicationFor($student, $periode, ApplicationStatus::VERIFIKASI_DINAS, $type);

        $tmsAgency = $tmsAgencyRole->agencyCode();
        $requiredAgencies = AgencyVerificationService::requiredAgencies($application);

        foreach ($requiredAgencies as $agency) {
            if ($agency === $tmsAgency) {
                continue;
            }

            $this->agencyDecision($application, $this->roleForAgency($agency), VerificationDecision::MS);
        }

        $kabupatenOperator = $this->kabupatenOperator($this->mainKabupaten());

        Sanggahan::query()->create([
            'application_id' => $application->id,
            'periode_id' => $periode->id,
            'submitted_by' => $student->id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => $uraian,
            'catatan_keputusan' => $catatanKeputusan,
            'decided_by' => $kabupatenOperator->id,
            'decided_at' => now(),
        ]);

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $kabupatenOperator->id,
            'from_status' => ApplicationStatus::TMS->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'sanggahan_granted',
            'notes' => $catatanKeputusan,
            'metadata' => [
                'annulled_agencies' => [$tmsAgency],
                'annulled_verifications' => [[
                    'agency' => $tmsAgency,
                    'agency_label' => config('kartu_hebat.agencies.'.$tmsAgency, $tmsAgency),
                    'decision' => VerificationDecision::TMS->value,
                    'notes' => 'NIK tidak sesuai dengan dokumen kependudukan.',
                ]],
            ],
            'created_at' => now(),
        ]);

        return $application->fresh();
    }

    private function applicationFor(
        User $student,
        Periode $periode,
        ApplicationStatus $status,
        ApplicationType $type = ApplicationType::AKADEMIK,
    ): Application {
        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'KAT-'.Str::random(10),
            'application_type' => $type,
            'nama' => 'Kategori Pengujian',
            'kuota' => 10,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $pendaftaran = Pendaftaran::query()->create([
            'user_id' => $student->id,
            'periode_id' => $periode->id,
            'kategori_beasiswa_id' => $kategori->id,
            'nomor_pendaftaran' => 'KHM-REG-'.$periode->id.'-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'status' => 'verification',
            'submitted_at' => now(),
        ]);

        return Application::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $pendaftaran->nomor_pendaftaran,
            'periode' => $periode->nama,
            'status' => $status,
            'application_type' => $type,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);
    }

    private function agencyDecision(
        Application $application,
        UserRole $role,
        VerificationDecision $decision,
    ): AgencyVerification {
        return AgencyVerification::query()->create([
            'application_id' => $application->id,
            'verifier_id' => $this->agencyOperator($role)->id,
            'agency' => $role->agencyCode(),
            'decision' => $decision,
            'notes' => $decision === VerificationDecision::TMS
                ? 'NIK tidak sesuai dengan dokumen kependudukan.'
                : 'Dokumen sesuai.',
            'verified_at' => now(),
        ]);
    }

    private function roleForAgency(string $agency): UserRole
    {
        return collect(UserRole::cases())
            ->first(fn (UserRole $role) => $role->isAgency() && $role->agencyCode() === $agency);
    }
}
