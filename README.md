# Sistem Kartu Hebat Mahasiswa

Implementasi Laravel untuk pendaftaran bantuan pendidikan mahasiswa dengan verifikasi berjenjang berdasarkan wilayah administrasi. Antarmuka mengadaptasi desain Stitch yang disertakan, sedangkan domain, workflow, keamanan, basis data, reporting, dan notifikasi mengikuti dokumentasi proyek.

## Teknologi

- Laravel 13, PHP 8.3+
- Laravel Jetstream, Fortify, Livewire 3
- Tailwind CSS dan Vite
- MySQL, PostgreSQL, atau SQLite
- Laravel Excel untuk ekspor XLSX
- DOMPDF untuk laporan PDF

## Modul yang telah diimplementasikan

- Landing page, persyaratan, jadwal, pengumuman, dan pencarian hasil publik.
- Registrasi mahasiswa, verifikasi email, reset password, profil, dan keamanan akun.
- Formulir data mahasiswa, pemilihan jalur Akademik/Tidak Mampu, sosial ekonomi, pendidikan, serta unggah dokumen khusus jalur.
- Penyimpanan dokumen privat dengan pemeriksaan policy saat mengunduh.
- Dashboard dan antrean operator Desa/Kelurahan, Kecamatan, Dukcapil, Sosial, Pendidikan, dan Kabupaten.
- Keputusan MS, BTL, dan TMS dengan transisi status terpusat.
- Verifikasi tiga dinas secara paralel.
- Rekonsiliasi tingkat kabupaten.
- Seleksi dua jalur: Akademik dihitung dari IPK dan semester; Tidak Mampu dihitung dari desil terverifikasi. Ranking dan kuota dipisahkan per jalur.
- Pemisahan keputusan internal dan publikasi hasil resmi.
- Rekap kandidat PDF/XLSX, PDF daftar penerima, rekap operasional kecamatan/kabupaten, notifikasi database, verification log, dan audit log.
- Pembatasan akses berdasarkan role dan wilayah administrasi.
- Kewajiban 2FA untuk seluruh operator.
- Modul Superadmin khusus untuk mengelola kategori beasiswa dan `document_types`, termasuk sinkronisasi ke jenis dokumen alur pendaftaran.

Rincian pemetaan kebutuhan tersedia di [docs/IMPLEMENTATION_MATRIX.md](docs/IMPLEMENTATION_MATRIX.md).

## Instalasi lokal

Prasyarat: PHP 8.3+, Composer 2, Node.js 20.19+ atau 22.12+, serta ekstensi PHP yang diperlukan Laravel.

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Untuk SQLite:

```bash
touch database/database.sqlite
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

Buka `http://localhost:8000`.

Untuk MySQL/PostgreSQL, ubah konfigurasi `DB_*` dalam `.env`, buat databasenya, lalu jalankan `php artisan migrate --seed`.

## Akun demo

Semua akun demo menggunakan kata sandi `password` dan hanya boleh dipakai pada lingkungan lokal.

| Role | Email |
|---|---|
| Superadmin | `superadmin@kartuhebat.test` |
| Mahasiswa | `mahasiswa@kartuhebat.test` |
| Operator Desa/Kelurahan | `desa@kartuhebat.test` |
| Operator Kecamatan | `kecamatan@kartuhebat.test` |
| Operator Dukcapil | `dukcapil@kartuhebat.test` |
| Operator Dinas Sosial | `sosial@kartuhebat.test` |
| Operator Dinas Pendidikan | `pendidikan@kartuhebat.test` |
| Operator Kabupaten | `kabupaten@kartuhebat.test` |

Akun Superadmin dan operator akan diarahkan untuk mengaktifkan 2FA pada akses pertama. Data demo juga memuat kandidat pada berbagai tahap workflow agar dashboard langsung terisi.

Untuk membuat akun Superadmin pada lingkungan non-demo, jalankan perintah interaktif berikut:

```bash
php artisan kartu-hebat:create-superadmin admin@example.go.id --name="Administrator Utama"
```

Superadmin hanya dapat mengakses master kategori beasiswa dan document type; role ini tidak memperoleh kewenangan verifikasi pengajuan.


## Jalur dan rumus seleksi

