<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use App\Models\Prestasi;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PrestasiController extends Controller
{
    private const CERTIFICATE_FORMATS = ['pdf', 'jpg', 'jpeg', 'png'];

    public function __construct(private readonly MahasiswaPendaftaranService $flow) {}

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Buat pendaftaran beasiswa terlebih dahulu.');
        }

        $pendaftaran->load(['prestasis' => fn ($query) => $query->latest('tahun')->latest('id')]);

        return view('mahasiswa.prestasi.index', [
            'pendaftaran' => $pendaftaran,
            'stepStatuses' => $this->flow->completion($pendaftaran),
            'canEdit' => $this->flow->isEditable($pendaftaran),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $pendaftaran = $this->editablePendaftaran($request);
        $validated = $this->validatedPrestasi($request);
        $path = $this->storeCertificate($request, $pendaftaran->id);

        if ($path) {
            $validated['dokumen_prestasi'] = $path;
        }

        try {
            DB::transaction(function () use ($pendaftaran, $validated): void {
                $pendaftaran->prestasis()->create($validated);
                $pendaftaran->forceFill([
                    'prestasi_dikonfirmasi_at' => now(),
                    'review_dikonfirmasi_at' => null,
                ])->save();
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk(config('kartu_hebat.document_disk'))->delete($path);
            }

            throw $exception;
        }

        return back()->with('success', 'Prestasi berhasil ditambahkan.');
    }

    public function update(Request $request, Prestasi $prestasi): RedirectResponse
    {
        $pendaftaran = $this->editablePendaftaran($request);
        $this->assertOwned($prestasi, $pendaftaran->id);

        $validated = $this->validatedPrestasi($request);
        $oldPath = $prestasi->dokumen_prestasi;
        $newPath = $this->storeCertificate($request, $pendaftaran->id);

        if ($newPath) {
            $validated['dokumen_prestasi'] = $newPath;
        }

        try {
            DB::transaction(function () use ($prestasi, $pendaftaran, $validated): void {
                $prestasi->update($validated);
                $pendaftaran->forceFill([
                    'prestasi_dikonfirmasi_at' => now(),
                    'review_dikonfirmasi_at' => null,
                ])->save();
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk(config('kartu_hebat.document_disk'))->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && $oldPath && $oldPath !== $newPath) {
            Storage::disk(config('kartu_hebat.document_disk'))->delete($oldPath);
        }

        return back()->with('success', 'Prestasi berhasil diperbarui.');
    }

    public function destroy(Request $request, Prestasi $prestasi): RedirectResponse
    {
        $pendaftaran = $this->editablePendaftaran($request);
        $this->assertOwned($prestasi, $pendaftaran->id);

        $documentPath = $prestasi->dokumen_prestasi;

        DB::transaction(function () use ($prestasi, $pendaftaran): void {
            $prestasi->delete();
            $pendaftaran->forceFill([
                'prestasi_dikonfirmasi_at' => now(),
                'review_dikonfirmasi_at' => null,
            ])->save();
        });

        if ($documentPath) {
            Storage::disk(config('kartu_hebat.document_disk'))->delete($documentPath);
        }

        return back()->with('success', 'Prestasi berhasil dihapus.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $pendaftaran = $this->editablePendaftaran($request);
        $pendaftaran->forceFill([
            'prestasi_dikonfirmasi_at' => now(),
            'review_dikonfirmasi_at' => null,
        ])->save();

        return redirect()
            ->route('mahasiswa.orang-tua.index')
            ->with('success', 'Tahap prestasi telah dikonfirmasi.');
    }

    public function download(Request $request, Prestasi $prestasi): StreamedResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());
        abort_unless($pendaftaran, 404);
        $this->assertOwned($prestasi, $pendaftaran->id);
        abort_unless($prestasi->dokumen_prestasi, 404);

        $disk = Storage::disk(config('kartu_hebat.document_disk'));
        abort_unless($disk->exists($prestasi->dokumen_prestasi), 404);

        return $disk->download(
            $prestasi->dokumen_prestasi,
            basename($prestasi->dokumen_prestasi),
            $this->privateHeaders($disk, $prestasi->dokumen_prestasi),
        );
    }

    private function editablePendaftaran(Request $request): Pendaftaran
    {
        $pendaftaran = $this->flow->currentFor($request->user());
        abort_unless($pendaftaran, 404);
        abort_unless($this->flow->isEditable($pendaftaran), 403, 'Pendaftaran tidak dapat diubah.');

        return $pendaftaran;
    }

    private function validatedPrestasi(Request $request): array
    {
        $validated = $request->validate([
            'jenis' => ['required', Rule::in(['akademik', 'non_akademik'])],
            'nama_prestasi' => ['required', 'string', 'max:200'],
            'tingkat' => [
                'required',
                Rule::in(['kampus', 'kabupaten', 'provinsi', 'nasional', 'internasional']),
            ],
            'peringkat' => ['required', 'string', 'max:100'],
            'penyelenggara' => ['required', 'string', 'max:200'],
            'tahun' => ['required', 'integer', 'digits:4', 'between:1990,'.now()->year],
            'dokumen' => ['nullable', File::types(self::CERTIFICATE_FORMATS)->max(2048)],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ]);

        unset($validated['dokumen']);

        return $validated;
    }

    private function storeCertificate(Request $request, int $pendaftaranId): ?string
    {
        $uploaded = $request->file('dokumen');

        if (! $uploaded) {
            return null;
        }

        $extension = strtolower($uploaded->getClientOriginalExtension() ?: $uploaded->extension());
        if (! in_array($extension, self::CERTIFICATE_FORMATS, true)) {
            throw ValidationException::withMessages([
                'dokumen' => 'Ekstensi dokumen prestasi tidak diizinkan.',
            ]);
        }

        $filename = Str::uuid().'.'.$extension;
        $path = $uploaded->storeAs(
            'pendaftarans/'.$pendaftaranId.'/prestasi',
            $filename,
            config('kartu_hebat.document_disk'),
        );

        abort_unless($path, 500, 'Dokumen prestasi gagal disimpan.');

        return $path;
    }

    private function assertOwned(Prestasi $prestasi, int $pendaftaranId): void
    {
        abort_unless((int) $prestasi->pendaftaran_id === $pendaftaranId, 404);
    }

    private function privateHeaders(FilesystemAdapter $disk, string $path): array
    {
        return [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
