<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationFormulirController extends Controller
{
    public function preview(Application $application, string $jenis): StreamedResponse
    {
        [$disk, $path, $name] = $this->resolveFormulir($application, $jenis);

        return $disk->response($path, $name, $this->responseHeaders(), 'inline');
    }

    public function download(Application $application, string $jenis): StreamedResponse
    {
        [$disk, $path, $name] = $this->resolveFormulir($application, $jenis);

        return $disk->download($path, $name, $this->responseHeaders());
    }

    /**
     * @return array{0: FilesystemAdapter, 1: string, 2: string}
     */
    private function resolveFormulir(Application $application, string $jenis): array
    {
        $this->authorize('view', $application);

        $application->loadMissing('pendaftaran.formulirPendaftaran');

        $column = match ($jenis) {
            'surat' => 'surat_permohonan',
            'pakta' => 'pakta_integritas',
            default => abort(404),
        };

        $path = $application->pendaftaran?->formulirPendaftaran?->{$column};

        abort_unless($path, 404, 'Formulir tidak ditemukan.');

        $disk = Storage::disk(config('kartu_hebat.document_disk'));

        abort_unless($disk->exists($path), 404, 'File formulir tidak ditemukan.');

        $mimeType = $disk->mimeType($path) ?: 'application/pdf';

        abort_unless($mimeType === 'application/pdf', 415, 'File formulir bukan PDF.');

        $label = $jenis === 'surat' ? 'Surat-Permohonan' : 'Pakta-Integritas';
        $nomorPengajuan = Str::slug($application->nomor_pengajuan);

        return [$disk, $path, "{$label}-{$nomorPengajuan}.pdf"];
    }

    /**
     * @return array<string, string>
     */
    private function responseHeaders(): array
    {
        return [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
