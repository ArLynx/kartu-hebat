<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Exports\CandidatesExport;
use App\Imports\SelectionDecisionImport;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\Selection;
use App\Models\User;
use App\Models\VerificationLog;
use App\Notifications\ApplicationStatusChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class SelectionWorkflowService
{
    public function __construct(
        private readonly SelectionScoringService $scoring,
    ) {}

    public function exportCandidates(
        User $operator,
        ?ApplicationType $type = null,
        ?int $jalurBeasiswaId = null,
    ): CandidatesExport {
        return new CandidatesExport((int) $operator->kabupaten_id, $type, $jalurBeasiswaId);
    }

    public function importDecisionsFromExcel(
        User $operator,
        UploadedFile $excelFile,
        ?string $period = null,
    ): int {
        $period ??= (string) config('kartu_hebat.current_period');
        $import = new SelectionDecisionImport;
        Excel::import($import, $excelFile);
        $rows = $import->getRows();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'excel_file' => 'Berkas Excel tidak memuat baris data kandidat.',
            ]);
        }

        $applications = Application::query()
            ->visibleTo($operator)
            ->where('periode', $period)
            ->whereIn('status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])
            ->with(['mahasiswa.profile', 'selection'])
            ->get()
            ->keyBy('nomor_pengajuan');

        $decisions = [];
        $acceptedCounts = [];

        foreach ($rows as $index => $row) {
            $rowArray = $row instanceof Collection ? $row->toArray() : (array) $row;
            $rowNum = $index + 2;

            $appNumber = trim((string) ($rowArray['nomor_pengajuan'] ?? $rowArray['nomor'] ?? ''));
            if ($appNumber === '') {
                continue;
            }

            $application = $applications->get($appNumber);
            if (! $application) {
                throw ValidationException::withMessages([
                    'excel_file' => "Baris ke-{$rowNum}: Nomor pengajuan '{$appNumber}' tidak ditemukan pada daftar seleksi aktif wilayah Anda.",
                ]);
            }

            if ($application->selection?->published_at) {
                throw ValidationException::withMessages([
                    'excel_file' => "Baris ke-{$rowNum} (Nomor {$appNumber}): Hasil seleksi sudah pernah dipublikasikan dan tidak dapat diubah.",
                ]);
            }

            $rawDecision = strtoupper(trim((string) ($rowArray['keputusan'] ?? $rowArray['status'] ?? '')));
            $decision = match ($rawDecision) {
                'DITERIMA', 'TERIMA', 'LOLOS', 'LULUS' => ApplicationStatus::DITERIMA,
                'DITOLAK', 'TOLAK', 'TIDAK LOLOS', 'TIDAK LULUS' => ApplicationStatus::DITOLAK,
                default => throw ValidationException::withMessages([
                    'excel_file' => "Baris ke-{$rowNum} (Nomor {$appNumber}): Nilai keputusan '{$rawDecision}' tidak valid. Gunakan 'DITERIMA' atau 'DITOLAK'.",
                ]),
            };

            $notes = isset($rowArray['catatan_internal']) ? trim((string) $rowArray['catatan_internal']) : null;
            if ($notes === '') {
                $notes = null;
            }

            $typeKey = $application->application_type?->value ?? 'UNKNOWN';
            if ($decision === ApplicationStatus::DITERIMA) {
                $acceptedCounts[$typeKey] = ($acceptedCounts[$typeKey] ?? 0) + 1;
            }

            $decisions[$application->id] = [
                'application' => $application,
                'decision' => $decision,
                'notes' => $notes,
            ];
        }

        if ($decisions === []) {
            throw ValidationException::withMessages([
                'excel_file' => 'Tidak ada nomor pengajuan valid yang dapat diproses dari berkas Excel.',
            ]);
        }

        // Validasi kuota per jalur
        foreach ($acceptedCounts as $typeValue => $count) {
            $track = ApplicationType::tryFrom($typeValue);
            $quota = $track?->quota() ?? 0;
            if ($track && $count > $quota) {
                throw ValidationException::withMessages([
                    'excel_file' => "Jumlah keputusan DITERIMA untuk jalur {$track->label()} ({$count} orang) melebihi kuota maksimal ({$quota} orang).",
                ]);
            }
        }

        DB::transaction(function () use ($operator, $decisions): void {
            $now = now();
            foreach ($decisions as $item) {
                /** @var Application $application */
                $application = $item['application'];
                /** @var ApplicationStatus $target */
                $target = $item['decision'];
                $notes = $item['notes'];

                Selection::query()->updateOrCreate(
                    ['application_id' => $application->id],
                    [
                        'manual_decision' => $target->value,
                        'notes' => $notes,
                        'decided_by' => $operator->id,
                        'decided_at' => $now,
                    ],
                );

                VerificationLog::query()->create([
                    'application_id' => $application->id,
                    'actor_id' => $operator->id,
                    'from_status' => $application->status->value,
                    'to_status' => $application->status->value,
                    'action' => 'selection_decision_recorded',
                    'notes' => $notes,
                    'metadata' => [
                        'manual_decision' => $target->value,
                        'source' => 'excel_import',
                        'application_type' => $application->application_type?->value,
                    ],
                    'created_at' => $now,
                ]);
            }

            $this->scoring->recalculateRanking($operator->kabupaten_id);
        });

        return count($decisions);
    }

    public function publishDecisions(
        User $operator,
        UploadedFile $skPdfFile,
        ?string $announcementTitle = null,
        ?string $period = null,
    ): int {
        $period ??= (string) config('kartu_hebat.current_period');

        $applications = Application::query()
            ->visibleTo($operator)
            ->where('periode', $period)
            ->whereIn('status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])
            ->with(['mahasiswa.profile', 'selection'])
            ->get();

        if ($applications->isEmpty()) {
            throw ValidationException::withMessages([
                'publication' => 'Tidak ada pengajuan pada antrean seleksi yang siap dipublikasikan.',
            ]);
        }

        $decisions = [];

        foreach (ApplicationType::cases() as $track) {
            $trackApps = $applications
                ->filter(fn (Application $app): bool => $app->application_type === $track || $app->application_type?->value === $track->value)
                ->sortBy(fn (Application $app): int => $app->selection?->rank ?? PHP_INT_MAX)
                ->values();

            $quota = $track->quota();

            foreach ($trackApps as $index => $app) {
                $manualDecision = $app->selection?->manual_decision;
                if ($manualDecision) {
                    $decision = ApplicationStatus::tryFrom($manualDecision) ?? ApplicationStatus::DITOLAK;
                } else {
                    $rank = $app->selection?->rank ?? ($index + 1);
                    $decision = ($rank <= $quota) ? ApplicationStatus::DITERIMA : ApplicationStatus::DITOLAK;
                }

                $decisions[$app->id] = [
                    'application' => $app,
                    'decision' => $decision,
                    'notes' => $app->selection?->notes,
                ];
            }
        }

        return $this->executePublication($operator, $decisions, $skPdfFile, $announcementTitle, $period);
    }

    // Alias kompatibilitas
    public function publishFromExcel(
        User $operator,
        UploadedFile $excelFile,
        UploadedFile $skPdfFile,
        ?string $announcementTitle = null,
        ?string $period = null,
    ): int {
        $this->importDecisionsFromExcel($operator, $excelFile, $period);

        return $this->publishDecisions($operator, $skPdfFile, $announcementTitle, $period);
    }

    // Alias kompatibilitas
    public function publishAutomatic(
        User $operator,
        UploadedFile $skPdfFile,
        ?string $announcementTitle = null,
        ?string $period = null,
    ): int {
        return $this->publishDecisions($operator, $skPdfFile, $announcementTitle, $period);
    }

    /**
     * @param  array<int, array{application: Application, decision: ApplicationStatus, notes: ?string}>  $decisions
     */
    private function executePublication(
        User $operator,
        array $decisions,
        UploadedFile $skPdfFile,
        ?string $announcementTitle,
        string $period,
    ): int {
        return DB::transaction(function () use ($operator, $decisions, $skPdfFile, $announcementTitle, $period): int {
            $publishedAt = now();
            $skPath = $skPdfFile->storePubliclyAs(
                'announcements',
                'sk-penetapan-'.Str::slug($period).'-'.Str::uuid().'.pdf',
                'public',
            );

            foreach ($decisions as $item) {
                /** @var Application $application */
                $application = $item['application'];
                /** @var ApplicationStatus $target */
                $target = $item['decision'];
                $notes = $item['notes'];
                $from = $application->status;

                Selection::query()->updateOrCreate(
                    ['application_id' => $application->id],
                    [
                        'manual_decision' => $target->value,
                        'notes' => $notes,
                        'decided_by' => $operator->id,
                        'decided_at' => $publishedAt,
                        'published_at' => $publishedAt,
                    ],
                );

                $application->update([
                    'status' => $target,
                    'catatan' => $target === ApplicationStatus::DITERIMA ? null : $notes,
                    'locked_at' => $publishedAt,
                ]);

                VerificationLog::query()->create([
                    'application_id' => $application->id,
                    'actor_id' => $operator->id,
                    'from_status' => $from->value,
                    'to_status' => $target->value,
                    'action' => 'selection_result_published',
                    'notes' => $notes,
                    'metadata' => [
                        'application_type' => $application->application_type?->value,
                        'manual_decision' => $target->value,
                    ],
                    'created_at' => $publishedAt,
                ]);

                $application->mahasiswa->notify(new ApplicationStatusChanged(
                    $application->fresh(),
                    'Hasil seleksi beasiswa telah dipublikasikan',
                    'Hasil resmi pengajuan '.$application->nomor_pengajuan.' telah ditetapkan sebagai '.$target->label().'.',
                    route('mahasiswa.dashboard', absolute: false),
                ));
            }

            $slug = 'hasil-seleksi-'.Str::slug($period);
            $title = $announcementTitle ?: 'Pengumuman Hasil Seleksi Kartu Hebat Mahasiswa Periode '.$period;

            Announcement::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'type' => 'hasil',
                    'excerpt' => 'Hasil resmi seleksi Kartu Hebat Mahasiswa periode '.$period.' telah dipublikasikan.',
                    'body' => 'Pengumuman resmi dan Surat Keputusan (SK) penetapan penerima beasiswa dapat diunduh pada tautan berkas terlampir.',
                    'attachment_path' => $skPath,
                    'is_active' => true,
                    'published_at' => $publishedAt,
                    'created_by' => $operator->id,
                ],
            );

            return count($decisions);
        });
    }

    public function recordDecision(
        Application $application,
        ApplicationStatus $decision,
        User $operator,
        ?string $notes = null,
    ): Selection {
        if (! $application->application_type) {
            throw ValidationException::withMessages([
                'decision' => 'Jalur pengajuan kandidat belum ditetapkan.',
            ]);
        }

        $existingSelection = $application->selection;
        if ($existingSelection?->published_at) {
            throw ValidationException::withMessages([
                'decision' => 'Hasil yang sudah dipublikasikan tidak dapat diubah dari halaman ini.',
            ]);
        }

        return DB::transaction(function () use ($application, $decision, $operator, $notes, $existingSelection): Selection {
            if ($decision === ApplicationStatus::DITERIMA && ! $this->quotaAvailable($operator, $application, $existingSelection)) {
                throw ValidationException::withMessages([
                    'decision' => 'Kuota jalur '.$application->application_type->label().' sudah terpenuhi.',
                ]);
            }

            $selection = $application->selection;
            if (! $selection) {
                $selection = $this->scoring->calculate($application, $operator->id);
            }

            $selection->update([
                'manual_decision' => $decision->value,
                'notes' => $notes,
                'decided_by' => $operator->id,
                'decided_at' => now(),
            ]);

            VerificationLog::query()->create([
                'application_id' => $application->id,
                'actor_id' => $operator->id,
                'from_status' => $application->status->value,
                'to_status' => $application->status->value,
                'action' => 'selection_decision_recorded',
                'notes' => $notes,
                'metadata' => [
                    'manual_decision' => $decision->value,
                    'application_type' => $application->application_type->value,
                ],
                'created_at' => now(),
            ]);

            $this->scoring->recalculateRanking(
                $operator->kabupaten_id,
                applicationType: $application->application_type,
            );

            return $selection->fresh();
        });
    }

    private function quotaAvailable(User $operator, Application $application, ?Selection $existingSelection): bool
    {
        $acceptedCount = Selection::query()
            ->whereHas('application', fn ($query) => $query
                ->visibleTo($operator)
                ->where('periode', config('kartu_hebat.current_period'))
                ->where('application_type', $application->application_type->value))
            ->where('manual_decision', ApplicationStatus::DITERIMA->value)
            ->when($existingSelection, fn ($query) => $query->where('id', '!=', $existingSelection->id))
            ->lockForUpdate()
            ->count();

        return $acceptedCount < $application->application_type->quota();
    }

    public function recalculateRanking(
        ?int $kabupatenId = null,
        ?string $period = null,
        ?ApplicationType $applicationType = null,
    ): Collection {
        return $this->scoring->recalculateRanking($kabupatenId, $period, $applicationType);
    }
}
