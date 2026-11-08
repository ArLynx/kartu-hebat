<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\JenisDokumen;
use App\Models\KategoriBeasiswa;
use App\Models\KategoriBeasiswaDokumen;
use Database\Seeders\BeasiswaMasterSeeder;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRequirementSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private const GENERAL_DOCUMENTS = [
        'KTP',
        'KK',
        'KTM',
        'SERTIFIKAT-AKREDITASI',
        'BUKU-REKENING-BPDK',
        'SURAT-AKTIF',
        'SCREENSHOT-PDDIKTI',
        'TAGIHAN-UKT',
    ];

    public function test_master_and_registration_document_types_are_complete_and_mirrored(): void
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);

        $expectedCodes = array_merge(self::GENERAL_DOCUMENTS, [
            'KHS',
            'SKTM',
            'SERTIFIKAT-PRESTASI',
            'SURAT-DISABILITAS',
            'FOTO-KONDISI',
            'TAMBAHAN',
        ]);

        $this->assertSame(
            $expectedCodes,
            DocumentType::query()->orderBy('sort_order')->pluck('code')->all(),
        );

        foreach (DocumentType::query()->pluck('code') as $code) {
            $this->assertDatabaseHas('jenis_dokumens', [
                'kode' => $code,
            ]);
        }

        $this->assertNull(DocumentType::query()->where('code', 'KHS')->firstOrFail()->application_type);
        $this->assertFalse(DocumentType::query()->where('code', 'SKTM')->firstOrFail()->is_required);
        $this->assertFalse(DocumentType::query()->where('code', 'SERTIFIKAT-PRESTASI')->firstOrFail()->is_required);
    }

    public function test_each_scholarship_category_requires_the_regulation_documents(): void
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);

        $expected = [
            'BEASISWA-MAHASISWA' => array_merge(self::GENERAL_DOCUMENTS, ['KHS']),
            'BEASISWA-TIDAK-MAMPU' => array_merge(self::GENERAL_DOCUMENTS, ['KHS']),
            'BEASISWA-DISABILITAS' => array_merge(self::GENERAL_DOCUMENTS, ['KHS', 'SURAT-DISABILITAS']),
            'BEASISWA-NON-AKADEMIK' => array_merge(self::GENERAL_DOCUMENTS, ['KHS']),
        ];

        foreach ($expected as $code => $documents) {
            $category = KategoriBeasiswa::query()->where('kode', $code)->firstOrFail();

            $this->assertSame(
                $documents,
                $category->jenisDokumens()->pluck('kode')->all(),
                "Komposisi dokumen kategori {$code} tidak sesuai.",
            );
        }
    }

    public function test_sktm_and_sertifikat_prestasi_are_not_attached_to_any_category(): void
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);

        $this->assertSame(
            0,
            KategoriBeasiswaDokumen::query()
                ->whereHas('jenisDokumen', fn ($query) => $query->whereIn('kode', ['SKTM', 'SERTIFIKAT-PRESTASI']))
                ->count(),
        );
    }

    public function test_seeders_can_be_run_repeatedly_without_duplicating_documents(): void
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);

        $counts = [
            'document_types' => DocumentType::query()->count(),
            'jenis_dokumens' => JenisDokumen::query()->count(),
            'kategori_beasiswa_dokumens' => KategoriBeasiswaDokumen::query()->count(),
        ];

        $this->seed(MasterDataSeeder::class);
        $this->seed(BeasiswaMasterSeeder::class);

        $this->assertSame($counts, [
            'document_types' => DocumentType::query()->count(),
            'jenis_dokumens' => JenisDokumen::query()->count(),
            'kategori_beasiswa_dokumens' => KategoriBeasiswaDokumen::query()->count(),
        ]);
    }
}
