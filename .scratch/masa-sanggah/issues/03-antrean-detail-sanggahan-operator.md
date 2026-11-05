# 03: Antrean dan detail sanggahan untuk operator kabupaten

**What to build:** Operator kabupaten mendapat menu **Sanggahan** berisi antrean seluruh sanggahan di wilayahnya, dengan ringkasan jumlah per status dan filter periode, status, serta pencarian nomor pengajuan atau nama. Membuka satu sanggahan menampilkan uraian mahasiswa, keputusan TMS tiap dinas beserta catatannya, dokumen yang dinilai tidak memenuhi, dan riwayat verifikasi application, sehingga operator dapat menilai klaim mahasiswa dengan bukti. Pada tiket ini belum ada tombol keputusan.

**Blocked by:** 02 (Mahasiswa mengajukan sanggahan)

**Status:** ready-for-agent

- [ ] Operator kabupaten melihat menu Sanggahan di sidebar portal operator.
- [ ] Antrean hanya menampilkan sanggahan atas application yang berada di wilayah kabupaten operator tersebut; sanggahan wilayah lain tidak pernah terlihat.
- [ ] Antrean menampilkan ringkasan jumlah sanggahan yang diajukan, dikabulkan, dan ditolak untuk data yang sedang difilter.
- [ ] Antrean dapat difilter berdasarkan periode dan status, serta dicari berdasarkan nomor pengajuan atau nama mahasiswa, dan filter tetap terbawa saat berpindah halaman.
- [ ] Halaman detail menampilkan uraian mahasiswa, daftar dinas yang memutuskan TMS beserta catatan verifikatornya, dokumen yang dinilai tidak memenuhi beserta catatannya, dan riwayat verifikasi application.
- [ ] Halaman detail menampilkan status sanggahan saat ini; sanggahan yang sudah diputuskan tidak menawarkan aksi apa pun pada tiket ini.
- [ ] Feature test menutup: batas wilayah, isi antrean, filter dan pencarian, ringkasan jumlah, serta isi halaman detail.
