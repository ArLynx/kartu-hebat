<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\MahasiswaPendaftaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        private readonly MahasiswaPendaftaranService $flow
    ) {}

    /**
     * Halaman Review
     */
    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with(
                    'error',
                    'Buat pendaftaran beasiswa terlebih dahulu.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Load seluruh data pendaftaran
        |--------------------------------------------------------------------------
        */

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'dataPribadi',
            'pendidikan',
            'prestasis',
            'orangTua',
            'dokumens.jenisDokumen',
            'application.documents.type',
            'application.documents.verifications',
            'formulirPendaftaran',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cek formulir resmi
        |--------------------------------------------------------------------------
        |
        | Surat Permohonan dan Pakta Integritas harus sudah
        | tersedia/upload sebelum review dapat dikonfirmasi.
        |
        */

        $formulir = $pendaftaran->formulirPendaftaran;

        $formulirLengkap =
            ! empty($formulir?->surat_permohonan) &&
            ! empty($formulir?->pakta_integritas);

        /*
        |--------------------------------------------------------------------------
        | Data yang dikirim ke Blade
        |--------------------------------------------------------------------------
        */

        return view('mahasiswa.review.index', [
            'pendaftaran' => $pendaftaran,

            'requiredTypes' => $this->flow->requiredDocumentTypes($pendaftaran),

            'stepStatuses' => $this->flow->completion($pendaftaran),

            'missingStages' => $this->flow->missingStageLabels($pendaftaran),

            'canEdit' => $this->flow->isEditable($pendaftaran),

            'formulirLengkap' => $formulirLengkap,
        ]);
    }

    /**
     * Upload / ganti formulir resmi
     */
    public function uploadFormulir(Request $request): RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        abort_unless(
            $this->flow->isEditable($pendaftaran),
            403,
            'Pendaftaran tidak dapat diubah.'
        );

        $jenis = $request->input('jenis');

        if (! in_array($jenis, ['surat', 'pakta'], true)) {
            abort(422, 'Jenis formulir tidak valid.');
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi PDF
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:2048',
            ],
        ], [
            'file.required' => 'Silakan pilih file terlebih dahulu.',
            'file.file' => 'File yang dikirim tidak valid.',
            'file.mimes' => 'File harus berupa PDF.',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Disk private
        |--------------------------------------------------------------------------
        */

        $diskName = config('kartu_hebat.document_disk');

        $disk = Storage::disk($diskName);

        /*
        |--------------------------------------------------------------------------
        | Ambil / buat data formulir
        |--------------------------------------------------------------------------
        */

        $formulir = $pendaftaran->formulirPendaftaran;

        if (! $formulir) {
            $formulir = $pendaftaran
                ->formulirPendaftaran()
                ->create([]);
        }

        /*
        |--------------------------------------------------------------------------
        | Tentukan kolom
        |--------------------------------------------------------------------------
        */

        $column = $jenis === 'surat'
            ? 'surat_permohonan'
            : 'pakta_integritas';

        /*
        |--------------------------------------------------------------------------
        | File lama
        |--------------------------------------------------------------------------
        */

        $oldFile = $formulir->{$column};

        /*
        |--------------------------------------------------------------------------
        | Simpan file baru
        |--------------------------------------------------------------------------
        */

        $path = $request->file('file')->storeAs(
            'pendaftarans/'.$pendaftaran->id.'/formulir',
            Str::uuid().'.pdf',
            $diskName
        );

        abort_unless(
            $path,
            500,
            'Formulir gagal disimpan.'
        );

        /*
        |--------------------------------------------------------------------------
        | Simpan path ke database
        |--------------------------------------------------------------------------
        */

        try {

            $formulir->{$column} = $path;

            $formulir->save();

        } catch (\Throwable $exception) {

            $disk->delete($path);

            throw $exception;
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus file lama setelah file baru berhasil tersimpan
        |--------------------------------------------------------------------------
        */

        if (
            $oldFile &&
            $oldFile !== $path &&
            $disk->exists($oldFile)
        ) {
            $disk->delete($oldFile);
        }

        /*
        |--------------------------------------------------------------------------
        | Jika sebelumnya review sudah dikonfirmasi,
        | upload/ganti formulir harus membatalkan konfirmasi.
        |--------------------------------------------------------------------------
        */

        $pendaftaran
            ->forceFill([
                'review_dikonfirmasi_at' => null,
            ])
            ->save();

        /*
        |--------------------------------------------------------------------------
        | Kembali ke halaman Review
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('mahasiswa.review.index')
            ->with(
                'success',
                $jenis === 'surat'
                    ? 'Surat Permohonan berhasil diunggah.'
                    : 'Pakta Integritas berhasil diunggah.'
            );
    }

    /**
     * Lihat formulir resmi
     */
    public function lihatFormulir(
        Request $request,
        string $jenis
    ) {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        $pendaftaran->load('formulirPendaftaran');

        $formulir = $pendaftaran->formulirPendaftaran;

        abort_unless($formulir, 404);

        /*
        |--------------------------------------------------------------------------
        | Tentukan file berdasarkan jenis formulir
        |--------------------------------------------------------------------------
        */

        if ($jenis === 'surat') {

            $file = $formulir->surat_permohonan;
            $namaFile = 'Surat-Permohonan.pdf';

        } elseif ($jenis === 'pakta') {

            $file = $formulir->pakta_integritas;
            $namaFile = 'Pakta-Integritas.pdf';

        } else {

            abort(404);

        }

        abort_unless($file, 404);

        /*
        |--------------------------------------------------------------------------
        | Gunakan private disk
        |--------------------------------------------------------------------------
        */

        $disk = Storage::disk(
            config('kartu_hebat.document_disk')
        );

        abort_unless(
            $disk->exists($file),
            404,
            'File formulir tidak ditemukan.'
        );

        /*
        |--------------------------------------------------------------------------
        | Pastikan PDF
        |--------------------------------------------------------------------------
        */

        $mimeType = $disk->mimeType($file)
            ?: 'application/pdf';

        abort_unless(
            $mimeType === 'application/pdf',
            415,
            'File formulir bukan PDF.'
        );

        /*
        |--------------------------------------------------------------------------
        | Tampilkan PDF di browser
        |--------------------------------------------------------------------------
        */

        return $disk->response(
            $file,
            $namaFile,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Unduh formulir resmi
     */
    public function downloadFormulir(
        Request $request,
        string $jenis
    ) {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        $pendaftaran->load('formulirPendaftaran');

        $formulir = $pendaftaran->formulirPendaftaran;

        abort_unless($formulir, 404);

        /*
        |--------------------------------------------------------------------------
        | Tentukan file berdasarkan jenis formulir
        |--------------------------------------------------------------------------
        */

        if ($jenis === 'surat') {

            $file = $formulir->surat_permohonan;
            $namaFile = 'Surat-Permohonan.pdf';

        } elseif ($jenis === 'pakta') {

            $file = $formulir->pakta_integritas;
            $namaFile = 'Pakta-Integritas.pdf';

        } else {

            abort(404);

        }

        /*
        |--------------------------------------------------------------------------
        | Pastikan path file tersedia
        |--------------------------------------------------------------------------
        */

        abort_unless($file, 404);

        /*
        |--------------------------------------------------------------------------
        | Gunakan disk PRIVATE yang sama dengan DokumenController
        |--------------------------------------------------------------------------
        */

        $disk = Storage::disk(
            config('kartu_hebat.document_disk')
        );

        /*
        |--------------------------------------------------------------------------
        | Cek file pada disk yang benar
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $disk->exists($file),
            404,
            'File formulir tidak ditemukan.'
        );

        /*
        |--------------------------------------------------------------------------
        | Download
        |--------------------------------------------------------------------------
        */

        return $disk->download(
            $file,
            $namaFile,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Hapus formulir resmi
     */
    public function deleteFormulir(
        Request $request
    ): RedirectResponse {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        abort_unless(
            $this->flow->isEditable($pendaftaran),
            403,
            'Pendaftaran tidak dapat diubah.'
        );

        $jenis = $request->input('jenis');

        if (! in_array($jenis, ['surat', 'pakta'], true)) {
            abort(422, 'Jenis formulir tidak valid.');
        }

        $formulir = $pendaftaran->formulirPendaftaran;

        if (! $formulir) {
            return back()->with(
                'error',
                'Data formulir tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tentukan kolom
        |--------------------------------------------------------------------------
        */

        $column = $jenis === 'surat'
            ? 'surat_permohonan'
            : 'pakta_integritas';

        $file = $formulir->{$column};

        /*
        |--------------------------------------------------------------------------
        | Private disk
        |--------------------------------------------------------------------------
        */

        $disk = Storage::disk(
            config('kartu_hebat.document_disk')
        );

        /*
        |--------------------------------------------------------------------------
        | Hapus file fisik
        |--------------------------------------------------------------------------
        */

        if ($file && $disk->exists($file)) {
            $disk->delete($file);
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus path dari database
        |--------------------------------------------------------------------------
        */

        $formulir->{$column} = null;

        $formulir->save();

        /*
        |--------------------------------------------------------------------------
        | Batalkan konfirmasi review
        |--------------------------------------------------------------------------
        */

        $pendaftaran
            ->forceFill([
                'review_dikonfirmasi_at' => null,
            ])
            ->save();

        return redirect()
            ->route('mahasiswa.review.index')
            ->with(
                'success',
                $jenis === 'surat'
                    ? 'Surat Permohonan berhasil dihapus.'
                    : 'Pakta Integritas berhasil dihapus.'
            );
    }

    /**
     * Konfirmasi Review
     */
    public function confirm(Request $request): RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        abort_unless(
            $this->flow->isEditable($pendaftaran),
            403,
            'Pendaftaran tidak dapat diubah.'
        );

        /*
        |--------------------------------------------------------------------------
        | Cek kelengkapan seluruh tahap
        |--------------------------------------------------------------------------
        */

        $missingStages = $this->flow->missingStageLabels($pendaftaran);

        if ($missingStages !== []) {
            return redirect()
                ->route('mahasiswa.review.index')
                ->with(
                    'error',
                    'Lengkapi tahap berikut sebelum mengonfirmasi review: '
                    .implode(', ', $missingStages)
                    .'.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Cek Surat Permohonan & Pakta Integritas
        |--------------------------------------------------------------------------
        */

        $pendaftaran->load('formulirPendaftaran');

        $formulir = $pendaftaran->formulirPendaftaran;

        $missingForms = [];

        if (! $formulir?->surat_permohonan) {
            $missingForms[] = 'Surat Permohonan';
        }

        if (! $formulir?->pakta_integritas) {
            $missingForms[] = 'Pakta Integritas';
        }

        /*
        |--------------------------------------------------------------------------
        | Jika formulir belum lengkap
        |--------------------------------------------------------------------------
        */

        if ($missingForms !== []) {
            return redirect()
                ->route('mahasiswa.review.index')
                ->with(
                    'error',
                    'Upload terlebih dahulu: '
                    .implode(' dan ', $missingForms)
                    .'.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Review berhasil dikonfirmasi
        |--------------------------------------------------------------------------
        */

        $pendaftaran
            ->forceFill([
                'review_dikonfirmasi_at' => now(),
            ])
            ->save();

        /*
        |--------------------------------------------------------------------------
        | Lanjut ke Submit
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('mahasiswa.submit.index')
            ->with(
                'success',
                'Review telah dikonfirmasi. Pendaftaran siap disubmit.'
            );
    }
}
