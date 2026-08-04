# Integrasi Pendaftaran dengan Workflow Verifikasi

## Arsitektur

- `pendaftarans` tetap menjadi sumber utama data formulir mahasiswa.
- `applications` menjadi mesin workflow verifikasi dan memiliki relasi satu-ke-satu melalui `applications.pendaftaran_id`.
- `mahasiswa_profiles` disinkronkan saat submit agar otorisasi wilayah dan scoring lama tetap bekerja.
- `documents` menyimpan metadata yang menunjuk ke file yang sama dengan `dokumens`; file fisik tidak disalin.

## Alur status

```text
Draft pendaftaran
→ Submit
→ VERIFIKASI_DESA
→ VERIFIKASI_KECAMATAN
→ VERIFIKASI_DINAS
→ SELEKSI_KABUPATEN
→ DITERIMA / DITOLAK
```

Keputusan `BTL_DESA` atau `BTL_KECAMATAN` mengubah `pendaftarans.status` menjadi `revision`. Keputusan `TMS` atau `DITOLAK` menjadi `rejected`, sedangkan `DITERIMA` menjadi `approved`.

## Komponen utama

- Migrasi: `2026_07_20_020000_integrate_registration_with_verification_workflow.php`
- Bridge: `app/Services/PendaftaranWorkflowBridgeService.php`
- Sinkronisasi status: event `saved` pada `App\Models\Application`
- Submit terintegrasi: `App\Http\Controllers\Mahasiswa\SubmitController`
- Migrasi data lama: perintah `kartu-hebat:integrate-pendaftarans`

## Instalasi atau peningkatan

```bash
composer install
php artisan optimize:clear
php artisan migrate --seed
php artisan kartu-hebat:integrate-pendaftarans
npm ci
npm run build
php artisan test
```

Pastikan master wilayah produksi telah diimpor. `village_id` menentukan operator desa, kecamatan, dan kabupaten yang berhak melihat pengajuan.

## Catatan validasi paket

Seluruh 148 berkas PHP pada paket ini telah diperiksa dengan `php -l` tanpa kesalahan sintaks. Pengujian PHPUnit tetap perlu dijalankan pada lingkungan proyek setelah dependensi Composer tersedia.
