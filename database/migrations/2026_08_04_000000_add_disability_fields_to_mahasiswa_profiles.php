<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa_profiles', function (Blueprint $table): void {
            $table->string('disability_type')->nullable()->after('ipk');
            $table->string('disability_grade')->nullable()->after('disability_type');
            $table->string('disability_document_number')->nullable()->after('disability_grade');
        });

        if (! Schema::hasTable('disability_metadata')) {
            Schema::create('disability_metadata', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('label');
                $table->string('category', 50)->index();
                $table->unsignedTinyInteger('default_weight')->default(60);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('disability_metadata');

        Schema::table('mahasiswa_profiles', function (Blueprint $table): void {
            $table->dropColumn(['disability_type', 'disability_grade', 'disability_document_number']);
        });
    }
};
