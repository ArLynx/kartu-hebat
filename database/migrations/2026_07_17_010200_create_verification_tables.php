<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('verifier_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 3);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
        });

        Schema::create('district_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('verifier_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 3);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
        });

        Schema::create('agency_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verifier_id')->constrained('users')->restrictOnDelete();
            $table->string('agency');
            $table->string('decision', 3);
            $table->decimal('score', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
            $table->unique(['application_id', 'agency']);
        });

        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('action');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
        Schema::dropIfExists('agency_verifications');
        Schema::dropIfExists('district_verifications');
        Schema::dropIfExists('village_verifications');
    }
};
