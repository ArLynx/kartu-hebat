<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\MahasiswaPendaftaranService;
use Barryvdh\DomPDF\Facade\Pdf;
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
     * Form A : Mahasiswa yang sudah memiliki IPK
     * Form B : Mahasiswa yang belum memiliki IPK (IPK = 0 / kosong)
     */
    public function suratPermohonan(Request $request): Response
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

        abort_unless(
            $pendaftaran->pendidikan,
            422,
            'Data pendidikan belum dilengkapi.'
        );

        abort_unless(
            $pendaftaran->dataPribadi,
            422,
            'Data pribadi belum dilengkapi.'
        );

        abort_unless(
            $pendaftaran->orangTua,
            422,
            'Data orang tua belum dilengkapi.'
        );

        /*
        |--------------------------------------------------------------------------
        | Tentukan Form A / Form B berdasarkan IPK
        |--------------------------------------------------------------------------
        |
        | IPK = 0 / kosong
        |     -> Form B
        |
        | IPK > 0
        |     -> Form A
        |
        */

        $pendidikan = $pendaftaran->pendidikan;

        $ipk = $pendidikan->ipk;

        /*
        |--------------------------------------------------------------------------
        | Tentukan jenis formulir
        |--------------------------------------------------------------------------
        */

        $jenisForm = (
            $ipk === null ||
            (float) $ipk <= 0
        )
            ? 'B'
            : 'A';


        /*
        |--------------------------------------------------------------------------
        | Form A wajib mempunyai IPK
        |--------------------------------------------------------------------------
        */

        if ($jenisForm === 'A') {

            abort_unless(
                (float) $ipk > 0,
                422,
                'IPK belum diisi.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Tentukan Blade Form
        |--------------------------------------------------------------------------
        */

        $viewName = $jenisForm === 'B'
            ? 'mahasiswa.formulir.surat-permohonan-b'
            : 'mahasiswa.formulir.surat-permohonan-a';


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
        | F4 Indonesia
        |--------------------------------------------------------------------------
        |
        | 215.9 mm x 330.2 mm
        | 612 pt x 935.43 pt
        |
        */

        $pdf->setPaper([
            0,
            0,
            612,
            935.43,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Nama File
        |--------------------------------------------------------------------------
        */

        $namaFile = 'Surat-Permohonan-'
            . $pendaftaran->nomor_pendaftaran
            . '-Form-' . $jenisForm
            . '.pdf';


        return $pdf->download($namaFile);
    }

    /**
     * Pakta Integritas
     */
    public function paktaIntegritas(Request $request): Response
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

        abort_unless(
            $pendaftaran->dataPribadi,
            422,
            'Data pribadi belum dilengkapi.'
        );

        abort_unless(
            $pendaftaran->pendidikan,
            422,
            'Data pendidikan belum dilengkapi.'
        );

        abort_unless(
            $pendaftaran->orangTua,
            422,
            'Data orang tua belum dilengkapi.'
        );

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
