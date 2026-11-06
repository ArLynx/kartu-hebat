# 05: Publikasi hasil perbaikan dan penanda Penetapan Penerima

**What to build:** Operator kabupaten dapat mempublikasikan hasil setelah ada penerima baru dari sanggahan tanpa merusak publikasi sebelumnya. Publikasi hanya memproses kandidat yang belum pernah dipublikasikan, sehingga penerima lama tidak menerima notifikasi ulang dan Surat Keputusan lama tidak pernah ditimpa; hasil perbaikan terbit sebagai pengumuman terpisah dengan berkas SK sendiri. Bila tidak ada kandidat baru, operator mendapat pesan yang menjelaskan hal itu. Tabel **Penetapan Penerima** menandai kandidat hasil sanggahan dengan badge dan tautan ke detail sanggahannya.

**Blocked by:** 04 (Keputusan sanggahan dan verifikasi ulang tertarget)

**Status:** resolved

- [x] Publikasi hanya menulis hasil untuk kandidat yang belum pernah dipublikasikan; kandidat yang sudah punya waktu publikasi tidak berubah.
- [x] Kandidat yang sudah pernah dipublikasikan tidak menerima notifikasi ulang.
- [x] Publikasi pertama tetap memakai pengumuman hasil seleksi periode yang sudah ada; publikasi perbaikan menerbitkan pengumuman baru dengan berkas SK sendiri, dan berkas SK lama tetap dapat diunduh.
- [x] Publikasi tanpa kandidat baru menghasilkan pesan yang menjelaskan bahwa tidak ada hasil baru, bukan error yang membingungkan.
- [x] Perhitungan kuota dan peringkat tetap dilakukan atas seluruh kandidat, sehingga susunan penerima tidak berubah hanya karena publikasi bertahap.
- [x] Nilai keputusan internal yang tersimpan dengan huruf kecil tetap terbaca benar, tidak lagi diam-diam dianggap ditolak.
- [x] Tabel Penetapan Penerima menampilkan badge hasil sanggahan pada kandidat yang berasal dari sanggahan dikabulkan, dengan tautan ke detail sanggahan.
- [x] Feature test menutup: publikasi bertahap, SK lama utuh, tidak ada notifikasi ulang, pesan saat tidak ada kandidat baru, pembacaan keputusan tidak peka huruf besar/kecil, dan badge pada tabel penetapan.

## Comments

Implementasi:

- `SelectionWorkflowService::publishDecisions()` masih menghitung keputusan kuota/peringkat untuk **seluruh** kandidat periode, lalu hanya meneruskan kandidat dengan `selection.published_at` kosong ke `executePublication()`. Kandidat lama tidak disentuh sehingga `published_at`, status, log, dan notifikasinya tidak berubah.
- `executePublication()` mengembalikan `0` tanpa menulis apa pun bila tidak ada kandidat baru; `SelectionController::publish()` menerjemahkannya menjadi flash `warning` "Tidak ada hasil baru untuk dipublikasikan…", bukan error validasi.
- Publikasi pertama tetap memakai slug `hasil-seleksi-<periode>` lewat `updateOrCreate`. Publikasi perbaikan memakai slug unik `hasil-seleksi-<periode>-perbaikan-<timestamp>-<random>` dan judul default "Penetapan Perbaikan Hasil Seleksi…", sehingga berkas SK lama tetap ada dan dapat diunduh. Penentuan "perbaikan" memakai keberadaan pengumuman dasar periode tersebut, bukan status kandidat.
- Keputusan internal dibaca lewat `Selection::manualDecisionStatus()` yang meng-`UPPER`-kan nilai, dipakai di service publikasi, export Excel, halaman seleksi, tombol keputusan, dan rekap PDF. Nilai yang tidak dikenali (mis. `DTERIMA`) kini menggagalkan publikasi dengan pesan jelas, bukan diam-diam menjadi DITOLAK. Filter `acceptedCount` dan cek kuota memakai scope `whereManualDecision()` yang juga tidak peka huruf besar/kecil.
- Publikasi periode tertutup dibuka khusus untuk kandidat dengan sanggahan `DIKABULKAN`: form publikasi mengirim `periode`, controller menolak periode lampau tanpa sanggahan dikabulkan (flash `error`), dan query service memfilter kandidat bersangkutan. Halaman seleksi periode lampau menampilkan tombol "Publikasikan Hasil Perbaikan" hanya pada mode arsip sanggahan.
- Tabel Penetapan Penerima menampilkan badge "Hasil Sanggahan" plus tautan "Lihat detail →" ke `operator.sanggahan.show` pada baris kandidat dengan sanggahan dikabulkan.
- Copy modal publikasi diperbarui: hanya kandidat belum pernah dipublikasikan yang diproses, penerima lama tidak dinotifikasi ulang, SK lama tetap utuh.
- Test: `tests/Feature/SelectionPublicationTest.php` (12 test) — publikasi bertahap tanpa menyentuh penerima lama, SK lama utuh + pengumuman perbaikan terpisah, pesan tanpa kandidat baru, kuota dihitung lintas kandidat, keputusan huruf kecil, keputusan tak dikenal ditolak, penerima hasil sanggahan hanya dinotifikasi sekali, publikasi periode tertutup via sanggahan, penolakan periode tertutup tanpa sanggahan, badge + tautan pada tabel, badge muncul hanya pada mode arsip sanggahan, dan rekap PDF membaca keputusan huruf kecil. `SelectionWorkbookExportTest` menambah test export Excel untuk keputusan huruf kecil. Seluruh suite: 308 lulus, 4 skip.