Setiap mahasiswa memilih satu jalur untuk satu periode aktif. Jalur dapat diubah selama pengajuan masih dapat diedit; skor dan hasil seleksi lama akan dihapus ketika jalur berubah.

- **Akademik:** IPK 75% dan semester aktif 25%. IPK dinormalisasi terhadap 4,00; semester dinormalisasi sampai batas `KHM_ACADEMIC_MAX_SEMESTER` (default 8).
- **Tidak Mampu:** 100% dari rata-rata desil Dinas Sosial dan Dinas Pendidikan. Desil 1 menghasilkan skor tertinggi dan desil 10 terendah.

Dokumen KHS diwajibkan khusus jalur Akademik, sedangkan SKTM diwajibkan khusus jalur Tidak Mampu. KTP, KK, dan KTM berlaku untuk kedua jalur. Kuota dikonfigurasi melalui `KHM_QUOTA_AKADEMIK` dan `KHM_QUOTA_TIDAK_MAMPU`.

## Workflow inti

```text
DRAFT / BTL
  → pilih jalur AKADEMIK atau TIDAK_MAMPU
  → VERIFIKASI_DESA
  → VERIFIKASI_KECAMATAN
  → VERIFIKASI_DINAS (Dukcapil + Sosial + Pendidikan)
  → SELEKSI_KABUPATEN
  → keputusan internal
  → publikasi
  → DITERIMA / DITOLAK
```

Pada setiap tahap verifikasi, keputusan `BTL` membuka kembali pengajuan mahasiswa dengan catatan perbaikan, sedangkan `TMS` mengakhiri proses. Detail transisi terdapat pada [docs/WORKFLOW.md](docs/WORKFLOW.md).

## Struktur penting

```text
app/Enums                         status, role, keputusan
app/Services                      workflow dan perhitungan seleksi
app/Policies                      otorisasi pengajuan berbasis wilayah
app/Http/Controllers/Student      modul mahasiswa
app/Http/Controllers/Operator     modul seluruh operator
app/Notifications                 notifikasi status
app/Traits/Auditable.php          audit perubahan domain
resources/views                   implementasi UI Stitch
config/kartu_hebat.php            periode, kuota per jalur, rumus, dinas, dokumen
database/migrations               skema domain final
database/seeders                  master data dan data demo
```

## Pengujian

```bash
php artisan test
```

Test tambahan mencakup alur lengkap dari submit sampai seleksi kabupaten, penolakan submit ketika dokumen belum lengkap, pembatasan akses lintas desa, dan middleware multi-role operator.

Status pemeriksaan artefak dan gerbang validasi deployment dicatat pada [docs/VALIDATION.md](docs/VALIDATION.md).

## Keamanan dan deployment

