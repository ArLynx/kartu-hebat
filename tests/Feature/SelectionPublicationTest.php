<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use App\Models\Periode;
use App\Models\Sanggahan;
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
use Tests\TestCase;

class SelectionPublicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        Storage::fake('public');
        Notification::fake();
    }

    public function test_republishing_only_writes_unpublished_candidates_and_keeps_previous_recipients_untouched(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 1]);
        $operator = $this->kabupatenOperator();

        $recipient = $this->candidateInSelection($operator, score: 90.0, rank: 1);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-awal.pdf', 200, 'application/pdf'),
                'title' => 'Pengumuman Awal',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $recipient->refresh();
        $publishedAt = $recipient->selection->published_at;
        $this->assertNotNull($publishedAt);
        $this->assertSame(ApplicationStatus::DITERIMA, $recipient->status);

        $this->travel(1)->hours();

        $lateCandidate = $this->candidateInSelection($operator, score: 70.0);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-perbaikan.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $recipient->refresh();
        $lateCandidate->refresh();

        $this->assertTrue($recipient->selection->published_at->equalTo($publishedAt));
        $this->assertSame(ApplicationStatus::DITERIMA, $recipient->status);
        $this->assertNotNull($lateCandidate->selection->published_at);
        $this->assertSame(ApplicationStatus::DITOLAK, $lateCandidate->status);

        Notification::assertSentToTimes($recipient->mahasiswa, ApplicationStatusChanged::class, 1);
        Notification::assertSentToTimes($lateCandidate->mahasiswa, ApplicationStatusChanged::class, 1);

        $this->assertSame(1, $recipient->verificationLogs()
            ->where('action', 'selection_result_published')
            ->count());
    }

    public function test_correction_publication_creates_a_separate_announcement_and_keeps_the_original_sk(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 1]);
        $operator = $this->kabupatenOperator();
        $period = (string) config('kartu_hebat.current_period');
        $baseSlug = 'hasil-seleksi-'.Str::slug($period);

        $this->candidateInSelection($operator, score: 90.0, rank: 1);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-awal.pdf', 200, 'application/pdf'),
                'title' => 'Pengumuman Awal',
            ])
            ->assertSessionHas('success');

        $original = Announcement::query()->where('slug', $baseSlug)->firstOrFail();
        $originalPath = $original->attachment_path;
        Storage::disk('public')->assertExists($originalPath);

        $this->travel(1)->hours();

        $this->candidateInSelection($operator, score: 70.0);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-perbaikan.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHas('success');

        $original->refresh();
        $this->assertSame($originalPath, $original->attachment_path);
        $this->assertSame('Pengumuman Awal', $original->title);
        Storage::disk('public')->assertExists($original->attachment_path);

        $correction = Announcement::query()
            ->where('slug', 'like', $baseSlug.'-perbaikan-%')
            ->firstOrFail();

        $this->assertNotSame($original->attachment_path, $correction->attachment_path);
        $this->assertStringContainsStringIgnoringCase('perbaikan', $correction->title);
        Storage::disk('public')->assertExists($correction->attachment_path);
    }

    public function test_publishing_without_new_candidates_explains_that_there_is_nothing_new(): void
    {
        $operator = $this->kabupatenOperator();
        $this->candidateInSelection($operator, score: 90.0, rank: 1);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-awal.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHas('success');

        $announcementCount = Announcement::query()->count();

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-baru.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('warning', 'Tidak ada hasil baru untuk dipublikasikan. Seluruh kandidat pada periode ini sudah pernah dipublikasikan.');

        $this->assertSame($announcementCount, Announcement::query()->count());
    }

    public function test_quota_is_computed_across_all_candidates_not_only_the_unpublished_ones(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 1]);
        $operator = $this->kabupatenOperator();

        $publishedRecipient = $this->candidateInSelection($operator, score: 90.0, rank: 1);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-awal.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::DITERIMA, $publishedRecipient->fresh()->status);

        $lateCandidate = $this->candidateInSelection($operator, score: 70.0, rank: 2);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-perbaikan.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::DITOLAK, $lateCandidate->fresh()->status);
        $this->assertSame(ApplicationStatus::DITERIMA, $publishedRecipient->fresh()->status);
    }

    public function test_publishing_after_a_granted_sanggahan_notifies_only_the_new_recipient(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 1]);
        $operator = $this->kabupatenOperator();
        $periode = $this->periode();

        $existingRecipient = $this->candidateInSelection($operator, score: 90.0, rank: 1);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-awal.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHas('success');

        $reopened = $this->candidateInSelection($operator, score: 80.0, rank: 2);
        $reopened->selection->update(['manual_decision' => ApplicationStatus::DITERIMA->value]);
        Sanggahan::query()->create([
            'application_id' => $reopened->id,
            'periode_id' => $periode->id,
            'submitted_by' => $reopened->mahasiswa_id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'Keputusan TMS perlu dinilai ulang karena dokumen sudah sesuai.',
            'catatan_keputusan' => 'Sanggahan dikabulkan.',
            'decided_at' => now(),
        ]);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-perbaikan.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::DITERIMA, $reopened->fresh()->status);
        Notification::assertSentToTimes($reopened->mahasiswa, ApplicationStatusChanged::class, 1);
        Notification::assertSentToTimes($existingRecipient->mahasiswa, ApplicationStatusChanged::class, 1);
    }

    public function test_granted_sanggahan_in_a_closed_period_can_be_published_with_its_own_sk(): void
    {
        $operator = $this->kabupatenOperator();

        $periode = Periode::query()->create([
            'tahun' => 2025,
            'nama' => 'Periode Beasiswa 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'status' => 'ditutup',
        ]);

        $archived = $this->candidateInSelection($operator, score: 88.0, rank: 1, period: $periode->nama);
        Sanggahan::query()->create([
            'application_id' => $archived->id,
            'periode_id' => $periode->id,
            'submitted_by' => $archived->mahasiswa_id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'Keputusan TMS perlu dinilai ulang karena dokumen sudah sesuai.',
            'catatan_keputusan' => 'Sanggahan dikabulkan.',
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($operator)->post(route('operator.selection.publish'), [
            'periode' => $periode->nama,
            'sk_file' => UploadedFile::fake()->create('sk-perbaikan.pdf', 200, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::DITERIMA, $archived->fresh()->status);
        $this->assertSame(
            'hasil-seleksi-'.Str::slug($periode->nama),
            Announcement::query()->latest('id')->firstOrFail()->slug,
        );
        Notification::assertSentToTimes($archived->mahasiswa, ApplicationStatusChanged::class, 1);
    }

    public function test_archived_selection_page_offers_correction_publishing_only_when_a_sanggahan_was_granted(): void
    {
        $operator = $this->kabupatenOperator();

        $periode = Periode::query()->create([
            'tahun' => 2025,
            'nama' => 'Periode Beasiswa 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'status' => 'ditutup',
        ]);

        $reopened = $this->candidateInSelection($operator, score: 88.0, rank: 1, period: $periode->nama);
        $this->candidateInSelection($operator, score: 70.0, rank: 2, period: $periode->nama);

        $this->actingAs($operator)
            ->get(route('operator.selection', [
                'periode' => $periode->nama,
                'application_type' => ApplicationType::AKADEMIK->value,
            ]))
            ->assertOk()
            ->assertSee('Mode Arsip (Read-Only)')
            ->assertDontSee('Publikasikan Hasil Perbaikan');

        Sanggahan::query()->create([
            'application_id' => $reopened->id,
            'periode_id' => $periode->id,
            'submitted_by' => $reopened->mahasiswa_id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'Keputusan TMS perlu dinilai ulang karena dokumen sudah sesuai.',
            'catatan_keputusan' => 'Sanggahan dikabulkan.',
            'decided_at' => now(),
        ]);

        $this->actingAs($operator)
            ->get(route('operator.selection', [
                'periode' => $periode->nama,
                'application_type' => ApplicationType::AKADEMIK->value,
            ]))
            ->assertOk()
            ->assertSee('Mode Arsip (Sanggahan)')
            ->assertSee('Publikasikan Hasil Perbaikan')
            ->assertSee('name="periode" value="'.$periode->nama.'"', false);
    }

    public function test_publishing_a_closed_period_is_blocked_without_a_granted_sanggahan(): void
    {
        $operator = $this->kabupatenOperator();

        $periode = Periode::query()->create([
            'tahun' => 2025,
            'nama' => 'Periode Beasiswa 2025',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'status' => 'ditutup',
        ]);

        $archived = $this->candidateInSelection($operator, score: 88.0, rank: 1, period: $periode->nama);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'periode' => $periode->nama,
                'sk_file' => UploadedFile::fake()->create('sk-perbaikan.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Publikasi periode lampau hanya diizinkan untuk kandidat hasil sanggahan yang dikabulkan.');

        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $archived->fresh()->status);
        $this->assertNull($archived->fresh()->selection->published_at);
        $this->assertSame(0, Announcement::query()->count());
    }

    public function test_manual_decisions_are_read_case_insensitively_during_publication(): void
    {
        config(['kartu_hebat.quotas.AKADEMIK' => 2]);
        $operator = $this->kabupatenOperator();

        $accepted = $this->candidateInSelection($operator, score: 90.0, rank: 1);
        $accepted->selection->update(['manual_decision' => 'diterima']);

        $rejected = $this->candidateInSelection($operator, score: 80.0, rank: 2);
        $rejected->selection->update(['manual_decision' => 'Ditolak']);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-awal.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHas('success');

        $this->assertSame(ApplicationStatus::DITERIMA, $accepted->fresh()->status);
        $this->assertSame(ApplicationStatus::DITOLAK, $rejected->fresh()->status);
    }

    public function test_unrecognized_internal_decisions_are_rejected_instead_of_silently_rejected_status(): void
    {
        $operator = $this->kabupatenOperator();
        $candidate = $this->candidateInSelection($operator, score: 90.0, rank: 1);
        $candidate->selection->update(['manual_decision' => 'DTERIMA']);

        $this->actingAs($operator)
            ->post(route('operator.selection.publish'), [
                'sk_file' => UploadedFile::fake()->create('sk-awal.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('publication');

        $this->assertSame(ApplicationStatus::SELEKSI_KABUPATEN, $candidate->fresh()->status);
        $this->assertNull($candidate->fresh()->selection->published_at);
        $this->assertSame(0, Announcement::query()->count());
    }

    public function test_pdf_recap_reads_lowercase_internal_decisions_case_insensitively(): void
    {
        $operator = $this->kabupatenOperator();
        $candidate = $this->candidateInSelection($operator, score: 90.0, rank: 1);
        $candidate->selection->update(['manual_decision' => 'diterima']);

        $this->view('reports.candidates-pdf', [
            'applications' => Application::query()
                ->with(['mahasiswa.profile.village.kecamatan', 'selection', 'pendaftaran.jalurBeasiswa'])
                ->whereKey($candidate->id)
                ->get(),
            'generatedAt' => now(),
            'reportTitle' => 'Rekap Seleksi',
        ])
            ->assertSee('Diterima')
            ->assertDontSee('diterima');
    }

    public function test_selection_table_marks_granted_sanggahan_candidates_with_a_link_to_the_detail(): void
    {
        $operator = $this->kabupatenOperator();
        $periode = $this->periode();

        $fromSanggahan = $this->candidateInSelection($operator, score: 90.0, rank: 1);
        $sanggahan = Sanggahan::query()->create([
            'application_id' => $fromSanggahan->id,
            'periode_id' => $periode->id,
            'submitted_by' => $fromSanggahan->mahasiswa_id,
            'status' => SanggahanStatus::DIKABULKAN,
            'uraian' => 'Dokumen sudah sesuai dan keputusan TMS perlu dinilai ulang.',
            'catatan_keputusan' => 'Sanggahan dikabulkan.',
            'decided_at' => now(),
        ]);

        $this->candidateInSelection($operator, score: 80.0, rank: 2);

        $response = $this->actingAs($operator)
            ->get(route('operator.selection', [
                'periode' => (string) config('kartu_hebat.current_period'),
                'application_type' => ApplicationType::AKADEMIK->value,
            ]));

        $response->assertOk();
        $response->assertSee('Hasil Sanggahan');
        $response->assertSee(route('operator.sanggahan.show', $sanggahan));
    }

    private function kabupatenOperator(): User
    {
        $village = Village::query()->firstOrFail();

        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function periode(): Periode
    {
        return Periode::query()->create([
            'tahun' => 2026,
            'nama' => (string) config('kartu_hebat.current_period'),
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);
    }

    private function candidateInSelection(
        User $operator,
        float $score = 80.0,
        ?int $rank = null,
        ApplicationType $type = ApplicationType::AKADEMIK,
        ?string $period = null,
    ): Application {
        $village = Village::query()->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212123456789'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT),
            'nim' => 'KHM-PUB-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jalan Pengujian',
        ]);

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-2026-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'periode' => $period ?? config('kartu_hebat.current_period'),
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
