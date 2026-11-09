<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\KategoriPrestasi;
use App\Enums\PosisiOrganisasi;
use App\Enums\TingkatOrganisasi;
use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use App\Models\Prestasi;
use App\Services\MahasiswaPendaftaranService;
use App\Services\Scoring\NonAcademicTierResolver;
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

        if (! $this->flow->requiresPrestasi($pendaftaran)) {
            return redirect()
                ->route('mahasiswa.orang-tua.index')
                ->with('info', 'Tahap prestasi hanya berlaku untuk beasiswa jalur Non-Akademik / Prestasi.');
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
        $validated = $this->validatedPrestasi($request, true);
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

        return back()->with('success', 'Data prestasi / organisasi berhasil ditambahkan.');
    }

    public function update(Request $request, Prestasi $prestasi): RedirectResponse
    {
        $pendaftaran = $this->editablePendaftaran($request);
        $this->assertOwned($prestasi, $pendaftaran->id);

        $validated = $this->validatedPrestasi($request, blank($prestasi->dokumen_prestasi));
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

        return back()->with('success', 'Data prestasi / organisasi berhasil diperbarui.');
    }

    public function destroy(Request $request, Prestasi $prestasi): RedirectResponse
    {
        $pendaftaran = $this->editablePendaftaran($request);
        $this->assertOwned($prestasi, $pendaftaran->id);

        $documentPath = $prestasi->dokumen_prestasi;

        DB::transaction(function () use ($prestasi, $pendaftaran): void {
            $prestasi->delete();
            $pendaftaran->forceFill([
                'prestasi_dikonfirmasi_at' => $pendaftaran->prestasis()->count() > 0
                    && ! $this->hasPrestasiWithoutDocument($pendaftaran) ? now() : null,
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

        if ($pendaftaran->prestasis()->count() === 0) {
            return back()->with('error', 'Wajib menambahkan minimal 1 data prestasi lomba atau SK kepengurusan organisasi mahasiswa untuk melanjutkan.');
        }

        if ($this->hasPrestasiWithoutDocument($pendaftaran)) {
            return back()->with('error', 'Lengkapi dokumen sertifikat / SK untuk semua data prestasi sebelum melanjutkan.');
        }

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
        abort_unless($this->flow->requiresPrestasi($pendaftaran), 403, 'Tahap prestasi hanya untuk jalur Non-Akademik / Prestasi.');

        return $pendaftaran;
    }

    private function validatedPrestasi(Request $request, bool $documentRequired): array
    {
        $isOrmawa = $request->input('kategori_bukti') === 'organisasi' || $request->boolean('is_pengurus_inti_ormawa');

        $documentRules = [
            $documentRequired ? 'required' : 'nullable',
            File::types(self::CERTIFICATE_FORMATS)->max(2048),
        ];

        if ($isOrmawa) {
            $validated = $request->validate([
                'kategori_bukti' => ['nullable', 'string', 'in:lomba,organisasi'],
                'nama_organisasi' => ['required_without:nama_prestasi', 'nullable', 'string', 'max:200'],
                'nama_prestasi' => ['required_without:nama_organisasi', 'nullable', 'string', 'max:200'],
                'posisi_organisasi' => ['nullable', Rule::in(array_column(PosisiOrganisasi::cases(), 'value'))],
                'tingkat_organisasi' => ['nullable', Rule::in(array_column(TingkatOrganisasi::cases(), 'value'))],
                'jabatan_ormawa' => ['nullable', 'string', 'max:150'],
                'penyelenggara' => ['required', 'string', 'max:200'],
                'tahun' => ['required', 'integer', 'digits:4', 'between:1990,'.now()->year],
                'dokumen' => $documentRules,
                'keterangan' => ['nullable', 'string', 'max:2000'],
            ], [
                'nama_organisasi.required_without' => 'Nama organisasi mahasiswa wajib diisi.',
                'nama_prestasi.required_without' => 'Nama organisasi mahasiswa wajib diisi.',
                'posisi_organisasi.in' => 'Posisi dalam organisasi tidak dikenal.',
                'tingkat_organisasi.in' => 'Tingkat organisasi tidak dikenal.',
                'penyelenggara.required' => 'Nama perguruan tinggi / penyelenggara wajib diisi.',
                'tahun.required' => 'Tahun masa bakti / SK wajib diisi.',
                'dokumen.required' => 'Dokumen Surat Keputusan (SK) wajib diunggah.',
                'dokumen.max' => 'Ukuran dokumen Surat Keputusan (SK) maksimal 2 MB.',
            ]);

            $validated['nama_prestasi'] = $validated['nama_organisasi'] ?? $validated['nama_prestasi'];
            unset($validated['nama_organisasi'], $validated['kategori_bukti']);

            $posisi = isset($validated['posisi_organisasi'])
                ? PosisiOrganisasi::from($validated['posisi_organisasi'])
                : NonAcademicTierResolver::posisiFromJabatan($validated['jabatan_ormawa'] ?? null);

            $validated['jenis'] = 'non_akademik';
            $validated['kategori_prestasi'] = KategoriPrestasi::ORGANISASI->value;
            $validated['tingkat_organisasi'] = isset($validated['tingkat_organisasi'])
                ? TingkatOrganisasi::from($validated['tingkat_organisasi'])->value
                : NonAcademicTierResolver::tingkatOrganisasiFromNama($validated['nama_prestasi'])->value;
            $validated['posisi_organisasi'] = $posisi->value;
            $validated['tingkat'] = 'kampus';
            $validated['peringkat'] = 'Pengurus Organisasi';
            $validated['is_pengurus_inti_ormawa'] = $posisi->isInti();
        } else {
            $validated = $request->validate([
                'kategori_bukti' => ['nullable', 'string', 'in:lomba,organisasi'],
                'nama_prestasi' => ['required', 'string', 'max:200'],
                'tingkat' => [
                    'required',
                    Rule::in(['kampus', 'kabupaten', 'provinsi', 'nasional', 'internasional']),
                ],
                'peringkat' => ['required', 'string', 'max:100'],
                'penyelenggara' => ['required', 'string', 'max:200'],
                'tahun' => ['required', 'integer', 'digits:4', 'between:1990,'.now()->year],
                'dokumen' => $documentRules,
                'keterangan' => ['nullable', 'string', 'max:2000'],
            ], [
                'nama_prestasi.required' => 'Nama kompetisi / kegiatan wajib diisi.',
                'tingkat.required' => 'Tingkat kompetisi / kegiatan wajib dipilih.',
                'peringkat.required' => 'Peringkat / capaian kejuaraan wajib dipilih.',
                'penyelenggara.required' => 'Penyelenggara kompetisi / kegiatan wajib diisi.',
                'tahun.required' => 'Tahun perolehan prestasi wajib diisi.',
                'dokumen.required' => 'Dokumen sertifikat / piagam penghargaan wajib diunggah.',
                'dokumen.max' => 'Ukuran dokumen sertifikat / piagam maksimal 2 MB.',
            ]);

            unset($validated['kategori_bukti']);

            $validated['jenis'] = 'non_akademik';
            $validated['kategori_prestasi'] = KategoriPrestasi::LOMBA->value;
            $validated['tingkat_organisasi'] = null;
            $validated['posisi_organisasi'] = null;
            $validated['is_pengurus_inti_ormawa'] = false;
            $validated['jabatan_ormawa'] = null;
        }

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

    private function hasPrestasiWithoutDocument(Pendaftaran $pendaftaran): bool
    {
        return $pendaftaran->prestasis()
            ->where(function ($query): void {
                $query->whereNull('dokumen_prestasi')
                    ->orWhere('dokumen_prestasi', '');
            })
            ->exists();
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
