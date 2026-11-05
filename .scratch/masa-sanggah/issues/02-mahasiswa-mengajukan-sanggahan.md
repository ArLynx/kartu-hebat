# 02: Mahasiswa mengajukan sanggahan

**What to build:** Mahasiswa pemilik application berstatus **TMS** dapat mengajukan satu **Sanggahan** berupa teks selama **Masa Sanggah** periode tersebut terbuka. Halaman Submit menampilkan kartu TMS berisi dinas yang menolak dan catatan verifikatornya, dengan tombol menuju halaman Sanggahan. Halaman Sanggahan tetap dapat dibuka meskipun tanggal pendaftaran sudah lewat, dan menampilkan jadwal masa sanggah beserta status jendelanya. Setelah dikirim, sanggahan terkunci: tidak dapat diedit, ditarik, atau diajukan ulang. Superadmin tidak dapat menutup atau menghapus periode yang masih memiliki sanggahan belum selesai.

**Blocked by:** 01 (Jadwal Masa Sanggah pada periode)

**Status:** ready-for-agent

- [ ] Tabel sanggahan menyimpan satu baris per application, dengan status awal diajukan, uraian, pengaju, dan periode terkait; unique constraint mencegah baris kedua untuk application yang sama.
- [ ] Mahasiswa dapat mengajukan sanggahan dengan uraian wajib dan pernyataan kebenaran; uraian terlalu pendek atau kosong ditolak dengan pesan yang jelas.
- [ ] Pengajuan ditolak bila application bukan TMS, bukan milik mahasiswa yang login, berada di luar jendela masa sanggah, periodenya tidak aktif, atau sudah pernah disanggah.
- [ ] Halaman Submit menampilkan nama dinas yang memutuskan TMS beserta catatan verifikatornya, dan tombol menuju halaman Sanggahan aktif hanya ketika syarat pengajuan terpenuhi.
- [ ] Halaman Sanggahan tetap dapat diakses setelah tanggal pendaftaran berakhir, menampilkan status jendela, dan mendaftar seluruh riwayat sanggahan milik mahasiswa lintas periode.
- [ ] Setelah dikirim, tidak ada aksi di UI maupun route yang memungkinkan mahasiswa mengubah atau menarik sanggahan.
- [ ] Pengajuan tercatat di riwayat verifikasi application dan mengirim notifikasi ke seluruh operator kabupaten di wilayah application tersebut.
- [ ] Menu Sanggahan tersedia di sidebar portal mahasiswa.
- [ ] Superadmin ditolak saat mengubah status periode menjadi ditutup atau menghapus periode yang masih punya sanggahan belum selesai, dengan pesan yang menjelaskan penyebabnya.
- [ ] Feature test menutup seluruh cabang kelayakan, penulisan baris, notifikasi, akses halaman setelah pendaftaran tutup, dan guard penutupan periode.
