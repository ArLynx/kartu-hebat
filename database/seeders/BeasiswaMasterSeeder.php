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

        $generalDocuments = [
            'KTP',
            'KK',
            'KTM',
            'SERTIFIKAT-AKREDITASI',
            'BUKU-REKENING-BPDK',
            'SURAT-AKTIF',
            'SCREENSHOT-PDDIKTI',
            'TAGIHAN-UKT',
        ];

        $categories = [
            [
                'kode' => 'BEASISWA-MAHASISWA',
                'application_type' => ApplicationType::AKADEMIK,
                'nama' => 'Beasiswa Prestasi Akademik',
                'deskripsi' => 'Jenis beasiswa berdasarkan IPK dan semester aktif.',
                'urutan' => 1,
                'documents' => array_merge($generalDocuments, ['KHS']),
            ],
            [
                'kode' => 'BEASISWA-TIDAK-MAMPU',
                'application_type' => ApplicationType::TIDAK_MAMPU,
                'nama' => 'Beasiswa Tidak Mampu',
                'deskripsi' => 'Jenis beasiswa berdasarkan kondisi sosial ekonomi yang diverifikasi lintas dinas.',
                'urutan' => 2,
                'documents' => array_merge($generalDocuments, ['KHS']),
            ],
            [
                'kode' => 'BEASISWA-DISABILITAS',
                'application_type' => ApplicationType::DISABILITAS,
                'nama' => 'Beasiswa Mahasiswa Disabilitas',
                'deskripsi' => 'Jenis beasiswa untuk mahasiswa penyandang disabilitas.',
                'urutan' => 3,
                'documents' => array_merge($generalDocuments, ['KHS', 'SURAT-DISABILITAS']),
            ],
            [
                'kode' => 'BEASISWA-NON-AKADEMIK',
                'application_type' => ApplicationType::NON_AKADEMIK,
                'nama' => 'Beasiswa Prestasi Non Akademik',
                'deskripsi' => 'Jenis beasiswa untuk mahasiswa berprestasi di bidang keagamaan, olahraga, seni budaya, kepemudaan atau aktif kepengurusan organisasi mahasiswa.',
                'urutan' => 4,
                'documents' => array_merge($generalDocuments, ['KHS']),
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
                'deskripsi' => 'KTM atau kartu identitas mahasiswa lain yang masih berlaku.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SERTIFIKAT-AKREDITASI' => [
                'nama' => 'Sertifikat Akreditasi Perguruan Tinggi',
                'deskripsi' => 'Sertifikat akreditasi perguruan tinggi yang masih berlaku.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'BUKU-REKENING-BPDK' => [
                'nama' => 'Buku Rekening Bank Kalteng (BPD Kalimantan Tengah)',
                'deskripsi' => 'Buku rekening atas nama mahasiswa pada Bank Pembangunan Daerah Kalimantan Tengah.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SURAT-AKTIF' => [
                'nama' => 'Surat Keterangan Aktif Kuliah',
                'deskripsi' => 'Surat keterangan aktif kuliah dari perguruan tinggi dengan tanda tangan dan cap basah atau tanda tangan elektronik pejabat berwenang.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SCREENSHOT-PDDIKTI' => [
                'nama' => 'Tangkapan Layar PDDIKTI',
                'deskripsi' => 'Gambar tangkapan layar terdaftar aktif sebagai mahasiswa pada PDDIKTI.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'TAGIHAN-UKT' => [
                'nama' => 'Tagihan Pembayaran UKT Semester Terakhir',
                'deskripsi' => 'Tagihan pembayaran uang kuliah tunggal (UKT) semester terakhir yang sedang ditempuh.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'KHS' => [
                'nama' => 'Kartu Hasil Studi (KHS) / Transkrip Nilai',
                'deskripsi' => 'Transkrip nilai atau KHS terakhir dengan IPK kumulatif minimal 2,50 untuk jalur Tidak Mampu dan 3,00 untuk jalur Prestasi Akademik.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SKTM' => [
                'nama' => 'Surat Keterangan Tidak Mampu',
                'deskripsi' => 'SKTM atau dokumen sosial ekonomi lain yang masih berlaku (dokumen pendukung, tidak wajib).',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SERTIFIKAT-PRESTASI' => [
                'nama' => 'Sertifikat Prestasi / SK Organisasi',
                'deskripsi' => 'Sertifikat penghargaan atau SK kepengurusan organisasi mahasiswa (diunggah melalui data prestasi, tidak wajib sebagai dokumen administrasi).',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'SURAT-DISABILITAS' => [
                'nama' => 'Surat Keterangan Disabilitas',
                'deskripsi' => 'Kartu penyandang disabilitas atau surat keterangan ragam penyandang disabilitas dari dokter/tenaga profesional yang berwenang.',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'FOTO-KONDISI' => [
                'nama' => 'Foto Kondisi / Alat Bantu',
                'deskripsi' => 'Foto kondisi disabilitas atau alat bantu yang digunakan (jika ada).',
                'format_file' => 'pdf,jpg,jpeg,png',
            ],
            'TAMBAHAN' => [
                'nama' => 'Dokumen Tambahan',
                'deskripsi' => 'Dokumen prestasi atau dokumen pendukung lain.',
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
