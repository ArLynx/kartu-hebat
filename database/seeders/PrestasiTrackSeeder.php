<?php

namespace Database\Seeders;

use App\Enums\ApplicationType;
use App\Models\Criterion;
use Illuminate\Database\Seeder;

class PrestasiTrackSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('kartu_hebat.scoring.non_academic_weights', [
            'ipk' => 25,
            'achievement_level' => 45,
            'achievement_rank' => 30,
        ]);

        $criteria = [
            ['code' => 'ipk', 'name' => 'IPK', 'weight' => $defaults['ipk'], 'sort_order' => 1],
            ['code' => 'achievement_level', 'name' => 'Tingkat Kejuaraan', 'weight' => $defaults['achievement_level'], 'sort_order' => 2],
            ['code' => 'achievement_rank', 'name' => 'Peringkat Kejuaraan', 'weight' => $defaults['achievement_rank'], 'sort_order' => 3],
        ];

        foreach ($criteria as $criterion) {
            Criterion::query()->updateOrCreate(
                [
                    'code' => $criterion['code'],
                    'application_type' => ApplicationType::NON_AKADEMIK->value,
                ],
                [
                    'name' => $criterion['name'],
                    'weight' => $criterion['weight'],
                    'sort_order' => $criterion['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
