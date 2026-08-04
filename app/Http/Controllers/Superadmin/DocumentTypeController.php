<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\JenisDokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        $types = DocumentType::query()
            ->withCount('documents')
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        $mirrorByCode = JenisDokumen::query()
            ->whereIn('kode', $types->getCollection()->pluck('code'))
            ->get()
            ->keyBy('kode');

        return view('superadmin.document-types.index', compact('types', 'mirrorByCode'));
    }

    public function create(): View
    {
        return view('superadmin.document-types.create', [
            'applicationTypes' => ApplicationType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $type = DB::transaction(function () use ($validated): DocumentType {
            $type = DocumentType::query()->create($validated);
            $this->syncIntegratedType($type);

            return $type;
        });

        return redirect()
            ->route('superadmin.document-types.edit', $type)
            ->with('success', 'Document type berhasil ditambahkan dan disinkronkan ke alur pendaftaran.');
    }

    public function edit(DocumentType $documentType): View
    {
        return view('superadmin.document-types.edit', [
            'documentType' => $documentType,
            'applicationTypes' => ApplicationType::cases(),
        ]);
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $validated = $this->validatedData($request, $documentType);
        $oldCode = $documentType->code;

        DB::transaction(function () use ($documentType, $validated, $oldCode): void {
            $oldMirror = JenisDokumen::query()->where('kode', $oldCode)->first();
            $targetMirror = JenisDokumen::query()->where('kode', $validated['code'])->first();

            if (
                $oldCode !== $validated['code']
                && $targetMirror
                && (! $oldMirror || ! $targetMirror->is($oldMirror))
            ) {
                throw ValidationException::withMessages([
                    'code' => 'Kode tersebut sudah digunakan pada master jenis dokumen terintegrasi.',
                ]);
            }

            $documentType->update($validated);
            $this->syncIntegratedType($documentType, $oldMirror);
        });

        return redirect()
            ->route('superadmin.document-types.edit', $documentType)
            ->with('success', 'Document type berhasil diperbarui dan disinkronkan.');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->documents()->exists()) {
            return back()->with(
                'error',
                'Document type tidak dapat dihapus karena sudah digunakan pada dokumen pengajuan. Nonaktifkan sebagai gantinya.',
            );
        }

        $mirror = JenisDokumen::query()->where('kode', $documentType->code)->first();

        if ($mirror && ($mirror->dokumens()->exists() || $mirror->kategoriBeasiswas()->exists())) {
            return back()->with(
                'error',
                'Document type tidak dapat dihapus karena sudah menjadi persyaratan kategori atau memiliki unggahan. Nonaktifkan sebagai gantinya.',
            );
        }

        DB::transaction(function () use ($documentType, $mirror): void {
            $documentType->delete();

            $mirror?->delete();
        });

        return redirect()
            ->route('superadmin.document-types.index')
            ->with('success', 'Document type berhasil dihapus.');
    }

    private function validatedData(Request $request, ?DocumentType $documentType = null): array
    {
        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('document_types', 'code')->ignore($documentType),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'application_type' => ['nullable', Rule::enum(ApplicationType::class)],
            'allowed_mimes' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9.+-]+(?:\s*,\s*[A-Za-z0-9.+-]+)*$/',
            ],
            'max_size_kb' => ['required', 'integer', 'min:1', 'max:102400'],
            'is_required' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ], [
            'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, tanda hubung, dan garis bawah.',
            'allowed_mimes.regex' => 'Format file harus berupa daftar ekstensi yang dipisahkan koma, misalnya pdf,jpg,png.',
        ]);

        $validated['allowed_mimes'] = collect(preg_split('/\s*,\s*/', $validated['allowed_mimes']))
            ->filter()
            ->map(fn (string $mime): string => strtolower(ltrim($mime, '.')))
            ->unique()
            ->values()
            ->all();

        return $validated;
    }

    private function syncIntegratedType(DocumentType $documentType, ?JenisDokumen $mirror = null): JenisDokumen
    {
        $mirror ??= JenisDokumen::query()->where('kode', $documentType->code)->first();

        $attributes = [
            'kode' => $documentType->code,
            'nama' => $documentType->name,
            'deskripsi' => $documentType->description,
            'format_file' => implode(',', $documentType->allowed_mimes ?: ['pdf']),
            'maksimal_ukuran' => $documentType->max_size_kb,
            'aktif' => $documentType->is_active,
        ];

        if ($mirror) {
            $mirror->update($attributes);

            return $mirror;
        }

        return JenisDokumen::query()->create($attributes);
    }
}
