<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Prestasi;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrestasiDocumentController extends Controller
{
    public function preview(Application $application, Prestasi $prestasi): StreamedResponse
    {
        [$disk, $path] = $this->resolveDocument($application, $prestasi);

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        if (! in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            abort(415, 'Format dokumen tidak mendukung pratinjau.');
        }

        return $disk->response($path, $this->downloadName($prestasi, $path), [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function download(Application $application, Prestasi $prestasi): StreamedResponse
    {
        [$disk, $path] = $this->resolveDocument($application, $prestasi);

        return $disk->download($path, $this->downloadName($prestasi, $path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array{0: FilesystemAdapter, 1: string}
     */
    private function resolveDocument(Application $application, Prestasi $prestasi): array
    {
        $this->authorize('view', $application);

        $application->loadMissing('pendaftaran');

        abort_unless(
            $application->pendaftaran_id !== null
                && (int) $prestasi->pendaftaran_id === (int) $application->pendaftaran_id,
            404,
        );

        abort_unless(filled($prestasi->dokumen_prestasi), 404, 'Dokumen prestasi tidak ditemukan.');

        $disk = Storage::disk(config('kartu_hebat.document_disk'));

        abort_unless($disk->exists($prestasi->dokumen_prestasi), 404, 'File dokumen prestasi tidak ditemukan.');

        return [$disk, $prestasi->dokumen_prestasi];
    }

    private function downloadName(Prestasi $prestasi, string $path): string
    {
        $label = $prestasi->is_pengurus_inti_ormawa ? 'SK-Organisasi' : 'Sertifikat-Prestasi';
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

        return Str::slug($label.'-'.$prestasi->nama_prestasi).'.'.$extension;
    }
}
