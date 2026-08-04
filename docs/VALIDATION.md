# Catatan Validasi Implementasi

Tanggal pemeriksaan: 18 Juli 2026.

## Pemeriksaan yang berhasil dijalankan

- PHP syntax lint terhadap 120 berkas pada `app`, `bootstrap`, `config`, `database`, `routes`, dan `tests`.
- Resolusi statis 24 pasangan controller/action yang didaftarkan pada `routes/web.php`.
- Pemeriksaan keseimbangan directive blok pada 71 template Blade.
- Resolusi 207 import kelas first-party namespace `App` tanpa referensi yang hilang.
- Resolusi 16 referensi view dari controller tanpa template yang hilang.
- Validasi JSON untuk `composer.json`, `package.json`, dan `package-lock.json`.
- Pemeriksaan konsistensi master kriteria: IPK 75%, semester 25%, dan desil 100%.
- Pemeriksaan contoh hitung yang digunakan test:
  - Akademik: IPK 3,50 dan semester 5 menghasilkan skor 81,25.
  - Tidak Mampu: desil sosial 2 dan desil pendidikan 4 menghasilkan skor 77,7778.
- Pemeriksaan filter jalur pada antrean operator, rekonsiliasi, seleksi, laporan, dan hasil publik.
- Pemeriksaan pemisahan dokumen wajib: KHS untuk Akademik dan SKTM untuk Tidak Mampu.

## Test yang ditambahkan

- `SelectionScoringServiceTest::test_academic_track_uses_only_ipk_and_semester`
- `SelectionScoringServiceTest::test_unable_track_uses_verified_desil_and_lower_desil_scores_higher`
- `SelectionScoringServiceTest::test_ranking_is_reset_for_each_application_track`
- Penyesuaian test workflow agar memakai jalur Akademik dan hanya mengharuskan dokumen jalur yang relevan.

## Pemeriksaan yang belum dapat dijalankan di lingkungan penyusunan

Direktori `vendor` dan `node_modules` tidak tersedia, serta Composer CLI tidak tersedia. Karena itu, perintah berikut belum dapat dijalankan pada artefak akhir:

```bash
php artisan migrate:fresh --seed
php artisan test
npm ci
npm run build
```

Kondisi tersebut bukan bukti bahwa pengujian runtime gagal; pengujian runtime belum dapat dieksekusi pada lingkungan penyusunan. Jalankan seluruh perintah di atas pada lingkungan lokal/CI yang memiliki Composer, akses registry paket, dan ekstensi PHP Laravel.

## Gerbang validasi sebelum deployment

1. Jalankan migrasi pada salinan database dan verifikasi bahwa pengajuan lama dipetakan ke jalur Akademik.
2. Jalankan `php artisan migrate:fresh --seed` dan seluruh test suite.
3. Uji perubahan jalur pada status DRAFT/BTL, termasuk penghapusan dokumen khusus jalur lama.
4. Uji submit Akademik tanpa SKTM dan submit Tidak Mampu tanpa KHS.
5. Uji kewajiban desil untuk operator Sosial/Pendidikan hanya pada jalur Tidak Mampu.
6. Uji bahwa ranking dimulai dari 1 pada masing-masing kombinasi jalur, kabupaten, dan periode dan kuota tidak saling mengurangi.
7. Uji PDF/XLSX per jalur serta publikasi hasil pada data representatif.
8. Tetapkan bobot IPK/semester, formula desil, kuota per jalur, dan semester maksimum berdasarkan regulasi/SOP resmi.
