<?php

namespace Tests\Feature;

use App\Enums\PosisiOrganisasi;
use App\Models\Prestasi;
use App\Services\Scoring\NonAcademicTierProfile;
use App\Services\Scoring\NonAcademicTierResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NonAcademicTierResolverTest extends TestCase
{
    public function test_pengurus_inti_ranks_above_juara_lomba(): void
    {
        $resolver = new NonAcademicTierResolver;

        $ketua = $this->profile($resolver, [
            'kategori_prestasi' => 'organisasi',
            'posisi_organisasi' => 'ketua',
            'tingkat_organisasi' => 'fakultas',
            'is_pengurus_inti_ormawa' => true,
        ]);
        $juaraInternasional = $this->profile($resolver, [
            'kategori_prestasi' => 'lomba',
            'tingkat' => 'internasional',
            'peringkat' => 'juara 1',
        ]);

        $this->assertSame(NonAcademicTierResolver::TIER_PENGURUS_INTI, $ketua->tier);
        $this->assertSame(NonAcademicTierResolver::TIER_JUARA, $juaraInternasional->tier);
        $this->assertLessThan(0, $ketua->compareTo($juaraInternasional));
    }

    public function test_juara_ranks_above_anggota_pengurus(): void
    {
        $resolver = new NonAcademicTierResolver;

        $juaraKabupaten = $this->profile($resolver, [
            'kategori_prestasi' => 'lomba',
            'tingkat' => 'kabupaten',
            'peringkat' => 'juara 3',
        ]);
        $anggota = $this->profile($resolver, [
            'kategori_prestasi' => 'organisasi',
            'posisi_organisasi' => 'anggota',
            'tingkat_organisasi' => 'universitas',
            'is_pengurus_inti_ormawa' => false,
        ]);

        $this->assertSame(NonAcademicTierResolver::TIER_JUARA, $juaraKabupaten->tier);
        $this->assertSame(NonAcademicTierResolver::TIER_ANGGOTA_ORGANISASI, $anggota->tier);
        $this->assertLessThan(0, $juaraKabupaten->compareTo($anggota));
    }

    public function test_anggota_pengurus_ranks_above_peserta_lomba(): void
    {
        $resolver = new NonAcademicTierResolver;

        $anggota = $this->profile($resolver, [
            'kategori_prestasi' => 'organisasi',
            'posisi_organisasi' => 'anggota',
            'tingkat_organisasi' => 'fakultas',
            'is_pengurus_inti_ormawa' => false,
        ]);
        $peserta = $this->profile($resolver, [
            'kategori_prestasi' => 'lomba',
            'tingkat' => 'internasional',
            'peringkat' => 'peserta',
        ]);

        $this->assertSame(NonAcademicTierResolver::TIER_PESERTA, $peserta->tier);
        $this->assertLessThan(0, $anggota->compareTo($peserta));
    }

    public function test_jabatan_diurutkan_sebelum_tingkat_organisasi(): void
    {
        $resolver = new NonAcademicTierResolver;

        $ketuaFakultas = $this->profile($resolver, [
            'kategori_prestasi' => 'organisasi',
            'posisi_organisasi' => 'ketua',
            'tingkat_organisasi' => 'fakultas',
            'is_pengurus_inti_ormawa' => true,
        ]);
        $koordinatorUniversitas = $this->profile($resolver, [
            'kategori_prestasi' => 'organisasi',
            'posisi_organisasi' => 'koordinator',
            'tingkat_organisasi' => 'universitas',
            'is_pengurus_inti_ormawa' => true,
        ]);

        $this->assertLessThan(0, $ketuaFakultas->compareTo($koordinatorUniversitas));
    }

    public function test_tingkat_organisasi_universitas_mengalahkan_fakultas_pada_posisi_sama(): void
    {
        $resolver = new NonAcademicTierResolver;

        $universitas = $this->profile($resolver, [
            'kategori_prestasi' => 'organisasi',
            'posisi_organisasi' => 'sekretaris',
            'tingkat_organisasi' => 'universitas',
            'is_pengurus_inti_ormawa' => true,
        ]);
        $fakultas = $this->profile($resolver, [
            'kategori_prestasi' => 'organisasi',
            'posisi_organisasi' => 'sekretaris',
            'tingkat_organisasi' => 'fakultas',
            'is_pengurus_inti_ormawa' => true,
        ]);

        $this->assertLessThan(0, $universitas->compareTo($fakultas));
    }

    public function test_urutan_tingkat_lomba_sesuai_perbup(): void
    {
        $this->assertSame(1, NonAcademicTierResolver::tingkatLombaRank('internasional'));
        $this->assertSame(2, NonAcademicTierResolver::tingkatLombaRank('nasional'));
        $this->assertSame(3, NonAcademicTierResolver::tingkatLombaRank('provinsi'));
        $this->assertSame(4, NonAcademicTierResolver::tingkatLombaRank('kampus'));
        $this->assertSame(5, NonAcademicTierResolver::tingkatLombaRank('kabupaten'));
    }

    public function test_harapan_dan_favorit_masih_tier_juara_tetapi_di_bawah_juara_3(): void
    {
        $resolver = new NonAcademicTierResolver;

        $juara3 = $this->profile($resolver, ['kategori_prestasi' => 'lomba', 'tingkat' => 'nasional', 'peringkat' => 'juara 3']);
        $harapan = $this->profile($resolver, ['kategori_prestasi' => 'lomba', 'tingkat' => 'nasional', 'peringkat' => 'harapan']);
        $favorit = $this->profile($resolver, ['kategori_prestasi' => 'lomba', 'tingkat' => 'nasional', 'peringkat' => 'favorit']);

        $this->assertSame(NonAcademicTierResolver::TIER_JUARA, $harapan->tier);
        $this->assertSame(NonAcademicTierResolver::TIER_JUARA, $favorit->tier);
        $this->assertLessThan(0, $juara3->compareTo($harapan));
        $this->assertLessThan(0, $harapan->compareTo($favorit));
    }

    /**
     * @return array<string, array{string, PosisiOrganisasi}>
     */
    public static function jabatanProvider(): array
    {
        return [
            'ketua umum' => ['Ketua Umum BEM', PosisiOrganisasi::KETUA],
            'presiden mahasiswa' => ['Presiden Mahasiswa', PosisiOrganisasi::KETUA],
            'wakil ketua' => ['Wakil Ketua BEM', PosisiOrganisasi::WAKIL_KETUA],
            'wakil sekretaris' => ['Wakil Sekretaris', PosisiOrganisasi::ANGGOTA],
            'sekretaris' => ['Sekretaris Jenderal', PosisiOrganisasi::SEKRETARIS],
            'bendahara' => ['Bendahara Umum', PosisiOrganisasi::BENDAHARA],
            'koordinator' => ['Koordinator Bidang Advokasi', PosisiOrganisasi::KOORDINATOR],
            'kepala departemen' => ['Kepala Departemen Kajian', PosisiOrganisasi::KOORDINATOR],
            'staf' => ['Staf Divisi Media', PosisiOrganisasi::ANGGOTA],
            'kosong' => ['', PosisiOrganisasi::ANGGOTA],
        ];
    }

    #[DataProvider('jabatanProvider')]
    public function test_pemetaan_jabatan_bebas_ke_posisi(string $jabatan, PosisiOrganisasi $expected): void
    {
        $this->assertSame($expected, NonAcademicTierResolver::posisiFromJabatan($jabatan));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function profile(NonAcademicTierResolver $resolver, array $attributes): NonAcademicTierProfile
    {
        $prestasi = new Prestasi(array_merge([
            'jenis' => 'non_akademik',
            'nama_prestasi' => 'Uji',
            'penyelenggara' => 'Universitas Uji',
            'tahun' => 2025,
        ], $attributes));

        return $resolver->profileFor($prestasi);
    }
}
