<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KategoriBeasiswaController extends Controller
{
    public function index(Request $request): View
    {
        $periods = Periode::query()
            ->orderByDesc('tahun')
            ->orderByDesc('tanggal_mulai')
            ->get();

        $activePeriod = $periods->firstWhere('status', 'aktif');

        $query = KategoriBeasiswa::query()
            ->with(['periode', 'jenisDokumens'])
            ->withCount('pendaftarans');

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                    ->orWhere('kode', 'like', $search)
                    ->orWhere('deskripsi', 'like', $search);
            });
        }

        // Tentukan filter periode terpilih:
        // 1. Jika di URL terdapat 'periode_id':
        //    - 'all': tampilkan seluruh periode
        //    - angka: filter berdasarkan ID periode tersebut
        // 2. Jika di URL belum ada 'periode_id' (pertama kali buka menu):
        //    - default otomatis ke periode aktif
        $selectedPeriodeId = 'all';
        if ($request->has('periode_id')) {
            $val = $request->input('periode_id');
            if ($val !== 'all' && $val !== '' && $val !== null) {
                $selectedPeriodeId = (int) $val;
                $query->where('periode_id', $selectedPeriodeId);
            }
        } elseif ($activePeriod) {
            $selectedPeriodeId = (int) $activePeriod->id;
            $query->where('periode_id', $selectedPeriodeId);
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $query->where('aktif', true);
            } elseif ($status === 'inactive') {
                $query->where('aktif', false);
            }
        }

        if ($request->filled('application_type')) {
            $query->where('application_type', $request->string('application_type'));
        }

        $categories = $query
            ->orderByDesc('aktif')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.kategori-beasiswa.index', compact('categories', 'periods', 'activePeriod', 'selectedPeriodeId'));
    }

    public function create(): View
    {
        return view('superadmin.kategori-beasiswa.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'kode' => Str::upper(trim((string) $request->input('kode'))),
            'aktif' => $request->boolean('aktif'),
        ]);

        $validated = $this->validateCategory($request);
        $documentIds = array_values($validated['jenis_dokumen_ids'] ?? []);
        unset($validated['jenis_dokumen_ids']);

        $category = DB::transaction(function () use ($validated, $documentIds): KategoriBeasiswa {
            $category = KategoriBeasiswa::query()->create($validated);
            $this->syncDocuments($category, $documentIds);

            return $category;
        });

        return redirect()
            ->route('superadmin.kategori-beasiswa.edit', $category)
            ->with('success', 'Kategori beasiswa berhasil ditambahkan.');
    }

    public function edit(KategoriBeasiswa $kategoriBeasiswa): View
    {
        $kategoriBeasiswa->load('jenisDokumens');

        return view('superadmin.kategori-beasiswa.edit', array_merge(
            $this->formData(),
            ['category' => $kategoriBeasiswa],
        ));
    }

    public function update(Request $request, KategoriBeasiswa $kategoriBeasiswa): RedirectResponse
    {
        $request->merge([
            'kode' => Str::upper(trim((string) $request->input('kode'))),
            'aktif' => $request->boolean('aktif'),
        ]);

        $validated = $this->validateCategory($request, $kategoriBeasiswa);
        $documentIds = array_values($validated['jenis_dokumen_ids'] ?? []);
        unset($validated['jenis_dokumen_ids']);

        DB::transaction(function () use ($kategoriBeasiswa, $validated, $documentIds): void {
            $kategoriBeasiswa->update($validated);
            $this->syncDocuments($kategoriBeasiswa, $documentIds);
        });

        return redirect()
            ->route('superadmin.kategori-beasiswa.edit', $kategoriBeasiswa)
            ->with('success', 'Kategori beasiswa berhasil diperbarui.');
    }

    public function destroy(KategoriBeasiswa $kategoriBeasiswa): RedirectResponse
    {
        if ($kategoriBeasiswa->pendaftarans()->exists()) {
            return back()->with(
                'error',
                'Kategori tidak dapat dihapus karena sudah digunakan pada pendaftaran. Nonaktifkan kategori sebagai gantinya.',
            );
        }

        $kategoriBeasiswa->delete();

        return redirect()
            ->route('superadmin.kategori-beasiswa.index')
            ->with('success', 'Kategori beasiswa berhasil dihapus.');
    }

    private function validateCategory(Request $request, ?KategoriBeasiswa $category = null): array
    {
        $documentExists = Rule::exists('jenis_dokumens', 'id');

        if ($request->boolean('aktif')) {
            $documentExists->where('aktif', true);
        }

        return $request->validate([
            'periode_id' => ['required', 'integer', Rule::exists('periodes', 'id')],
            'kode' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
                Rule::unique('kategori_beasiswas', 'kode')->ignore($category),
            ],
            'application_type' => ['required', Rule::enum(ApplicationType::class)],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'kuota' => ['required', 'integer', 'min:0', 'max:1000000'],
            'aktif' => ['required', 'boolean'],
            'urutan' => ['required', 'integer', 'min:1', 'max:255'],
            'icon' => ['nullable', 'string', 'max:50'],
            'warna' => ['nullable', 'string', 'max:30'],
            'jenis_dokumen_ids' => [
                Rule::requiredIf($request->boolean('aktif')),
                'array',
                'min:1',
            ],
            'jenis_dokumen_ids.*' => ['integer', 'distinct', $documentExists],
        ], [
            'kode.regex' => 'Kode hanya boleh berisi huruf kapital, angka, tanda hubung, dan garis bawah.',
            'jenis_dokumen_ids.required' => 'Kategori aktif wajib memiliki minimal satu dokumen persyaratan aktif.',
            'jenis_dokumen_ids.min' => 'Pilih minimal satu dokumen persyaratan.',
            'jenis_dokumen_ids.*.exists' => 'Dokumen persyaratan harus aktif dan tersedia.',
        ]);
    }

    private function formData(): array
    {
        return [
            'periods' => Periode::query()
                ->orderByDesc('tahun')
                ->orderByDesc('tanggal_mulai')
                ->get(),
            'applicationTypes' => ApplicationType::cases(),
            'documentTypes' => JenisDokumen::query()
                ->orderByDesc('aktif')
                ->orderBy('nama')
                ->get(),
        ];
    }

    /**
     * @param  array<int, int|string>  $documentIds
     */
    private function syncDocuments(KategoriBeasiswa $category, array $documentIds): void
    {
        $syncData = [];

        foreach ($documentIds as $index => $documentId) {
            $syncData[(int) $documentId] = ['urutan' => $index + 1];
        }

        $category->jenisDokumens()->sync($syncData);
    }
}
