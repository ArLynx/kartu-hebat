<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\OrangTua;
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

class OrangTuaController extends Controller
{
    private const SK_FORMATS = ['pdf', 'jpg', 'jpeg', 'png'];

    public function __construct(private readonly MahasiswaPendaftaranService $flow) {}

    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with('error', 'Buat pendaftaran beasiswa terlebih dahulu.');
        }

        $pendaftaran->load('orangTua');

        return view('mahasiswa.orang-tua.index', [
            'pendaftaran' => $pendaftaran,
            'stepStatuses' => $this->flow->completion($pendaftaran),
            'canEdit' => $this->flow->isEditable($pendaftaran),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        abort_unless(
            $this->flow->isEditable($pendaftaran),
            403,
            'Pendaftaran tidak dapat diubah.'
        );

        $bothParentsDeceased = $request->input('status_ayah') === 'meninggal_dunia'
            && $request->input('status_ibu') === 'meninggal_dunia';
        $memilikiWali = $bothParentsDeceased && $request->boolean('memiliki_wali');
        $existing = $pendaftaran->orangTua;

        $request->merge([
            'memiliki_wali' => $memilikiWali,
            'pekerjaan_wali' => $memilikiWali ? $request->input('pekerjaan_wali') : null,
        ]);

        $ayahAsnActive = $request->input('status_ayah') === 'hidup' && $request->input('pekerjaan_ayah') === 'ASN';
        $ibuAsnActive = $request->input('status_ibu') === 'hidup' && $request->input('pekerjaan_ibu') === 'ASN';
        $waliAsnActive = $memilikiWali && $request->input('pekerjaan_wali') === 'ASN';

        $skAyahRequired = $ayahAsnActive && blank($existing?->sk_jabatan_ayah);
        $skIbuRequired = $ibuAsnActive && blank($existing?->sk_jabatan_ibu);
        $skWaliRequired = $waliAsnActive && blank($existing?->sk_jabatan_wali);

        $validated = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | Ayah
            |--------------------------------------------------------------------------
            */
            'nama_ayah' => [
                'required',
                'string',
                'max:150',
            ],

            'status_ayah' => [
                'required',
                'in:hidup,meninggal_dunia',
            ],

            'nik_ayah' => [
                'required_if:status_ayah,hidup',
                'nullable',
                'digits:16',
            ],

            'pekerjaan_ayah' => [
                'required_if:status_ayah,hidup',
                'nullable',
                'string',
                'max:150',
            ],

            'pekerjaan_ayah_lainnya' => [
                'required_if:pekerjaan_ayah,Lainnya',
                'nullable',
                'string',
                'max:150',
            ],

            'jabatan_ayah' => [
                $ayahAsnActive ? 'required' : 'nullable',
                'nullable',
                'string',
                'max:150',
            ],

            'sk_jabatan_ayah' => [
                $skAyahRequired ? 'required' : 'nullable',
                File::types(self::SK_FORMATS)->max(2048),
            ],

            /*
            |--------------------------------------------------------------------------
            | Ibu
            |--------------------------------------------------------------------------
            */
            'nama_ibu' => [
                'required',
                'string',
                'max:150',
            ],

            'status_ibu' => [
                'required',
                'in:hidup,meninggal_dunia',
            ],

            'nik_ibu' => [
                'required_if:status_ibu,hidup',
                'nullable',
                'digits:16',
            ],

            'pekerjaan_ibu' => [
                'required_if:status_ibu,hidup',
                'nullable',
                'string',
                'max:150',
            ],

            'pekerjaan_ibu_lainnya' => [
                'required_if:pekerjaan_ibu,Lainnya',
                'nullable',
                'string',
                'max:150',
            ],

            'jabatan_ibu' => [
                $ibuAsnActive ? 'required' : 'nullable',
                'nullable',
                'string',
                'max:150',
            ],

            'sk_jabatan_ibu' => [
                $skIbuRequired ? 'required' : 'nullable',
                File::types(self::SK_FORMATS)->max(2048),
            ],

            /*
            |--------------------------------------------------------------------------
            | Wali
            |--------------------------------------------------------------------------
            */
            'memiliki_wali' => [
                'required',
                'boolean',
            ],

            'nama_wali' => [
                'required_if:memiliki_wali,true,1',
                'nullable',
                'string',
                'max:150',
            ],

            'nik_wali' => [
                'required_if:memiliki_wali,true,1',
                'nullable',
                'digits:16',
            ],

            'pekerjaan_wali' => [
                'required_if:memiliki_wali,true,1',
                'nullable',
                'string',
                'max:150',
            ],

            'pekerjaan_wali_lainnya' => [
                'required_if:pekerjaan_wali,Lainnya',
                'nullable',
                'string',
                'max:150',
            ],

            'jabatan_wali' => [
                $waliAsnActive ? 'required' : 'nullable',
                'nullable',
                'string',
                'max:150',
            ],

            'sk_jabatan_wali' => [
                $skWaliRequired ? 'required' : 'nullable',
                File::types(self::SK_FORMATS)->max(2048),
            ],
        ], [
            'nik_ayah.required_if' => 'NIK Ayah wajib diisi 16 digit apabila status ayah hidup.',
            'nik_ayah.digits' => 'NIK Ayah harus terdiri dari 16 digit angka.',
            'pekerjaan_ayah.required_if' => 'Pekerjaan Ayah wajib dipilih apabila status ayah hidup.',
            'pekerjaan_ayah_lainnya.required_if' => 'Nama pekerjaan Ayah wajib diisi apabila memilih Lainnya.',
            'jabatan_ayah.required' => 'Jabatan Ayah wajib diisi apabila memilih pekerjaan ASN.',
            'jabatan_ayah.required_if' => 'Jabatan Ayah wajib diisi apabila memilih pekerjaan ASN.',
            'sk_jabatan_ayah.required' => 'Dokumen SK Jabatan Terakhir Ayah wajib diunggah apabila memilih pekerjaan ASN.',
            'sk_jabatan_ayah.max' => 'Ukuran berkas SK Jabatan Terakhir Ayah maksimal 2 MB.',

            'nik_ibu.required_if' => 'NIK Ibu wajib diisi 16 digit apabila status ibu hidup.',
            'nik_ibu.digits' => 'NIK Ibu harus terdiri dari 16 digit angka.',
            'pekerjaan_ibu.required_if' => 'Pekerjaan Ibu wajib dipilih apabila status ibu hidup.',
            'pekerjaan_ibu_lainnya.required_if' => 'Nama pekerjaan Ibu wajib diisi apabila memilih Lainnya.',
            'jabatan_ibu.required' => 'Jabatan Ibu wajib diisi apabila memilih pekerjaan ASN.',
            'jabatan_ibu.required_if' => 'Jabatan Ibu wajib diisi apabila memilih pekerjaan ASN.',
            'sk_jabatan_ibu.required' => 'Dokumen SK Jabatan Terakhir Ibu wajib diunggah apabila memilih pekerjaan ASN.',
            'sk_jabatan_ibu.max' => 'Ukuran berkas SK Jabatan Terakhir Ibu maksimal 2 MB.',

            'nama_wali.required_if' => 'Nama wali wajib diisi jika memiliki wali.',
            'nik_wali.required_if' => 'NIK Wali wajib diisi jika memiliki wali.',
            'nik_wali.digits' => 'NIK Wali harus terdiri dari 16 digit angka.',
            'pekerjaan_wali.required_if' => 'Pekerjaan wali wajib dipilih jika memiliki wali.',
            'pekerjaan_wali_lainnya.required_if' => 'Nama pekerjaan Wali wajib diisi apabila memilih Lainnya.',
            'jabatan_wali.required' => 'Jabatan Wali wajib diisi apabila memilih pekerjaan ASN.',
            'jabatan_wali.required_if' => 'Jabatan Wali wajib diisi apabila memilih pekerjaan ASN.',
            'sk_jabatan_wali.required' => 'Dokumen SK Jabatan Terakhir Wali wajib diunggah apabila memilih pekerjaan ASN.',
            'sk_jabatan_wali.max' => 'Ukuran berkas SK Jabatan Terakhir Wali maksimal 2 MB.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Normalisasi Pekerjaan, Jabatan & Berkas SK Jabatan
        |--------------------------------------------------------------------------
        */

        $disk = Storage::disk(config('kartu_hebat.document_disk'));
        $newlyStoredPaths = [];
        $pathsToDeleteAfterCommit = [];

        unset(
            $validated['sk_jabatan_ayah'],
            $validated['sk_jabatan_ibu'],
            $validated['sk_jabatan_wali'],
        );

        try {
            if (($validated['pekerjaan_ayah'] ?? null) === 'Lainnya') {
                $validated['pekerjaan_ayah'] = trim((string) ($validated['pekerjaan_ayah_lainnya'] ?? ''));
            }
            if (! $ayahAsnActive) {
                $validated['jabatan_ayah'] = null;
                $validated['sk_jabatan_ayah'] = null;
                if ($existing?->sk_jabatan_ayah) {
                    $pathsToDeleteAfterCommit[] = $existing->sk_jabatan_ayah;
                }
            } elseif ($request->hasFile('sk_jabatan_ayah')) {
                $newPath = $this->storeSkFile($request, 'sk_jabatan_ayah', 'ayah', $pendaftaran->id);
                $newlyStoredPaths[] = $newPath;
                $validated['sk_jabatan_ayah'] = $newPath;
                if ($existing?->sk_jabatan_ayah && $existing->sk_jabatan_ayah !== $newPath) {
                    $pathsToDeleteAfterCommit[] = $existing->sk_jabatan_ayah;
                }
            }

            if (($validated['pekerjaan_ibu'] ?? null) === 'Lainnya') {
                $validated['pekerjaan_ibu'] = trim((string) ($validated['pekerjaan_ibu_lainnya'] ?? ''));
            }
            if (! $ibuAsnActive) {
                $validated['jabatan_ibu'] = null;
                $validated['sk_jabatan_ibu'] = null;
                if ($existing?->sk_jabatan_ibu) {
                    $pathsToDeleteAfterCommit[] = $existing->sk_jabatan_ibu;
                }
            } elseif ($request->hasFile('sk_jabatan_ibu')) {
                $newPath = $this->storeSkFile($request, 'sk_jabatan_ibu', 'ibu', $pendaftaran->id);
                $newlyStoredPaths[] = $newPath;
                $validated['sk_jabatan_ibu'] = $newPath;
                if ($existing?->sk_jabatan_ibu && $existing->sk_jabatan_ibu !== $newPath) {
                    $pathsToDeleteAfterCommit[] = $existing->sk_jabatan_ibu;
                }
            }

            if (! $validated['memiliki_wali']) {
                $validated = array_merge($validated, [
                    'nama_wali' => null,
                    'nik_wali' => null,
                    'pekerjaan_wali' => null,
                    'jabatan_wali' => null,
                    'sk_jabatan_wali' => null,
                ]);
                if ($existing?->sk_jabatan_wali) {
                    $pathsToDeleteAfterCommit[] = $existing->sk_jabatan_wali;
                }
            } else {
                if (($validated['pekerjaan_wali'] ?? null) === 'Lainnya') {
                    $validated['pekerjaan_wali'] = trim((string) ($validated['pekerjaan_wali_lainnya'] ?? ''));
                }
                if (! $waliAsnActive) {
                    $validated['jabatan_wali'] = null;
                    $validated['sk_jabatan_wali'] = null;
                    if ($existing?->sk_jabatan_wali) {
                        $pathsToDeleteAfterCommit[] = $existing->sk_jabatan_wali;
                    }
                } elseif ($request->hasFile('sk_jabatan_wali')) {
                    $newPath = $this->storeSkFile($request, 'sk_jabatan_wali', 'wali', $pendaftaran->id);
                    $newlyStoredPaths[] = $newPath;
                    $validated['sk_jabatan_wali'] = $newPath;
                    if ($existing?->sk_jabatan_wali && $existing->sk_jabatan_wali !== $newPath) {
                        $pathsToDeleteAfterCommit[] = $existing->sk_jabatan_wali;
                    }
                }
            }

            unset(
                $validated['pekerjaan_ayah_lainnya'],
                $validated['pekerjaan_ibu_lainnya'],
                $validated['pekerjaan_wali_lainnya'],
            );

            /*
            |--------------------------------------------------------------------------
            | Simpan
            |--------------------------------------------------------------------------
            */

            DB::transaction(function () use ($pendaftaran, $validated): void {
                OrangTua::query()->updateOrCreate(
                    [
                        'pendaftaran_id' => $pendaftaran->id,
                    ],
                    $validated,
                );

                /*
                |--------------------------------------------------------------------------
                | Jika data diubah, review harus dikonfirmasi ulang
                |--------------------------------------------------------------------------
                */

                $pendaftaran
                    ->forceFill([
                        'review_dikonfirmasi_at' => null,
                    ])
                    ->save();
            });
        } catch (Throwable $exception) {
            foreach ($newlyStoredPaths as $storedPath) {
                $disk->delete($storedPath);
            }

            throw $exception;
        }

        foreach ($pathsToDeleteAfterCommit as $oldPath) {
            $disk->delete($oldPath);
        }

        return redirect()
            ->route('mahasiswa.dokumen.index')
            ->with(
                'success',
                'Data orang tua/wali berhasil disimpan.'
            );
    }

    public function previewSk(Request $request, string $peran): StreamedResponse
    {
        [$disk, $path, $mimeType] = $this->resolveAuthorizedSk($request, $peran);

        abort_unless(
            $mimeType === 'application/pdf' || in_array($mimeType, ['image/jpeg', 'image/png'], true),
            415,
            'Format dokumen SK tidak mendukung pratinjau.',
        );

        return $disk->response(
            $path,
            basename($path),
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }

    public function downloadSk(Request $request, string $peran): StreamedResponse
    {
        [$disk, $path, $mimeType] = $this->resolveAuthorizedSk($request, $peran);

        return $disk->download(
            $path,
            basename($path),
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-cache',
            ],
        );
    }

    public function destroySk(Request $request, string $peran): RedirectResponse
    {
        abort_unless(in_array($peran, ['ayah', 'ibu', 'wali'], true), 404);

        $pendaftaran = $this->flow->currentFor($request->user());
        abort_unless($pendaftaran, 404);
        abort_unless(
            $this->flow->isEditable($pendaftaran),
            403,
            'Pendaftaran tidak dapat diubah.'
        );

        $orangTua = $pendaftaran->orangTua;
        abort_unless($orangTua, 404);

        $column = 'sk_jabatan_'.$peran;
        $path = $orangTua->{$column};
        abort_unless(filled($path), 404, 'Berkas SK Jabatan tidak ditemukan.');

        DB::transaction(function () use ($orangTua, $column, $pendaftaran): void {
            $orangTua->forceFill([$column => null])->save();
            $pendaftaran->forceFill(['review_dikonfirmasi_at' => null])->save();
        });

        Storage::disk(config('kartu_hebat.document_disk'))->delete($path);

        return back()->with(
            'success',
            'Dokumen SK Jabatan Terakhir '.ucfirst($peran).' berhasil dihapus. Silakan unggah kembali berkas SK sebelum melanjutkan.'
        );
    }

    private function storeSkFile(Request $request, string $field, string $peran, int $pendaftaranId): string
    {
        $uploaded = $request->file($field);
        abort_unless($uploaded !== null, 422, 'Berkas SK Jabatan tidak ditemukan.');

        $extension = strtolower($uploaded->getClientOriginalExtension() ?: $uploaded->extension());
        if (! in_array($extension, self::SK_FORMATS, true)) {
            throw ValidationException::withMessages([
                $field => 'Ekstensi berkas SK Jabatan tidak diizinkan (gunakan PDF, JPG, atau PNG).',
            ]);
        }

        $filename = 'sk_jabatan_'.$peran.'_'.Str::uuid().'.'.$extension;
        $path = $uploaded->storeAs(
            'pendaftarans/'.$pendaftaranId.'/orang-tua',
            $filename,
            config('kartu_hebat.document_disk'),
        );

        abort_unless($path, 500, 'Berkas SK Jabatan gagal disimpan.');

        return $path;
    }

    /**
     * @return array{0: FilesystemAdapter, 1: string, 2: string}
     */
    private function resolveAuthorizedSk(Request $request, string $peran): array
    {
        abort_unless(in_array($peran, ['ayah', 'ibu', 'wali'], true), 404);

        $pendaftaran = $this->flow->currentFor($request->user());
        abort_unless($pendaftaran, 404);

        $orangTua = $pendaftaran->orangTua;
        abort_unless($orangTua, 404);

        $column = 'sk_jabatan_'.$peran;
        $path = $orangTua->{$column};
        abort_unless(filled($path), 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(config('kartu_hebat.document_disk'));
        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path) ?: 'application/octet-stream';

        return [$disk, $path, $mimeType];
    }
}
