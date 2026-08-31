<?php

namespace Database\Seeders;

use App\Models\JalurBeasiswa;
use Illuminate\Database\Seeder;

class JalurBeasiswaSeeder extends Seeder
{
    public function run(): void
    {
        JalurBeasiswa::updateOrCreate(
            ['kode' => 'REGULER'],
            [
                'nama' => 'Reguler',
                'deskripsi' => 'Diberikan kepada mahasiswa yang melaksanakan perkuliahan secara langsung atau tatap muka sesuai dengan jadwal perkuliahan yang telah ditetapkan.',
                'aktif' => true,
                'urutan' => 1,
            ]
        );

        JalurBeasiswa::updateOrCreate(
            ['kode' => 'NON_REGULER'],
            [
                'nama' => 'Non Reguler',
                'deskripsi' => 'Diberikan kepada mahasiswa yang melaksanakan perkuliahan jarak jauh atau secara daring sesuai dengan jadwal perkuliahan yang telah ditetapkan.',
                'aktif' => true,
                'urutan' => 2,
            ]
        );
    }
}