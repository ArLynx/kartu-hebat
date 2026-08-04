<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateDocumentController extends Controller
{
    public function preview(Document $document): StreamedResponse
    {
        $disk = $this->authorizedDisk($document);

        abort_unless($this->isPreviewable($document), 415, 'Format dokumen tidak mendukung pratinjau.');

        return $disk->response(
            $document->path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }

    public function download(Document $document): StreamedResponse
    {
        $disk = $this->authorizedDisk($document);

        return $disk->download(
            $document->path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    private function authorizedDisk(Document $document): FilesystemAdapter
    {
        $document->loadMissing('application');
        $this->authorize('view', $document->application);

        $disk = Storage::disk(config('kartu_hebat.document_disk'));

        abort_unless($disk->exists($document->path), 404);

        return $disk;
    }

    private function isPreviewable(Document $document): bool
    {
        return $document->mime_type === 'application/pdf'
            || in_array($document->mime_type, ['image/jpeg', 'image/png'], true);
    }
}
