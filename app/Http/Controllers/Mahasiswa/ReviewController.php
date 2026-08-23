<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly MahasiswaPendaftaranService $flow)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Buat pendaftaran beasiswa terlebih dahulu.');
        }

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'dataPribadi',
            'pendidikan',
            'prestasis',
            'orangTua',
            'dokumens.jenisDokumen',
            'application.documents.type',
            'application.documents.verifications',
        ]);

        return view('mahasiswa.review.index', [
            'pendaftaran' => $pendaftaran,
            'requiredTypes' => $this->flow->requiredDocumentTypes($pendaftaran),
            'stepStatuses' => $this->flow->completion($pendaftaran),
            'missingStages' => $this->flow->missingStageLabels($pendaftaran),
            'canEdit' => $this->flow->isEditable($pendaftaran),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);
        abort_unless($this->flow->isEditable($pendaftaran), 403, 'Pendaftaran tidak dapat diubah.');

        $missingStages = $this->flow->missingStageLabels($pendaftaran);

        if ($missingStages !== []) {
            return redirect()
                ->route('mahasiswa.review.index')
                ->with('error', 'Lengkapi tahap berikut sebelum mengonfirmasi review: '.implode(', ', $missingStages).'.');
        }

        $pendaftaran->forceFill(['review_dikonfirmasi_at' => now()])->save();

        return redirect()
            ->route('mahasiswa.submit.index')
            ->with('success', 'Review telah dikonfirmasi. Pendaftaran siap disubmit.');
    }
}
