# Implementasi Tahap Pendaftaran 2–7

Alur pendaftaran mahasiswa sekarang terdiri dari tujuh halaman:

1. Data Pribadi
2. Pendidikan
3. Prestasi
4. Orang Tua/Wali
5. Dokumen
6. Review
7. Submit

## Perubahan utama

- Menambahkan controller, route, dan Blade view untuk tahap 2–7.
- Menambahkan indikator progres yang dapat diklik pada dashboard dan setiap halaman tahap.
- Menambahkan validasi kepemilikan data agar mahasiswa hanya dapat mengakses data pendaftarannya sendiri.
- Menambahkan unggah dokumen privat dengan validasi format/ukuran, pratinjau, unduh, ganti, dan hapus.
- Menambahkan konfirmasi tahap prestasi untuk mahasiswa yang tidak memiliki prestasi.
- Menambahkan pemeriksaan kelengkapan terpusat sebelum submit.
- Menambahkan konfirmasi review; konfirmasi otomatis dibatalkan apabila data atau dokumen diubah kembali.
- Submit menggunakan transaksi dan row lock, mengubah status menjadi `submitted`, mengisi `submitted_at`, lalu mengunci perubahan mahasiswa.
- Seeder master sekarang menyediakan KTP, KK, KTM, Surat Aktif Kuliah, dan KHS sebagai dokumen wajib kategori demo.

## Perintah setelah memperbarui proyek

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=BeasiswaMasterSeeder
npm run build
php artisan test --filter=BeasiswaRegistrationFlowTest
```
