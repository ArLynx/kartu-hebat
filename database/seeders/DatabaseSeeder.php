<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            MasterDataSeeder::class,
            BeasiswaMasterSeeder::class,
            DisabilityTrackSeeder::class,
            PrestasiTrackSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(BeasiswaDemoUserSeeder::class);
            $this->call(CompleteStudentSeeder::class);
        }
    }
}