Baca [docs/SECURITY.md](docs/SECURITY.md). Untuk produksi, minimal lakukan:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan test
```

Gunakan HTTPS, kredensial database khusus, mail server nyata, worker queue terkelola, backup terenkripsi, dan rotasi `APP_KEY` hanya melalui prosedur migrasi yang aman.

## Catatan data wilayah

`RegionSeeder` dijalankan sebagai master awal karena `village_id` menjadi dasar pembatasan antrean operator desa, kecamatan, lintas dinas, dan kabupaten. Dataset bawaan tetap bersifat demonstrasi. Sebelum produksi, ganti atau lengkapi master wilayah dengan kode dan daftar resmi pemerintah yang berlaku tanpa mengubah relasi ID yang sudah dipakai oleh pendaftaran.

## Integrasi pendaftaran dan verifikasi berjenjang

Data formulir mahasiswa tetap disimpan pada tabel pendaftaran:

- `periodes`, `kategori_beasiswas`, dan `pendaftarans`;
- `data_pribadis`, `pendidikans`, `prestasis`, dan `orang_tuas`;
- `jenis_dokumens`, `kategori_beasiswa_dokumens`, dan `dokumens`.

Ketika mahasiswa menekan **Submit**, `PendaftaranWorkflowBridgeService` menjalankan integrasi dalam satu transaksi:

1. memvalidasi wilayah berdasarkan `village_id`;
2. menyinkronkan data ke `mahasiswa_profiles`;
3. membuat atau memperbarui satu record `applications` melalui `pendaftaran_id`;
4. menyinkronkan metadata dokumen ke `documents` tanpa menggandakan file fisik;
5. mengirim pengajuan ke status `VERIFIKASI_DESA`;
6. menyelaraskan status `pendaftarans` setiap kali workflow berubah.

Alur operasional setelah submit adalah:

```text
VERIFIKASI_DESA
→ VERIFIKASI_KECAMATAN
→ VERIFIKASI_DINAS
→ SELEKSI_KABUPATEN
→ DITERIMA / DITOLAK
```

Keputusan BTL dari desa atau kecamatan mengubah status pendaftaran menjadi `revision`, sehingga mahasiswa dapat memperbaiki data dan mengirim ulang melalui formulir yang sama.

Jalankan pembaruan proyek dengan:

```bash
php scripts/prepare-runtime-directories.php
composer install
php artisan optimize:clear
php artisan migrate --seed
php artisan kartu-hebat:integrate-pendaftarans
```

Perintah integrasi terakhir memproses pendaftaran versi sebelumnya yang sudah berstatus `submitted` atau `verification` tetapi belum memiliki relasi `applications`. Kegagalan per record ditampilkan tanpa menghentikan pemrosesan record lainnya.

Seeder menyediakan kategori Akademik dan Tidak Mampu. Kolom `kategori_beasiswas.application_type` menentukan jalur scoring. Untuk kategori lama yang belum memiliki pemetaan, fallback dapat diatur melalui `KHM_DEFAULT_APPLICATION_TYPE`.

### Akun demo lokal

Setelah `php artisan migrate --seed` pada lingkungan `local`:

- Mahasiswa: `mahasiswa@kartuhebat.test`
- Operator desa: `desa@kartuhebat.test`
- Operator kecamatan: `kecamatan@kartuhebat.test`
- Operator Dukcapil: `dukcapil@kartuhebat.test`
- Operator Dinas Sosial: `sosial@kartuhebat.test`
- Operator Dinas Pendidikan: `pendidikan@kartuhebat.test`
- Operator kabupaten: `kabupaten@kartuhebat.test`
- Kata sandi seluruh akun: `password`

Data `applications` baru dibuat ketika mahasiswa menyelesaikan tujuh tahap dan menekan submit.

## Peningkatan dari ZIP integrasi sebelumnya

Ekstraksi paling aman dilakukan ke direktori baru. Jika berkas baru ditimpa ke direktori versi sebelumnya, jalankan terlebih dahulu:

```bash
php scripts/cleanup-previous-integration.php
php scripts/prepare-runtime-directories.php
composer dump-autoload
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=BeasiswaMasterSeeder
```

Script pembersihan hanya menghapus berkas kode integrasi lama bernama `ScholarshipPeriod`, `ScholarshipCategory`, dan migrasi `scholarship_registration`. Script tersebut tidak menghapus tabel atau data database secara otomatis.

## Perbaikan draft tidak tampil pada dashboard

Modul sumber `beasiswa.zip` semula memakai pengguna dummy dengan `user_id = 1`. Versi ini telah diperbaiki sehingga pembuatan draft, dashboard, dan data pribadi selalu memakai ID akun yang sedang login.

Setelah mengganti versi lama, bersihkan cache aplikasi:

```bash
php artisan optimize:clear
composer dump-autoload
```

Kemudian restart `php artisan serve`, Apache, Nginx/PHP-FPM, Laragon, atau XAMPP yang digunakan.

Periksa draft yang terlanjur masuk ke pengguna dummy:

```sql
SELECT id, user_id, periode_id, nomor_pendaftaran, status
FROM pendaftarans
ORDER BY id DESC;
```

Jangan mengubah `user_id` sebelum memastikan ID akun mahasiswa yang benar pada tabel `users`.

## Halaman tahap pendaftaran 2–7

Tahap Pendidikan, Prestasi, Orang Tua/Wali, Dokumen, Review, dan Submit telah ditambahkan pada modul pendaftaran berbasis tabel `pendaftarans`. Detail implementasi dan perintah peningkatan tersedia di [docs/TAHAP_PENDAFTARAN_2_7.md](docs/TAHAP_PENDAFTARAN_2_7.md).
