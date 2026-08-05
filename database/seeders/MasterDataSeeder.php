<?php

namespace Database\Seeders;

use App\Enums\ApplicationType;
use App\Models\Criterion;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $documents = [
            ['KHS', 'Kartu Hasil Studi (KHS)', 'KHS semester terakhir yang telah dilegalisasi untuk jalur Akademik.', true, 10, ApplicationType::AKADEMIK],
            ['SKTM', 'Surat Keterangan Tidak Mampu', 'SKTM yang masih berlaku untuk jalur Tidak Mampu.', true, 20, ApplicationType::TIDAK_MAMPU],
            ['SURAT-DISABILITAS', 'Surat Keterangan Disabilitas', 'Surat keterangan disabilitas dari dokter/RS atau dari dinas sosial yang masih berlaku.', true, 15, ApplicationType::DISABILITAS],
            ['FOTO-KONDISI', 'Foto Kondisi / Alat Bantu', 'Foto kondisi disabilitas atau alat bantu yang digunakan (jika ada).', false, 16, ApplicationType::DISABILITAS],
            ['SERTIFIKAT-PRESTASI', 'Sertifikat / Piagam Prestasi', 'Sertifikat atau piagam kejuaraan beserta surat keterangan dari perguruan tinggi untuk jalur Non-Akademik.', true, 25, ApplicationType::NON_AKADEMIK],
            ['KTP', 'Kartu Tanda Penduduk', 'KTP mahasiswa yang masih berlaku.', true, 30, null],
            ['KK', 'Kartu Keluarga', 'Kartu Keluarga terbaru.', true, 40, null],
            ['KTM', 'Kartu Mahasiswa', 'Kartu Tanda Mahasiswa aktif.', true, 50, null],
            ['TAMBAHAN', 'Dokumen Tambahan', 'Dokumen prestasi atau dokumen pendukung lain.', false, 60, null],
        ];

        foreach ($documents as [$code, $name, $description, $required, $order, $applicationType]) {
            DocumentType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'application_type' => $applicationType?->value,
                    'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
                    'max_size_kb' => 2048,
                    'is_required' => $required,
                    'is_active' => true,
                    'sort_order' => $order,
                ],
            );
        }

        Criterion::query()->whereNotIn('code', ['ipk', 'semester', 'desil'])->delete();

        $criteria = [
            ['ipk', 'Indeks Prestasi Kumulatif', ApplicationType::AKADEMIK, 75, 'benefit', 10],
            ['semester', 'Semester Aktif', ApplicationType::AKADEMIK, 25, 'benefit', 20],
            ['desil', 'Desil Sosial Ekonomi', ApplicationType::TIDAK_MAMPU, 100, 'cost', 10],
        ];

        foreach ($criteria as [$code, $name, $applicationType, $weight, $type, $order]) {
            Criterion::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'application_type' => $applicationType->value,
                    'weight' => $weight,
                    'type' => $type,
                    'is_active' => true,
                    'sort_order' => $order,
                ],
            );
        }
    }
}
