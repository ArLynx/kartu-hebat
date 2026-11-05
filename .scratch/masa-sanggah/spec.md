# Masa Sanggah — Spec

Status: ready-for-agent
Type: feature

## Problem Statement

Mahasiswa yang dinyatakan TMS (Tidak Memenuhi Syarat) oleh verifikator dinas tidak punya saluran apa pun untuk menolak hasil itu. Padahal TMS tidak selalu berarti berkas pendaftar salah: keputusan bisa keliru karena salah membaca dokumen, salah mencocokkan NIK, atau salah menilai bukti yang sudah benar. Saat ini keputusan TMS final tanpa banding, sehingga pendaftar yang dirugikan tidak punya cara memperbaiki keadaan.

Di sisi lain, halaman publik `/pengumuman-hasil` sudah menampilkan panel "Masa Sanggah", tetapi seluruh isinya hardcode ("10 November 2026", "12 November 2026", tombol nonaktif, catatan "data simulasi"). Superadmin tidak punya cara mengatur jadwal sanggah, operator kabupaten tidak punya antrean untuk memeriksanya, dan mahasiswa tidak punya tempat mengajukannya.

Ada dua masalah tambahan yang ditemukan saat analisis:

- Halaman publik hanya mencari hasil pada periode berstatus `aktif`, padahal hasil seleksi terbit setelah pendaftaran ditutup. Akibatnya hasil yang sudah dipublikasikan pada periode tertutup tidak pernah muncul.
- Publikasi ulang hasil seleksi memproses seluruh kandidat satu periode dan menimpa berkas SK lama pada slug pengumuman yang sama, sehingga satu koreksi kecil akan mengganti SK resmi yang sudah terbit dan mengirim ulang notifikasi ke semua penerima sebelumnya.

## Solution

Tambahkan **Masa Sanggah** sebagai jadwal milik periode dan **Sanggahan** sebagai keberatan milik satu application TMS.

- Superadmin mengatur `tanggal_mulai_sanggah` dan `tanggal_selesai_sanggah` pada periode aktif.
- Mahasiswa pemilik application TMS dapat mengajukan satu sanggahan **berupa teks saja** selama jendela terbuka. Tidak ada unggah berkas dan tidak ada unggah ulang dokumen: sanggahan hanya untuk mengoreksi dugaan kesalahan verifikator, bukan untuk memperbaiki kesalahan unggah pendaftar.
- Operator kabupaten memeriksa antrean sanggahan dan memutuskan **Kabulkan** atau **Tolak** dengan catatan wajib. Keputusan final; tidak ada sanggahan kedua.
- Pengabulan membuka kembali **hanya dinas yang memutuskan TMS**; dinas yang sudah MS dipertahankan. Application kembali ke VERIFIKASI_DINAS. Jika dinas itu kembali memutuskan TMS, hasilnya final.
- Setelah dinas memutuskan MS, application masuk SELEKSI_KABUPATEN dan mengikuti alur Penetapan Penerima seperti biasa, dengan badge "Hasil Sanggahan" agar operator sadar konteksnya.
- Publikasi hasil dibuat inkremental: hanya kandidat yang belum pernah dipublikasikan yang diproses, SK lama tidak pernah ditimpa, dan hasil perbaikan terbit sebagai pengumuman terpisah.
- Halaman publik menampilkan jadwal sanggah yang sebenarnya, selalu terlihat dengan label status yang jujur, dan hasil seleksi dari periode mana pun yang sudah dipublikasikan tetap bisa dicari.

## User Stories

