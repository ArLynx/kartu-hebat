# Panduan Menambahkan Jalur Seleksi Baru

Dokumen ini menjelaskan langkah-langkah untuk menambah **jalur (track) baru** ke Sistem Kartu Hebat Mahasiswa, contoh: `DISABILITAS`.

> Setelah refactor Strategy Pattern (lihat `app/Services/Scoring/`), tambah jalur baru **tidak perlu** mengubah logika `match()` di service utama. Cukup daftarkan enum case + buat class strategi.

## Daftar Cek

### A. Kode Wajib

- [ ] **`app/Enums/ApplicationType.php`** — tambah `case` baru + isi `label()`, `description()`, `scoringStrategyClass()`, dan `isAffirmation()` jika perlu.
- [ ] **`app/Services/Scoring/<Nama>Scoring.php`** — buat class baru yang mengimplementasikan `ScoringStrategy`. Wajib override:
   - `type(): ApplicationType`
   - `values(?MahasiswaProfile $profile, Application $application): array<string, array{raw: float|int|string, normalized: float}>`
- [ ] **(Opsional) `config/kartu_hebat.php`** — tambah bobot, kuota, dan parameter khusus jalur di `quotas` dan `scoring`.
- [ ] **(Wajib jika butuh data tambahan)** Tambahkan field di `mahasiswa_profiles` ATAU gunakan model relasi (mis. `Prestasi` lewat `pendaftaran`).

### B. Database

- [ ] **Migration profil** — jika butuh field baru (mis. `disability_type`), tambah kolom di tabel `mahasiswa_profiles`. Jangan lupa isi `$fillable`/casts di model.
- [ ] **Migration kriteria** — tambah baris di tabel `criteria` dengan `application_type = 'NAMA_JALUR'`. Bisa via migration atau seeder.
- [ ] **(Opsional) Lookup table** — jika ada banyak nilai tetap (mis. daftar jenis disabilitas), gunakan tabel referensi (`disability_metadata`) agar UI bisa `<select>` dinamis.

### C. Seeder

- [ ] **Kategori** — tambah entry di `BeasiswaMasterSeeder::$categories` dengan `application_type` baru.
- [ ] **Dokumen** — jika ada dokumen wajib khusus, tambah di `documentDefinitions` dan masukkan ke `documents` kategori.
- [ ] **Kriteria** — buat seeder khusus (mis. `DisabilityTrackSeeder`) untuk insert kriteria + lookup metadata.

### D. UI / Form

- [ ] **Formulir mahasiswa** — tambah field input di Blade (tahap Data Pribadi / Pendidikan).
- [ ] **Validasi** — tambahkan rule di `Request` class terkait (wajib/optional, enum, range).
- [ ] **View daftar jalur** — `resources/views/mahasiswa/pendaftaran/create.blade.php` otomatis menampilkan kategori baru (via `KategoriBeasiswa::aktif`).
- [ ] **View seleksi & rekonsiliasi operator** — tambahkan filter/tab untuk jalur baru.

### E. Verifikasi Workflow

- [ ] **Kebijakan desil** — jika jalur baru **tidak** butuh verifikasi desil dari operator Sosial/Pendidikan, update logika di `AgencyVerification` / `ApplicationWorkflowService` untuk skip desil.
- [ ] **Dokumen wajib** — pastikan `document_types.application_type` sesuai untuk jalur baru (atau `null` jika berlaku untuk semua jalur).

### F. Test

- [ ] **Unit test** untuk strategi baru — minimal:
  - Kombinasi input valid menghasilkan skor sesuai rumus.
  - Field wajib kosong memunculkan `ValidationException`.
  - Ranking dipisah per jalur (jalur baru tidak tercampur dengan jalur lain).
- [ ] **Integration test** untuk submit + verifikasi + seleksi penuh.

### G. Deployment

- [ ] Jalankan `php artisan test` — semua test hijau.
- [ ] Jalankan `php artisan migrate --seed` pada environment staging.
- [ ] Tambah env baru di `.env.example` (mis. `KHM_QUOTA_DISABILITAS`, `KHM_DISABILITY_WEIGHT_*`).
- [ ] Update `docs/IMPLEMENTATION_MATRIX.md` — tambah baris untuk jalur baru.

## Contoh: `DISABILITAS`

Berkas yang disentuh untuk jalur ini:

