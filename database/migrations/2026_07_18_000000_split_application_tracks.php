<?php

use App\Enums\ApplicationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('application_type')->nullable()->after('periode')->index();
        });

        Schema::table('document_types', function (Blueprint $table): void {
            $table->string('application_type')->nullable()->after('description')->index();
        });

        Schema::table('criteria', function (Blueprint $table): void {
            $table->string('application_type')->nullable()->after('name')->index();
        });

        DB::table('applications')
            ->whereNull('application_type')
            ->update(['application_type' => ApplicationType::AKADEMIK->value]);

        DB::table('document_types')->where('code', 'KHS')->update([
            'application_type' => ApplicationType::AKADEMIK->value,
            'description' => 'KHS semester terakhir yang telah dilegalisasi untuk jalur Akademik.',
        ]);
        DB::table('document_types')->where('code', 'SKTM')->update([
            'application_type' => ApplicationType::TIDAK_MAMPU->value,
            'description' => 'SKTM yang masih berlaku untuk jalur Tidak Mampu.',
        ]);

        DB::table('criteria')->whereIn('code', ['ekonomi', 'prestasi'])->delete();
        DB::table('criteria')->where('code', 'ipk')->update([
            'name' => 'Indeks Prestasi Kumulatif',
            'application_type' => ApplicationType::AKADEMIK->value,
            'weight' => 75,
            'type' => 'benefit',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        DB::table('criteria')->where('code', 'semester')->update([
            'name' => 'Semester Aktif',
            'application_type' => ApplicationType::AKADEMIK->value,
            'weight' => 25,
            'type' => 'benefit',
            'sort_order' => 20,
            'is_active' => true,
        ]);
        DB::table('criteria')->updateOrInsert(
            ['code' => 'desil'],
            [
                'name' => 'Desil Sosial Ekonomi',
                'application_type' => ApplicationType::TIDAK_MAMPU->value,
                'weight' => 100,
                'type' => 'cost',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->recalculateLegacyAcademicScores();
    }

    public function down(): void
    {
        DB::table('criteria')->where('code', 'desil')->delete();
        DB::table('criteria')->where('code', 'ipk')->update([
            'application_type' => null,
            'weight' => 30,
            'sort_order' => 20,
        ]);
        DB::table('criteria')->where('code', 'semester')->update([
            'application_type' => null,
            'weight' => 10,
            'sort_order' => 30,
        ]);
        DB::table('criteria')->updateOrInsert(
            ['code' => 'ekonomi'],
            [
                'name' => 'Kondisi Ekonomi',
                'application_type' => null,
                'weight' => 40,
                'type' => 'cost',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('criteria')->updateOrInsert(
            ['code' => 'prestasi'],
            [
                'name' => 'Prestasi Akademik/Nonakademik',
                'application_type' => null,
                'weight' => 20,
                'type' => 'benefit',
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('document_types')->whereIn('code', ['KHS', 'SKTM'])->update(['application_type' => null]);

        Schema::table('criteria', function (Blueprint $table): void {
            $table->dropColumn('application_type');
        });
        Schema::table('document_types', function (Blueprint $table): void {
            $table->dropColumn('application_type');
        });
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn('application_type');
        });
    }

    private function recalculateLegacyAcademicScores(): void
    {
        $ipkCriterionId = DB::table('criteria')->where('code', 'ipk')->value('id');
        $semesterCriterionId = DB::table('criteria')->where('code', 'semester')->value('id');
        $maxSemester = max(1, (int) config('kartu_hebat.scoring.academic_max_semester', 8));

        $rows = DB::table('selections')
            ->join('applications', 'applications.id', '=', 'selections.application_id')
            ->join('mahasiswa_profiles', 'mahasiswa_profiles.user_id', '=', 'applications.mahasiswa_id')
            ->join('villages', 'villages.id', '=', 'mahasiswa_profiles.village_id')
            ->select([
                'selections.id as selection_id',
                'applications.id as application_id',
                'applications.periode',
                'villages.kabupaten_id',
                'mahasiswa_profiles.ipk',
                'mahasiswa_profiles.semester',
            ])
            ->get();

        $rankRows = [];
        $timestamp = now();

        foreach ($rows as $row) {
            $ipk = (float) ($row->ipk ?? 0);
            $semester = (int) ($row->semester ?? 0);
            $ipkNormalized = min(100, max(0, ($ipk / 4) * 100));
            $semesterNormalized = min(100, max(0, ($semester / $maxSemester) * 100));
            $ipkWeighted = $ipkNormalized * 0.75;
            $semesterWeighted = $semesterNormalized * 0.25;
            $finalScore = round($ipkWeighted + $semesterWeighted, 4);

            if ($ipkCriterionId !== null) {
                DB::table('application_scores')->updateOrInsert(
                    [
                        'application_id' => $row->application_id,
                        'criterion_id' => $ipkCriterionId,
                    ],
                    [
                        'raw_value' => $ipk,
                        'normalized_score' => round($ipkNormalized, 4),
                        'weighted_score' => round($ipkWeighted, 4),
                        'source' => 'automatic',
                        'updated_at' => $timestamp,
                        'created_at' => $timestamp,
                    ],
                );
            }

            if ($semesterCriterionId !== null) {
                DB::table('application_scores')->updateOrInsert(
                    [
                        'application_id' => $row->application_id,
                        'criterion_id' => $semesterCriterionId,
                    ],
                    [
                        'raw_value' => $semester,
                        'normalized_score' => round($semesterNormalized, 4),
                        'weighted_score' => round($semesterWeighted, 4),
                        'source' => 'automatic',
                        'updated_at' => $timestamp,
                        'created_at' => $timestamp,
                    ],
                );
            }

            DB::table('selections')->where('id', $row->selection_id)->update([
                'final_score' => $finalScore,
                'rank' => null,
                'updated_at' => $timestamp,
            ]);

            $rankRows[] = [
                'selection_id' => $row->selection_id,
                'application_id' => $row->application_id,
                'group' => $row->kabupaten_id.'|'.$row->periode,
                'final_score' => $finalScore,
            ];
        }

        collect($rankRows)
            ->groupBy('group')
            ->each(function ($group): void {
                $group
                    ->sort(function (array $left, array $right): int {
                        return $right['final_score'] <=> $left['final_score']
                            ?: $left['application_id'] <=> $right['application_id'];
                    })
                    ->values()
                    ->each(function (array $row, int $index): void {
                        DB::table('selections')
                            ->where('id', $row['selection_id'])
                            ->update(['rank' => $index + 1]);
                    });
            });
    }
};
