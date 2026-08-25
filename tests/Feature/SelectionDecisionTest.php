<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\MahasiswaProfile;
use App\Models\Selection;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionDecisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        config(['kartu_hebat.quotas.AKADEMIK' => 1]);
    }

    public function test_acceptance_is_blocked_when_quota_is_already_full(): void
    {
        $operator = $this->kabupatenOperator();
        $first = $this->candidateInSelection();
        $second = $this->candidateInSelection();

        $this->actingAs($operator)
            ->post(route('operator.selection.store', $first), ['decision' => 'DITERIMA'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($operator)
            ->post(route('operator.selection.store', $second), ['decision' => 'DITERIMA'])
            ->assertRedirect()
            ->assertSessionHasErrors('decision');

        $this->assertNull($second->fresh()->selection->manual_decision);
    }

    public function test_revising_the_accepted_candidate_does_not_count_against_quota_again(): void
    {
        $operator = $this->kabupatenOperator();
        $accepted = $this->candidateInSelection();

        $this->actingAs($operator)
            ->post(route('operator.selection.store', $accepted), ['decision' => 'DITERIMA'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($operator)
            ->post(route('operator.selection.store', $accepted), [
                'decision' => 'DITERIMA',
                'notes' => 'Konfirmasi ulang penerima.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('DITERIMA', $accepted->fresh()->selection->manual_decision);
    }

    private function kabupatenOperator(): User
    {
        $village = Village::query()->with('kecamatan')->firstOrFail();

        $operator = User::factory()->create([
            'role' => UserRole::OPERATOR_KABUPATEN,
            'kabupaten_id' => $village->kabupaten_id,
        ]);
        $operator->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $operator;
    }

    private function candidateInSelection(): Application
    {
        $village = Village::query()->with('kecamatan')->firstOrFail();
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212123456789'.str_pad((string) $student->id, 3, '0', STR_PAD_LEFT),
            'nim' => 'KHM-SEL-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.75,
            'alamat' => 'Jalan Pengujian',
        ]);

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-2026-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'application_type' => ApplicationType::AKADEMIK,
        ]);

        Selection::query()->create([
            'application_id' => $application->id,
            'final_score' => 80.0,
        ]);

        return $application;
    }
}
