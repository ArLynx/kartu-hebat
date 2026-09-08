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

        /*
        |--------------------------------------------------------------------------
        | DATA KECAMATAN
        |--------------------------------------------------------------------------
        |
        | Urutan dan kode mengikuti data dari teman:
        |
        | 621201 = Murung
        | 621202 = Tanah Siang
        | 621203 = Laung Tuhup
        | 621204 = Permata Intan
        | 621205 = Sumber Barito
        | 621206 = Sungai Babuat
        | 621207 = Seribu Riam
        | 621208 = Uut Murung
        | 621209 = Barito Tuhup Raya
        | 621210 = Tanah Siang Selatan
        |
        */

        $districts = [
            [
                '621201',
                'Murung',
                [
                    'Beriwit',
                    'Danau Usung',
                    'Muara Jaan',
                    'Puruk Cahu',
                    'Bahitom',
                    'Batu Putih',
                    'Dirung',
                    'Malasan',
                    'Mangkahui',
                    'Muara Bumban',
                    'Muara Untu',
                    "Panu'ut",
                    'Penyang',
                ],
            ],

            [
                '621202',
                'Tanah Siang',
                [
                    'Saripoi',
                    'Dirung Lingkin',
                    'Kolam',
                    'Belawan',
                    'Doan Arung',
                    'Cangkang',
                    'Kalang Kaluh',
                    'Karali',
                    'Konut',
                    'Mahanyan',
                    'Mangkalisoi',
                    'Mantiat Pari',
                    'Muwun',
                    'Nono Kliwon',
                    'Olung Balo',
                    'Olung Dojou',
                    'Olung Nango',
                    'Olung Siron',
                    'Olung Soloi',
                    'Olung Ulu',
                    'Osom Tompok',
                    'Puruk Batu',
                    'Saruhung',
                    'Sungai Lunuk',
                    'Tabulang',
                    'Tino Talih',
                    'Tokung',
                ],
            ],

            [
                '621203',
                'Laung Tuhup',
                [
                    'Muara Laung I',
                    'Muara Tuhup',
                    'Batu Tuhup',
                    'Batu Bua I',
                    'Batu Bua II',
                    'Batu Karang',
                    'Beras Balange',
                    'Beralang',
                    'Biha',
                    'Dirung Pinang',
                    'Dirung Pundu',
                    'Kalang Dohong',
                    'Lakutan',
                    'Muara Laung II',
                    'Muara Maruwei I',
                    'Muara Maruwei II',
                    'Muara Tupuh',
                    'Narui',
                    'Pelaci',
                    'Penda Siron',
                    'Tahujang Laung',
                    'Tawai Haui',
                    'Tumbang Bahan',
                    'Tumbang Bana',
                    'Tumbang Bondang',
                    'Tumbang Tonduk',
                ],
            ],

            [
                '621204',
                'Permata Intan',
                [
                    'Tumbang Lahung',
                    'Muara Bakanon',
                    'Juking Sopan',
                    'Baratu',
                    'Muara Babuat',
                    'Pantai Laga',
                    'Purnama',
                    'Sungai Bakanon',
                    'Sungai Batang',
                    'Sungai Gula',
                    'Sungai Lobang',
                    'Tumbang Salio',
                ],
            ],

            [
                '621205',
                'Sumber Barito',
                [
                    'Tumbang Kunyi',
                    'Tumbang Masao',
                    'Tumbang Tuan',
                    'Batu Makap',
                    'Kelapeh Baru',
                    "La'as Baru",
                    'Olong Liko',
                    'Telok Jolo',
                    'Tumbang Molut',
                ],
            ],

            [
                '621206',
                'Sungai Babuat',
                [
                    'Tumbang Bantian',
                    'Tumbang Apat',
                    'Tumbang Saan',
                    'Mirau',
                    'Tambelum',
                    'Tumbang Kolon',
                ],
            ],

            [
                '621207',
                'Seribu Riam',
                [
                    'Muara Joloi I',
                    'Muara Joloi II',
                    'Tumbang Naan',
                    'Parahau',
                    'Tumbang Jojang',
                    'Tumbang Takajung',
                    'Tumbang Tohan',
                ],
            ],

            [
                '621208',
                'Uut Murung',
                [
                    'Tumbang Olong I',
                    'Tumbang Olong II',
                    'Tumbang Topus',
                    'Kalasin',
                    'Tumbang Tujang',
                ],
            ],

            [
                '621209',
                'Barito Tuhup Raya',
                [
                    'Makunjung',
                    'Cinta Budiman',
                    'Batu Tojah',
                    'Bumban Tuhup',
                    'Dirung Sararong',
                    'Hingan Tokung',
                    'Kohong',
                    'Liang Nyaling',
                    'Tumbang Baloi',
                    'Tumbang Bauh',
                    'Tumbang Masalo',
                ],
            ],

            [
                '621210',
                'Tanah Siang Selatan',
                [
                    'Datah Kotou',
                    'Dirung Bakung',
                    'Oreng',
                    'Olung Hanangan',
                    'Olung Muro',
                    'Puruk Kambang',
                    'Tahujan Ontu',
                ],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | DAFTAR KELURAHAN
        |--------------------------------------------------------------------------
        |
        | Nama di sini harus sama persis dengan nama yang ada di $districts.
        |
        */

        $kelurahans = [
            'Beriwit',
            'Puruk Cahu',

            'Saripoi',

            'Muara Laung I',
            'Muara Tuhup',
            'Batu Bua I',

            'Tumbang Lahung',
            'Muara Bakanon',

            'Tumbang Kunyi',
        ];

        /*
        |--------------------------------------------------------------------------
        | SIMPAN KECAMATAN DAN DESA / KELURAHAN
        |--------------------------------------------------------------------------
        */

        foreach ($districts as $districtIndex => [$code, $name, $villages]) {
            $kecamatan = Kecamatan::query()->updateOrCreate(
                ['code' => $code],
                [
                    'kabupaten_id' => $kabupaten->id,
                    'name' => $name,
                ],
            );

            foreach ($villages as $villageIndex => $villageName) {
                Village::query()->updateOrCreate(
                    [
                        'code' => $code . str_pad(
                            (string) ($villageIndex + 1),
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),
                    ],
                    [
                        'kabupaten_id' => $kabupaten->id,
                        'kecamatan_id' => $kecamatan->id,
                        'name' => $villageName,
                        'type' => in_array(
                            $villageName,
                            $kelurahans,
                            true
                        )
                            ? 'kelurahan'
                            : 'desa',
                    ],
                );
            }
        }
    }
}