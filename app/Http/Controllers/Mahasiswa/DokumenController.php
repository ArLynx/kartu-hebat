<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\Dokumen;
use App\Models\JenisDokumen;
use App\Models\Pendaftaran;
use App\Services\DocumentVerificationService;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DokumenController extends Controller
{
    private const SAFE_FORMATS = ['pdf', 'jpg', 'jpeg', 'png'];

    public function __construct(
        private readonly MahasiswaPendaftaranService $flow,
        private readonly DocumentVerificationService $documentVerifications,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Buat pendaftaran beasiswa terlebih dahulu.');
        }

        $pendaftaran->load(['dokumens.jenisDokumen']);

        return view('mahasiswa.dokumen.index', [
            'pendaftaran' => $pendaftaran,
            'requiredTypes' => $this->flow->requiredDocumentTypes($pendaftaran),
            'stepStatuses' => $this->flow->completion($pendaftaran),
            'canEdit' => $this->flow->isEditable($pendaftaran),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pendaftaran = $this->editablePendaftaran($request);

        $request->validate([
            'jenis_dokumen_id' => ['required', 'integer'],
        ]);

        $jenisDokumen = $this->flow
            ->requiredDocumentTypes($pendaftaran)
            ->firstWhere('id', $request->integer('jenis_dokumen_id'));

        if (! $jenisDokumen instanceof JenisDokumen) {
            return back()->withErrors([
                'jenis_dokumen_id' => 'Jenis dokumen tidak berlaku untuk kategori beasiswa ini.',
            ]);
        }

        $allowedFormats = $this->allowedFormats($jenisDokumen);
        $maxSizeKb = max(1, min((int) $jenisDokumen->maksimal_ukuran, 20 * 1024));

        $request->validate([
            'file' => ['required', File::types($allowedFormats)->max($maxSizeKb)],
        ], [
            'file.max' => 'Ukuran dokumen melebihi batas '.$maxSizeKb.' KB.',
        ]);

        $uploaded = $request->file('file');
        $diskName = config('kartu_hebat.document_disk');
        $oldDocument = $pendaftaran->dokumens()
            ->where('jenis_dokumen_id', $jenisDokumen->id)
            ->first();

        $extension = strtolower($uploaded->getClientOriginalExtension() ?: $uploaded->extension());
        if (! in_array($extension, $allowedFormats, true)) {
            throw ValidationException::withMessages([
                'file' => 'Ekstensi dokumen tidak diizinkan.',
            ]);
        }

        $path = $uploaded->storeAs(
            'pendaftarans/'.$pendaftaran->id.'/dokumen/'.$jenisDokumen->kode,
            Str::uuid().'.'.$extension,
            $diskName,
        );

        abort_unless($path, 500, 'Dokumen gagal disimpan.');

        $originalName = basename(str_replace('\\', '/', $uploaded->getClientOriginalName()));

        try {
            $document = DB::transaction(function () use ($pendaftaran, $jenisDokumen, $path, $uploaded, $originalName, $oldDocument): Dokumen {
                $document = $pendaftaran->dokumens()->updateOrCreate(
                    ['jenis_dokumen_id' => $jenisDokumen->id],
                    [
                        'file_path' => $path,
                        'nama_file_asli' => Str::limit($originalName, 255, ''),
                        'mime_type' => $uploaded->getMimeType() ?: 'application/octet-stream',
                        'ukuran_file' => $uploaded->getSize(),
                        'status' => 'uploaded',
                        'catatan' => null,
                        'verified_at' => null,
                    ],
                );

                if ($oldDocument && $oldDocument->file_path !== $path) {
                    $documentTypeId = DocumentType::query()
                        ->where('code', $jenisDokumen->kode)
                        ->value('id');

                    $target = $documentTypeId === null
                        ? null
                        : $pendaftaran->application?->documents()
                            ->where('document_type_id', $documentTypeId)
                            ->first();

                    if ($target) {
                        $this->documentVerifications->resetForDocument($target);
                    }
                }

                $pendaftaran->forceFill(['review_dikonfirmasi_at' => null])->save();

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk($diskName)->delete($path);

            throw $exception;
        }

        if ($oldDocument && $oldDocument->file_path !== $document->file_path) {
            Storage::disk($diskName)->delete($oldDocument->file_path);
        }

        return back()->with('success', $jenisDokumen->nama.' berhasil diunggah.');
    }

    public function destroy(Request $request, Dokumen $dokumen): RedirectResponse
    {
        $pendaftaran = $this->editablePendaftaran($request);
        $this->assertOwned($dokumen, $pendaftaran->id);

        $path = $dokumen->file_path;

        DB::transaction(function () use ($dokumen, $pendaftaran): void {
            $dokumen->delete();
            $pendaftaran->forceFill(['review_dikonfirmasi_at' => null])->save();
        });

        Storage::disk(config('kartu_hebat.document_disk'))->delete($path);

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function preview(Request $request, Dokumen $dokumen): StreamedResponse
    {
        [$disk, $mimeType] = $this->authorizedDisk($request, $dokumen);

        abort_unless(
            $mimeType === 'application/pdf' || in_array($mimeType, ['image/jpeg', 'image/png'], true),
            415,
            'Format dokumen tidak mendukung pratinjau.',
        );

        return $disk->response(
            $dokumen->file_path,
            $this->downloadName($dokumen),
            $this->privateHeaders($mimeType),
            'inline',
        );
    }

    public function download(Request $request, Dokumen $dokumen): StreamedResponse
    {
        [$disk, $mimeType] = $this->authorizedDisk($request, $dokumen);

        return $disk->download(
            $dokumen->file_path,
            $this->downloadName($dokumen),
            $this->privateHeaders($mimeType),
        );
    }

    private function editablePendaftaran(Request $request): Pendaftaran
    {
        $pendaftaran = $this->flow->currentFor($request->user());
        abort_unless($pendaftaran, 404);
        abort_unless($this->flow->isEditable($pendaftaran), 403, 'Pendaftaran tidak dapat diubah.');

        return $pendaftaran;
    }

    /**
     * @return array{0: FilesystemAdapter, 1: string}
     */
    private function authorizedDisk(Request $request, Dokumen $dokumen): array
    {
        $pendaftaran = $this->flow->currentFor($request->user());
        abort_unless($pendaftaran, 404);
        $this->assertOwned($dokumen, $pendaftaran->id);

        $disk = Storage::disk(config('kartu_hebat.document_disk'));
        abort_unless($disk->exists($dokumen->file_path), 404);

        $mimeType = $dokumen->mime_type ?: ($disk->mimeType($dokumen->file_path) ?: 'application/octet-stream');

        return [$disk, $mimeType];
    }

    private function assertOwned(Dokumen $dokumen, int $pendaftaranId): void
    {
        abort_unless((int) $dokumen->pendaftaran_id === $pendaftaranId, 404);
    }

    /**
     * @return array<int, string>
     */
    private function allowedFormats(JenisDokumen $jenisDokumen): array
    {
        $configured = preg_split('/[\s,|;\/]+/', strtolower((string) $jenisDokumen->format_file)) ?: [];
        $allowed = array_values(array_intersect(array_unique(array_filter($configured)), self::SAFE_FORMATS));

        return $allowed !== [] ? $allowed : ['pdf'];
    }

    private function downloadName(Dokumen $dokumen): string
    {
        if ($dokumen->nama_file_asli) {
            return basename($dokumen->nama_file_asli);
        }

        return basename($dokumen->file_path);
    }

    private function privateHeaders(string $mimeType): array
    {
        return [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
