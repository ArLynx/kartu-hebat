# 06: Halaman pengumuman hasil yang jujur

**What to build:** Halaman pengumuman hasil menampilkan **Masa Sanggah** yang sebenarnya, bukan tanggal simulasi. Panel jadwal selalu tampil dengan label yang sesuai keadaan hari ini (belum dijadwalkan, belum dimulai, sedang berlangsung, telah berakhir), dan tombol menuju pengajuan sanggahan mengarah ke portal login. Pencarian hasil tidak lagi dibatasi pada periode aktif, sehingga hasil dari periode yang sudah ditutup tetap ditemukan lewat nomor pengajuan atau NIK, dan panel jadwal yang ditampilkan adalah milik periode hasil tersebut.

**Blocked by:** 01 (Jadwal Masa Sanggah pada periode), 02 (Mahasiswa mengajukan sanggahan)

**Status:** resolved

- [x] Pencarian berdasarkan nomor pengajuan atau NIK menemukan hasil yang sudah dipublikasikan pada periode mana pun, termasuk periode yang sudah ditutup.
- [x] Bila ada lebih dari satu hasil yang cocok, yang paling baru dipublikasikan yang ditampilkan.
- [x] Panel masa sanggah memakai tanggal dari periode hasil yang ditemukan; bila belum ada hasil yang dicari, memakai periode aktif terbaru sebagai konteks.
- [x] Panel selalu tampil: tanpa tanggal menampilkan "Belum dijadwalkan", sebelum tanggal mulai "Belum dimulai", di dalam rentang "Sedang berlangsung", setelah tanggal selesai "Masa sanggah telah berakhir".
- [x] Tombol menuju pengajuan sanggahan selalu terlihat tetapi mengarah ke portal login, bukan ke halaman yang dapat diakses tanpa autentikasi.
- [x] Teks "data simulasi" dan seluruh tanggal hardcode pada panel dihapus.
- [x] Feature test menutup: hasil periode tertutup tetap ditemukan, pemilihan hasil terbaru, keempat label status panel, dan jadwal mengikuti periode hasil.

## Comments

Implementasi:

- `ResultController` mencari application terpublikasi lintas periode (nomor pengajuan atau NIK) dan mengurutkan dengan subquery `selections.published_at` terbaru, lalu `id` sebagai penentu akhir. Filter `published_at` tetap di `whereHas` sehingga hasil yang belum terbit tidak pernah menutupi hasil yang sudah terbit.
- Periode panel: `$result->resolvedPeriode()` bila ada hasil, selain itu `Periode::latestAktif()` (method baru pada model, juga dipakai `SanggahanController` dan `AppServiceProvider`, menggantikan tiga salinan query `status = aktif` yang sama). Bila hasil ditemukan tetapi periodenya tidak lagi ada, panel tidak meminjam jadwal periode aktif — labelnya menjadi "Periode hasil tidak ditemukan" dan jadwal "Belum dijadwalkan".
- Panel dipindah ke komponen anonim `resources/views/components/sanggahan/public-schedule.blade.php`, sehingga penandaan status berada di satu tempat dengan komponen mahasiswa `sanggahan/schedule`. Kalimat status dipusatkan di `MasaSanggahStatus::notice()` (match), bukan cascade `@if` di Blade; `isScheduled()` dipakai bersama alih-alih menghitung ulang dua tanggal.
- Tombol "Ajukan Sanggahan" selalu dirender dan selalu menuju `route('login')`. Catatan kaki menyebut pengajuan wajib lewat portal dengan akun mahasiswa.
- Teks "data simulasi", jam hardcode (08.00/23.59 WIB), dan tanggal 10/12 November 2026 dihapus. Kalimat "Siapkan dokumen pendukung" diganti agar konsisten dengan aturan domain: sanggahan hanya untuk mengoreksi dugaan kekeliruan verifikasi, bukan unggah ulang dokumen.
- Test: `tests/Feature/PublicResultTest.php` (15 test) — hasil periode tertutup via nomor dan NIK, pemilihan published_at terbaru, hasil belum terbit tidak menutupi yang terbit, empat label status beserta badge-nya, panel tanpa periode sama sekali, periode hasil tidak ditemukan, jadwal mengikuti periode hasil, tombol login pada keempat keadaan dan saat hasil periode tertutup, serta tidak ada lagi teks simulasi/tanggal hardcode. Seluruh suite: 301 lulus, 4 skip.
