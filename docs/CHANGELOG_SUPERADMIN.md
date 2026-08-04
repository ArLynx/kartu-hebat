# Penambahan Role Superadmin

## Ruang lingkup

Role `superadmin` ditambahkan sebagai pengelola master data. Role ini terpisah dari seluruh role operator verifikasi dan tidak memperoleh akses ke antrean maupun tindakan verifikasi pengajuan.

## Fitur

- Dashboard Superadmin pada `/superadmin/dashboard`.
- CRUD kategori beasiswa, termasuk periode, jalur, kuota, urutan, status aktif, dan dokumen persyaratan.
- CRUD tabel `document_types`.
- Sinkronisasi otomatis `document_types` ke `jenis_dokumens` agar dokumen dapat langsung digunakan pada kategori beasiswa terintegrasi.
- Proteksi penghapusan kategori/document type yang sudah digunakan.
- Kategori aktif wajib memiliki minimal satu dokumen persyaratan aktif.
- 2FA wajib untuk Superadmin.
- Akun demo lokal `superadmin@kartuhebat.test` dengan password `password`.
- Perintah interaktif produksi:

```bash
php artisan kartu-hebat:create-superadmin admin@example.go.id --name="Administrator Utama"
```

## Pengujian

Test fitur berada pada `tests/Feature/SuperadminMasterDataTest.php` dan mencakup:

- redirect dashboard berdasarkan role;
- penolakan akses operator ke modul Superadmin;
- pembuatan `document_types` dan sinkronisasi `jenis_dokumens`;
- pembuatan kategori beserta urutan dokumen persyaratan.