1. Sebagai superadmin, saya ingin mengisi tanggal mulai dan tanggal selesai masa sanggah pada sebuah periode, supaya jadwal sanggah bisa diumumkan tanpa mengubah kode.
2. Sebagai superadmin, saya ingin melihat status masa sanggah (belum dijadwalkan, belum dimulai, sedang berlangsung, telah berakhir) di daftar periode, supaya saya tahu jadwal mana yang masih perlu diatur.
3. Sebagai superadmin, saya ingin ditolak saat mencoba menutup periode yang masih punya sanggahan belum diputuskan, supaya tidak ada sanggahan yang menggantung pada periode yang sudah dikunci.
4. Sebagai superadmin, saya ingin ditolak saat mencoba menghapus periode yang memiliki sanggahan, supaya riwayat tidak hilang.
5. Sebagai superadmin, saya ingin validasi tanggal selesai sanggah tidak boleh lebih awal dari tanggal mulai, supaya jadwal yang tersimpan selalu masuk akal.
6. Sebagai mahasiswa, saya ingin melihat kartu TMS di halaman Submit yang menyebut dinas yang menolak dan catatan verifikatornya, supaya saya tahu apa yang dianggap salah.
7. Sebagai mahasiswa, saya ingin melihat status sanggahan (diajukan, dikabulkan, ditolak) pada kartu TMS yang sama, supaya saya tidak perlu mencari ke halaman lain.
8. Sebagai mahasiswa, saya ingin membuka halaman Sanggahan yang tetap bisa diakses meskipun tanggal pendaftaran sudah lewat, supaya saya tidak kehilangan akses tepat saat masa sanggah dimulai.
9. Sebagai mahasiswa, saya ingin melihat jadwal masa sanggah dan apakah jendelanya sedang terbuka, supaya saya tahu kapan harus mengirim.
10. Sebagai mahasiswa, saya ingin mengisi uraian keberatan dan mencentang pernyataan kebenaran, supaya sanggahan saya tercatat sebagai pernyataan resmi.
11. Sebagai mahasiswa, saya ingin diminta menulis uraian yang cukup panjang, supaya sanggahan tidak dikirim kosong atau tidak informatif.
12. Sebagai mahasiswa, saya ingin ditolak saat mengajukan sanggahan untuk application yang bukan TMS, supaya fitur ini tidak disalahgunakan untuk menolak keputusan DITERIMA/DITOLAK yang sudah ber-SK.
13. Sebagai mahasiswa, saya ingin ditolak saat mengajukan sanggahan di luar jendela masa sanggah, supaya batas waktu dihormati.
14. Sebagai mahasiswa, saya ingin ditolak saat mengajukan sanggahan kedua untuk application yang sama, supaya keputusan bersifat final dan tidak ada spam.
15. Sebagai mahasiswa, saya ingin tidak bisa mengajukan sanggahan atas nama orang lain, supaya data pendaftar lain aman.
16. Sebagai mahasiswa, saya ingin sanggahan saya terkunci setelah dikirim, supaya tidak ada perubahan isi setelah operator mulai memeriksa.
17. Sebagai mahasiswa, saya ingin menerima notifikasi saat sanggahan diputuskan, supaya saya tahu hasilnya tanpa memeriksa portal terus-menerus.
18. Sebagai mahasiswa, saya ingin melihat catatan keputusan operator, supaya saya paham alasan kabul atau tolak.
19. Sebagai mahasiswa, saya ingin daftar sanggahan mencakup seluruh riwayat lintas periode, supaya saya bisa melihat jejak pengajuan lama.
20. Sebagai mahasiswa yang sanggahannya dikabulkan, saya ingin status aplikasi saya berubah dari TMS menjadi menunggu verifikasi ulang, supaya saya tahu prosesnya berjalan.
21. Sebagai mahasiswa yang sanggahannya ditolak, saya ingin status TMS saya tetap dan dijelaskan bahwa keputusan final, supaya saya tidak menunggu perubahan yang tidak akan datang.
22. Sebagai operator dinas, saya ingin menerima notifikasi ketika sebuah application dikembalikan ke dinas saya karena sanggahan dikabulkan, supaya saya bisa segera menilai ulang.
23. Sebagai operator dinas, saya ingin menilai ulang dokumen pada putaran berjalan tanpa kehilangan penilaian dinas lain, supaya koreksi bersifat tertarget.
24. Sebagai operator dinas, saya ingin verifikasi ulang tetap diizinkan meskipun tanggal pendaftaran sudah berakhir, asalkan jendela masa sanggah periode itu masih berjalan, supaya koreksi tidak diblokir oleh jadwal pendaftaran.
25. Sebagai operator kabupaten, saya ingin melihat antrean sanggahan dengan ringkasan jumlah diajukan/dikabulkan/ditolak, supaya saya tahu beban kerja yang menunggu.
26. Sebagai operator kabupaten, saya ingin memfilter antrean berdasarkan periode, status, dan pencarian nomor pengajuan/nama, supaya saya bisa menemukan satu sanggahan dengan cepat.
27. Sebagai operator kabupaten, saya ingin membuka detail sanggahan yang memuat catatan TMS tiap dinas, dokumen yang dinilai tidak memenuhi, dan riwayat verifikasi, supaya saya bisa menilai klaim mahasiswa dengan bukti.
28. Sebagai operator kabupaten, saya ingin memutuskan Kabulkan atau Tolak dengan catatan wajib, supaya setiap keputusan punya alasan tertulis.
29. Sebagai operator kabupaten, saya ingin keputusan sanggahan bersifat final dan tidak bisa diubah, supaya tidak ada bolak-balik keputusan.
30. Sebagai operator kabupaten, saya ingin hanya melihat dan memutuskan sanggahan pada wilayah kabupaten saya, supaya batas kewenangan terjaga.
31. Sebagai operator kabupaten, saya ingin melihat badge "Hasil Sanggahan" pada tabel Penetapan Penerima, supaya saya sadar sedang menetapkan kandidat yang pernah TMS.
32. Sebagai operator kabupaten, saya ingin mempublikasikan hasil perbaikan tanpa menimpa SK lama, supaya dokumen resmi yang sudah terbit tetap utuh.
33. Sebagai operator kabupaten, saya ingin publikasi perbaikan hanya memproses kandidat yang belum pernah dipublikasikan, supaya penerima lama tidak menerima notifikasi ulang.
34. Sebagai operator kabupaten, saya ingin mendapat pesan jelas ketika tidak ada kandidat baru untuk dipublikasikan, supaya saya tidak mengira publikasi gagal karena error.
35. Sebagai pengunjung publik, saya ingin melihat tanggal masa sanggah yang sebenarnya di halaman pengumuman hasil, supaya saya tahu batas waktu tanpa menebak.
36. Sebagai pengunjung publik, saya ingin panel masa sanggah tetap tampil walau jadwal belum diatur, dengan label "Belum dijadwalkan", supaya tidak ada informasi yang hilang diam-diam.
37. Sebagai pengunjung publik, saya ingin panel menampilkan "Belum dimulai" atau "Telah berakhir" sesuai tanggal hari ini, supaya statusnya tidak menyesatkan.
38. Sebagai pengunjung publik, saya ingin tombol menuju halaman sanggahan tetap ada tetapi mengarah ke portal login, supaya jelas bahwa pengajuan wajib login.
39. Sebagai pengunjung publik, saya ingin hasil seleksi dari periode yang sudah ditutup tetap ditemukan lewat nomor pengajuan atau NIK, supaya pengumuman lama tidak hilang dari pencarian.
40. Sebagai pengunjung publik, saya ingin jadwal sanggah yang tampil adalah milik periode hasil yang saya cari, supaya tidak tertukar dengan periode lain yang sedang aktif.
41. Sebagai pengembang, saya ingin seluruh perilaku diuji lewat named route, supaya test tidak terikat detail implementasi service.
42. Sebagai pengembang, saya ingin istilah Masa Sanggah dan Sanggahan tercatat di glossary, supaya kode dan UI tidak memakai sinonim yang berbeda-beda.

