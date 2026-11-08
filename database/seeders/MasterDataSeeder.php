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
            // Persyaratan umum
            ['KTP', 'Kartu Tanda Penduduk', 'KTP mahasiswa yang masih berlaku.', true, 10, null],
            ['KK', 'Kartu Keluarga', 'Kartu Keluarga terbaru dan terbaca jelas.', true, 20, null],
            ['KTM', 'Kartu Tanda Mahasiswa', 'KTM atau kartu identitas mahasiswa lain yang masih berlaku.', true, 30, null],
            ['SERTIFIKAT-AKREDITASI', 'Sertifikat Akreditasi Perguruan Tinggi', 'Sertifikat akreditasi perguruan tinggi yang masih berlaku.', true, 40, null],
            ['BUKU-REKENING-BPDK', 'Buku Rekening Bank Kalteng (BPD Kalimantan Tengah)', 'Buku rekening atas nama mahasiswa pada Bank Pembangunan Daerah Kalimantan Tengah.', true, 50, null],
            ['SURAT-AKTIF', 'Surat Keterangan Aktif Kuliah', 'Surat keterangan aktif kuliah dari perguruan tinggi dengan tanda tangan dan cap basah atau tanda tangan elektronik pejabat berwenang.', true, 60, null],
            ['SCREENSHOT-PDDIKTI', 'Tangkapan Layar PDDIKTI', 'Gambar tangkapan layar terdaftar aktif sebagai mahasiswa pada PDDIKTI.', true, 70, null],
            ['TAGIHAN-UKT', 'Tagihan Pembayaran UKT Semester Terakhir', 'Tagihan pembayaran uang kuliah tunggal (UKT) semester terakhir yang sedang ditempuh.', true, 80, null],

            // Persyaratan khusus
            ['KHS', 'Kartu Hasil Studi (KHS) / Transkrip Nilai', 'Transkrip nilai atau KHS terakhir dengan IPK kumulatif minimal 2,50 untuk jalur Tidak Mampu dan 3,00 untuk jalur Prestasi Akademik.', true, 90, null],
            ['SKTM', 'Surat Keterangan Tidak Mampu', 'SKTM atau dokumen sosial ekonomi lain yang masih berlaku (dokumen pendukung, tidak wajib).', false, 100, ApplicationType::TIDAK_MAMPU],
            ['SERTIFIKAT-PRESTASI', 'Sertifikat Prestasi / SK Organisasi', 'Sertifikat penghargaan atau SK kepengurusan organisasi mahasiswa (diunggah melalui data prestasi, tidak wajib sebagai dokumen administrasi).', false, 110, ApplicationType::NON_AKADEMIK],
            ['SURAT-DISABILITAS', 'Surat Keterangan Disabilitas', 'Kartu penyandang disabilitas atau surat keterangan ragam penyandang disabilitas dari dokter/tenaga profesional yang berwenang.', true, 120, ApplicationType::DISABILITAS],

            // Dokumen tambahan
            ['FOTO-KONDISI', 'Foto Kondisi / Alat Bantu', 'Foto kondisi disabilitas atau alat bantu yang digunakan (jika ada).', false, 130, ApplicationType::DISABILITAS],
            ['TAMBAHAN', 'Dokumen Tambahan', 'Dokumen prestasi atau dokumen pendukung lain.', false, 140, null],
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