```
app/Enums/ApplicationType.php                                   (+1 case)
app/Services/Scoring/ScoringStrategy.php                       (interface)
app/Services/Scoring/AcademicScoring.php                       (existing, refactor)
app/Services/Scoring/DesilScoring.php                          (existing, refactor)
app/Services/Scoring/DisabilityScoring.php                     (BARU)
app/Services/ScoringStrategyResolver.php                       (BARU)
app/Services/SelectionScoringService.php                       (refactor pakai resolver)
app/Models/MahasiswaProfile.php                                (+ disability constants)
config/kartu_hebat.php                                         (+ quotas + scoring)
database/migrations/2026_08_04_000000_add_disability_fields...  (BARU)
database/seeders/BeasiswaMasterSeeder.php                      (+ kategori & dokumen)
database/seeders/DisabilityTrackSeeder.php                     (BARU)
database/seeders/DatabaseSeeder.php                            (+ panggil DisabilityTrackSeeder)
tests/Feature/DisabilityScoringServiceTest.php                  (BARU)
```

## Contoh: `NON_AKADEMIK` (Prestasi)

Berkas yang disentuh untuk jalur afirmasi prestasi (MTQ, olahraga, seni, wirausaha, dsb.):

```
app/Enums/ApplicationType.php                                   (+1 case NON_AKADEMIK)
app/Services/Scoring/PrestasiScoring.php                       (BARU)
config/kartu_hebat.php                                         (+ KHM_QUOTA_NON_AKADEMIK=30,
                                                                  non_academic_weights,
                                                                  non_academic_rubric)
app/Services/ApplicationWorkflowService.php                     (+ cek minimal 1 prestasi saat submit)
database/seeders/MasterDataSeeder.php                          (+ SERTIFIKAT-PRESTASI)
database/seeders/BeasiswaMasterSeeder.php                      (+ kategori BEASISWA-NON-AKADEMIK,
                                                                  icon=trophy, warna=#7C3AED)
database/seeders/PrestasiTrackSeeder.php                       (BARU)
database/seeders/DatabaseSeeder.php                            (+ panggil PrestasiTrackSeeder)
app/Http/Controllers/Operator/ApplicationController.php        (+ eager load pendaftaran.prestasis di show)
resources/views/operator/applications/show.blade.php            (+ section daftar prestasi)
tests/Feature/PrestasiScoringServiceTest.php                    (BARU, 5 test)
```

**Catatan penting untuk jalur berbasis relasi (bukan field profil):**

- Data prestasi ada di model `Prestasi` (terkoneksi ke `Pendaftaran`, bukan langsung ke `MahasiswaProfile`).
- Strategy class menerima `Application $application` lewat signature `values(?MahasiswaProfile $profile, Application $application)`, sehingga bisa akses `$application->pendaftaran?->prestasis`.
- Saat `submit()`, validasi tambahan wajib di `ApplicationWorkflowService::submit()` bahwa minimal 1 prestasi sudah terinput.
- Form input prestasi sudah tersedia via `Mahasiswa\PrestasiController` — tidak perlu Form Request baru.

## Prinsip Desain

1. **Tambah jalur = tambah class + 1 baris enum** — tidak boleh ubah `match()` di service utama.
2. **Rumus di kode, bobot di config** — jika hanya bobot yang berubah, cukup env/config; tidak perlu ubah kode.
3. **Field profil nullable by default** — kolom baru di `mahasiswa_profiles` selalu nullable agar tidak破坏 data lama.
4. **Kriteria via DB** — rumus yang ditulis di `Criteria` (kode) + nilai di strategi, sehingga audit & pengubahan lebih transparan.
5. **Validasi eksplisit** — setiap strategi melempar `ValidationException` untuk field wajib yang belum diisi (bukan silent zero).
6. **Test ranking terpisah** — setiap jalur harus punya `test_ranking_is_reset_for_each_application_track` untuk mencegah kontaminasi skor antar jalur.

## Anti-Pola yang Harus Dihindari

- ❌ Menambah `case` enum tanpa membuat class strategi (akan kena `UnhandledMatchError` di runtime).
- ❌ Membaca konfigurasi/env langsung di strategi (gunakan `config()`).
- ❌ Menggabungkan logika lintas jalur dalam satu strategi.
- ❌ Mengubah signature `ScoringStrategy::values()` tanpa update semua implementasi.
- ❌ Lupa mendaftarkan strategi di `enum::scoringStrategyClass()`.
