<?php

namespace Database\Seeders;

use App\Enums\ApplicationType;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\KategoriBeasiswaDokumen;
use App\Models\Periode;
use Illuminate\Database\Seeder;

class BeasiswaMasterSeeder extends Seeder
{
    public function run(): void
    {
        $periode = Periode::query()->updateOrCreate(
            ['nama' => '2026/2027 Ganjil'],
            [
                'tahun' => 2026,
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2026-12-31',
                'status' => 'aktif',
            ],
        );

        $categories = [
            [
                'kode' => 'BEASISWA-MAHASISWA',
                'application_type' => ApplicationType::AKADEMIK,
                'nama' => 'Beasiswa Akademik',
                'deskripsi' => 'Jalur beasiswa berdasarkan IPK dan semester aktif.',
                'urutan' => 1,
                'documents' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'KHS'],
            ],
            [
                'kode' => 'BEASISWA-TIDAK-MAMPU',
                'application_type' => ApplicationType::TIDAK_MAMPU,
                'nama' => 'Beasiswa Tidak Mampu',
                'deskripsi' => 'Jalur afirmasi berdasarkan kondisi sosial ekonomi yang diverifikasi lintas dinas.',
                'urutan' => 2,
                'documents' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'SKTM'],
            ],
            [
                'kode' => 'BEASISWA-DISABILITAS',
                'application_type' => ApplicationType::DISABILITAS,
                'nama' => 'Beasiswa Mahasiswa Disabilitas',
                'deskripsi' => 'Jalur afirmasi untuk mahasiswa penyandang disabilitas dengan pertimbangan jenis dan tingkat disabilitas.',
                'urutan' => 3,
                'documents' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'KHS', 'SURAT-DISABILITAS'],
            ],
            [
                'kode' => 'BEASISWA-NON-AKADEMIK',
                'application_type' => ApplicationType::NON_AKADEMIK,
                'nama' => 'Beasiswa Non-Akademik / Prestasi',
                'deskripsi' => 'Jalur afirmasi untuk mahasiswa berprestasi (MTQ, olahraga, seni, wirausaha, dsb.) berdasarkan tingkat dan peringkat kejuaraan.',
                'urutan' => 4,
                'documents' => ['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'KHS', 'SERTIFIKAT-PRESTASI'],
            ],
        ];

        $documentDefinitions = [
            'KTP' => [
                'nama' => 'Kartu Tanda Penduduk',
                'deskripsi' => 'KTP mahasiswa yang masih berlaku.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'KK' => [
                'nama' => 'Kartu Keluarga',
                'deskripsi' => 'Kartu Keluarga terbaru dan terbaca jelas.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'KTM' => [
                'nama' => 'Kartu Tanda Mahasiswa',
                'deskripsi' => 'KTM atau identitas mahasiswa yang masih berlaku.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SURAT-AKTIF' => [
                'nama' => 'Surat Keterangan Aktif Kuliah',
                'deskripsi' => 'Surat aktif kuliah dari perguruan tinggi untuk semester berjalan.',
                'format_file' => 'pdf',
            ],
            'KHS' => [
                'nama' => 'Kartu Hasil Studi Terakhir',
                'deskripsi' => 'KHS semester terakhir yang memuat IPK untuk jalur Akademik.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SKTM' => [
                'nama' => 'Surat Keterangan Tidak Mampu',
                'deskripsi' => 'SKTM atau dokumen sosial ekonomi lain yang masih berlaku.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SURAT-DISABILITAS' => [
                'nama' => 'Surat Keterangan Disabilitas',
                'deskripsi' => 'Surat keterangan dari dokter/dinas sosial yang menyatakan jenis dan tingkat disabilitas.',
                'format_file' => 'pdf',
            ],
            'SERTIFIKAT-PRESTASI' => [
                'nama' => 'Sertifikat / Piagam Prestasi',
                'deskripsi' => 'Sertifikat atau piagam kejuaraan beserta surat keterangan dari perguruan tinggi.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
        ];

        $documentTypes = collect($documentDefinitions)->mapWithKeys(function (array $definition, string $code): array {
            $document = JenisDokumen::query()->updateOrCreate(
                ['kode' => $code],
                array_merge($definition, [
                    'maksimal_ukuran' => 2048,
                    'aktif' => true,
                ]),
            );

            return [$code => $document];
        });

        foreach ($categories as $categoryDefinition) {
            $category = KategoriBeasiswa::query()->updateOrCreate(
                ['kode' => $categoryDefinition['kode']],
                [
                    'periode_id' => $periode->id,
                    'application_type' => $categoryDefinition['application_type'],
                    'nama' => $categoryDefinition['nama'],
                    'deskripsi' => $categoryDefinition['deskripsi'],
                    'kuota' => $categoryDefinition['application_type']->quota(),
                    'aktif' => true,
                    'urutan' => $categoryDefinition['urutan'],
                'icon' => match ($categoryDefinition['application_type']) {
                    ApplicationType::AKADEMIK => 'school',
                    ApplicationType::TIDAK_MAMPU => 'users',
                    ApplicationType::DISABILITAS => 'heart',
                    ApplicationType::NON_AKADEMIK => 'trophy',
                },
                'warna' => match ($categoryDefinition['application_type']) {
                    ApplicationType::AKADEMIK => '#1E40AF',
                    ApplicationType::TIDAK_MAMPU => '#047857',
                    ApplicationType::DISABILITAS => '#B45309',
                    ApplicationType::NON_AKADEMIK => '#7C3AED',
                },
                ],
            );

            KategoriBeasiswaDokumen::query()
                ->where('kategori_beasiswa_id', $category->id)
                ->delete();

            foreach ($categoryDefinition['documents'] as $index => $code) {
                KategoriBeasiswaDokumen::query()->create([
                    'kategori_beasiswa_id' => $category->id,
                    'jenis_dokumen_id' => $documentTypes[$code]->id,
                    'urutan' => $index + 1,
                ]);
            }
        }
    }
}
