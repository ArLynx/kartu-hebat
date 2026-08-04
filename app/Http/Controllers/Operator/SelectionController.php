<?php

namespace App\Http\Controllers\Operator;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SelectionRequest;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\Selection;
use App\Models\VerificationLog;
use App\Notifications\ApplicationStatusChanged;
use App\Services\SelectionScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SelectionController extends Controller
{
    public function index(Request $request, SelectionScoringService $scoring)
    {
        $kabupatenId = $request->user()->kabupaten_id;
        $selectedType = ApplicationType::tryFrom($request->string('application_type')->toString())
            ?? ApplicationType::AKADEMIK;

        Application::query()
            ->visibleTo($request->user())
            ->where('periode', config('kartu_hebat.current_period'))
            ->whereIn('status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ])
            ->whereNotNull('application_type')
            ->with('mahasiswa.profile')
            ->get()
            ->each(fn (Application $application) => $scoring->calculate($application, $request->user()->id));

        $scoring->recalculateRanking($kabupatenId);

        $query = Application::query()
            ->visibleTo($request->user())
            ->where('periode', config('kartu_hebat.current_period'))
            ->where('application_type', $selectedType->value)
            ->with(['mahasiswa.profile.village.kecamatan', 'selection', 'scores.criterion'])
            ->whereIn('status', [
                ApplicationStatus::SELEKSI_KABUPATEN->value,
                ApplicationStatus::DITERIMA->value,
                ApplicationStatus::DITOLAK->value,
            ]);

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim()->toString().'%';
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('applications.nomor_pengajuan', 'like', $search)
                    ->orWhereHas('mahasiswa', fn ($student) => $student->where('name', 'like', $search));
            });
        }

        $countySelections = Selection::query()
            ->whereHas('application', fn ($application) => $application
                ->visibleTo($request->user())
                ->where('periode', config('kartu_hebat.current_period'))
                ->where('application_type', $selectedType->value));

        $typeCounts = collect(ApplicationType::cases())->mapWithKeys(function (ApplicationType $type) use ($request): array {
            $count = Application::query()
                ->visibleTo($request->user())
                ->where('periode', config('kartu_hebat.current_period'))
                ->where('application_type', $type->value)
                ->whereIn('status', [
                    ApplicationStatus::SELEKSI_KABUPATEN->value,
                    ApplicationStatus::DITERIMA->value,
                    ApplicationStatus::DITOLAK->value,
                ])
                ->count();

            return [$type->value => $count];
        });

        return view('operator.selection', [
            'applications' => $query
                ->leftJoin('selections', 'selections.application_id', '=', 'applications.id')
                ->select('applications.*')
                ->orderByRaw('COALESCE(selections.rank, 999999)')
                ->paginate(20)
                ->withQueryString(),
            'applicationTypes' => ApplicationType::cases(),
            'selectedType' => $selectedType,
            'typeCounts' => $typeCounts,
            'quota' => $selectedType->quota(),
            'acceptedCount' => (clone $countySelections)
                ->where('manual_decision', ApplicationStatus::DITERIMA->value)
                ->count(),
            'publishedCount' => (clone $countySelections)
                ->whereNotNull('published_at')
                ->count(),
        ]);
    }

    public function store(
        SelectionRequest $request,
        Application $application,
        SelectionScoringService $scoring,
    ) {
        $this->authorize('select', $application);
        $data = $request->validated();
        $target = ApplicationStatus::from($data['decision']);
        $existingSelection = $application->selection;

        if (!$application->application_type) {
            throw ValidationException::withMessages([
                'decision' => 'Jalur pengajuan kandidat belum ditetapkan.',
            ]);
        }

        if ($existingSelection?->published_at) {
            throw ValidationException::withMessages([
                'decision' => 'Hasil yang sudah dipublikasikan tidak dapat diubah dari halaman ini.',
            ]);
        }

        $acceptedCount = Selection::query()
            ->whereHas('application', fn ($query) => $query
                ->visibleTo($request->user())
                ->where('periode', config('kartu_hebat.current_period'))
                ->where('application_type', $application->application_type->value))
            ->where('manual_decision', ApplicationStatus::DITERIMA->value)
            ->when($existingSelection, fn ($query) => $query->where('id', '!=', $existingSelection->id))
            ->count();

        if ($target === ApplicationStatus::DITERIMA && $acceptedCount >= $application->application_type->quota()) {
            throw ValidationException::withMessages([
                'decision' => 'Kuota jalur '.$application->application_type->label().' sudah terpenuhi.',
            ]);
        }

        DB::transaction(function () use ($request, $application, $data, $target, $scoring): void {
            $selection = $application->selection;

            if (!$selection) {
                $selection = $scoring->calculate($application, $request->user()->id);
            }

            $selection->update([
                'manual_decision' => $target->value,
                'notes' => $data['notes'] ?? null,
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ]);

            VerificationLog::query()->create([
                'application_id' => $application->id,
                'actor_id' => $request->user()->id,
                'from_status' => $application->status->value,
                'to_status' => $application->status->value,
                'action' => 'selection_decision_recorded',
                'notes' => $data['notes'] ?? null,
                'metadata' => [
                    'manual_decision' => $target->value,
                    'application_type' => $application->application_type->value,
                ],
                'created_at' => now(),
            ]);

            $scoring->recalculateRanking(
                $request->user()->kabupaten_id,
                applicationType: $application->application_type,
            );
        });

        return back()->with('success', 'Keputusan internal kandidat berhasil disimpan.');
    }

    public function publish(Request $request)
    {
        $decided = Selection::query()
            ->whereHas('application', fn ($application) => $application
                ->visibleTo($request->user())
                ->where('periode', config('kartu_hebat.current_period')))
            ->whereNotNull('manual_decision')
            ->whereNull('published_at')
            ->with('application.mahasiswa')
            ->get();

        if ($decided->isEmpty()) {
            return back()->with('warning', 'Tidak ada keputusan baru yang siap dipublikasikan.');
        }

        DB::transaction(function () use ($request, $decided): void {
            $publishedAt = now();

            foreach ($decided as $selection) {
                $application = $selection->application;
                $target = ApplicationStatus::from($selection->manual_decision);
                $from = $application->status;

                $selection->update(['published_at' => $publishedAt]);
                $application->update([
                    'status' => $target,
                    'current_step' => 6,
                    'catatan' => $selection->notes,
                    'locked_at' => $publishedAt,
                ]);

                VerificationLog::query()->create([
                    'application_id' => $application->id,
                    'actor_id' => $request->user()->id,
                    'from_status' => $from->value,
                    'to_status' => $target->value,
                    'action' => 'selection_result_published',
                    'notes' => $selection->notes,
                    'metadata' => ['application_type' => $application->application_type?->value],
                    'created_at' => $publishedAt,
                ]);

                $application->mahasiswa->notify(new ApplicationStatusChanged(
                    $application->fresh(),
                    'Hasil seleksi telah dipublikasikan',
                    'Hasil resmi pengajuan '.$application->nomor_pengajuan.' sudah dapat diperiksa.',
                    route('student.history', absolute: false),
                ));
            }

            $slug = 'hasil-seleksi-'.Str::slug(config('kartu_hebat.current_period'));

            Announcement::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => 'Pengumuman Hasil Seleksi Kartu Hebat Mahasiswa',
                    'type' => 'hasil',
                    'excerpt' => 'Hasil seleksi jalur Akademik dan Tidak Mampu periode '.config('kartu_hebat.current_period').' telah dipublikasikan.',
                    'body' => 'Peserta dapat memeriksa hasil menggunakan nomor pengajuan atau NIK pada halaman pengumuman.',
                    'is_active' => true,
                    'published_at' => $publishedAt,
                    'created_by' => $request->user()->id,
                ],
            );
        });

        return back()->with('success', $decided->count().' hasil seleksi berhasil dipublikasikan.');
    }
}
