# Keamanan

## Kontrol yang diterapkan

- Fortify/Jetstream untuk autentikasi, verifikasi email, password reset, konfirmasi password, dan 2FA.
- 2FA wajib untuk Superadmin dan seluruh role operator berdasarkan `two_factor_confirmed_at`.
- Rate limiting login, challenge 2FA, passkey, dan pencarian hasil publik.
- Policy pengajuan berbasis role dan hierarki wilayah.
- Dokumen berada pada disk privat dan tidak memiliki URL publik langsung.
- Unduhan dokumen selalu melalui controller dan policy authorization.
- Nama file penyimpanan menggunakan UUID; metadata asli hanya digunakan sebagai nama unduhan.
- Validasi tipe dan ukuran file, checksum SHA-256, dan versioning dokumen.
- Query antrean menggunakan scope wilayah sehingga operator tidak menerima data di luar kewenangannya.
- Keputusan workflow berjalan dalam transaksi database.
- Policy mengunci pengajuan historis agar hanya periode aktif yang dapat diedit, diverifikasi, atau ditetapkan ulang.
- Verification log merekam perubahan status; audit log merekam perubahan model domain.
- Password, secret 2FA, dan recovery code dikecualikan dari audit values.
- Respons halaman autentikasi menggunakan cache-control privat/no-store.
- Hasil publik memasker nama dan NIK.

## Batasan yang perlu ditangani di produksi

- Tambahkan antivirus/malware scanning untuk semua unggahan.
- Terapkan object storage privat dengan server-side encryption dan signed internal access bila volume besar.
- Tambahkan CSP, HSTS, secure cookies, trusted proxy, dan konfigurasi CORS sesuai topologi deployment.
- Pisahkan database user aplikasi dari akun administratif database.
- Terapkan centralized logging, alerting, backup terenkripsi, uji restore, dan retensi data.
- Ganti seluruh password demo; `DatabaseSeeder` membatasi `RegionSeeder` dan `DemoDataSeeder` ke environment `local`/`testing`, dan keduanya tetap tidak boleh dijalankan manual pada produksi.
- Lakukan penetration testing, dependency audit, dan review perlindungan data pribadi sebelum go-live.
- Pertimbangkan CAPTCHA atau verifikasi tambahan pada pencarian hasil untuk membatasi enumerasi NIK.
