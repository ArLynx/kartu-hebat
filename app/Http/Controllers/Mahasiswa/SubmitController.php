<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use App\Services\ApplicationWorkflowService;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubmitController extends Controller
{
    public function __construct(
        private readonly MahasiswaPendaftaranService $flow,
        private readonly ApplicationWorkflowService $workflow,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Buat pendaftaran beasiswa terlebih dahulu.');
        }

        $pendaftaran->load(['periode', 'kategoriBeasiswa', 'application']);

        return view('mahasiswa.submit.index', [
            'pendaftaran' => $pendaftaran,
            'stepStatuses' => $this->flow->completion($pendaftaran),
            'missingStages' => $this->flow->missingStageLabels($pendaftaran, true),
            'canEdit' => $this->flow->isEditable($pendaftaran),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pernyataan_kebenaran' => ['accepted'],
            'pernyataan_final' => ['accepted'],
        ], [
            'pernyataan_kebenaran.accepted' => 'Pernyataan kebenaran data harus disetujui.',
            'pernyataan_final.accepted' => 'Konfirmasi penguncian pendaftaran harus disetujui.',
        ]);

        $result = DB::transaction(function () use ($request): array {
            $pendaftaran = Pendaftaran::query()
                ->where('user_id', $request->user()->getKey())
                ->whereHas('periode', fn ($query) => $query->aktif())
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $pendaftaran) {
                return ['status' => 'missing'];
            }

            if (! $this->flow->isEditable($pendaftaran)) {
                return ['status' => 'locked'];
            }

            $missingStages = $this->flow->missingStageLabels($pendaftaran, true);

            if ($missingStages !== []) {
                return ['status' => 'incomplete', 'missing' => $missingStages];
            }

            $application = $this->workflow->submit($pendaftaran, $request->user());

            return [
                'status' => 'submitted',
                'application_status' => $application->status->label(),
            ];
        });

        return match ($result['status']) {
            'submitted' => redirect()
                ->route('mahasiswa.bukti-pendaftaran.index')
                ->with(
                    'success',
                    'Pendaftaran berhasil dikirim. Bukti pendaftaran Anda tersedia.'
                ),
            'incomplete' => redirect()
                ->route('mahasiswa.review.index')
                ->with('error', 'Lengkapi tahap berikut sebelum submit: '.implode(', ', $result['missing']).'.'),
            'locked' => redirect()
                ->route('mahasiswa.dashboard')
                ->with('warning', 'Pendaftaran sudah dikirim atau tidak dapat diubah.'),
            default => redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Pendaftaran tidak ditemukan.'),
        };
    }
}