## Implementation Decisions

### Data

- **Masa Sanggah** disimpan pada `periodes` sebagai dua kolom nullable: `tanggal_mulai_sanggah` dan `tanggal_selesai_sanggah`. Mengikuti preseden `tanggal_mulai_lpj`/`tanggal_selesai_lpj`, termasuk penggunaan tipe `date` (bukan `datetime`). Satu periode hanya punya satu jendela sanggah; tidak ada tabel jadwal terpisah.
- **Sanggahan** disimpan di tabel baru `sanggahans` dengan satu baris per application (unique constraint pada `application_id`). Kolom: `application_id`, `periode_id`, `submitted_by`, `status` (string + cast enum), `uraian` (text), `catatan_keputusan` (text nullable), `decided_by`, `decided_at`, timestamps.
- Status sanggahan adalah state machine tiga nilai: `DIAJUKAN → DIKABULKAN` atau `DIAJUKAN → DITOLAK`. Tidak ada transisi balik, tidak ada status "perlu perbaikan".
- Status `Application` **tidak** bertambah. Application tetap `TMS` selama sanggahan menunggu; status sanggahan adalah konsep terpisah.

### Perilaku jendela

- Jendela dianggap terbuka bila kedua tanggal terisi dan `today()` berada di antara keduanya (inklusif).
- Status periode **tidak** ikut menentukan terbukanya jendela secara langsung, tetapi pengajuan sanggahan mensyaratkan periode berstatus `aktif`, karena hasil sanggahan harus bisa diselesaikan lewat alur seleksi yang memang mensyaratkan periode aktif.
- Masa sanggah **hanya untuk periode aktif**. Superadmin tidak boleh mengubah status periode menjadi `ditutup` selama masih ada sanggahan `DIAJUKAN`, atau sanggahan `DIKABULKAN` yang application-nya belum final (`DITERIMA`/`DITOLAK`/`TMS`).

