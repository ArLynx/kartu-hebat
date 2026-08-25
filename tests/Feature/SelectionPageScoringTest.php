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
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionPageScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RegionSeeder::class);
        $this->seed(MasterDataSeeder::class);
    }

    public function test_selection_index_does_not_create_or_modify_selections(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->with('kecamatan')->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        // Kandidat tanpa selection — sebelum fix, GET akan backfill skor.
        $withoutSelection = $this->candidate($village, ApplicationType::AKADEMIK, [
            'ipk' => 3.80,
            'semester' => 6,
        ], withSelection: false);

        // Dua kandidat dengan skor dan rank sengaja dibalik — sebelum fix, GET akan merapikan rank.
        $highScore = $this->candidate($village, ApplicationType::AKADEMIK, [
            'ipk' => 3.90,
            'semester' => 8,
        ], withSelection: true, finalScore: 95.0, rank: 2);

        $lowScore = $this->candidate($village, ApplicationType::AKADEMIK, [
            'ipk' => 3.00,
            'semester' => 2,
        ], withSelection: true, finalScore: 50.0, rank: 1);

        $selectionsBefore = Selection::query()->pluck('final_score', 'id')->all();
        $ranksBefore = Selection::query()->pluck('rank', 'id')->all();
        $countBefore = Selection::query()->count();
        $highUpdatedAt = $highScore->selection->fresh()->updated_at;
        $lowUpdatedAt = $lowScore->selection->fresh()->updated_at;

        $this->actingAs($operator)
            ->get(route('operator.selection', ['application_type' => ApplicationType::AKADEMIK->value]))
            ->assertOk()
            ->assertViewIs('operator.selection');

        // Tidak ada baris baru yang tercipta
        $this->assertSame($countBefore, Selection::query()->count(), 'GET tidak boleh membuat selection baru.');
        $this->assertFalse($withoutSelection->fresh()->relationLoaded('selection') ? (bool) $withoutSelection->selection : (bool) $withoutSelection->fresh()->selection, 'Kandidat tanpa selection tetap tanpa selection setelah GET.');
        $this->assertDatabaseMissing('selections', ['application_id' => $withoutSelection->id]);

        // Skor dan rank stabil (tidak ada write)
        $this->assertSame($selectionsBefore[$highScore->selection->id], (string) Selection::query()->find($highScore->selection->id)->final_score);
        $this->assertSame($selectionsBefore[$lowScore->selection->id], (string) Selection::query()->find($lowScore->selection->id)->final_score);
        $this->assertSame($ranksBefore[$highScore->selection->id], Selection::query()->find($highScore->selection->id)->rank);
        $this->assertSame($ranksBefore[$lowScore->selection->id], Selection::query()->find($lowScore->selection->id)->rank);

        // updated_at tidak boleh berubah
        $this->assertTrue($highScore->selection->fresh()->updated_at->equalTo($highUpdatedAt), 'Rank/score stabil: updated_at tidak boleh berubah.');
        $this->assertTrue($lowScore->selection->fresh()->updated_at->equalTo($lowUpdatedAt));
    }

    public function test_selection_index_still_renders_sorted_by_rank(): void
    {
        $operator = $this->kabupatenOperator();
        $village = Village::query()->with('kecamatan')->where('kabupaten_id', $operator->kabupaten_id)->firstOrFail();

        $first = $this->candidate($village, ApplicationType::AKADEMIK, ['ipk' => 3.90, 'semester' => 8], withSelection: true, finalScore: 90.0, rank: 1);
        $second = $this->candidate($village, ApplicationType::AKADEMIK, ['ipk' => 3.00, 'semester' => 2], withSelection: true, finalScore: 40.0, rank: 2);

        $response = $this->actingAs($operator)
            ->get(route('operator.selection', ['application_type' => ApplicationType::AKADEMIK->value]))
            ->assertOk();

        // Pastikan kedua kandidat tampil, halaman tidak error
        $response->assertSee($first->nomor_pengajuan);
        $response->assertSee($second->nomor_pengajuan);
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

    private function candidate(Village $village, ApplicationType $type, array $profileOverrides, bool $withSelection, ?float $finalScore = null, ?int $rank = null): Application
    {
        $student = User::factory()->create(['role' => UserRole::MAHASISWA]);
        MahasiswaProfile::query()->create(array_merge([
            'user_id' => $student->id,
            'village_id' => $village->id,
            'nik' => '6212'.str_pad((string) $student->id, 12, '0', STR_PAD_LEFT),
            'nim' => 'KHM-T-'.str_pad((string) $student->id, 5, '0', STR_PAD_LEFT),
            'universitas' => 'Universitas Pengujian',
            'program_studi' => 'Teknik Informatika',
            'semester' => 5,
            'ipk' => 3.50,
            'alamat' => 'Jalan Pengujian',
        ], $profileOverrides));

        $application = Application::query()->create([
            'mahasiswa_id' => $student->id,
            'nomor_pengajuan' => 'KHM-2026-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT),
            'periode' => config('kartu_hebat.current_period'),
            'status' => ApplicationStatus::SELEKSI_KABUPATEN,
            'application_type' => $type,
        ]);

        if ($withSelection) {
            Selection::query()->create([
                'application_id' => $application->id,
                'final_score' => $finalScore ?? 80.0,
                'rank' => $rank,
            ]);
            $application->load('selection');
        }

        return $application;
    }
}
