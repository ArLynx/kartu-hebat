<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
use App\Http\Controllers\Mahasiswa\DataPribadiController;
use App\Http\Controllers\Mahasiswa\DokumenController;
use App\Http\Controllers\Mahasiswa\OrangTuaController;
use App\Http\Controllers\Mahasiswa\PendaftaranController;
use App\Http\Controllers\Mahasiswa\PendidikanController;
use App\Http\Controllers\Mahasiswa\PrestasiController;
use App\Http\Controllers\Mahasiswa\ReviewController;
use App\Http\Controllers\Mahasiswa\SubmitController;
use App\Http\Controllers\Mahasiswa\BuktiPendaftaranController;
use App\Http\Controllers\Mahasiswa\FormulirController;

use App\Http\Controllers\NotificationController;

use App\Http\Controllers\Operator\ApplicationController as OperatorApplicationController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Operator\ReconciliationController;
use App\Http\Controllers\Operator\ReportController;
use App\Http\Controllers\Operator\SelectionController;
use App\Http\Controllers\Operator\VerificationController;

use App\Http\Controllers\PrivateDocumentController;

use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\ResultController;

use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\DocumentTypeController as SuperadminDocumentTypeController;
use App\Http\Controllers\Superadmin\KategoriBeasiswaController as SuperadminKategoriBeasiswaController;
use App\Http\Controllers\Superadmin\OperatorController as SuperadminOperatorController;