### Alur pengajuan (mahasiswa)

- Form sanggahan hidup di halaman `/mahasiswa/sanggahan` dengan controller sendiri, **bukan** di halaman Submit. Halaman Submit hanya menampilkan kartu TMS beserta tautan, karena halaman Submit mengambil data lewat service yang memfilter periode `aktif` **dan** tanggal pendaftaran berjalan, sehingga akan kehilangan konteks tepat saat masa sanggah dibuka.
- Syarat mengajukan: pemilik application, status `TMS`, periode application berstatus `aktif`, jendela terbuka, dan belum ada baris `sanggahans` untuk application tersebut.
- Uraian wajib, minimal 40 karakter dan maksimal 2000 karakter, dengan pernyataan kebenaran wajib dicentang.
- Tidak ada unggah berkas dan tidak ada perubahan dokumen. Ini aturan domain, bukan keterbatasan teknis: sanggahan hanya untuk kesalahan verifikator; kesalahan unggah berkas oleh mahasiswa tidak dapat diperbaiki lewat sanggahan.
- Setelah dikirim, isi tidak dapat diedit dan tidak dapat ditarik.
- Pengajuan menulis satu `VerificationLog` dengan action `sanggahan_submitted` dan mengirim notifikasi ke seluruh operator kabupaten di wilayah application tersebut.

### Alur keputusan (operator kabupaten)

- Operator kabupaten melihat antrean, membuka detail, lalu memilih Kabulkan atau Tolak. Catatan keputusan wajib untuk kedua pilihan, maksimal 2000 karakter.
- Keputusan bersifat final. Semua operator kabupaten pada wilayah yang sama boleh memutuskan; superadmin tidak, karena superadmin tidak memiliki akses ke data application pada aturan akses yang berlaku saat ini.
- **Kabulkan** berarti, dalam satu transaksi:
  1. Hapus baris `agency_verifications` milik dinas yang memutuskan TMS, beserta `document_verifications` dinas tersebut pada putaran verifikasi berjalan. Jejak tetap terekam karena model memakai trait audit, dan metadata log menyimpan keputusan yang dianulir.
  2. Kembalikan mutasi profil yang berasal dari keputusan TMS itu (status kependudukan Dukcapil dan desil sosial Dinas Sosial) agar penilaian ulang tidak mewarisi nilai lama.
  3. Ubah application ke `VERIFIKASI_DINAS`, kosongkan catatan, set `locked_at`.
  4. Set sanggahan ke `DIKABULKAN` beserta pemutus dan waktunya.
  5. Tulis `VerificationLog` action `sanggahan_granted` dengan metadata dinas dan keputusan yang dianulir.
  6. Notifikasi mahasiswa dan operator dinas terkait.
- **Tolak** berarti sanggahan menjadi `DITOLAK`, application tetap `TMS`, tulis log `sanggahan_rejected`, notifikasi mahasiswa.
- Satu application hanya bisa punya satu TMS pada satu waktu, sehingga "dinas yang salah" selalu tepat satu dinas. Bila dinas itu kembali memutuskan TMS, hasilnya final dan tidak ada sanggahan kedua.

### Gerbang yang dilonggarkan khusus hasil sanggahan

