<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use App\Models\Selection;
use App\Models\User;
use App\Models\Village;
use App\Notifications\ApplicationStatusChanged;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SelectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        Storage::fake('public');
        Notification::fake();
    }

    public function test_export_candidates_downloads_excel_file(): void
    {
        Excel::fake();
        $operator = $this->kabupatenOperator();
        $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 85.0);

        $response = $this->actingAs($operator)
            ->get(route('operator.selection.export', ['application_type' => 'AKADEMIK']));

        $response->assertSuccessful();
        Excel::assertDownloaded('rekap-seleksi-kartu-hebat-akademik.xlsx');
    }

    public function test_publish_automatic_accepts_candidates_within_quota_and_rejects_remainder(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 1]);
        $operator = $this->kabupatenOperator();

        $first = $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 90.0, rank: 1);
        $second = $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 80.0, rank: 2);

        $skFile = UploadedFile::fake()->create('sk-penetapan-2026.pdf', 500, 'application/pdf');

        $response = $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => $skFile,
                'title' => 'Pengumuman Resmi Kelulusan',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::DITERIMA, $first->fresh()->status);
        $this->assertSame(ApplicationStatus::DITOLAK, $second->fresh()->status);
        $this->assertNotNull($first->fresh()->selection->published_at);
        $this->assertNotNull($second->fresh()->selection->published_at);

        $slug = 'hasil-seleksi-'.Str::slug((string) config('kartu_hebat.current_period'));
        $announcement = Announcement::query()->where('slug', $slug)->first();
        $this->assertNotNull($announcement);
        $this->assertSame('Pengumuman Resmi Kelulusan', $announcement->title);
        $this->assertNotNull($announcement->attachment_path);
        Storage::disk('public')->assertExists($announcement->attachment_path);

        Notification::assertSentTo($first->mahasiswa, ApplicationStatusChanged::class);
        Notification::assertSentTo($second->mahasiswa, ApplicationStatusChanged::class);
    }

    public function test_import_excel_updates_internal_decisions_without_publishing_to_students(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 2]);
        $operator = $this->kabupatenOperator();

        $first = $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 90.0, rank: 1);
        $second = $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 80.0, rank: 2);

        $csvContent = "nomor_pengajuan,keputusan,catatan_internal\n"
            ."{$first->nomor_pengajuan},DITERIMA,ACC Pimpinan OK\n"
            ."{$second->nomor_pengajuan},DITOLAK,Di luar kuota disetujui\n";

        $excelFile = UploadedFile::fake()->createWithContent('acc-pimpinan.csv', $csvContent);

        $response = $this->actingAs($operator)
            ->post(route('operator.selection.import'), [
                'excel_file' => $excelFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Status aplikasi tetap SELEKSI_KABUPATEN (belum rilis publik ke mahasiswa)
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $first->fresh()->status);
        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $second->fresh()->status);

        // Keputusan internal tersimpan
        $this->assertSame('DITERIMA', $first->fresh()->selection->manual_decision);
        $this->assertSame('DITOLAK', $second->fresh()->selection->manual_decision);
        $this->assertSame('ACC Pimpinan OK', $first->fresh()->selection->notes);
        $this->assertNull($first->fresh()->selection->published_at);

        // Mahasiswa belum menerima notifikasi di tahap 1
        Notification::assertNothingSent();
    }

    public function test_import_excel_fails_when_quota_is_exceeded(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 1]);
        $operator = $this->kabupatenOperator();

        $first = $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 90.0, rank: 1);
        $second = $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 80.0, rank: 2);

        $csvContent = "nomor_pengajuan,keputusan,catatan_internal\n"
            ."{$first->nomor_pengajuan},DITERIMA,\n"
            ."{$second->nomor_pengajuan},DITERIMA,\n";

        $excelFile = UploadedFile::fake()->createWithContent('acc-pimpinan.csv', $csvContent);

        $response = $this->actingAs($operator)
            ->post(route('operator.selection.import'), [
                'excel_file' => $excelFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('excel_file');
    }

    public function test_publish_decisions_creates_announcement_and_notifies_students_using_imported_acc_decisions(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 2]);
        $operator = $this->kabupatenOperator();

        $first = $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 90.0, rank: 1);
        $second = $this->candidateInSelection($operator, ApplicationType::AKADEMIK, 80.0, rank: 2);

        // Tahap 1: Impor hasil ACC
        $csvContent = "nomor_pengajuan,keputusan,catatan_internal\n"
            ."{$first->nomor_pengajuan},DITERIMA,ACC Pimpinan\n"
            ."{$second->nomor_pengajuan},DITOLAK,Tidak disetujui\n";
        $excelFile = UploadedFile::fake()->createWithContent('acc-pimpinan.csv', $csvContent);
        $this->actingAs($operator)->post(route('operator.selection.import'), ['excel_file' => $excelFile]);

        // Tahap 2: Publikasi resmi dengan SK PDF
        $skFile = UploadedFile::fake()->create('sk-resmi.pdf', 200, 'application/pdf');
        $response = $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => $skFile,
                'title' => 'Pengumuman Resmi Kelulusan Beasiswa',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::DITERIMA, $first->fresh()->status);
        $this->assertSame(ApplicationStatus::DITOLAK, $second->fresh()->status);
        $this->assertNotNull($first->fresh()->selection->published_at);
        $this->assertNotNull($second->fresh()->selection->published_at);

        $slug = 'hasil-seleksi-'.Str::slug((string) config('kartu_hebat.current_period'));
        $announcement = Announcement::query()->where('slug', $slug)->first();
        $this->assertNotNull($announcement);
        $this->assertSame('Pengumuman Resmi Kelulusan Beasiswa', $announcement->title);
        $this->assertNotNull($announcement->attachment_path);
        Storage::disk('public')->assertExists($announcement->attachment_path);

        Notification::assertSentTo($first->mahasiswa, ApplicationStatusChanged::class);
        Notification::assertSentTo($second->mahasiswa, ApplicationStatusChanged::class);
    }

    private function kabupatenOperator(): User
    {
        $village = Village::query()->with('kecamatan')->firstOrFail();

        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function candidateInSelection(
        User $operator,
        ApplicationType $type,
        float $score,
        ?int $rank = null,
    ): Application {
        $village = Village::query()->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212123456789'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT),
            'nim' => 'KHM-SEL-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jalan Pengujian',
        ]);

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-2026-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'application_type' => $type,
        ]);

        Selection::query()->create([
            'application_id' => $application->id,
            'final_score' => $score,
            'rank' => $rank,
        ]);

        return $application;
    }
}
