<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class DocumentController extends Controller
{
    public function store(Request $request, Application $application)
    {
        $this->authorize('update', $application);

        $request->validate([
            'document_type_id' => [
                'required',
                'integer',
                'exists:document_types,id',
            ],
        ]);

        $type = DocumentType::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('document_type_id'));

        if ($type->application_type && $type->application_type !== $application->application_type) {
            return back()->withErrors([
                'document_type_id' => 'Dokumen ini tidak berlaku untuk jalur pengajuan yang dipilih.',
            ]);
        }

        $request->validate([
            'file' => [
                'required',
                File::types($type->allowed_mimes ?: ['pdf', 'jpg', 'jpeg', 'png'])
                    ->max($type->max_size_kb),
            ],
        ], [
            'file.max' => 'Ukuran dokumen melebihi batas '.$type->max_size_kb.' KB.',
        ]);

        $uploaded = $request->file('file');
        $disk = config('kartu_hebat.document_disk');
        $existing = $application->documents()
            ->where('document_type_id', $type->id)
            ->first();

        $filename = Str::uuid().'.'.strtolower($uploaded->getClientOriginalExtension());
        $path = $uploaded->storeAs(
            'applications/'.$application->id.'/'.$type->code,
            $filename,
            $disk,
        );

        abort_unless($path, 500, 'Dokumen gagal disimpan.');

        $checksum = hash_file('sha256', $uploaded->getRealPath());

        $document = $application->documents()->updateOrCreate(
            ['document_type_id' => $type->id],
            [
                'uploaded_by' => $request->user()->id,
                'path' => $path,
                'original_name' => $uploaded->getClientOriginalName(),
                'mime_type' => $uploaded->getMimeType() ?: 'application/octet-stream',
                'size' => $uploaded->getSize(),
                'checksum' => $checksum,
                'version' => ($existing?->version ?? 0) + 1,
                'verified_at' => null,
            ],
        );

        if ($existing && $existing->path !== $document->path) {
            Storage::disk($disk)->delete($existing->path);
        }

        return back()->with('success', $type->name.' berhasil diunggah.');
    }

    public function destroy(Request $request, Application $application, Document $document)
    {
        $this->authorize('update', $application);
        abort_unless($document->application_id === $application->id, 404);

        Storage::disk(config('kartu_hebat.document_disk'))->delete($document->path);
        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
