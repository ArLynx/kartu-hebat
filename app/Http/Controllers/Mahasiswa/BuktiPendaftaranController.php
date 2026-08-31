<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\Types\TypeCode128;
use Picqer\Barcode\Renderers\PngRenderer;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;

class BuktiPendaftaranController extends Controller
{
    /**
     * Menampilkan Bukti Pendaftaran.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $request->user()->getKey())
            ->with([
                'user',
                'periode',
                'kategoriBeasiswa',
                'dataPribadi',
                'pendidikan',
                'application',
            ])
            ->latest('id')
            ->first();

        if (! $pendaftaran) {
            return redirect()
                ->route('mahasiswa.pendaftaran.create')
                ->with(
                    'error',
                    'Pendaftaran belum ditemukan.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Bukti pendaftaran hanya tersedia setelah submit.
        |--------------------------------------------------------------------------
        */

        $sudahSubmit =
            $pendaftaran->submitted_at !== null
            || $pendaftaran->application?->submitted_at !== null;

        if (! $sudahSubmit) {
            return redirect()
                ->route('mahasiswa.submit.index')
                ->with(
                    'error',
                    'Bukti pendaftaran tersedia setelah pendaftaran berhasil dikirim.'
                );
        }

        return view(
            'mahasiswa.bukti-pendaftaran.index',
            [
                'pendaftaran' => $pendaftaran,
            ]
        );
    }


    /**
     * Cetak / Download Bukti Pendaftaran PDF.
     */
    public function pdf(Request $request)
    {
        $pendaftaran = Pendaftaran::query()
            ->where('user_id', $request->user()->getKey())
            ->with([
                'user',
                'periode',
                'kategoriBeasiswa',
                'dataPribadi',
                'pendidikan',
                'application',
            ])
            ->latest('id')
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Pastikan sudah submit
        |--------------------------------------------------------------------------
        */

        $sudahSubmit =
            $pendaftaran->submitted_at !== null
            || $pendaftaran->application?->submitted_at !== null;

        abort_unless($sudahSubmit, 403);


        /*
        |--------------------------------------------------------------------------
        | Logo
        |--------------------------------------------------------------------------
        */

        $logoPath = public_path(
            'images/logo-murung-raya.png'
        );

        $logo = null;

        if (file_exists($logoPath)) {

            $logo = 'data:image/png;base64,' .
                base64_encode(
                    file_get_contents($logoPath)
                );

        }


       /*
        |--------------------------------------------------------------------------
        | QR CODE BUKTI PENDAFTARAN
        |--------------------------------------------------------------------------
        | URL diambil dari .env
        */

        $barcodeUrl = config('app.barcode_home_url');

        $renderer = new GDLibRenderer(300);

        $writer = new Writer($renderer);

        $barcodeImage = $writer->writeString($barcodeUrl);

        $barcode = 'data:image/png;base64,' . base64_encode($barcodeImage);


        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'mahasiswa.bukti-pendaftaran.pdf',
            [
                'pendaftaran' => $pendaftaran,
                'logo' => $logo,
                'barcode' => $barcode,
            ]
        );

        $pdf->setPaper(
            'a4',
            'portrait'
        );


        return $pdf->download(
            'bukti-pendaftaran-' .
            $pendaftaran->nomor_pendaftaran .
            '.pdf'
        );
    }
}