# 02: Mahasiswa mengajukan sanggahan

**What to build:** Mahasiswa pemilik application berstatus **TMS** dapat mengajukan satu **Sanggahan** berupa teks selama **Masa Sanggah** periode tersebut terbuka. Halaman Submit menampilkan kartu TMS berisi dinas yang menolak dan catatan verifikatornya, dengan tombol menuju halaman Sanggahan. Halaman Sanggahan tetap dapat dibuka meskipun tanggal pendaftaran sudah lewat, dan menampilkan jadwal masa sanggah beserta status jendelanya. Setelah dikirim, sanggahan terkunci: tidak dapat diedit, ditarik, atau diajukan ulang. Superadmin tidak dapat menutup atau menghapus periode yang masih memiliki sanggahan belum selesai.

**Blocked by:** 01 (Jadwal Masa Sanggah pada periode)

**Status:** resolved

- [x] Tabel sanggahan menyimpan satu baris per application, dengan status awal diajukan, uraian, pengaju, dan periode terkait; unique constraint mencegah baris kedua untuk application yang sama.
- [x] Mahasiswa dapat mengajukan sanggahan dengan uraian wajib dan pernyataan kebenaran; uraian terlalu pendek atau kosong ditolak dengan pesan yang jelas.
- [x] Pengajuan ditolak bila application bukan TMS, bukan milik mahasiswa yang login, berada di luar jendela masa sanggah, periodenya tidak aktif, atau sudah pernah disanggah.
- [x] Halaman Submit menampilkan nama dinas yang memutuskan TMS beserta catatan verifikatornya, dan tombol menuju halaman Sanggahan aktif hanya ketika syarat pengajuan terpenuhi.
- [x] Halaman Sanggahan tetap dapat diakses setelah tanggal pendaftaran berakhir, menampilkan status jendela, dan mendaftar seluruh riwayat sanggahan milik mahasiswa lintas periode.
- [x] Setelah dikirim, tidak ada aksi di UI maupun route yang memungkinkan mahasiswa mengubah atau menarik sanggahan.
- [x] Pengajuan tercatat di riwayat verifikasi application dan mengirim notifikasi ke seluruh operator kabupaten di wilayah application tersebut.
- [x] Menu Sanggahan tersedia di sidebar portal mahasiswa.
- [x] Superadmin ditolak saat mengubah status periode menjadi ditutup atau menghapus periode yang masih punya sanggahan belum selesai, dengan pesan yang menjelaskan penyebabnya.
- [x] Feature test menutup seluruh cabang kelayakan, penulisan baris, notifikasi, akses halaman setelah pendaftaran tutup, dan guard penutupan periode.

## Comments

Implementasi:

- Tabel `sanggahans`: `application_id` (unique, cascade), `periode_id`, `submitted_by`, `status` string + cast `SanggahanStatus`, `uraian`, `catatan_keputusan`, `decided_by`, `decided_at`. Status TIDAK ditambahkan ke `ApplicationStatus`; application tetap TMS selama sanggahan menunggu.
- `SanggahanService::ineligibilityReason()` adalah satu-satunya sumber aturan kelayakan (pemilik, TMS, periode aktif, jendela terbuka, belum pernah disanggah). Dipakai policy `submitSanggahan`, halaman Submit (teks alasan tombol nonaktif), dan `store` sebelum menulis. Penulisan baris memakai `lockForUpdate` + unique constraint sebagai pengaman balapan.
- Halaman `/mahasiswa/sanggahan` punya controller sendiri dan tidak bergantung pada `MahasiswaPendaftaranService::currentFor()` yang memfilter tanggal pendaftaran, sehingga tetap terbuka saat pendaftaran tutup. Halaman Submit hanya menampilkan kartu TMS + tautan.
- `Application::resolvedPeriode()` menyelesaikan periode lewat `pendaftaran.periode`, dengan fallback pencocokan nama untuk application lama.
- Pengajuan menulis `VerificationLog` action `sanggahan_submitted` dan mengirim `ApplicationStatusChanged` ke seluruh `operator_kabupaten` aktif di kabupaten application.
- Guard periode: `Periode::hasUnresolvedSanggahans()` (DIAJUKAN, atau DIKABULKAN yang application-nya belum final) dipakai saat status berubah menjadi `ditutup`; `destroy` menolak periode yang punya riwayat sanggahan apa pun.
- Test: `tests/Feature/MahasiswaSanggahanTest.php` (24 test) — pengajuan sukses, validasi uraian/pernyataan, bukan TMS, bukan pemilik, di luar jendela, periode nonaktif, duplikat (HTTP + unique DB), finalitas (PUT/DELETE 405), kartu Submit, akses halaman setelah pendaftaran tutup, riwayat lintas periode, menu sidebar, notifikasi per kabupaten, dan guard tutup/hapus periode.

