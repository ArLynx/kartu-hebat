# 05: Publikasi hasil perbaikan dan penanda Penetapan Penerima

**What to build:** Operator kabupaten dapat mempublikasikan hasil setelah ada penerima baru dari sanggahan tanpa merusak publikasi sebelumnya. Publikasi hanya memproses kandidat yang belum pernah dipublikasikan, sehingga penerima lama tidak menerima notifikasi ulang dan Surat Keputusan lama tidak pernah ditimpa; hasil perbaikan terbit sebagai pengumuman terpisah dengan berkas SK sendiri. Bila tidak ada kandidat baru, operator mendapat pesan yang menjelaskan hal itu. Tabel **Penetapan Penerima** menandai kandidat hasil sanggahan dengan badge dan tautan ke detail sanggahannya.

**Blocked by:** 04 (Keputusan sanggahan dan verifikasi ulang tertarget)

**Status:** ready-for-agent

- [ ] Publikasi hanya menulis hasil untuk kandidat yang belum pernah dipublikasikan; kandidat yang sudah punya waktu publikasi tidak berubah.
- [ ] Kandidat yang sudah pernah dipublikasikan tidak menerima notifikasi ulang.
- [ ] Publikasi pertama tetap memakai pengumuman hasil seleksi periode yang sudah ada; publikasi perbaikan menerbitkan pengumuman baru dengan berkas SK sendiri, dan berkas SK lama tetap dapat diunduh.
- [ ] Publikasi tanpa kandidat baru menghasilkan pesan yang menjelaskan bahwa tidak ada hasil baru, bukan error yang membingungkan.
- [ ] Perhitungan kuota dan peringkat tetap dilakukan atas seluruh kandidat, sehingga susunan penerima tidak berubah hanya karena publikasi bertahap.
- [ ] Nilai keputusan internal yang tersimpan dengan huruf kecil tetap terbaca benar, tidak lagi diam-diam dianggap ditolak.
- [ ] Tabel Penetapan Penerima menampilkan badge hasil sanggahan pada kandidat yang berasal dari sanggahan dikabulkan, dengan tautan ke detail sanggahan.
- [ ] Feature test menutup: publikasi bertahap, SK lama utuh, tidak ada notifikasi ulang, pesan saat tidak ada kandidat baru, pembacaan keputusan tidak peka huruf besar/kecil, dan badge pada tabel penetapan.