- Verifikasi ulang oleh dinas diizinkan bila application memiliki sanggahan `DIKABULKAN` dan periode application berstatus `aktif`, meskipun tanggal pendaftaran periode sudah lewat. Tanpa pelonggaran ini, verifikasi ulang selalu ditolak oleh aturan "pengajuan periode historis".
- Penetapan penerima oleh operator kabupaten mengizinkan application hasil sanggahan meskipun string `applications.periode` berbeda dari periode aktif yang dipakai konfigurasi aplikasi. Syaratnya: ada sanggahan `DIKABULKAN` pada application tersebut.
- Pelonggaran ini tidak boleh mengubah perilaku application lain: hanya berlaku ketika relasi sanggahan berstatus `DIKABULKAN` ada.

### Publikasi inkremental

- Perhitungan keputusan (kuota dan peringkat) tetap dilakukan untuk seluruh kandidat, agar hasil tidak berubah hanya karena publikasi bertahap.
- Penulisan hanya dilakukan pada kandidat yang `selection.published_at` masih kosong. Kandidat yang sudah pernah dipublikasikan tidak disentuh dan tidak menerima notifikasi ulang.
- Bila tidak ada kandidat baru, operator mendapat pesan yang menjelaskan bahwa tidak ada hasil baru untuk dipublikasikan.
- Publikasi pertama memakai slug pengumuman yang sudah ada. Publikasi perbaikan memakai slug berbeda sehingga SK lama tidak pernah ditimpa, dengan judul default yang menandai bahwa itu penetapan perbaikan.
- Nilai `manual_decision` dinormalisasi agar tidak peka huruf besar/kecil saat membaca keputusan yang tersimpan. Saat ini terdapat data berisi `diterima`/`ditolak` huruf kecil yang diam-diam jatuh ke `DITOLAK`.

### Halaman publik

- Pencarian hasil tidak lagi dibatasi pada periode aktif. Hasil dicari berdasarkan nomor pengajuan atau NIK di antara application yang sudah dipublikasikan, dan bila ada beberapa, yang terbaru yang ditampilkan.
- Panel masa sanggah selalu tampil. Bila tanggal kosong, label "Belum dijadwalkan" dan tombol nonaktif. Bila hari ini sebelum tanggal mulai, label "Belum dimulai". Bila setelah tanggal selesai, label "Masa sanggah telah berakhir". Teks "data simulasi" dan tanggal hardcode dihapus.
- Jadwal yang ditampilkan adalah milik periode hasil yang ditemukan, bukan periode aktif. Bila belum ada hasil yang dicari, panel memakai periode aktif terbaru sebagai konteks.

### Otorisasi dan penempatan

- Policy application mendapat dua kemampuan baru: mengajukan sanggahan (pemilik + TMS + jendela terbuka + belum ada sanggahan) dan memutuskan sanggahan (operator kabupaten + dapat melihat application).
- Menu "Sanggahan" ditambahkan di sidebar portal mahasiswa dan portal operator kabupaten, selalu tampil.
- Operator kabupaten mendapat badge "Hasil Sanggahan" pada tabel Penetapan Penerima dan pada halaman detail pengajuan, dengan tautan ke detail sanggahan.

### Istilah

- **Masa Sanggah**: jendela waktu milik periode. **Sanggahan**: keberatan milik satu application. Kedua istilah dicatat di glossary; UI memakai "Sanggahan" untuk menu dan dokumen, "Masa Sanggah" untuk jadwal.

## Testing Decisions

**Seam tunggal: HTTP lewat named route.** Semua perilaku diuji dari luar melalui route yang sudah ada atau yang baru, mengikuti pola `SelectionWorkflowTest` dan `SelectionDecisionTest`. Tidak ada unit test langsung ke service, tidak ada factory baru, dan tidak ada mocking. Model dan relasi dibuat langsung di dalam helper test seperti pola yang sudah dipakai proyek ini.

Yang membuat test baik di sini:

- Menguji perilaku yang bisa diamati pengguna dan kontrak: status HTTP, redirect, isi flash message, baris yang ada/tidak ada di database, status application setelah aksi, notifikasi yang terkirim, dan teks yang muncul di halaman.
- Tidak menguji nama method service, struktur internal transaksi, atau jumlah query.
- Untuk pengabulan, assertion utama adalah **efek yang terlihat**: application kembali ke VERIFIKASI_DINAS, keputusan dinas yang TMS hilang, keputusan dinas MS tetap ada, dan dinas bisa mengirim keputusan baru lewat route verifikasi yang sudah ada.

