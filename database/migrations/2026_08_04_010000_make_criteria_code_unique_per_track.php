<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::table('criteria', function (Blueprint $table) use ($driver): void {
            if ($driver === 'mysql') {
                $table->dropUnique('criteria_code_unique');
            } else {
                $table->dropUnique(['code']);
            }

            $table->unique(['code', 'application_type'], 'criteria_code_application_type_unique');
        });
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::table('criteria', function (Blueprint $table) use ($driver): void {
            if ($driver === 'mysql') {
                $table->dropUnique('criteria_code_application_type_unique');
            } else {
                $table->dropUnique(['code', 'application_type']);
            }

            $table->unique('code', 'criteria_code_unique');
        });
    }
};
