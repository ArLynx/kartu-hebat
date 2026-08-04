<?php

namespace Database\Seeders;

use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Village;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $kabupaten = Kabupaten::query()->updateOrCreate(
            ['code' => '6212'],
            ['name' => 'Murung Raya'],
        );

        $districts = [
            ['621201', 'Murung', ['Beriwit', 'Danau Usung', 'Muara Jaan']],
            ['621202', 'Tanah Siang', ['Saripoi', 'Dirung Lingkin', 'Kolam']],
            ['621203', 'Laung Tuhup', ['Muara Laung I', 'Muara Tuhup', 'Batu Tuhup']],
            ['621204', 'Permata Intan', ['Tumbang Lahung', 'Muara Bakanon', 'Juking Pajang']],
            ['621205', 'Sumber Barito', ['Tumbang Kunyi', 'Tumbang Masao', 'Tumbang Tuan']],
            ['621206', 'Sungai Babuat', ['Tumbang Bantian', 'Tumbang Apat', 'Tumbang Saan']],
            ['621207', 'Seribu Riam', ['Muara Joloi I', 'Muara Joloi II', 'Tumbang Naan']],
            ['621208', 'Uut Murung', ['Tumbang Olong I', 'Tumbang Olong II', 'Tumbang Topus']],
            ['621209', 'Barito Tuhup Raya', ['Makunjung', 'Cinta Budiman', 'Batu Tojah']],
            ['621210', 'Tanah Siang Selatan', ['Datah Kotou', 'Dirung Bakung', 'Oreng']],
        ];

        foreach ($districts as $districtIndex => [$code, $name, $villages]) {
            $kecamatan = Kecamatan::query()->updateOrCreate(
                ['code' => $code],
                ['kabupaten_id' => $kabupaten->id, 'name' => $name],
            );

            foreach ($villages as $villageIndex => $villageName) {
                Village::query()->updateOrCreate(
                    ['code' => $code.str_pad((string) ($villageIndex + 1), 4, '0', STR_PAD_LEFT)],
                    [
                        'kabupaten_id' => $kabupaten->id,
                        'kecamatan_id' => $kecamatan->id,
                        'name' => $villageName,
                        'type' => $districtIndex === 0 && $villageIndex === 0 ? 'kelurahan' : 'desa',
                    ],
                );
            }
        }
    }
}
