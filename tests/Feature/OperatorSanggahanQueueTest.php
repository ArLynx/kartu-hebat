<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVerification;
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
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperatorSanggahanQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->travelTo('2026-11-11');
    }

    public function test_kabupaten_operator_sees_sanggahan_menu_and_only_sanggahan_from_own_region(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();

        $ownStudent = $this->student($this->mainKabupaten(), 'Fajar Pratama');
        $ownApplication = $this->applicationFor($ownStudent, $periode);
        $ownSanggahan = $this->sanggahanFor($ownApplication);

        $otherStudent = $this->student($this->otherKabupaten(), 'Budi Santoso');
        $otherApplication = $this->applicationFor($otherStudent, $periode);
        $otherSanggahan = $this->sanggahanFor($otherApplication, attributes: [
            'uraian' => 'Sanggahan dari wilayah kabupaten lain yang tidak boleh terlihat.',
        ]);

        $this->actingAs($operator)
            ->get(route('operator.dashboard'))
            ->assertOk()
            ->assertSee(route('operator.sanggahan.index'))
            ->assertSee('Sanggahan');

        $response = $this->actingAs($operator)
            ->get(route('operator.sanggahan.index'));

        $response->assertOk();
        $response->assertSee($ownApplication->nomor_pengajuan);
        $response->assertSee('Fajar Pratama');
        $response->assertDontSee($otherApplication->nomor_pengajuan);
        $response->assertDontSee('Budi Santoso');

        $this->assertDatabaseHas('sanggahans', ['id' => $ownSanggahan->id]);
        $this->assertDatabaseHas('sanggahans', ['id' => $otherSanggahan->id]);
    }

    public function test_operator_from_another_kabupaten_cannot_open_sanggahan_detail(): void
    {
        $period = $this->periode();
        $ownerStudent = $this->student($this->mainKabupaten(), 'Fajar Pratama');
        $sanggahan = $this->sanggahanFor($this->applicationFor($ownerStudent, $period));

        $otherOperator = $this->kabupatenOperator($this->otherKabupaten());

        $this->actingAs($otherOperator)
            ->get(route('operator.sanggahan.show', $sanggahan))
            ->assertForbidden();
    }

    public function test_queue_can_be_filtered_by_periode_and_status_and_searched_by_nomor_or_name(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periodeA = $this->periode(['nama' => 'Periode Beasiswa 2026']);
        $periodeB = $this->periode(['tahun' => 2025, 'nama' => 'Periode Beasiswa 2025']);

        $fajar = $this->student($this->mainKabupaten(), 'Fajar Pratama');
        $siti = $this->student($this->mainKabupaten(), 'Siti Aminah');
        $budi = $this->student($this->mainKabupaten(), 'Budi Hartono');

        $fajarSanggahan = $this->sanggahanFor($this->applicationFor($fajar, $periodeA));
        $sitiSanggahan = $this->sanggahanFor($this->applicationFor($siti, $periodeA), SanggahanStatus::DIKABULKAN);
        $budiSanggahan = $this->sanggahanFor($this->applicationFor($budi, $periodeB), SanggahanStatus::DITOLAK);

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', ['periode_id' => $periodeA->id]))
            ->assertOk()
            ->assertSee($fajarSanggahan->application->nomor_pengajuan)
            ->assertSee($sitiSanggahan->application->nomor_pengajuan)
            ->assertDontSee($budiSanggahan->application->nomor_pengajuan);

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', ['status' => SanggahanStatus::DIKABULKAN->value]))
            ->assertOk()
            ->assertSee($sitiSanggahan->application->nomor_pengajuan)
            ->assertDontSee($fajarSanggahan->application->nomor_pengajuan)
            ->assertDontSee($budiSanggahan->application->nomor_pengajuan);

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', ['q' => 'Fajar']))
            ->assertOk()
            ->assertSee($fajarSanggahan->application->nomor_pengajuan)
            ->assertDontSee($sitiSanggahan->application->nomor_pengajuan);

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', ['q' => $budiSanggahan->application->nomor_pengajuan]))
            ->assertOk()
            ->assertSee($budiSanggahan->application->nomor_pengajuan)
            ->assertDontSee($fajarSanggahan->application->nomor_pengajuan);
    }

    public function test_queue_summary_counts_follow_periode_filter_and_exclude_other_regions(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periodeA = $this->periode(['nama' => 'Periode Beasiswa 2026']);
        $periodeB = $this->periode(['tahun' => 2025, 'nama' => 'Periode Beasiswa 2025']);

        $this->sanggahanFor($this->applicationFor($this->student($this->mainKabupaten(), 'Satu'), $periodeA));
        $this->sanggahanFor($this->applicationFor($this->student($this->mainKabupaten(), 'Dua'), $periodeA));
        $this->sanggahanFor($this->applicationFor($this->student($this->mainKabupaten(), 'Tiga'), $periodeA), SanggahanStatus::DIKABULKAN);
        $this->sanggahanFor($this->applicationFor($this->student($this->mainKabupaten(), 'Empat'), $periodeA), SanggahanStatus::DITOLAK);

        $this->sanggahanFor($this->applicationFor($this->student($this->mainKabupaten(), 'Lima'), $periodeB), SanggahanStatus::DITOLAK);

        $this->sanggahanFor($this->applicationFor($this->student($this->otherKabupaten(), 'Lain Wilayah'), $periodeA), SanggahanStatus::DIKABULKAN);

        $response = $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', ['periode_id' => $periodeA->id]));

        $response->assertOk();
        $response->assertSee('2 diajukan');
        $response->assertSee('1 dikabulkan');
        $response->assertSee('1 ditolak');

        $response = $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', ['q' => 'Lima']));

        $response->assertOk();
        $response->assertSee('0 diajukan');
        $response->assertSee('0 dikabulkan');
        $response->assertSee('1 ditolak');

        $response = $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', [
                'periode_id' => $periodeA->id,
                'status' => SanggahanStatus::DIAJUKAN->value,
            ]));

        $response->assertOk();
        $response->assertSee('2 diajukan');
        $response->assertSee('1 dikabulkan');
        $response->assertSee('1 ditolak');
    }

    public function test_pagination_links_keep_periode_status_and_search_filters(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode(['nama' => 'Periode Beasiswa 2026']);

        $applications = [];

        foreach (range(1, 16) as $index) {
            $student = $this->student($this->mainKabupaten(), 'Mahasiswa '.$index);
            $applications[$index] = $this->applicationFor($student, $periode);
            $this->sanggahanFor($applications[$index], attributes: [
                'uraian' => 'Uraian sanggahan nomor '.$index.' yang cukup panjang untuk diuji.',
            ]);
        }

        $filters = [
            'periode_id' => $periode->id,
            'status' => SanggahanStatus::DIAJUKAN->value,
            'q' => 'Mahasiswa',
        ];

        $response = $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', $filters));

        $response->assertOk();
        $response->assertSee($applications[16]->nomor_pengajuan);
        $response->assertDontSee($applications[1]->nomor_pengajuan);
        $response->assertSee('page=2', false);
        $response->assertSee('periode_id='.$periode->id, false);
        $response->assertSee('status='.SanggahanStatus::DIAJUKAN->value, false);
        $response->assertSee('q=Mahasiswa', false);

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.index', $filters + ['page' => 2]))
            ->assertOk()
            ->assertSee($applications[1]->nomor_pengajuan)
            ->assertDontSee($applications[16]->nomor_pengajuan);
    }

    public function test_detail_page_shows_uraian_tms_agencies_rejected_documents_and_verification_history(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();

        $student = $this->student($this->mainKabupaten(), 'Fajar Pratama');
        $application = $this->applicationFor($student, $periode);

        $documentType = DocumentType::query()->create([
            'code' => 'KTP-'.Str::random(6),
            'name' => 'Kartu Tanda Penduduk',
            'is_required' => true,
            'is_active' => true,
        ]);

        $document = Document::query()->create([
            'application_id' => $application->id,
            'document_type_id' => $documentType->id,
            'path' => 'documents/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'checksum' => Str::random(64),
        ]);

        DocumentVerification::query()->create([
            'application_id' => $application->id,
            'document_id' => $document->id,
            'verifier_id' => $application->agencyVerifications->first()->verifier_id,
            'stage' => 'dukcapil',
            'round' => 1,
            'result' => DocumentVerificationResult::TIDAK_MEMENUHI,
            'notes' => 'Foto KTP buram dan NIK tidak terbaca.',
            'verified_at' => now(),
        ]);

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $student->id,
            'from_status' => ApplicationStatus::DRAFT->value,
            'to_status' => ApplicationStatus::TMS->value,
            'action' => 'submitted',
            'notes' => 'Pengajuan dikirim oleh mahasiswa.',
            'created_at' => now()->subDay(),
        ]);

        $sanggahan = $this->sanggahanFor(
            $application,
            uraian: 'NIK pada KTP saya sudah benar, mohon keputusan TMS ditinjau kembali.',
        );

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $student->id,
            'from_status' => ApplicationStatus::TMS->value,
            'to_status' => ApplicationStatus::TMS->value,
            'action' => 'sanggahan_submitted',
            'notes' => 'Sanggahan diajukan oleh mahasiswa.',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($operator)
            ->get(route('operator.sanggahan.show', $sanggahan));

        $response->assertOk();
        $response->assertSee('Detail Sanggahan');
        $response->assertSee('Fajar Pratama');
        $response->assertSee($application->nomor_pengajuan);
        $response->assertSee('NIK pada KTP saya sudah benar, mohon keputusan TMS ditinjau kembali.');
        $response->assertSee('Dinas Kependudukan dan Pencatatan Sipil');
        $response->assertSee('NIK tidak sesuai dengan dokumen kependudukan.');
        $response->assertSee('Kartu Tanda Penduduk');
        $response->assertSee('Foto KTP buram dan NIK tidak terbaca.');
        $response->assertSee('Pengajuan dikirim oleh mahasiswa.');
        $response->assertSee('Sanggahan diajukan oleh mahasiswa.');
        $response->assertSee('Riwayat Verifikasi');
        $response->assertSee('Status Sanggahan');
        $response->assertSee('Diajukan');
    }

    public function test_non_kabupaten_operator_cannot_access_sanggahan_queue_or_detail(): void
    {
        $periode = $this->periode();
        $student = $this->student($this->mainKabupaten(), 'Fajar Pratama');
        $sanggahan = $this->sanggahanFor($this->applicationFor($student, $periode));

        $agencyOperator = User::factory()->create([
            'role' => UserRole::OPERATOR_DUKCAPIL,
            'kabupaten_id' => $this->mainKabupaten(),
        ]);
        $agencyOperator->forceFill(['two_factor_confirmed_at' => now()])->save();

        $this->actingAs($agencyOperator)
            ->get(route('operator.sanggahan.index'))
            ->assertForbidden();

        $this->actingAs($agencyOperator)
            ->get(route('operator.sanggahan.show', $sanggahan))
            ->assertForbidden();
    }

    public function test_detail_page_only_shows_rejected_documents_from_the_current_verification_round(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();

        $student = $this->student($this->mainKabupaten(), 'Fajar Pratama');
        $application = $this->applicationFor($student, $periode);

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $student->id,
            'from_status' => ApplicationStatus::DRAFT->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'submitted',
            'notes' => 'Pengajuan dikirim oleh mahasiswa.',
            'created_at' => now()->subDay(),
        ]);

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $student->id,
            'from_status' => ApplicationStatus::DRAFT->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'submitted',
            'notes' => 'Pengajuan dikirim ulang setelah perbaikan.',
            'created_at' => now()->subHours(2),
        ]);

        $documentType = DocumentType::query()->create([
            'code' => 'KTP-'.Str::random(6),
            'name' => 'Kartu Tanda Penduduk',
            'is_required' => true,
            'is_active' => true,
        ]);

        $document = Document::query()->create([
            'application_id' => $application->id,
            'document_type_id' => $documentType->id,
            'path' => 'documents/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'checksum' => Str::random(64),
        ]);

        DocumentVerification::query()->create([
            'application_id' => $application->id,
            'document_id' => $document->id,
            'verifier_id' => $application->agencyVerifications->first()->verifier_id,
            'stage' => 'dukcapil',
            'round' => 1,
            'result' => DocumentVerificationResult::TIDAK_MEMENUHI,
            'notes' => 'Penilaian lama yang sudah tidak berlaku.',
            'verified_at' => now()->subDay(),
        ]);

        DocumentVerification::query()->create([
            'application_id' => $application->id,
            'document_id' => $document->id,
            'verifier_id' => $application->agencyVerifications->first()->verifier_id,
            'stage' => 'dukcapil',
            'round' => 2,
            'result' => DocumentVerificationResult::TIDAK_MEMENUHI,
            'notes' => 'Penilaian putaran berjalan yang menjadi dasar TMS.',
            'verified_at' => now(),
        ]);

        $sanggahan = $this->sanggahanFor($application);

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.show', $sanggahan))
            ->assertOk()
            ->assertSee('Penilaian putaran berjalan yang menjadi dasar TMS.')
            ->assertDontSee('Penilaian lama yang sudah tidak berlaku.');
    }

    public function test_decided_sanggahan_detail_shows_decision_but_offers_no_action(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());
        $periode = $this->periode();

        $student = $this->student($this->mainKabupaten(), 'Fajar Pratama');
        $application = $this->applicationFor($student, $periode);

        $sanggahan = $this->sanggahanFor($application, SanggahanStatus::DITOLAK, attributes: [
            'catatan_keputusan' => 'Dokumen terbukti tidak sesuai setelah ditinjau ulang.',
            'decided_by' => $operator->id,
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($operator)
            ->get(route('operator.sanggahan.show', $sanggahan));

        $response->assertOk();
        $response->assertSee('Ditolak');
        $response->assertSee('Dokumen terbukti tidak sesuai setelah ditinjau ulang.');
        $response->assertSee($operator->name);
        $response->assertDontSee('Kabulkan Sanggahan');
        $response->assertDontSee('Tolak Sanggahan');
    }

    public function test_queue_shows_empty_state_when_no_sanggahan_exists(): void
    {
        $operator = $this->kabupatenOperator($this->mainKabupaten());

        $this->actingAs($operator)
            ->get(route('operator.sanggahan.index'))
            ->assertOk()
            ->assertSee('Belum ada sanggahan yang sesuai filter.');
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

    private function student(int $kabupatenId, string $name): User
    {
        $village = Village::query()->where('kabupaten_id', $kabupatenId)->firstOrFail();

        $student = User::factory()->create([
            'name' => $name,
            'role' => UserRole::MAHASISWA,
        ]);

        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212123456789'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT),
            'nim' => 'KHM-SNG-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
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

    private function applicationFor(User $student, Periode $periode): Application
    {
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
            'nomor_pendaftaran' => 'KHM-REG-'.$periode->id.'-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'status' => 'rejected',
            'submitted_at' => now(),
        ]);

        $application = Application::query()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => $pendaftaran->nomor_pendaftaran,
            'periode' => $periode->nama,
            'status' => ApplicationStatus::TMS,
            'application_type' => ApplicationType::AKADEMIK,
            'submitted_at' => now(),
            'locked_at' => now(),
        ]);

        AgencyVerification::query()->create([
            'application_id' => $application->id,
            'verifier_id' => User::factory()->create(['role' => UserRole::OPERATOR_DUKCAPIL])->id,
            'agency' => 'dukcapil',
            'decision' => VerificationDecision::TMS,
            'notes' => 'NIK tidak sesuai dengan dokumen kependudukan.',
            'verified_at' => now(),
        ]);

        return $application;
    }

    private function sanggahanFor(
        Application $application,
        SanggahanStatus $status = SanggahanStatus::DIAJUKAN,
        ?string $uraian = null,
        array $attributes = [],
    ): Sanggahan {
        return Sanggahan::query()->create(array_merge([
            'application_id' => $application->id,
            'periode_id' => $application->pendaftaran->periode_id,
            'submitted_by' => $application->mahasiswa_id,
            'status' => $status,
            'uraian' => $uraian ?? 'Saya keberatan atas keputusan TMS karena dokumen yang saya unggah sudah sesuai.',
        ], $attributes));
    }
}
