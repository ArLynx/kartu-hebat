<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrangTuaSkDocumentController extends Controller
{
    public function preview(Application $application, string $peran): StreamedResponse
    {
        [$disk, $path, $namaOrtu] = $this->resolveDocument($application, $peran);

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        if (! in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            abort(415, 'Format dokumen SK Jabatan tidak mendukung pratinjau.');
        }

        return $disk->response($path, $this->downloadName($peran, $namaOrtu, $path), [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function download(Application $application, string $peran): StreamedResponse
    {
        [$disk, $path, $namaOrtu] = $this->resolveDocument($application, $peran);

        return $disk->download($path, $this->downloadName($peran, $namaOrtu, $path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array{0: FilesystemAdapter, 1: string, 2: string}
     */
    private function resolveDocument(Application $application, string $peran): array
    {
        abort_unless(in_array($peran, ['ayah', 'ibu', 'wali'], true), 404);

        $this->authorize('view', $application);

        $application->loadMissing('pendaftaran.orangTua');

        $orangTua = $application->pendaftaran?->orangTua;
        abort_unless($orangTua !== null, 404, 'Data orang tua/wali tidak ditemukan.');

        $column = 'sk_jabatan_'.$peran;
        $namaColumn = 'nama_'.$peran;
        $path = $orangTua->{$column};

        abort_unless(filled($path), 404, 'Berkas SK Jabatan tidak ditemukan.');

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(config('kartu_hebat.document_disk'));

        abort_unless($disk->exists($path), 404, 'File SK Jabatan tidak ditemukan di penyimpanan.');

        return [$disk, $path, (string) ($orangTua->{$namaColumn} ?: $peran)];
    }

    private function downloadName(string $peran, string $namaOrtu, string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

        return Str::slug('SK-Jabatan-'.ucfirst($peran).'-'.$namaOrtu).'.'.$extension;
    }
}
