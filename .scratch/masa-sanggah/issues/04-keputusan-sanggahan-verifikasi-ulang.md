# 04: Keputusan sanggahan dan verifikasi ulang tertarget

**What to build:** Operator kabupaten dapat memutuskan **Kabulkan** atau **Tolak** atas sebuah sanggahan, dengan catatan wajib, dan keputusannya final. Pengabulan membuka kembali **hanya dinas yang memutuskan TMS**: keputusan dinas itu beserta penilaian dokumennya pada putaran berjalan dihapus, application kembali ke **Verifikasi Lintas Dinas**, dan dinas MS tidak tersentuh. Dinas tersebut lalu dapat menilai ulang dan mengirim keputusan baru meskipun tanggal pendaftaran sudah lewat, asalkan masa sanggah periode itu masih berjalan. Setelah seluruh dinas wajib memutuskan MS, application masuk **Seleksi Kabupaten** seperti biasa. Bila dinas itu kembali memutuskan TMS, hasilnya final dan tidak ada sanggahan kedua. Penolakan sanggahan membiarkan application tetap TMS.

**Blocked by:** 03 (Antrean dan detail sanggahan untuk operator kabupaten)

**Status:** resolved

- [x] Operator kabupaten dapat mengabulkan atau menolak sanggahan; kedua aksi mewajibkan catatan dan menolak catatan kosong dengan pesan yang jelas.
- [x] Hanya operator kabupaten yang dapat melihat application terkait yang boleh memutuskan; role lain ditolak.
- [x] Setelah diputuskan, tidak ada route atau aksi yang dapat mengubah keputusan tersebut.
- [x] Pengabulan hanya menghapus keputusan dinas yang memutuskan TMS beserta penilaian dokumennya pada putaran berjalan; keputusan dinas yang sudah MS tetap ada.
- [x] Pengabulan mengembalikan application ke Verifikasi Lintas Dinas, mengosongkan catatan TMS lama, dan mengembalikan perubahan profil yang berasal dari keputusan TMS itu.
- [x] Pengabulan mencatat dinas dan keputusan yang dianulir pada riwayat verifikasi, serta mengirim notifikasi ke mahasiswa dan ke operator dinas terkait.
- [x] Penolakan membiarkan application tetap TMS, mencatat alasannya pada riwayat verifikasi, dan mengirim notifikasi ke mahasiswa.
- [x] Dinas yang dibuka kembali dapat mengirim keputusan baru lewat alur verifikasi yang sudah ada meskipun tanggal pendaftaran periode sudah berakhir, selama masih ada sanggahan dikabulkan pada application itu.
- [x] Bila seluruh dinas wajib memutuskan MS, application berpindah ke Seleksi Kabupaten; bila dinas yang dibuka kembali memutuskan TMS, application kembali TMS dan tidak ada sanggahan kedua yang dapat diajukan.
- [x] Penetapan penerima oleh operator kabupaten mengizinkan application hasil sanggahan meskipun periodenya bukan periode aktif yang dipakai konfigurasi aplikasi.
- [x] Halaman detail pengajuan menampilkan penanda bahwa application berasal dari hasil sanggahan, dengan tautan ke detail sanggahan.
- [x] Pelonggaran gerbang verifikasi dan penetapan tidak mengubah perilaku application yang tidak memiliki sanggahan dikabulkan.
- [x] Feature test menutup: kabul dan tolak, catatan wajib, finalitas, penghapusan tertarget, verifikasi ulang di luar tanggal pendaftaran, MS menjadi Seleksi Kabupaten, TMS berulang menjadi final, serta tidak berubahnya application lain.

## Comments

Implementasi:

