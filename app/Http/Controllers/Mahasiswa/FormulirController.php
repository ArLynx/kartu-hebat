<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Services\MahasiswaPendaftaranService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FormulirController extends Controller
{
    public function __construct(
        private readonly MahasiswaPendaftaranService $flow
    ) {}

    /**
     * Surat Permohonan
     *
     * Setiap jenis beasiswa memiliki format formulir tersendiri (4 Formulir Spesifik):
     * 1. Prestasi Non-Akademik   -> surat-permohonan-non-akademik.blade.php
     * 2. Tidak Mampu             -> surat-permohonan-tidak-mampu.blade.php
     * 3. Prestasi Akademik       -> surat-permohonan-akademik.blade.php
     * 4. Mahasiswa Disabilitas   -> surat-permohonan-disabilitas.blade.php
     */
    public function suratPermohonan(Request $request): Response|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'jalurBeasiswa',
            'dataPribadi',
            'pendidikan',
            'orangTua',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Pastikan data wajib tersedia
        |--------------------------------------------------------------------------
        */

        if (! $pendaftaran->dataPribadi || ! $pendaftaran->pendidikan || ! $pendaftaran->orangTua) {
            $missing = [];
            if (! $pendaftaran->dataPribadi) {
                $missing[] = 'Data Pribadi';
            }
            if (! $pendaftaran->pendidikan) {
                $missing[] = 'Data Pendidikan';
            }
            if (! $pendaftaran->orangTua) {
                $missing[] = 'Data Orang Tua';
            }

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Surat Permohonan belum dapat diunduh. Silakan lengkapi ' . implode(', ', $missing) . ' terlebih dahulu.'
                );
        }

        $applicationType = $pendaftaran->kategoriBeasiswa?->application_type;

        if ($applicationType === ApplicationType::NON_AKADEMIK) {
            $viewName = 'mahasiswa.formulir.surat-permohonan-non-akademik';
            $jenisForm = 'Non-Akademik';
            $namaFile = 'Surat-Permohonan-'
                . $pendaftaran->nomor_pendaftaran
                . '-Non-Akademik.pdf';
        } elseif ($applicationType === ApplicationType::TIDAK_MAMPU) {
            $viewName = 'mahasiswa.formulir.surat-permohonan-tidak-mampu';
            $jenisForm = 'Tidak-Mampu';
            $namaFile = 'Surat-Permohonan-'
                . $pendaftaran->nomor_pendaftaran
                . '-Tidak-Mampu.pdf';
        } elseif ($applicationType === ApplicationType::AKADEMIK) {
            $viewName = 'mahasiswa.formulir.surat-permohonan-akademik';
            $jenisForm = 'Prestasi-Akademik';
            $namaFile = 'Surat-Permohonan-'
                . $pendaftaran->nomor_pendaftaran
                . '-Prestasi-Akademik.pdf';
        } elseif ($applicationType === ApplicationType::DISABILITAS) {
            $viewName = 'mahasiswa.formulir.surat-permohonan-disabilitas';
            $jenisForm = 'Disabilitas';
            $namaFile = 'Surat-Permohonan-'
                . $pendaftaran->nomor_pendaftaran
                . '-Disabilitas.pdf';
        } else {
            /*
            |--------------------------------------------------------------------------
            | Tentukan Form A / Form B berdasarkan IPK
            |--------------------------------------------------------------------------
            */
            $pendidikan = $pendaftaran->pendidikan;
            $ipk = $pendidikan->ipk;

            $jenisForm = (
                $ipk === null ||
                (float) $ipk <= 0
            )
                ? 'B'
                : 'A';

            if ($jenisForm === 'A' && (float) $ipk <= 0) {
                return redirect()
                    ->back()
                    ->with('error', 'IPK belum diisi pada Data Pendidikan.');
            }

            $viewName = $jenisForm === 'B'
                ? 'mahasiswa.formulir.surat-permohonan-b'
                : 'mahasiswa.formulir.surat-permohonan-a';

            $namaFile = 'Surat-Permohonan-'
                . $pendaftaran->nomor_pendaftaran
                . '-Form-' . $jenisForm
                . '.pdf';
        }

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            $viewName,
            [
                'pendaftaran' => $pendaftaran,
                'jenisForm' => $jenisForm,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | F4 Indonesia (215.9 mm x 330.2 mm / 612 pt x 935.43 pt)
        |--------------------------------------------------------------------------
        */

        $pdf->setPaper([
            0,
            0,
            612,
            935.43,
        ]);

        return $pdf->download($namaFile);
    }

    /**
     * Pakta Integritas
     */
    public function paktaIntegritas(Request $request): Response|RedirectResponse
    {
        $pendaftaran = $this->flow->currentFor($request->user());

        abort_unless($pendaftaran, 404);

        $pendaftaran->load([
            'periode',
            'kategoriBeasiswa',
            'jalurBeasiswa',
            'dataPribadi',
            'pendidikan',
            'orangTua',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Pastikan data wajib tersedia
        |--------------------------------------------------------------------------
        */

        if (! $pendaftaran->dataPribadi || ! $pendaftaran->pendidikan || ! $pendaftaran->orangTua) {
            $missing = [];
            if (! $pendaftaran->dataPribadi) {
                $missing[] = 'Data Pribadi';
            }
            if (! $pendaftaran->pendidikan) {
                $missing[] = 'Data Pendidikan';
            }
            if (! $pendaftaran->orangTua) {
                $missing[] = 'Data Orang Tua';
            }

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Pakta Integritas belum dapat diunduh. Silakan lengkapi ' . implode(', ', $missing) . ' terlebih dahulu.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Data utama
        |--------------------------------------------------------------------------
        */

        $dataPribadi = $pendaftaran->dataPribadi;
        $pendidikan = $pendaftaran->pendidikan;
        $orangTua = $pendaftaran->orangTua;

        /*
        |--------------------------------------------------------------------------
        | Tentukan nama orang tua / wali / keluarga
        |--------------------------------------------------------------------------
        |
        | Prioritas:
        |
        | 1. Jika menggunakan wali -> nama wali
        | 2. Jika ayah masih hidup -> nama ayah
        | 3. Jika ayah meninggal dan ibu hidup -> nama ibu
        | 4. Jika keduanya meninggal -> nama wali jika tersedia
        |
        */

        $namaPenandatanganOrangTua = null;

        if ($orangTua->memiliki_wali) {

            $namaPenandatanganOrangTua = $orangTua->nama_wali;

        } elseif ($orangTua->status_ayah === 'hidup') {

            $namaPenandatanganOrangTua = $orangTua->nama_ayah;

        } elseif ($orangTua->status_ibu === 'hidup') {

            $namaPenandatanganOrangTua = $orangTua->nama_ibu;

        } elseif ($orangTua->nama_wali) {

            $namaPenandatanganOrangTua = $orangTua->nama_wali;
        }

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'mahasiswa.formulir.pakta-integritas',
            [
                'pendaftaran' => $pendaftaran,

                // Object supaya Blade bisa mengambil data langsung
                'dataPribadi' => $dataPribadi,
                'pendidikan' => $pendidikan,
                'orangTua' => $orangTua,

                // Nama penandatangan otomatis
                'namaPenandatanganOrangTua' => $namaPenandatanganOrangTua,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | F4 Indonesia
        |--------------------------------------------------------------------------
        */

        $pdf->setPaper([
            0,
            0,
            612,
            935.43,
        ]);

        $namaFile = 'Pakta-Integritas-'
            .$pendaftaran->nomor_pendaftaran
            .'.pdf';

        return $pdf->download($namaFile);
    }
}
