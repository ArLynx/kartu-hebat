<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\DocumentVerificationResult;
use App\Enums\SanggahanStatus;
use App\Enums\UserRole;
use App\Enums\VerificationDecision;
use App\Models\AgencyVerification;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\DataPribadi;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVerification;
use App\Models\Dokumen;
use App\Models\FormulirPendaftaran;
use App\Models\JenisDokumen;
use App\Models\Kabupaten;
use App\Models\KategoriBeasiswa;
use App\Models\MahasiswaProfile;
use App\Models\OrangTua;
use App\Models\Pendaftaran;
use App\Models\Pendidikan;
use App\Models\Periode;
use App\Models\Sanggahan;
use App\Models\Selection;
use App\Models\User;
use App\Models\VerificationLog;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Data demo untuk mencoba alur Masa Sanggah secara manual di lingkungan lokal.
 *
 * Seeder ini menyiapkan ulang empat skenario secara deterministik setiap kali
 * dijalankan: belum menyanggah, sanggahan menunggu keputusan, sanggahan
 * dikabulkan, dan hasil seleksi periode arsip yang sudah terbit.
 *
 * Hanya boleh dijalankan di local/testing.
 */
class MasaSanggahDemoSeeder extends Seeder
{
    /**
     * @var array<string, User>
     */
    private array $operators = [];

    private Periode $periodeAktif;

    private Village $village;