use App\Http\Controllers\TwoFactorSetupController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/pengumuman-hasil', [ResultController::class, 'index'])
    ->middleware('throttle:20,1')
    ->name('public.results');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'nocache'])->group(function (): void {
    Route::get('/user/profile', [ProfileController::class, 'show'])->name('profile.show');

    Route::middleware('verified')->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/2fa-setup', [TwoFactorSetupController::class, 'index'])->name('2fa.setup');

        Route::middleware('2fa.ensure')->group(function (): void {
            Route::get('/dokumen/{document}/lihat', [PrivateDocumentController::class, 'preview'])->name('documents.preview');
            Route::get('/dokumen/{document}/unduh', [PrivateDocumentController::class, 'download'])->name('documents.download');
        });

        Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifikasi/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
        Route::post('/notifikasi/tandai-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');

        /*
        |--------------------------------------------------------------------------
        | Pendaftaran mahasiswa terintegrasi workflow verifikasi
        |--------------------------------------------------------------------------
        |
        | Data utama tersimpan pada tabel pendaftaran. Saat submit, sistem membuat
        | atau memperbarui applications untuk verifikasi desa hingga kabupaten.
        |
        */
        Route::middleware('role:mahasiswa')
            ->prefix('mahasiswa')
            ->name('mahasiswa.')
            ->group(function (): void {
                Route::get('/dashboard', [MahasiswaDashboardController::class, 'index'])->name('dashboard');
                Route::get('/pendaftaran/create', [PendaftaranController::class, 'create'])->name('pendaftaran.create');
                Route::post('/pendaftaran', [PendaftaranController::class, 'store'])->name('pendaftaran.store');
                Route::get('/data-pribadi', [DataPribadiController::class, 'index'])->name('data-pribadi.index');
                Route::put('/data-pribadi', [DataPribadiController::class, 'update'])->name('data-pribadi.update');

                Route::get('/pendidikan', [PendidikanController::class, 'index'])->name('pendidikan.index');
                Route::put('/pendidikan', [PendidikanController::class, 'update'])->name('pendidikan.update');

                Route::get('/prestasi', [PrestasiController::class, 'index'])->name('prestasi.index');
                Route::post('/prestasi', [PrestasiController::class, 'store'])->name('prestasi.store');
                Route::put('/prestasi/{prestasi}', [PrestasiController::class, 'update'])->name('prestasi.update');
                Route::delete('/prestasi/{prestasi}', [PrestasiController::class, 'destroy'])->name('prestasi.destroy');
                Route::post('/prestasi/konfirmasi', [PrestasiController::class, 'confirm'])->name('prestasi.confirm');
                Route::get('/prestasi/{prestasi}/unduh', [PrestasiController::class, 'download'])->name('prestasi.download');

                Route::get('/orang-tua', [OrangTuaController::class, 'index'])->name('orang-tua.index');
                Route::put('/orang-tua', [OrangTuaController::class, 'update'])->name('orang-tua.update');

                Route::get('/dokumen', [DokumenController::class, 'index'])->name('dokumen.index');
                Route::post('/dokumen', [DokumenController::class, 'store'])->name('dokumen.store');
                Route::delete('/dokumen/{dokumen}', [DokumenController::class, 'destroy'])->name('dokumen.destroy');
                Route::get('/dokumen/{dokumen}/lihat', [DokumenController::class, 'preview'])->name('dokumen.preview');
                Route::get('/dokumen/{dokumen}/unduh', [DokumenController::class, 'download'])->name('dokumen.download');

                Route::get('/review', [ReviewController::class, 'index'])->name('review.index');
                Route::post('/review/konfirmasi', [ReviewController::class, 'confirm'])->name('review.confirm');
                Route::get('/submit', [SubmitController::class, 'index'])->name('submit.index');
                Route::post('/submit', [SubmitController::class, 'store'])->name('submit.store');

                Route::get('/bukti-pendaftaran', [BuktiPendaftaranController::class, 'index'])->name('bukti-pendaftaran.index');
                Route::get('/bukti-pendaftaran/cetak', [BuktiPendaftaranController::class, 'pdf'])->name('bukti-pendaftaran.pdf');

                Route::get('/formulir/surat-permohonan', [FormulirController::class, 'suratPermohonan'])->name('formulir.surat-permohonan');
                Route::get('/formulir/pakta-integritas', [FormulirController::class, 'paktaIntegritas'])->name('formulir.pakta-integritas');
                Route::post('/review/formulir/upload', [ReviewController::class, 'uploadFormulir'])->name('formulir.upload');
                Route::delete('/review/formulir/delete', [ReviewController::class, 'deleteFormulir'])->name('formulir.delete');
                Route::get('/review/formulir/{jenis}/download', [ReviewController::class, 'downloadFormulir'])->name('formulir.download');
                Route::get('/review/formulir/{jenis}/lihat', [ReviewController::class, 'lihatFormulir'])->name('formulir.lihat');

                Route::get('/lpj', [\App\Http\Controllers\Mahasiswa\LpjController::class, 'index'])->name('lpj.index');
            });

        // Alias kompatibilitas untuk tautan lama. Semuanya diarahkan ke modul beasiswa.
        Route::middleware('role:mahasiswa')
            ->prefix('mahasiswa')
            ->name('student.')
            ->group(function (): void {
                Route::get('/pendaftaran', fn() => redirect()->route('mahasiswa.dashboard'))->name('application');
                Route::redirect('/riwayat-verifikasi', '/mahasiswa/dashboard')->name('history');
                Route::put('/pendaftaran/profil', fn() => redirect()->route('mahasiswa.data-pribadi.index'))->name('profile.update');
                Route::post('/pendaftaran/kirim', fn() => redirect()->route('mahasiswa.dashboard')->with('error', 'Pengiriman melalui alur lama dinonaktifkan. Gunakan modul pendaftaran beasiswa.'))->name('application.submit');
                Route::post('/pendaftaran/{application}/dokumen', fn() => redirect()->route('mahasiswa.dashboard')->with('error', 'Unggah dokumen alur lama dinonaktifkan.'))->name('documents.store');
                Route::delete('/pendaftaran/{application}/dokumen/{document}', fn() => redirect()->route('mahasiswa.dashboard'))->name('documents.destroy');
            });

        Route::middleware(['role:superadmin', '2fa.ensure'])
            ->prefix('superadmin')
            ->name('superadmin.')
            ->group(function (): void {
                Route::get('/dashboard', SuperadminDashboardController::class)->name('dashboard');

                Route::resource('kategori-beasiswa', SuperadminKategoriBeasiswaController::class)
                    ->except('show')
                    ->parameters(['kategori-beasiswa' => 'kategoriBeasiswa']);

                Route::resource('document-types', SuperadminDocumentTypeController::class)
                    ->except('show')
                    ->parameters(['document-types' => 'documentType']);

                Route::resource('operators', SuperadminOperatorController::class)
                    ->except('show')
                    ->parameters(['operators' => 'operator']);

                Route::post('operators/{operator}/reset-password', [SuperadminOperatorController::class, 'resetPassword'])->name('operators.reset-password');
            });

        Route::middleware(['role:operator_desa,operator_kecamatan,operator_dukcapil,operator_sosial,operator_pendidikan,operator_kabupaten', '2fa.ensure'])
            ->prefix('operator')
            ->name('operator.')
            ->group(function (): void {
                Route::get('/dashboard', [OperatorDashboardController::class, 'index'])->name('dashboard');
                Route::get('/pengajuan', [OperatorApplicationController::class, 'index'])->name('applications.index');
                Route::get('/pengajuan/{application}', [OperatorApplicationController::class, 'show'])->name('applications.show');
                Route::post('/pengajuan/{application}/verifikasi', [VerificationController::class, 'store'])->name('applications.verify');

                Route::middleware('role:operator_kecamatan,operator_kabupaten')->group(function (): void {
                    Route::get('/laporan/rekap-excel', [ReportController::class, 'recap'])->name('reports.recap');
                });

                Route::middleware('role:operator_kabupaten')->group(function (): void {
                    Route::get('/rekonsiliasi', [ReconciliationController::class, 'index'])->name('reconciliation');
                    Route::get('/seleksi', [SelectionController::class, 'index'])->name('selection');
                    Route::post('/seleksi/{application}', [SelectionController::class, 'store'])->name('selection.store');
                    Route::post('/seleksi-publikasi', [SelectionController::class, 'publish'])->name('selection.publish');
                    Route::get('/laporan/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
                    Route::get('/laporan/penerima/pdf', [ReportController::class, 'recipientsPdf'])->name('reports.recipients-pdf');
                    Route::get('/laporan/excel', [ReportController::class, 'excel'])->name('reports.excel');
                });
            });
    });
});
