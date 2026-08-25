<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')
            ->where('status', 'MS_DESA')
            ->update(['status' => 'VERIFIKASI_KECAMATAN']);

        DB::table('applications')
            ->where('status', 'MS')
            ->update(['status' => 'VERIFIKASI_DINAS']);

        DB::table('verification_logs')
            ->where('from_status', 'MS_DESA')
            ->update(['from_status' => 'VERIFIKASI_KECAMATAN']);

        DB::table('verification_logs')
            ->where('to_status', 'MS_DESA')
            ->update(['to_status' => 'VERIFIKASI_KECAMATAN']);

        DB::table('verification_logs')
            ->where('from_status', 'MS')
            ->update(['from_status' => 'VERIFIKASI_DINAS']);

        DB::table('verification_logs')
            ->where('to_status', 'MS')
            ->update(['to_status' => 'VERIFIKASI_DINAS']);
    }

    public function down(): void {}
};