    public function run(): void
    {
        $this->guardEnvironment();

        $this->periodeAktif = $this->ensureActivePeriode();
        $this->village = $this->ensureRegions();
        $this->ensureDocumentDefinitions();

        $this->operators = $this->ensureOperators();

        $kategori = $this->ensureKategori($this->periodeAktif);

        $diajukan = $this->seedSkenarioDiajukan($kategori);
        $antrean = $this->seedSkenarioAntrean($kategori);
        $dikabulkan = $this->seedSkenarioDikabulkan($kategori);

        $periodeArsip = $this->ensureArchivedPeriode();
        $arsip = $this->seedSkenarioHasilArsip($periodeArsip);

        $this->command?->info('✓ MasaSanggahDemoSeeder selesai.');
        $this->command?->info('  Masa sanggah aktif: '.$this->periodeAktif->tanggal_mulai_sanggah?->format('d M Y').' – '.$this->periodeAktif->tanggal_selesai_sanggah?->format('d M Y'));
        $this->command?->info('  1. Belum menyanggah : '.$diajukan->nomor_pengajuan);
        $this->command?->info('  2. Sanggahan menunggu keputusan : '.$antrean->nomor_pengajuan);
        $this->command?->info('  3. Sanggahan dikabulkan, menunggu verifikasi ulang : '.$dikabulkan->nomor_pengajuan);
        $this->command?->info('  4. Hasil periode arsip yang sudah terbit : '.$arsip->nomor_pengajuan);
        $this->command?->info('  Semua akun memakai kata sandi: password');
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('MasaSanggahDemoSeeder hanya boleh dijalankan di lingkungan local/testing.');
        }
    }

    private function ensureActivePeriode(): Periode
    {
        $periode = Periode::latestAktif() ?? Periode::query()->latest('id')->first();

        if (! $periode) {
            throw new \RuntimeException('Tidak ada periode. Jalankan BeasiswaMasterSeeder terlebih dahulu.');
        }

        $today = today();

        $periode->forceFill([
            'status' => 'aktif',
            // Pastikan jendela pendaftaran mencakup hari ini agar halaman Submit
            // dan verifikasi dinas tetap dapat dibuka saat demo.
            'tanggal_mulai' => $periode->tanggal_mulai?->lessThanOrEqualTo($today)
                ? $periode->tanggal_mulai
                : $today->copy()->subMonth(),
            'tanggal_selesai' => $periode->tanggal_selesai?->greaterThanOrEqualTo($today)
                ? $periode->tanggal_selesai
                : $today->copy()->addMonth(),
            'tanggal_mulai_sanggah' => $today->copy()->subDay(),
            'tanggal_selesai_sanggah' => $today->copy()->addDays(6),
        ])->save();

        return $periode;
    }

    private function ensureRegions(): Village
    {
        $kabupaten = Kabupaten::query()->first()
            ?? Kabupaten::query()->create(['code' => '6212', 'name' => 'Murung Raya']);

        $kecamatan = $kabupaten->kecamatans()->first()
            ?? $kabupaten->kecamatans()->create(['code' => '621201', 'name' => 'Murung']);

        return $kecamatan->villages()->first()
            ?? $kecamatan->villages()->create([
                'code' => '6212011001',
                'name' => 'Beriwit',
                'kabupaten_id' => $kabupaten->id,
            ]);
    }

    private function ensureDocumentDefinitions(): void
    {
        $definitions = [
            'KTP' => ['Kartu Tanda Penduduk', 'pdf,jpg,jpeg,png'],
            'KK' => ['Kartu Keluarga', 'pdf,jpg,jpeg,png'],
            'KTM' => ['Kartu Tanda Mahasiswa', 'pdf,jpg,jpeg,png'],
            'SURAT-AKTIF' => ['Surat Keterangan Aktif Kuliah', 'pdf'],
            'KHS' => ['Kartu Hasil Studi (KHS) / Transkrip Nilai', 'pdf,jpg,jpeg,png'],
        ];

        foreach ($definitions as $code => [$name, $format]) {
            JenisDokumen::query()->updateOrCreate(
                ['kode' => $code],
                [
                    'nama' => $name,
                    'deskripsi' => "Dokumen persyaratan {$name}",
                    'format_file' => $format,
                    'maksimal_ukuran' => 2048,
                    'aktif' => true,
                ],
            );

            DocumentType::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => "Dokumen persyaratan {$name}",
                    'allowed_mimes' => explode(',', $format),
                    'max_size_kb' => 2048,
                    'is_required' => true,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<string, User>
     */
    private function ensureOperators(): array
    {
        $accounts = [
            UserRole::SUPERADMIN->value => ['Superadmin Kartu Hebat', 'superadmin@kartuhebat.test'],
            UserRole::OPERATOR_DUKCAPIL->value => ['Operator Dukcapil', 'dukcapil@kartuhebat.test'],
            UserRole::OPERATOR_PENDIDIKAN->value => ['Operator Dinas Pendidikan', 'pendidikan@kartuhebat.test'],
            UserRole::OPERATOR_BKPSDM->value => ['Operator BKPSDM', 'bkpsdm@kartuhebat.test'],
            UserRole::OPERATOR_KABUPATEN->value => ['Operator Kabupaten', 'kabupaten@kartuhebat.test'],
        ];

        $operators = [];

        foreach ($accounts as $role => [$name, $email]) {
            $operators[$role] = $this->ensureUser($name, $email, UserRole::from($role));
        }

        return $operators;
    }

    private function ensureUser(string $name, string $email, UserRole $role): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make('password'),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'role' => $role,
            'status' => 'active',
            'village_id' => $role === UserRole::MAHASISWA ? $this->village->id : null,
            'kecamatan_id' => $role === UserRole::MAHASISWA ? $this->village->kecamatan_id : null,
            'kabupaten_id' => $this->village->kabupaten_id,
            // Demo lokal: seluruh operator dianggap sudah melewati 2FA.
            'two_factor_confirmed_at' => $user->two_factor_confirmed_at ?? now(),
        ])->save();

        return $user;
    }

    private function ensureKategori(Periode $periode): KategoriBeasiswa
    {
        $kategori = KategoriBeasiswa::query()
            ->where('periode_id', $periode->id)
            ->where('application_type', ApplicationType::AKADEMIK->value)
            ->first();

        if ($kategori) {
            return $kategori;
        }

        $kategori = KategoriBeasiswa::query()->create([
            'periode_id' => $periode->id,
            'kode' => 'DEMO-AKADEMIK-'.$periode->id,
            'application_type' => ApplicationType::AKADEMIK,
            'nama' => 'Beasiswa Prestasi Akademik',
            'deskripsi' => 'Kategori demo untuk alur masa sanggah.',
            'kuota' => 250,
            'aktif' => true,
            'urutan' => 1,
        ]);

        $this->attachDocuments($kategori);

        return $kategori;
    }

    private function attachDocuments(KategoriBeasiswa $kategori): void
    {
        foreach (['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'KHS'] as $index => $code) {
            $jenis = JenisDokumen::query()->where('kode', $code)->first();

            if (! $jenis) {
                continue;
            }

            $kategori->dokumenPersyaratan()->updateOrCreate(
                ['jenis_dokumen_id' => $jenis->id],
                ['urutan' => $index + 1],
            );
        }
    }

    /**
     * Skenario 1: TMS dan belum menyanggah. Untuk mencoba pengajuan sanggahan.
     */
    private function seedSkenarioDiajukan(KategoriBeasiswa $kategori): Application
    {
        $student = $this->ensureUser(
            'Maya Anggraini',
            'sanggahan.ajukan@kartuhebat.test',
            UserRole::MAHASISWA,
        );

        $this->seedProfile(
            student: $student,
            nik: '6212019000000001',
            nim: '2026SANGG001',
            ipk: 3.72,
            semester: 6,
        );

        $application = $this->seedApplication(
            student: $student,
            periode: $this->periodeAktif,
            kategori: $kategori,
            nomor: 'KHM-SG-001',
            status: ApplicationStatus::TMS,
            tmsAgency: UserRole::OPERATOR_DUKCAPIL,
            tmsNotes: 'Foto KTP yang diunggah terlihat buram sehingga NIK tidak dapat dipastikan.',
            rejectedDocumentCode: 'KTP',
            msAgencies: [UserRole::OPERATOR_PENDIDIKAN, UserRole::OPERATOR_BKPSDM],
        );

        // Seeder demo bersifat deterministik: buang sisa hasil percobaan manual
        // agar skenario "belum menyanggah" selalu bisa dicoba ulang.
        $this->resetManualOutcome($application);

        return $application;
    }

    /**
     * Skenario 2: TMS dan sudah mengajukan sanggahan. Untuk mencoba antrean
     * dan keputusan operator kabupaten.
     */
    private function seedSkenarioAntrean(KategoriBeasiswa $kategori): Application
    {
        $student = $this->ensureUser(
            'Rendra Saputra',
            'sanggahan.antre@kartuhebat.test',
            UserRole::MAHASISWA,
        );

        $this->seedProfile(
            student: $student,
            nik: '6212019000000002',
            nim: '2026SANGG002',
            ipk: 3.41,
            semester: 4,
        );

        $application = $this->seedApplication(
            student: $student,
            periode: $this->periodeAktif,
            kategori: $kategori,
            nomor: 'KHM-SG-002',
            status: ApplicationStatus::TMS,
            tmsAgency: UserRole::OPERATOR_PENDIDIKAN,
            tmsNotes: 'Dokumen KHS terbaca sebagai milik program studi lain.',
            rejectedDocumentCode: 'KHS',
            msAgencies: [UserRole::OPERATOR_DUKCAPIL, UserRole::OPERATOR_BKPSDM],
        );

        $this->resetManualOutcome($application);

        $sanggahan = Sanggahan::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'periode_id' => $this->periodeAktif->id,
                'submitted_by' => $student->id,
                'status' => SanggahanStatus::DIAJUKAN,
                'uraian' => 'KHS yang saya unggah memang atas nama saya dan program studi Teknik Informatika. '
                    .'Mohon diperiksa kembali halaman identitas pada berkas tersebut.',
                'catatan_keputusan' => null,
                'decided_by' => null,
                'decided_at' => null,
                'created_at' => now()->subDay(),
            ],
        );

        $this->seedSanggahanLog($application, $sanggahan, $student);

        return $application;
    }

    /**
     * Skenario 3: sanggahan sudah dikabulkan. Application kembali ke
     * VERIFIKASI_DINAS, hanya Dukcapil yang dibuka ulang, dan menunggu
     * penilaian ulang.
     */
    private function seedSkenarioDikabulkan(KategoriBeasiswa $kategori): Application
    {
        $student = $this->ensureUser(
            'Nadia Puspita',
            'sanggahan.dikabulkan@kartuhebat.test',
            UserRole::MAHASISWA,
        );

        $this->seedProfile(
            student: $student,
            nik: '6212019000000003',
            nim: '2026SANGG003',
            ipk: 3.95,
            semester: 2,
        );

        $application = $this->seedApplication(
            student: $student,
            periode: $this->periodeAktif,
            kategori: $kategori,
            nomor: 'KHM-SG-003',
            status: ApplicationStatus::VERIFIKASI_DINAS,
            tmsAgency: UserRole::OPERATOR_DUKCAPIL,
            tmsNotes: 'NIK pada KTP berbeda satu digit dengan data pribadi.',
            msAgencies: [UserRole::OPERATOR_PENDIDIKAN, UserRole::OPERATOR_BKPSDM],
            annulledAgency: UserRole::OPERATOR_DUKCAPIL,
            lockedAt: now()->subHours(4),
        );

        $this->resetManualOutcome($application);

        $sanggahan = Sanggahan::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'periode_id' => $this->periodeAktif->id,
                'submitted_by' => $student->id,
                'status' => SanggahanStatus::DIKABULKAN,
                'uraian' => 'NIK pada KTP sudah benar. Kesalahan terjadi saat verifikator mengetik ulang nomor '
                    .'sehingga satu digit tertukar. Mohon dinilai ulang.',
                'catatan_keputusan' => 'NIK pada KTP sudah sesuai dengan data pribadi. Keputusan TMS Dukcapil dianulir dan dinas dapat menilai ulang.',
                'decided_by' => $this->operators[UserRole::OPERATOR_KABUPATEN->value]->id,
                'decided_at' => now()->subHours(4),
                'created_at' => now()->subDay(),
            ],
        );

        $this->seedSanggahanLog($application, $sanggahan, $student);
        $this->seedGrantedLog($application, $sanggahan);

        return $application;
    }

    /**
     * Skenario 4: hasil seleksi periode lampau yang sudah terbit. Dipakai untuk
     * mencoba pencarian hasil lintas periode dan publikasi perbaikan.
     */
    private function seedSkenarioHasilArsip(Periode $periode): Application
    {
        $kategori = KategoriBeasiswa::query()
            ->where('periode_id', $periode->id)
            ->where('application_type', ApplicationType::AKADEMIK->value)
            ->first();

        if (! $kategori) {
            $kategori = KategoriBeasiswa::query()->create([
                'periode_id' => $periode->id,
                'kode' => 'DEMO-AKADEMIK-ARSIP-'.$periode->id,
                'application_type' => ApplicationType::AKADEMIK,
                'nama' => 'Beasiswa Prestasi Akademik (Arsip)',
                'deskripsi' => 'Kategori demo arsip.',
                'kuota' => 250,
                'aktif' => true,
                'urutan' => 1,
            ]);

            $this->attachDocuments($kategori);
        }

        $student = $this->ensureUser(
            'Bagas Setiawan',
            'sanggahan.arsip@kartuhebat.test',
            UserRole::MAHASISWA,
        );

        $this->seedProfile(
            student: $student,
            nik: '6212019000000004',
            nim: '2025SANGG004',
            ipk: 3.88,
            semester: 4,
        );

        $application = $this->seedApplication(
            student: $student,
            periode: $periode,
            kategori: $kategori,
            nomor: 'KHM-ARSIP-001',
            status: ApplicationStatus::DITERIMA,
            tmsAgency: null,
            tmsNotes: null,
            lockedAt: now()->subMonths(10),
        );

        Selection::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'final_score' => 91.25,
                'rank' => 1,
                'manual_decision' => ApplicationStatus::DITERIMA->value,
                'notes' => 'Memenuhi seluruh persyaratan akademik.',
                'decided_by' => $this->operators[UserRole::OPERATOR_KABUPATEN->value]->id,
                'decided_at' => now()->subMonths(10),
                'published_at' => now()->subMonths(10),
            ],
        );

        $this->ensureAnnouncement($periode);

        return $application;
    }

    /**
     * @param  array<int, UserRole>  $msAgencies
     */
    private function seedApplication(
        User $student,
        Periode $periode,
        KategoriBeasiswa $kategori,
        string $nomor,
        ApplicationStatus $status,
        ?UserRole $tmsAgency,
        ?string $tmsNotes,
        array $msAgencies = [],
        ?UserRole $annulledAgency = null,
        ?string $rejectedDocumentCode = null,
        ?\DateTimeInterface $lockedAt = null,
    ): Application {
        $pendaftaran = Pendaftaran::query()->updateOrCreate(
            ['user_id' => $student->id, 'periode_id' => $periode->id],
            [
                'kategori_beasiswa_id' => $kategori->id,
                'nomor_pendaftaran' => $nomor,
                'status' => 'verification',
                'submitted_at' => now()->subDays(30),
                'review_dikonfirmasi_at' => now()->subDays(30),
            ],
        );

        $annulled = $annulledAgency !== null;

        $application = Application::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'mahasiswa_id' => $student->id,
                'nomor_pengajuan' => $nomor,
                'periode' => $periode->nama ?: (string) $periode->tahun,
                'application_type' => ApplicationType::AKADEMIK,
                'status' => $status,
                'catatan' => $annulled ? null : $tmsNotes,
                'submitted_at' => now()->subDays(30),
                'locked_at' => $lockedAt ?? now()->subDays(20),
            ],
        );

        $this->seedPendaftaranDetail($pendaftaran, $student);
        $this->seedDocuments($application, $student);

        if ($application->verificationLogs()->where('action', 'submitted')->doesntExist()) {
            VerificationLog::query()->create([
                'application_id' => $application->id,
                'actor_id' => $student->id,
                'from_status' => ApplicationStatus::DRAFT->value,
                'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
                'action' => 'submitted',
                'notes' => 'Pengajuan dikirim oleh mahasiswa.',
                'created_at' => now()->subDays(30),
            ]);
        }

        $round = max(1, $application->verificationLogs()->where('action', 'submitted')->count());

        if ($status === ApplicationStatus::DITERIMA && $tmsAgency === null && $msAgencies === []) {
            $msAgencies = [UserRole::OPERATOR_DUKCAPIL, UserRole::OPERATOR_PENDIDIKAN, UserRole::OPERATOR_BKPSDM];
        }

        // Sisa percobaan manual sebelumnya bisa meninggalkan keputusan dinas yang
        // tidak lagi relevan dengan skenario ini. Bersihkan agar keadaan awal
        // selalu sama setiap kali seeder dijalankan.
        $intendedAgencies = collect($msAgencies)
            ->push($tmsAgency)
            ->filter()
            ->reject(fn (UserRole $role): bool => $role === $annulledAgency)
            ->map(fn (UserRole $role): string => $role->agencyCode())
            ->unique()
            ->values()
            ->all();

        if ($intendedAgencies !== []) {
            AgencyVerification::query()
                ->where('application_id', $application->id)
                ->whereNotIn('agency', $intendedAgencies)
                ->delete();

            DocumentVerification::query()
                ->where('application_id', $application->id)
                ->where('round', $round)
                ->whereNotIn('stage', $intendedAgencies)
                ->delete();
        }

        foreach ($msAgencies as $msAgency) {
            $this->seedAgencyDecision(
                application: $application,
                agency: $msAgency,
                decision: VerificationDecision::MS,
                notes: 'Berkas sesuai dengan data instansi.',
                round: $round,
            );
        }

        // Keputusan TMS yang sudah dianulir lewat sanggahan tidak boleh
        // disisakan: pengabulan menghapus baris agency_verifications dan
        // document_verifications dinas tersebut pada putaran berjalan.
        if ($tmsAgency !== null && $tmsAgency !== $annulledAgency) {
            $this->seedAgencyDecision(
                application: $application,
                agency: $tmsAgency,
                decision: VerificationDecision::TMS,
                notes: $tmsNotes,
                round: $round,
                rejectedDocumentCode: $rejectedDocumentCode,
            );
        }

        return $application;
    }

    private function seedPendaftaranDetail(Pendaftaran $pendaftaran, User $student): void
    {
        $profile = $student->profile()->first();

        DataPribadi::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'village_id' => $this->village->id,
                'nik' => $profile?->nik ?? '6212019000009999',
                'nomor_kk' => '6212019000009998',
                'nomor_rekening' => '62120000099'.$pendaftaran->id,
                'nama_lengkap' => $student->name,
                'tempat_lahir' => 'Puruk Cahu',
                'tanggal_lahir' => '2004-05-12',
                'jenis_kelamin' => 'L',
                'agama' => 'Islam',
                'alamat' => 'Jl. Demo Masa Sanggah No. '.$pendaftaran->id,
                'provinsi' => 'Kalimantan Tengah',
                'kabupaten' => 'Murung Raya',
                'kecamatan' => 'Murung',
                'desa' => 'Beriwit',
                'kode_pos' => '73911',
                'no_hp' => '0812500099'.$pendaftaran->id,
            ],
        );

        Pendidikan::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nim' => $profile?->nim ?? 'DEMO'.$pendaftaran->id,
                'universitas' => 'Universitas Palangka Raya',
                'status_perguruan_tinggi' => 'negeri',
                'akreditasi_perguruan_tinggi' => 'Baik Sekali',
                'alamat_perguruan_tinggi' => 'Jl. Yos Sudarso, Palangka Raya',
                'fakultas' => 'Fakultas Teknik',
                'jurusan' => 'Teknik Informatika',
                'program_studi' => 'Teknik Informatika',
                'jenjang' => 'S1',
                'semester' => $profile?->semester ?? 4,
                'ipk' => $profile?->ipk ?? 3.5,
                'nominal_ukt' => 3500000,
                'tahun_masuk' => 2022,
            ],
        );

        OrangTua::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            [
                'nama_ayah' => 'Ayah '.$student->name,
                'status_ayah' => 'hidup',
                'nik_ayah' => '621201010170000'.$pendaftaran->id,
                'pekerjaan_ayah' => 'Petani',
                'nama_ibu' => 'Ibu '.$student->name,
                'status_ibu' => 'hidup',
                'nik_ibu' => '621201410172000'.$pendaftaran->id,
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'memiliki_wali' => false,
            ],
        );

        $surat = "pendaftarans/{$pendaftaran->id}/formulir/surat_permohonan.pdf";
        $pakta = "pendaftarans/{$pendaftaran->id}/formulir/pakta_integritas.pdf";
        $this->putValidPdf($surat, 'Surat Permohonan '.$student->name);
        $this->putValidPdf($pakta, 'Pakta Integritas '.$student->name);

        FormulirPendaftaran::query()->updateOrCreate(
            ['pendaftaran_id' => $pendaftaran->id],
            ['jenis_form' => 'A', 'surat_permohonan' => $surat, 'pakta_integritas' => $pakta],
        );
    }

    private function seedProfile(
        User $student,
        string $nik,
        string $nim,
        float $ipk,
        int $semester,
    ): void {
        MahasiswaProfile::query()->updateOrCreate(
            ['user_id' => $student->id],
            [
                'nik' => $nik,
                'nim' => $nim,
                'phone' => '0812500012'.str_pad((string) $student->id, 2, '0', STR_PAD_LEFT),
                'universitas' => 'Universitas Palangka Raya',
                'program_studi' => 'Teknik Informatika',
                'semester' => $semester,
                'ipk' => $ipk,
                'alamat' => 'Jl. Demo Masa Sanggah, Puruk Cahu',
                'village_id' => $this->village->id,
                'status_kependudukan' => 'sesuai',
                'penghasilan_keluarga' => 2500000,
                'jumlah_tanggungan' => 3,
            ],
        );
    }

    private function seedDocuments(Application $application, User $student): void
    {
        foreach (['KTP', 'KK', 'KTM', 'SURAT-AKTIF', 'KHS'] as $code) {
            $type = DocumentType::query()->where('code', $code)->first();
            $jenis = JenisDokumen::query()->where('kode', $code)->first();

            if (! $type) {
                continue;
            }

            $path = "demo/sanggahan/{$application->nomor_pengajuan}-{$code}.pdf";
            $this->putValidPdf($path, "Dokumen {$code} {$application->nomor_pengajuan}");

            Document::query()->updateOrCreate(
                ['application_id' => $application->id, 'document_type_id' => $type->id],
                [
                    'uploaded_by' => $student->id,
                    'path' => $path,
                    'original_name' => strtolower($code).'-'.$application->nomor_pengajuan.'.pdf',
                    'mime_type' => 'application/pdf',
                    'size' => 10240,
                    'checksum' => md5($path),
                    'version' => 1,
                ],
            );

            if ($jenis && $application->pendaftaran_id) {
                Dokumen::query()->updateOrCreate(
                    ['pendaftaran_id' => $application->pendaftaran_id, 'jenis_dokumen_id' => $jenis->id],
                    [
                        'file_path' => $path,
                        'nama_file_asli' => strtolower($code).'-'.$application->nomor_pengajuan.'.pdf',
                        'mime_type' => 'application/pdf',
                        'ukuran_file' => 10240,
                        'status' => 'uploaded',
                    ],
                );
            }
        }
    }

    private function seedAgencyDecision(
        Application $application,
        UserRole $agency,
        VerificationDecision $decision,
        ?string $notes,
        int $round,
        ?string $rejectedDocumentCode = null,
    ): void {
        $operator = $this->operators[$agency->value] ?? null;

        if (! $operator) {
            return;
        }

        AgencyVerification::query()->updateOrCreate(
            ['application_id' => $application->id, 'agency' => $agency->agencyCode()],
            [
                'verifier_id' => $operator->id,
                'decision' => $decision,
                'score' => $decision === VerificationDecision::MS ? 85.00 : null,
                'notes' => $notes,
                'verified_at' => now()->subDays(20),
            ],
        );

        $this->seedDocumentVerifications(
            application: $application,
            agency: $agency,
            round: $round,
            rejectedDocumentCode: $decision === VerificationDecision::TMS ? $rejectedDocumentCode : null,
            notes: $notes,
        );

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $operator->id,
            'from_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'agency_verification_'.$decision->value,
            'notes' => null,
            'metadata' => ['agency' => $agency->agencyCode()],
            'created_at' => now()->subDays(20),
        ]);
    }

    private function seedDocumentVerifications(
        Application $application,
        UserRole $agency,
        int $round,
        ?string $rejectedDocumentCode = null,
        ?string $notes = null,
    ): void {
        $operator = $this->operators[$agency->value] ?? null;

        if (! $operator) {
            return;
        }

        $documents = $application->documents()->with('type')->get();

        foreach ($documents as $document) {
            $isRejected = $rejectedDocumentCode !== null
                && $document->type?->code === $rejectedDocumentCode;

            DocumentVerification::query()->updateOrCreate(
                [
                    'document_id' => $document->id,
                    'stage' => $agency->agencyCode(),
                    'round' => $round,
                ],
                [
                    'application_id' => $application->id,
                    'verifier_id' => $operator->id,
                    'result' => $isRejected
                        ? DocumentVerificationResult::TIDAK_MEMENUHI
                        : DocumentVerificationResult::MEMENUHI,
                    'notes' => $isRejected ? ($notes ?? 'Dokumen tidak dapat dibaca dengan jelas.') : null,
                    'verified_at' => now()->subDays(20),
                ],
            );
        }
    }

    /**
     * Hapus jejak percobaan manual (sanggahan, keputusan seleksi) agar
     * skenario kembali ke keadaan awal yang diharapkan.
     */
    private function resetManualOutcome(Application $application): void
    {
        Sanggahan::query()->where('application_id', $application->id)->delete();
        Selection::query()->where('application_id', $application->id)->delete();

        VerificationLog::query()
            ->where('application_id', $application->id)
            ->whereIn('action', [
                'sanggahan_submitted',
                'sanggahan_granted',
                'sanggahan_rejected',
                'selection_decision_recorded',
                'selection_result_published',
            ])
            ->delete();
    }

    private function seedSanggahanLog(Application $application, Sanggahan $sanggahan, User $student): void
    {
        if (VerificationLog::query()
            ->where('application_id', $application->id)
            ->where('action', 'sanggahan_submitted')
            ->exists()) {
            return;
        }

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $student->id,
            'from_status' => ApplicationStatus::TMS->value,
            'to_status' => ApplicationStatus::TMS->value,
            'action' => 'sanggahan_submitted',
            'notes' => 'Sanggahan diajukan oleh mahasiswa.',
            'metadata' => ['sanggahan_id' => $sanggahan->id],
            'created_at' => now()->subDay(),
        ]);
    }

    private function seedGrantedLog(Application $application, Sanggahan $sanggahan): void
    {
        $metadata = [
            'sanggahan_id' => $sanggahan->id,
            'annulled_agencies' => [UserRole::OPERATOR_DUKCAPIL->agencyCode()],
            'annulled_verifications' => [
                [
                    'agency' => UserRole::OPERATOR_DUKCAPIL->agencyCode(),
                    'agency_label' => 'Dukcapil',
                    'decision' => 'TMS',
                    'notes' => 'NIK pada KTP berbeda satu digit dengan data pribadi.',
                    'verifier_id' => $this->operators[UserRole::OPERATOR_DUKCAPIL->value]->id,
                ],
            ],
        ];

        $existing = VerificationLog::query()
            ->where('application_id', $application->id)
            ->where('action', 'sanggahan_granted')
            ->first();

        if ($existing) {
            $existing->update(['metadata' => $metadata]);
            return;
        }

        VerificationLog::query()->create([
            'application_id' => $application->id,
            'actor_id' => $this->operators[UserRole::OPERATOR_KABUPATEN->value]->id,
            'from_status' => ApplicationStatus::TMS->value,
            'to_status' => ApplicationStatus::VERIFIKASI_DINAS->value,
            'action' => 'sanggahan_granted',
            'notes' => $sanggahan->catatan_keputusan,
            'metadata' => $metadata,
            'created_at' => now()->subHours(4),
        ]);
    }

    private function ensureArchivedPeriode(): Periode
    {
        $periode = Periode::query()
            ->where('status', 'ditutup')
            ->where('id', '!=', $this->periodeAktif->id)
            ->latest('id')
            ->first();

        if ($periode) {
            return $periode;
        }

        $tahun = (int) ($this->periodeAktif->tahun - 1);
        $nama = $tahun.'/'.($tahun + 1).' Ganjil';

        return Periode::query()->updateOrCreate(
            ['nama' => $nama],
            [
                'tahun' => $tahun,
                'tanggal_mulai' => $tahun.'-07-01',
                'tanggal_selesai' => $tahun.'-12-31',
                'tanggal_mulai_sanggah' => $tahun.'-11-10',
                'tanggal_selesai_sanggah' => $tahun.'-11-12',
                'status' => 'ditutup',
            ],
        );
    }

    private function ensureAnnouncement(Periode $periode): void
    {
        $slug = 'hasil-seleksi-'.str()->slug($periode->nama ?: (string) $periode->tahun);

        if (Announcement::query()->where('slug', $slug)->exists()) {
            return;
        }

        $path = 'announcements/sk-penetapan-'.str()->slug($periode->nama ?: (string) $periode->tahun).'-arsip.pdf';
        $this->putValidPdf($path, 'SK Penetapan Arsip '.$periode->nama, 'public');

        Announcement::query()->create([
            'slug' => $slug,
            'title' => 'Pengumuman Hasil Seleksi Kartu Hebat Mahasiswa Periode '.$periode->nama,
            'type' => 'hasil',
            'excerpt' => 'Hasil resmi seleksi Kartu Hebat Mahasiswa periode '.$periode->nama.' telah dipublikasikan.',
            'body' => 'Pengumuman resmi dan Surat Keputusan (SK) penetapan penerima beasiswa dapat diunduh pada tautan berkas terlampir.',
            'attachment_path' => $path,
            'is_active' => true,
            'published_at' => now()->subMonths(10),
            'created_by' => $this->operators[UserRole::OPERATOR_KABUPATEN->value]->id,
        ]);
    }

    private function putValidPdf(string $path, string $title, string $disk = 'local'): void
    {
        if (Storage::disk($disk)->exists($path)) {
            return;
        }

        $content = "%PDF-1.4\n"
            ."1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\n"
            ."xref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000053 00000 n \n0000000102 00000 n \n"
            ."trailer<</Size 4/Root 1 0 R>>\nstartxref\n178\n%%EOF\n"
            ."% {$title}\n";

        Storage::disk($disk)->put($path, $content);
    }
}
