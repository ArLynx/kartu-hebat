# 06: Halaman pengumuman hasil yang jujur

**What to build:** Halaman pengumuman hasil menampilkan **Masa Sanggah** yang sebenarnya, bukan tanggal simulasi. Panel jadwal selalu tampil dengan label yang sesuai keadaan hari ini (belum dijadwalkan, belum dimulai, sedang berlangsung, telah berakhir), dan tombol menuju pengajuan sanggahan mengarah ke portal login. Pencarian hasil tidak lagi dibatasi pada periode aktif, sehingga hasil dari periode yang sudah ditutup tetap ditemukan lewat nomor pengajuan atau NIK, dan panel jadwal yang ditampilkan adalah milik periode hasil tersebut.

**Blocked by:** 01 (Jadwal Masa Sanggah pada periode), 02 (Mahasiswa mengajukan sanggahan)

**Status:** ready-for-agent

- [ ] Pencarian berdasarkan nomor pengajuan atau NIK menemukan hasil yang sudah dipublikasikan pada periode mana pun, termasuk periode yang sudah ditutup.
- [ ] Bila ada lebih dari satu hasil yang cocok, yang paling baru dipublikasikan yang ditampilkan.
- [ ] Panel masa sanggah memakai tanggal dari periode hasil yang ditemukan; bila belum ada hasil yang dicari, memakai periode aktif terbaru sebagai konteks.
- [ ] Panel selalu tampil: tanpa tanggal menampilkan "Belum dijadwalkan", sebelum tanggal mulai "Belum dimulai", di dalam rentang "Sedang berlangsung", setelah tanggal selesai "Masa sanggah telah berakhir".
- [ ] Tombol menuju pengajuan sanggahan selalu terlihat tetapi mengarah ke portal login, bukan ke halaman yang dapat diakses tanpa autentikasi.
- [ ] Teks "data simulasi" dan seluruh tanggal hardcode pada panel dihapus.
- [ ] Feature test menutup: hasil periode tertutup tetap ditemukan, pemilihan hasil terbaru, keempat label status panel, dan jadwal mengikuti periode hasil.
