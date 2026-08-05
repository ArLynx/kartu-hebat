<?php

namespace Database\Seeders;

use App\Enums\ApplicationType;
use App\Models\Criterion;
use Illuminate\Database\Seeder;

class DisabilityTrackSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('kartu_hebat.scoring.disability_weights', [
            'ipk' => 50,
            'semester' => 15,
            'disability_grade' => 20,
            'disability_type' => 15,
        ]);

        $criteria = [
            ['code' => 'ipk', 'name' => 'IPK', 'weight' => $defaults['ipk'], 'sort_order' => 1],
            ['code' => 'semester', 'name' => 'Semester Aktif', 'weight' => $defaults['semester'], 'sort_order' => 2],
            ['code' => 'disability_grade', 'name' => 'Tingkat Disabilitas', 'weight' => $defaults['disability_grade'], 'sort_order' => 3],
            ['code' => 'disability_type', 'name' => 'Jenis Disabilitas', 'weight' => $defaults['disability_type'], 'sort_order' => 4],
        ];

        foreach ($criteria as $criterion) {
            Criterion::query()->updateOrCreate(
                [
                    'code' => $criterion['code'],
                    'application_type' => ApplicationType::DISABILITAS->value,
                ],
                [
                    'name' => $criterion['name'],
                    'weight' => $criterion['weight'],
                    'sort_order' => $criterion['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $metadatas = [
            ['code' => 'TUNANETRA', 'label' => 'Tunanetra', 'category' => 'sensorik', 'default_weight' => 100],
            ['code' => 'TUNARUNGU', 'label' => 'Tunarunggu', 'category' => 'sensorik', 'default_weight' => 100],
            ['code' => 'TUNAWICARA', 'label' => 'Tunawicara', 'category' => 'sensorik', 'default_weight' => 100],
            ['code' => 'TUNADAKSA', 'label' => 'Tunadaksa', 'category' => 'fisik', 'default_weight' => 90],
            ['code' => 'TUNAGRAHITA', 'label' => 'Tunagrahita', 'category' => 'intelektual', 'default_weight' => 85],
            ['code' => 'DISABILITAS_GANDA', 'label' => 'Disabilitas Ganda', 'category' => 'ganda', 'default_weight' => 100],
            ['code' => 'LAINNYA', 'label' => 'Lainnya', 'category' => 'lainnya', 'default_weight' => 60],
        ];

        foreach ($metadatas as $row) {
            \DB::table('disability_metadata')->updateOrInsert(
                ['code' => $row['code']],
                $row + ['is_active' => true, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }
}