- Route `POST operator.sanggahan.decide` di dalam group `role:operator_kabupaten`. `SanggahanDecisionRequest` memvalidasi `decision` (hanya `DIKABULKAN`/`DITOLAK`) dan `catatan_keputusan` wajib, maksimal 2000 karakter; spasi saja ditolak karena nilai di-trim sebelum validasi.
- Otorisasi memakai policy baru `ApplicationPolicy::decideSanggahan` (operator kabupaten + `view` application). Karena route sudah dibatasi role kabupaten, superadmin, operator dinas, mahasiswa, dan operator kabupaten wilayah lain semuanya 403.
- `SanggahanService::decide()` berjalan dalam satu `DB::transaction` dengan `lockForUpdate` pada sanggahan dan application. Status yang sudah diputuskan atau application yang bukan TMS ditolak lewat `ValidationException`, sehingga tidak ada jalur HTTP kedua untuk mengubah keputusan.
- **Kabulkan**: hanya `agency_verifications` berstatus TMS pada dinas wajib yang dihapus, beserta `document_verifications` dinas itu pada putaran berjalan (`AgencyVerificationService::currentRound`). Mutasi profil yang berasal dari keputusan itu dikembalikan (`status_kependudukan` → `belum_diverifikasi`, `desil_sosial` → `null`), application kembali ke `VERIFIKASI_DINAS`, `catatan` dikosongkan, `locked_at` diisi. Satu `VerificationLog` action `sanggahan_granted` menyimpan `annulled_agencies` dan detail keputusan yang dianulir di metadata.
- **Tolak**: application tetap TMS, satu `VerificationLog` action `sanggahan_rejected` dengan catatan operator, notifikasi ke mahasiswa. Tidak ada notifikasi ke dinas.
- Notifikasi kabul: mahasiswa + seluruh operator aktif dinas yang dibuka kembali di kabupaten application. Notifikasi tolak: mahasiswa saja. Keduanya memakai `ApplicationStatusChanged` (database).
- Gerbang dilonggarkan lewat dua predicate di `Application`: `isReopenedForVerification()` (sanggahan `DIKABULKAN` + periode `aktif`) dan `isVerificationWindowOpen()` (tanggal pendaftaran berjalan **atau** application dibuka kembali). `AgencyVerificationService::submitDecision` dan `ApplicationPolicy::verify` memakai helper yang sama, sehingga verifikasi ulang berhasil meski tanggal pendaftaran lewat.
- Antrean dinas (`operator.applications.index`) memakai scope baru `whereOpenForVerification()`: application periode aktif aplikasi **plus** application periode lain yang punya sanggahan dikabulkan pada periode `aktif`. Tanpa ini, application yang dibuka kembali tidak muncul di antrean dinas.
- Penetapan penerima: `SelectionController::store` dan `ApplicationPolicy::select` mengizinkan application dengan sanggahan dikabulkan di luar `config('kartu_hebat.current_period')`. `SelectionWorkflowService` menghitung kuota dan memeringkat ulang memakai `applications.periode` milik kandidat (identik dengan perilaku lama untuk periode aktif konfigurasi). Halaman seleksi menampilkan label arsip khusus ("Mode Arsip (Sanggahan)") bila periodenya punya kandidat hasil sanggahan.
- Halaman detail pengajuan menampilkan badge "Hasil Sanggahan" (status chip) dan tautan ke detail sanggahan untuk operator kabupaten; operator dinas lain hanya melihat badge tanpa tautan karena route sanggahan khusus kabupaten.
- Form keputusan di halaman detail sanggahan muncul hanya untuk status `DIAJUKAN`, memakai komponen anonim `x-sanggahan.decision-option`; konfirmasi finalitas memakai Alpine (`@submit`), bukan inline `onclick`.
- Test: `tests/Feature/OperatorSanggahanDecisionTest.php` (13 test) — kabul (penghapusan tertarget, putaran berjalan, profil, log, notifikasi), kabul Dinas Sosial, tolak, catatan wajib/terlalu panjang/keputusan asing, finalitas (POST ulang, PUT, DELETE), batas wilayah dan role, verifikasi ulang setelah tanggal pendaftaran sampai Seleksi Kabupaten, TMS berulang final + sanggahan kedua ditolak, gerbang application tanpa sanggahan tidak berubah, penetapan penerima lintas periode, antrean dinas, dan penanda halaman detail. Seluruh suite: 280 lulus, 4 skip.