Modul/route yang diuji:

1. `PUT superadmin.periodes.update` — menyimpan dan memvalidasi tanggal sanggah; menolak penutupan periode dengan sanggahan menggantung.
2. `POST mahasiswa.sanggahan.store` — seluruh aturan kelayakan dan penulisan data.
3. `POST operator.sanggahan.decide` — kabul, tolak, catatan wajib, batas wilayah, finalitas.
4. `POST operator.applications.verify` — verifikasi ulang setelah kabul, termasuk skenario tanggal pendaftaran sudah lewat.
5. `POST operator.selection.publish` — publikasi inkremental dan SK lama utuh.
6. `GET operator.selection` — badge hasil sanggahan.
7. `GET public.results` — jadwal asli, label status, dan hasil periode tertutup.
8. `GET mahasiswa.submit` dan `GET mahasiswa.sanggahan` — kartu TMS, tombol, status sanggahan.

File test baru, mengikuti pola feature test yang ada:

- Test pengajuan sanggahan oleh mahasiswa.
- Test keputusan operator dan verifikasi ulang tertarget.
- Test jadwal masa sanggah pada periode (superadmin) termasuk guard penutupan.
- Test halaman publik dan publikasi inkremental.

Prior art: `SelectionWorkflowTest`, `SelectionDecisionTest`, `LpjAndBlacklistTest`, `SuperadminMasterDataTest`. Semua memakai `RefreshDatabase`, `RegionSeeder`, dan `User::factory()`.

## Out of Scope

- Sanggahan untuk keputusan `DITOLAK` hasil Seleksi Kabupaten yang sudah ber-SK.
- Unggah dokumen, unggah ulang berkas, atau perbaikan data pendaftaran lewat sanggahan.
- Sanggahan kedua, banding berjenjang, atau eskalasi ke superadmin.
- Keputusan operator berupa "minta perbaikan" atau pengembalian sanggahan untuk dilengkapi.
- Input sanggahan oleh operator atas nama pendaftar (kanal walk-in).
- Perubahan pada `ApplicationStatus` (tidak ada status baru).
- Perubahan pada alur verifikasi desa/kecamatan atau kebangkitan tahap yang sudah dihapus.
- Pengaturan jam (hanya tanggal) pada masa sanggah.
- Perubahan kuota, aturan skoring, atau aturan Perbup.
- Notifikasi email/WhatsApp; hanya notifikasi database yang sudah dipakai aplikasi.
- Penghapusan atau penarikan sanggahan oleh mahasiswa setelah dikirim.

## Further Notes

- **Risiko yang disadari:** karena masa sanggah hanya untuk periode `aktif`, penerbitan hasil seleksi suatu periode harus selesai sebelum periode itu ditutup. Guard pada form periode menegakkan ini secara eksplisit, sehingga kesalahan konfigurasi menghasilkan pesan yang jelas, bukan data menggantung.
- **Prasyarat data:** saat ini tidak ada satu application pun yang memiliki lebih dari satu keputusan TMS, sehingga asumsi "satu dinas yang salah" konsisten dengan perilaku sistem. Bila kelak konsensus dinas berubah menjadi paralel penuh, asumsi ini perlu ditinjau ulang.
- **Dua perbaikan ikutan** (pencarian hasil lintas periode dan publikasi inkremental) disertakan karena tanpa keduanya fitur sanggah tidak bisa tuntas: hasil sanggahan tidak akan pernah tampil di publik, dan satu koreksi akan menimpa SK resmi.
- **Berkas SK:** publikasi perbaikan selalu menerbitkan berkas SK baru, tetapi tidak pernah menimpa berkas SK lama. Berkas lama tetap dapat diunduh dari pengumuman periode yang asli.
- **Glossary dan ADR:** entri Masa Sanggah/Sanggahan ditambahkan ke `GLOSSARY.md`, dan keputusan "hanya TMS, teks saja, satu kali, hanya periode aktif" layak dicatat sebagai ADR karena sulit dibalik dan tidak jelas tanpa konteks.
- **Seam verifikasi dinas yang sudah ada** (`operator.applications.verify`) dipakai ulang untuk menguji verifikasi ulang, sehingga tidak perlu route atau mekanisme khusus untuk pengujian.
