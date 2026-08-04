# Implementasi Pratinjau Dokumen Operator

Tanggal: 18 Juli 2026

## Perubahan

- Menambahkan endpoint `documents.preview` yang mengirim PDF/JPEG/PNG dengan `Content-Disposition: inline`.
- Mempertahankan endpoint `documents.download` dengan `Content-Disposition: attachment`.
- Menambahkan modal pratinjau pada halaman verifikasi operator:
  - PDF ditampilkan melalui `iframe`.
  - JPG/JPEG/PNG ditampilkan sebagai gambar.
  - Tombol unduh tetap tersedia pada daftar dokumen dan modal.
- Endpoint preview dan unduh tetap menggunakan `ApplicationPolicy::view` untuk pembatasan mahasiswa dan wilayah operator.
- Middleware 2FA diterapkan pada akses dokumen; mahasiswa tetap dapat mengakses, sedangkan operator wajib telah mengaktifkan 2FA.
- Menambahkan header `X-Content-Type-Options: nosniff` serta cache privat/no-store untuk pratinjau.

## Pengujian yang ditambahkan

`PrivateDocumentControllerTest` mencakup:

1. PDF dikirim secara inline untuk pengguna yang berwenang.
2. PNG dikirim secara inline untuk pengguna yang berwenang.
3. Operator satu wilayah dapat melihat preview setelah 2FA.
4. Operator tanpa 2FA diarahkan ke halaman pengaturan 2FA.
5. Endpoint unduh tetap menghasilkan attachment.
6. Operator di luar wilayah ditolak.
7. Format selain PDF/JPEG/PNG ditolak oleh endpoint preview dengan HTTP 415.

## Validasi lingkungan penyusunan

- PHP syntax lint berhasil untuk 121 berkas.
- Keseimbangan directive Blade diperiksa untuk 71 template.
- Runtime test dan build frontend belum dijalankan karena direktori `vendor` dan `node_modules` tidak tersedia serta Composer CLI tidak tersedia.

Jalankan pada lingkungan lokal/CI:

```bash
composer install
php artisan test --filter=PrivateDocumentControllerTest
npm ci
npm run build
```
