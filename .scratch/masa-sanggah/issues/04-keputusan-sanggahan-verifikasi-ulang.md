# 04: Keputusan sanggahan dan verifikasi ulang tertarget

**What to build:** Operator kabupaten dapat memutuskan **Kabulkan** atau **Tolak** atas sebuah sanggahan, dengan catatan wajib, dan keputusannya final. Pengabulan membuka kembali **hanya dinas yang memutuskan TMS**: keputusan dinas itu beserta penilaian dokumennya pada putaran berjalan dihapus, application kembali ke **Verifikasi Lintas Dinas**, dan dinas MS tidak tersentuh. Dinas tersebut lalu dapat menilai ulang dan mengirim keputusan baru meskipun tanggal pendaftaran sudah lewat, asalkan masa sanggah periode itu masih berjalan. Setelah seluruh dinas wajib memutuskan MS, application masuk **Seleksi Kabupaten** seperti biasa. Bila dinas itu kembali memutuskan TMS, hasilnya final dan tidak ada sanggahan kedua. Penolakan sanggahan membiarkan application tetap TMS.

**Blocked by:** 03 (Antrean dan detail sanggahan untuk operator kabupaten)

**Status:** ready-for-agent

- [ ] Operator kabupaten dapat mengabulkan atau menolak sanggahan; kedua aksi mewajibkan catatan dan menolak catatan kosong dengan pesan yang jelas.
- [ ] Hanya operator kabupaten yang dapat melihat application terkait yang boleh memutuskan; role lain ditolak.
- [ ] Setelah diputuskan, tidak ada route atau aksi yang dapat mengubah keputusan tersebut.
- [ ] Pengabulan hanya menghapus keputusan dinas yang memutuskan TMS beserta penilaian dokumennya pada putaran berjalan; keputusan dinas yang sudah MS tetap ada.
- [ ] Pengabulan mengembalikan application ke Verifikasi Lintas Dinas, mengosongkan catatan TMS lama, dan mengembalikan perubahan profil yang berasal dari keputusan TMS itu.
- [ ] Pengabulan mencatat dinas dan keputusan yang dianulir pada riwayat verifikasi, serta mengirim notifikasi ke mahasiswa dan ke operator dinas terkait.
- [ ] Penolakan membiarkan application tetap TMS, mencatat alasannya pada riwayat verifikasi, dan mengirim notifikasi ke mahasiswa.
- [ ] Dinas yang dibuka kembali dapat mengirim keputusan baru lewat alur verifikasi yang sudah ada meskipun tanggal pendaftaran periode sudah berakhir, selama masih ada sanggahan dikabulkan pada application itu.
- [ ] Bila seluruh dinas wajib memutuskan MS, application berpindah ke Seleksi Kabupaten; bila dinas yang dibuka kembali memutuskan TMS, application kembali TMS dan tidak ada sanggahan kedua yang dapat diajukan.
- [ ] Penetapan penerima oleh operator kabupaten mengizinkan application hasil sanggahan meskipun periodenya bukan periode aktif yang dipakai konfigurasi aplikasi.
- [ ] Halaman detail pengajuan menampilkan penanda bahwa application berasal dari hasil sanggahan, dengan tautan ke detail sanggahan.
- [ ] Pelonggaran gerbang verifikasi dan penetapan tidak mengubah perilaku application yang tidak memiliki sanggahan dikabulkan.
- [ ] Feature test menutup: kabul dan tolak, catatan wajib, finalitas, penghapusan tertarget, verifikasi ulang di luar tanggal pendaftaran, MS menjadi Seleksi Kabupaten, TMS berulang menjadi final, serta tidak berubahnya application lain.
