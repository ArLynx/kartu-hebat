# Perbaikan Draft Pendaftaran Tidak Tampil

## Gejala

Setelah tombol **Buat Draft Pendaftaran** ditekan, pengguna kembali ke dashboard, tetapi dashboard masih menampilkan **Belum Ada Pendaftaran Beasiswa**.

## Penyebab

Kode awal pada `beasiswa.zip` menggunakan pengguna dummy:

```php
private function currentUserId(): int
{
    return 1;
}
```

Pada sistem dengan autentikasi, ID akun mahasiswa yang login tidak selalu `1`. Akibatnya draft dapat tersimpan untuk akun lain dan tidak ditemukan oleh dashboard pengguna aktif.

## Perbaikan

- Pembuatan draft menggunakan `$request->user()->getKey()`.
- Dashboard menggunakan ID akun yang sedang login.
- Pengecekan draft ganda menggunakan kombinasi `user_id` dan `periode_id`.
- Nomor pendaftaran dibuat setelah ID baris pendaftaran tersedia.
- Pembuatan draft diarahkan kembali ke dashboard agar hasilnya langsung terlihat.
- Periode aktif juga diperiksa berdasarkan rentang tanggal.

## Aktivasi perubahan

```bash
php artisan optimize:clear
composer dump-autoload
```

Restart layanan PHP/web server setelah menjalankan perintah tersebut.
