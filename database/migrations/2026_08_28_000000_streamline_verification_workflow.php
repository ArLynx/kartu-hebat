<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Aplikasi yang masih dalam antrean desa/kecamatan (termasuk yang
        // baru dikirim sebelum sempat diverifikasi) langsung masuk antrean
        // lintas dinas karena tahap desa/kecamatan dihapus dari alur.
        DB::table('applications')
            ->whereIn('status', ['SUBMITTED', 'VERIFIKASI_DESA', 'VERIFIKASI_KECAMATAN'])
            ->update(['status' => 'VERIFIKASI_DINAS']);

        // BTL tidak lagi dikenal. Aplikasi yang dikembalikan ke mahasiswa
        // kembali menjadi draf agar bisa diperbaiki dan disubmit ulang
        // langsung ke lintas dinas.
        DB::table('applications')
            ->whereIn('status', ['BTL_DESA', 'BTL_KECAMATAN'])
            ->update(['status' => 'DRAFT']);

        DB::table('verification_logs')
            ->whereIn('from_status', ['SUBMITTED', 'VERIFIKASI_DESA', 'VERIFIKASI_KECAMATAN'])
            ->update(['from_status' => 'VERIFIKASI_DINAS']);

        DB::table('verification_logs')
            ->whereIn('to_status', ['SUBMITTED', 'VERIFIKASI_DESA', 'VERIFIKASI_KECAMATAN'])
            ->update(['to_status' => 'VERIFIKASI_DINAS']);

        DB::table('verification_logs')
            ->whereIn('from_status', ['BTL_DESA', 'BTL_KECAMATAN'])
            ->update(['from_status' => 'DRAFT']);

        DB::table('verification_logs')
            ->whereIn('to_status', ['BTL_DESA', 'BTL_KECAMATAN'])
            ->update(['to_status' => 'DRAFT']);
    }

    public function down(): void {}
};
