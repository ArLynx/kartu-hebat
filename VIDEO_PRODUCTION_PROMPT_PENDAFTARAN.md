# PROMPT PRODUKSI VIDEO
## Panduan Pendaftaran Beasiswa Kartu Hebat Mahasiswa

**Versi:** 1.0 — November 2026  
**Basis:** Kode aplikasi Kartu Hebat Mahasiswa (alur mahasiswa, verifikasi, seleksi, hasil, dan LPJ).  
**Bahasa:** Indonesia.  
**Format keluaran:** 1 video orientasi + 8 episode kombinasi; dapat digabung menjadi satu video panjang berbab.  
**Rasio:** 16:9 (1920×1080, 30 fps) untuk YouTube/website; sediakan potongan 9:16 untuk Reels/TikTok.  
**Durasi:** video orientasi 8–10 menit; setiap episode 7–9 menit.

> **Cara membaca dokumen ini:** Bagian 2 adalah prompt global yang bisa langsung ditempel ke InVideo AI, Fliki, Pictory, atau alat video AI lain. Bagian 3 adalah bank scene wajib. Bagian 4 berisi naskah scene-by-scene untuk 8 kombinasi jalur dan jenis. Bagian 5–6 adalah checklist produksi dan catatan teknis untuk tim pengembang.

---

## 0. Ringkasan Produksi

### 0.1 Gaya Visual

- **Konsep:** tutorial layar bersih, modern, profesional, bernuansa layanan publik digital. Bukan iklan sinetron, bukan stock footage generik.
- **Palet:** navy `#0B1F3A`, biru `#1E40AF`, emas `#D4A017`, putih `#FFFFFF`, abu `#475569`.
- **Tipografi:** sans-serif tebal dan mudah dibaca (Inter, Plus Jakarta Sans, atau sejenisnya).
- **Elemen berulang:** kartu UI membulat, bayangan halus, progress bar “Langkah X dari 7”, kursor yang terlihat, klik ripple, toast sukses, dan timeline status.
- **Gerakan kamera:** slow push-in, pan halus, zoom ringan ke area yang diklik. Hindari gerakan cepat dan transisi flash.
- **Transisi:** soft wipe, slide, atau cut bersih. Durasi transisi 0,2–0,4 detik.
- **Suara:** musik latar inspirasional lembut, volume rendah, tanpa lirik saat VO. Efek suara klik dan sukses digunakan tipis-tipis.
- **Narator:** suara Indonesia yang hangat, jelas, tempo 150–160 kata per menit. Gunakan “Anda”, bukan “kamu”.
- **Subtitle:** aktif sejak awal, kontras tinggi, maksimal dua baris, tidak menutupi tombol penting.

### 0.2 Aturan Data dan Etika

- Gunakan **data contoh fiktif**: “Mahasiswa Contoh”, email `mahasiswa.contoh@gmail.com`, NIK disamarkan (`6401********1234`), dan nomor rekening fiktif.
- Jangan pernah menampilkan NIK, nomor KK, nomor rekening, alamat lengkap, atau dokumen pribadi milik orang nyata.
- Jangan menampilkan tanggal, kuota, atau nomor SK sebagai fakta final. Gunakan placeholder `[TANGGAL PERIODE]`, `[KUOTA RESMI]`, dan `[NOMOR SK]`.
- Jangan menampilkan fitur yang tidak ada di aplikasi. Contoh: tidak ada kolom “jenis disabilitas” atau “derajat disabilitas” untuk mahasiswa; jangan membuat layar palsu untuk itu.
- Jangan menampilkan email sebagai kanal pengumuman hasil. Notifikasi status dikirim **di dalam aplikasi**.
- Jangan menampilkan SKTM sebagai dokumen unggahan wajib. SKTM bukan dokumen wajib di tahap Dokumen.

### 0.3 Aturan “Tanpa Jalan Buntu”

Setiap scene harus berakhir dengan salah satu dari berikut:

1. Kursor menekan tombol yang terlihat.
2. Transisi ke layar berikutnya.
3. Toast sukses atau pesan validasi yang jelas.
4. Progress timeline yang menunjukkan langkah berikutnya.

Jangan biarkan frame statis lebih dari 3 detik tanpa gerakan halus. Jika layar menunggu, tampilkan progress bar, timeline verifikasi, atau notifikasi yang muncul — bukan layar kosong.

---

## 1. Peta Alur dan Tujuh Tahap Inti

### 1.1 Tujuh Tahap Video

| Tahap | Nama di video | Isi | Padanan di aplikasi |
|---:|---|---|---|
| 1 | **Pendaftaran Akun** | Membuat akun, mengisi nama, email, kata sandi, dan konfirmasi. | Halaman “Buat akun baru”. |
| 2 | **Login dan Verifikasi Email** | Membuka tautan verifikasi, masuk, dan mengenali dashboard. | Halaman verifikasi email dan “Masuk ke akun Anda”. |
| 3 | **Pemilihan Jalur** | Memilih Reguler atau Non Reguler. | Kartu “Pilih Kategori Beasiswa” di halaman Buat Pendaftaran. |
| 4 | **Pemilihan Jenis** | Memilih Akademik, Tidak Mampu, Disabilitas, atau Non-Akademik. | Kartu “Pilih Jenis Beasiswa”. |
| 5 | **Pengisian Data** | Data Pribadi, Pendidikan, Prestasi (khusus Non-Akademik), dan Orang Tua/Wali. | Wizard langkah 1–4. |
| 6 | **Unggah Dokumen dan Formulir** | 9 dokumen umum, dokumen khusus jenis, lalu Surat Permohonan dan Pakta Integritas yang sudah ditandatangani. | Wizard langkah 5–6. |
| 7 | **Review Akhir dan Submit** | Memeriksa ulang, mencentang pernyataan, mengirim, dan menyimpan bukti. | Wizard langkah 6–7. |

**Catatan penting:** Di aplikasi, wizard mahasiswa memiliki 7 langkah internal: Data Pribadi → Pendidikan → Prestasi (hanya Non-Akademik) → Orang Tua → Dokumen → Review → Submit. Video memakai 7 tahap versi penonton di atas agar mudah diikuti, tetapi semua layar dan tombolnya tetap mengikuti wizard asli.

**Istilah di layar:** Reguler/Non Reguler tampil sebagai **“Kategori Beasiswa”**, sedangkan empat pilihan tampil sebagai **“Jenis Beasiswa”**. Di panduan ini, Reguler/Non Reguler disebut **“jalur”** agar mudah dibedakan dari jenis.

### 1.2 Delapan Kombinasi

| No | Jalur | Jenis | Dokumen khusus | Dinas tambahan | Fokus seleksi | Kuota default |
|---:|---|---|---|---|---|---:|
| 1 | Reguler | Akademik | Tidak ada; 9 dokumen umum | — | IPK dan semester | 250 |
| 2 | Reguler | Non-Akademik | Sertifikat/SK di tahap Prestasi | Parsepor | Tier prestasi/organisasi | 30 |
| 3 | Reguler | Tidak Mampu | Tidak ada; SKTM hanya pendukung di luar sistem | Dinas Sosial | Desil sosial ekonomi | 250 |
| 4 | Reguler | Disabilitas | Surat Keterangan Disabilitas | Dinas Kesehatan | IPK, semester, data disabilitas | 50 |
| 5 | Non Reguler | Akademik | Tidak ada; 9 dokumen umum | — | IPK dan semester | 250 |
| 6 | Non Reguler | Non-Akademik | Sertifikat/SK di tahap Prestasi | Parsepor | Tier prestasi/organisasi | 30 |
| 7 | Non Reguler | Tidak Mampu | Tidak ada; SKTM hanya pendukung di luar sistem | Dinas Sosial | Desil sosial ekonomi | 250 |
| 8 | Non Reguler | Disabilitas | Surat Keterangan Disabilitas | Dinas Kesehatan | IPK, semester, data disabilitas | 50 |

**Dinas wajib untuk semua jenis:** Dukcapil, Disdikbud, dan BKPSDM.  
**Kuota default** diambil dari konfigurasi awal. Kuota resmi dapat berubah dan ditetapkan pengelola periode. Jalur menentukan kelompok pemeringkatan; jenis menentukan kuota, dokumen, dinas tambahan, dan dasar penilaian. Peringkat dikelompokkan per **kabupaten, jalur, dan jenis**.

### 1.3 Pilihan Tahap Prestasi (Hanya Non-Akademik)

| Kategori bukti | Kolom | Pilihan di aplikasi |
|---|---|---|
| Kompetisi/Lomba | Tingkat | Universitas, Kabupaten/Kota, Provinsi, Nasional, Internasional |
| Kompetisi/Lomba | Peringkat | Juara 1/Medali Emas, Juara 2/Medali Perak, Juara 3/Medali Perunggu, Juara Harapan, Kategori Khusus/Juara Favorit, Finalis/Peserta/Kontingen Kegiatan |
| Pengurus Organisasi Mahasiswa | Posisi | Ketua, Wakil Ketua, Sekretaris, Bendahara, Koordinator Bidang, Anggota Pengurus |
| Pengurus Organisasi Mahasiswa | Tingkat organisasi | Universitas, Fakultas |

### 1.4 Status Setelah Submit

```text
Draf
  │ submit
  ▼
Verifikasi Lintas Dinas
  │
  ├── ada dinas TMS ──► TMS ──► sanggahan (sekali, jika masa sanggah terbuka)
  │                              ├── dikabulkan ──► verifikasi ulang
  │                              └── ditolak ─────► TMS final
  │
  └── semua dinas MS
          ▼
   Seleksi Kabupaten
          │ publikasi hasil + SK
          ▼
   Diterima / Ditolak
```

- **TMS** = Tidak Memenuhi Syarat. Bisa disanggah satu kali selama masa sanggah.
- **Ditolak** = tidak masuk kuota penerima. Bukan TMS; tidak dapat disanggah.
- Hasil publik hanya menampilkan **Diterima** dan **Ditolak** yang sudah dipublikasikan. TMS dicek di dashboard dan menu Sanggahan.

---

## 2. Prompt Global untuk AI Video

Tempel blok berikut ke InVideo AI, Fliki, atau alat video AI lain. Setelah itu, tempel tabel episode dari Bagian 4.

```text
ANDA ADALAH PRODUSER DAN PENULIS NASKAH VIDEO TUTORIAL PROFESIONAL.

Buat video tutorial berbahasa Indonesia untuk mahasiswa calon penerima beasiswa
Kartu Hebat Mahasiswa Kabupaten Murung Raya. Video harus terasa seperti panduan
resmi yang ramah, bukan iklan dan bukan presentasi korporat yang kaku.

TUJUAN
Menuntun mahasiswa dari pembuatan akun sampai menunggu hasil seleksi, dengan
delapan variasi jalur dan jenis beasiswa. Penonton harus paham apa yang harus
diklik, data apa yang harus diisi, dokumen apa yang harus diunggah, dan apa yang
terjadi setelah submit.

AUDIENS
Mahasiswa aktif yang baru pertama kali mendaftar. Sebagian belum terbiasa dengan
istilah administrasi. Jelaskan istilah penting sekali saja, lalu gunakan konsisten.

BAHASA
Bahasa Indonesia baku tetapi luwes. Gunakan "Anda". Hindari istilah teknis tanpa
penjelasan. Hindari singkatan yang tidak dijelaskan pada kemunculan pertama.

FORMAT
16:9, 1920x1080, 30 fps, subtitle Indonesia aktif. Sediakan versi 9:16 untuk
potongan pendek. Durasi 7-9 menit per episode. Setiap scene 5-25 detik.

GAYA VISUAL
Tutorial layar bersih dan modern. Palet navy #0B1F3A, biru #1E40AF, emas #D4A017,
putih, dan abu #475569. Kartu UI membulat, bayangan halus, tipografi sans-serif
tebal, kursor terlihat, klik ripple, toast sukses. Gunakan screen recording atau
mockup UI yang persis mengikuti label aplikasi. Jangan memakai stock footage
generik mahasiswa kecuali sebagai cutaway 1-2 detik. Jangan membuat layar yang
tidak ada di aplikasi.

STRUKTUR WAJIB
Setiap episode mengikuti 7 tahap inti:
1. Pendaftaran Akun
2. Login dan Verifikasi Email
3. Pemilihan Jalur (Reguler/Non Reguler)
4. Pemilihan Jenis (Akademik/Tidak Mampu/Disabilitas/Non-Akademik)
5. Pengisian Data (Data Pribadi, Pendidikan, Prestasi bila ada, Orang Tua/Wali)
6. Unggah Dokumen dan Formulir
7. Review Akhir dan Submit

Setelah itu tambahkan scene "Setelah Submit" yang menjelaskan verifikasi dinas,
seleksi, hasil, sanggahan, dan LPJ.

ATURAN AKURASI
- Jangan mengubah label tombol. Gunakan label persis seperti di aplikasi:
  "Buat akun baru", "Daftar Akun", "Masuk ke Sistem", "Buat Pendaftaran",
  "Buat Draft Pendaftaran", "Simpan & Lanjut", "Konfirmasi Review & Lanjut ke
  Submit", "Submit Pendaftaran".
- Jalur dan jenis beasiswa dikunci setelah draf dibuat. Satu akun hanya boleh
  memiliki satu pendaftaran pada periode aktif.
- Dokumen umum berjumlah 9: KTP, KK, KTM, Sertifikat Akreditasi Perguruan
  Tinggi, Buku Rekening Bank Kalteng, Surat Keterangan Aktif Kuliah, Tangkapan
  Layar PDDIKTI, Tagihan UKT Semester Terakhir, dan KHS/Transkrip Nilai.
- Disabilitas menambah Surat Keterangan Disabilitas menjadi 10 dokumen.
- Non-Akademik mengunggah sertifikat/SK di tahap Prestasi, bukan di tahap Dokumen.
- Dokumen menerima PDF, JPG, JPEG, atau PNG, maksimal 2 MB per file.
- Surat Permohonan dan Pakta Integritas wajib diunggah sebagai PDF, maksimal 2 MB.
- Pakta Integritas ditandatangani mahasiswa dan orang tua/wali. Mahasiswa wajib
  membubuhkan materai Rp10.000 pada tanda tangan "yang membuat pernyataan".
  Orang tua/wali tidak perlu materai.
- Notifikasi status hanya muncul di dalam aplikasi, bukan email.
- Hasil publik hanya menampilkan Diterima dan Ditolak yang sudah dipublikasikan.
  TMS tidak muncul di halaman hasil publik.
- Sanggahan hanya untuk TMS, satu kali, selama masa sanggah.
- LPJ hanya untuk mahasiswa yang diterima. LPJ yang belum disetujui dapat
  memblokir NIK pada periode berikutnya.

ATURAN VISUAL
- Setiap scene harus bergerak: kursor, zoom, transisi, progress, atau teks masuk.
- Jangan ada layar kosong. Saat menunggu, tampilkan timeline verifikasi,
  progress bar, atau notifikasi.
- Saat menampilkan error, tampilkan pesan persis dan langkah pemulihannya.
- Saat menampilkan dokumen, sorot nama file, ukuran, dan status unggah.
- Gunakan data contoh fiktif. Blur atau samarkan NIK, KK, rekening, dan alamat.
- Tampilkan progress "Langkah X dari 7" di sudut layar.

OUTPUT YANG DIMINTA
Untuk setiap scene, tulis:
- Nomor dan durasi scene.
- Visual Prompt Description: apa yang terlihat, apa yang diklik, teks di layar,
  gerakan kamera, dan transisi.
- Voiceover (VO): narasi Indonesia yang natural, 2-5 kalimat.
- Teks di layar: maksimal 6 kata per baris.
- SFX/transisi: sebutkan jika perlu.

Nada akhir: menenangkan, jelas, dan memberi keyakinan bahwa proses dapat
diselesaikan langkah demi langkah.
```

### 2.1 Placeholder Episode

Saat membuat episode, isi placeholder berikut:

| Placeholder | Isi |
|---|---|
| `[JALUR]` | Reguler atau Non Reguler |
| `[JENIS]` | Akademik, Non-Akademik, Tidak Mampu, atau Disabilitas |
| `[DESKRIPSI_JALUR]` | Tatap muka atau daring/jarak jauh |
| `[DOKUMEN_KHUSUS]` | Tidak ada, sertifikat/SK prestasi, SKTM pendukung, atau surat disabilitas |
| `[DINAS_TAMBAHAN]` | Tidak ada, Parsepor, Dinas Sosial, atau Dinas Kesehatan |
| `[FOKUS_SELEKSI]` | IPK/semester, tier prestasi, desil, atau data disabilitas |
| `[KUOTA]` | Kuota resmi; gunakan `[KUOTA RESMI]` jika belum pasti |

### 2.2 Template Prompt per Episode

```text
Buat Episode [N] dari serial Panduan Pendaftaran Beasiswa Kartu Hebat Mahasiswa.
JALUR: [JALUR]
JENIS: [JENIS]
DESKRIPSI JALUR: [DESKRIPSI_JALUR]
DOKUMEN KHUSUS: [DOKUMEN_KHUSUS]
DINAS TAMBAHAN: [DINAS_TAMBAHAN]
FOKUS SELEKSI: [FOKUS_SELEKSI]
KUOTA: [KUOTA]

Gunakan struktur 7 tahap inti dan bank scene S00-S14. Pertahankan label UI persis.
Gunakan data contoh fiktif dan samarkan data pribadi. Akhiri setiap scene dengan
aksi atau transisi yang jelas. Buat narasi Indonesia yang hangat dan mudah diikuti.
```

---

## 3. Bank Scene Wajib (Video Orientasi)

Bank scene ini adalah aset visual bersama. Rekam sekali, pakai di semua episode. Episode di Bagian 4 menambahkan VO dan penekanan khusus.

### S00 — Opening dan Janji Video

- **Visual Prompt:** Layar biru navy dengan wordmark “Kartu Hebat Mahasiswa”. Muncul tiga kartu: “Reguler”, “Non Reguler”, dan “4 Jenis Beasiswa”. Kursor menyorot alur dari akun sampai hasil. Teks di layar: “Dari akun sampai hasil, langkahnya jelas.”
- **VO:** “Ingin mendaftar beasiswa Kartu Hebat Mahasiswa, tetapi bingung mulai dari mana? Video ini menuntun Anda dari membuat akun, memilih jalur dan jenis, melengkapi dokumen, sampai menunggu hasil seleksi. Semua langkah mengikuti aplikasi resmi.”
- **Transisi:** Zoom ke dashboard aplikasi.

### S01 — Pendaftaran Akun

- **Visual Prompt:** Halaman “Buat akun baru”. Kursor mengisi “Nama Lengkap”, “Email Aktif”, “Kata Sandi”, dan “Konfirmasi Kata Sandi”. Sorot teks bantuan password. Klik “Daftar Akun”. Muncul halaman “Verifikasi Email Anda”.
- **VO:** “Langkah pertama, buat akun. Isi nama lengkap sesuai identitas resmi, email aktif yang benar-benar Anda kuasai, dan kata sandi minimal delapan karakter dengan huruf besar, huruf kecil, dan simbol. Gunakan email Gmail, Yahoo, Outlook, atau email kampus yang diizinkan. Setelah menekan Daftar Akun, sistem akan meminta verifikasi email.”
- **Teks layar:** “Satu mahasiswa, satu akun.”
- **Transisi:** Cut ke kotak masuk email.

### S02 — Verifikasi Email

- **Visual Prompt:** Kotak masuk email. Sorot email dari Kartu Hebat Mahasiswa. Klik tautan verifikasi. Browser membuka halaman “Email terverifikasi”. Jika tautan tidak masuk, sorot tombol “Kirim Ulang Email Verifikasi” dan opsi “Perbaiki Alamat Email”.
- **VO:** “Buka kotak masuk, cari email verifikasi, dan klik tautannya. Jika belum menemukan, periksa folder Spam atau Promosi, lalu kirim ulang email verifikasi. Tanpa verifikasi, dashboard pendaftaran belum bisa dibuka.”
- **Teks layar:** “Klik tautan verifikasi.”
- **Transisi:** Fade ke halaman login.

### S03 — Login

- **Visual Prompt:** Halaman “Masuk ke akun Anda”. Kursor mengisi email dan kata sandi, lalu klik “Masuk ke Sistem”. Muncul dashboard “Dashboard Pendaftaran Beasiswa”.
- **VO:** “Setelah email terverifikasi, masuk dengan email dan kata sandi Anda. Jika sesi berakhir, Anda bisa masuk kembali kapan saja. Di dashboard, perhatikan periode aktif dan status pendaftaran Anda.”
- **Teks layar:** “Masuk ke Sistem.”
- **Transisi:** Pan ke kartu periode.

### S04 — Orientasi Dashboard

- **Visual Prompt:** Dashboard dengan kartu periode aktif, tombol “Daftar Periode [tahun]” atau “Buat Pendaftaran”, status verifikasi, notifikasi, dan riwayat. Sorot masing-masing bagian satu per satu.
- **VO:** “Dashboard adalah pusat kendali Anda. Di sini terlihat periode yang sedang dibuka, nomor pendaftaran, status verifikasi, notifikasi, dan riwayat periode sebelumnya. Jika belum ada pendaftaran pada periode aktif, tombol Buat Pendaftaran akan muncul. Perlu diingat: jika Anda pernah diterima pada periode sebelumnya dan LPJ belum disetujui, sistem akan menahan pembuatan pendaftaran baru sampai LPJ diverifikasi admin.”
- **Teks layar:** “Periode aktif · Status · Notifikasi · LPJ.”
- **Transisi:** Klik “Buat Pendaftaran”.

### S05 — Pemilihan Jalur (Kategori)

- **Visual Prompt:** Halaman “Buat Pendaftaran Beasiswa”. Dua kartu besar: “Reguler” dan “Non Reguler”. Kursor menyorot kartu yang dipilih. Muncul deskripsi: tatap muka atau daring/jarak jauh. Kartu berubah warna dan tanda centang muncul.
- **VO:** “Di halaman ini, pilih jalur sesuai cara Anda menjalani perkuliahan. Reguler untuk perkuliahan tatap muka. Non Reguler untuk perkuliahan jarak jauh atau daring. Pilih yang benar-benar sesuai kondisi Anda, karena pilihan ini akan mengunci pengelompokan pemeringkatan Anda.”
- **Teks layar:** “Pilih sesuai metode kuliah.”
- **Transisi:** Scroll ke bagian jenis.

### S06 — Pemilihan Jenis

- **Visual Prompt:** Empat kartu jenis: “Beasiswa Prestasi Akademik”, “Beasiswa Tidak Mampu”, “Beasiswa Mahasiswa Disabilitas”, “Beasiswa Prestasi Non Akademik”. Kursor menyorot satu kartu. Muncul deskripsi singkat. Centang pernyataan, lalu klik “Buat Draft Pendaftaran”. Muncul peringatan bahwa jalur dan jenis tidak dapat diubah.
- **VO:** “Setelah jalur, pilih jenis beasiswa yang paling sesuai dengan kondisi dan bukti yang Anda miliki. Baca deskripsinya. Setelah yakin, centang pernyataan dan klik Buat Draft Pendaftaran. Perlu diingat: jalur dan jenis tidak dapat diubah setelah draf dibuat, dan satu akun hanya boleh memiliki satu pendaftaran pada periode aktif.”
- **Teks layar:** “Jalur + jenis terkunci setelah draf.”
- **Transisi:** Toast nomor pendaftaran, lalu masuk ke Data Pribadi.

### S07 — Data Pribadi

- **Visual Prompt:** Form “Data Pribadi”. Kursor mengisi NIK, Nomor KK, Nomor Rekening Bank Kalteng, Nama Lengkap, Nomor HP/WhatsApp, Tempat Lahir, Tanggal Lahir, Jenis Kelamin, Agama, Alamat, Provinsi, dan Kode Pos. Sorot kolom pencarian Desa/Kelurahan; pilih desa dari daftar. Kabupaten dan kecamatan terisi otomatis. Klik “Simpan & Lanjut ke Pendidikan”.
- **VO:** “Isi data pribadi dengan teliti. NIK dan nomor KK harus 16 digit. Satu NIK hanya boleh terdaftar pada satu akun; jika muncul pesan bahwa NIK sudah digunakan, masuk dengan akun yang sesuai dan jangan membuat akun ganda. Nomor rekening harus rekening Bank Kalteng atas nama Anda sendiri. Untuk desa atau kelurahan, ketik nama lalu pilih dari daftar master wilayah; jangan mengetik nama desa secara bebas. Kabupaten dan kecamatan akan terisi otomatis. Periksa sekali lagi sebelum menyimpan.”
- **Teks layar:** “NIK 16 digit · Satu NIK, satu akun.”
- **Transisi:** Slide ke Pendidikan.

### S08 — Pendidikan

- **Visual Prompt:** Form “Pendidikan”. Kursor mengisi NIM, Universitas, Status Perguruan Tinggi, Fakultas, Jurusan, Program Studi, Jenjang, Semester, IPK, Nominal UKT, Tahun Masuk, Akreditasi Perguruan Tinggi, dan Alamat Perguruan Tinggi. Sorot bantuan “isi - jika tidak ada jurusan/prodi”. Klik “Simpan & Lanjut”.
- **VO:** “Lanjut ke data pendidikan. Isi NIM, nama kampus, status negeri atau swasta, fakultas, jurusan, program studi, jenjang, semester, IPK, nominal UKT, tahun masuk, akreditasi, dan alamat kampus. Jika kampus tidak memiliki jurusan atau program studi, isi tanda minus sesuai petunjuk. Pastikan IPK dan semester sesuai KHS terakhir.”
- **Teks layar:** “Samakan dengan KHS dan KTM.”
- **Transisi:** Jika Non-Akademik, ke Prestasi. Jika bukan, ke Orang Tua.

### S09 — Prestasi (Khusus Non-Akademik)

- **Visual Prompt:** Halaman “Prestasi”. Dua pilihan kategori bukti: “Kompetisi/Lomba” dan “Pengurus Organisasi Mahasiswa”. Untuk lomba, tampilkan pilihan tingkat: Universitas, Kabupaten/Kota, Provinsi, Nasional, Internasional; dan peringkat: Juara 1/Medali Emas, Juara 2/Perak, Juara 3/Perunggu, Juara Harapan, Kategori Khusus/Juara Favorit, Finalis/Peserta/Kontingen. Untuk organisasi, tampilkan posisi: Ketua, Wakil Ketua, Sekretaris, Bendahara, Koordinator Bidang, Anggota Pengurus; serta tingkat organisasi: Universitas atau Fakultas. Kursor mengisi nama, penyelenggara, tahun, dan mengunggah sertifikat/SK. Klik “Tambah ke Daftar”.
- **VO:** “Tahap Prestasi hanya muncul untuk jenis Non-Akademik. Tambahkan minimal satu prestasi lomba atau SK kepengurusan organisasi. Untuk lomba, pilih tingkat dari Universitas sampai Internasional dan peringkat dari Juara 1 sampai Finalis atau Peserta. Untuk organisasi, pilih posisi dari Ketua sampai Anggota Pengurus serta tingkat Universitas atau Fakultas. Isi nama kegiatan atau organisasi, penyelenggara, tahun, dan unggah sertifikat atau SK. Setelah semua data punya dokumen, klik Tambah ke Daftar lalu konfirmasi untuk melanjutkan.”
- **Teks layar:** “Minimal 1 prestasi atau SK.”
- **Transisi:** Slide ke Orang Tua.

### S10 — Orang Tua/Wali

- **Visual Prompt:** Form “Orang Tua/Wali”. Bagian Ayah dan Ibu: nama, status hidup/meninggal, NIK, pekerjaan, pekerjaan lainnya, dan jabatan jika ASN. Jika meninggal, kolom NIK dan pekerjaan dinonaktifkan. Centang “Memiliki Wali” bila ada; isi nama, NIK, pekerjaan, dan jabatan wali. Klik “Simpan & Lanjut ke Dokumen”.
- **VO:** “Isi data ayah dan ibu sesuai kondisi sebenarnya. Jika salah satu orang tua meninggal, pilih Meninggal Dunia; NIK dan pekerjaan tidak perlu diisi untuk yang meninggal. Jika Anda memiliki wali, centang dan lengkapi datanya. Data orang tua juga dipakai pada Pakta Integritas, jadi pastikan namanya benar.”
- **Teks layar:** “Isi sesuai kondisi sebenarnya.”
- **Transisi:** Slide ke Dokumen.

### S11 — Dokumen

- **Visual Prompt:** Halaman “Dokumen” dengan daftar 9 dokumen. Setiap baris punya tombol “Pilih File” dan “Unggah”. Kursor mengunggah KTP, KK, KTM, Sertifikat Akreditasi, Buku Rekening, Surat Aktif Kuliah, Screenshot PDDIKTI, Tagihan UKT, dan KHS. Setiap file yang berhasil berubah menjadi centang hijau. Sorot tautan “Lihat”, tombol “Ganti”, dan “Hapus dokumen”; tampilkan juga nama file, ukuran, dan format.
- **VO:** “Sekarang unggah dokumen. Ada sembilan dokumen umum: KTP, KK, KTM, sertifikat akreditasi perguruan tinggi, buku rekening Bank Kalteng, surat keterangan aktif kuliah, tangkapan layar PDDIKTI, tagihan UKT semester terakhir, dan KHS atau transkrip nilai. Format yang diterima PDF, JPG, JPEG, atau PNG, maksimal dua megabita per dokumen. Setelah terunggah, Anda bisa memeriksa file lewat tautan Lihat, mengganti dengan tombol Ganti, atau menghapusnya lalu mengunggah ulang. Pastikan semua dokumen terbaca dan sesuai nama Anda.”
- **Teks layar:** “9 dokumen · Maks. 2 MB · Lihat/Ganti/Hapus.”
- **Transisi:** Semua centang hijau, lalu ke Review.

### S12 — Review dan Formulir

- **Visual Prompt:** Halaman “Review”. Kursor memeriksa ringkasan data. Sorot catatan bahwa tombol unduh formulir aktif setelah Data Pribadi, Pendidikan, dan Orang Tua/Wali lengkap. Klik “Unduh Surat Permohonan” dan “Unduh Pakta Integritas”. Cutaway: printer mencetak, tangan menandatangani, materai Rp10.000 ditempel pada Pakta Integritas. Kembali ke layar, unggah kedua formulir dalam PDF. Klik “Konfirmasi Review & Lanjut ke Submit”. Tampilkan toast bahwa mengubah data atau mengganti formulir akan mereset konfirmasi review.
- **VO:** “Di tahap Review, periksa kembali seluruh data. Tombol unduh formulir baru aktif setelah Data Pribadi, Pendidikan, dan Orang Tua atau Wali lengkap. Jika ada yang salah, klik Perbaiki pada bagian tersebut. Setelah benar, unduh Surat Permohonan dan Pakta Integritas, cetak, lalu tandatangani. Pada Pakta Integritas, mahasiswa dan orang tua atau wali menandatangani; mahasiswa wajib membubuhkan materai sepuluh ribu rupiah pada tanda tangan yang membuat pernyataan. Unggah kembali kedua formulir sebagai PDF maksimal dua megabita, lalu konfirmasi review. Ingat, jika Anda mengubah data atau mengganti formulir setelah konfirmasi, konfirmasi review akan direset dan harus diulang.”
- **Teks layar:** “Formulir aktif setelah data lengkap · Konfirmasi bisa direset.”
- **Transisi:** Slide ke Submit.

### S13 — Submit

- **Visual Prompt:** Halaman “Submit Pendaftaran”. Checklist semua tahap bercentang. Kursor mencentang “Saya menyatakan bahwa seluruh data dan dokumen yang saya sampaikan benar, sah, dan dapat dipertanggungjawabkan” dan “Saya memahami bahwa setelah pendaftaran dikirim, seluruh data dan dokumen akan dikunci.” Klik “Submit Pendaftaran”. Muncul dialog konfirmasi. Klik “Ya”. Muncul halaman “Bukti Pendaftaran” dengan nomor pendaftaran.
- **VO:** “Di halaman Submit, baca checklist terakhir. Centang dua pernyataan: data yang Anda sampaikan benar, dan Anda memahami data akan dikunci setelah dikirim. Klik Submit Pendaftaran, lalu konfirmasi. Setelah berhasil, simpan nomor pendaftaran dan unduh bukti pendaftaran sebagai arsip.”
- **Teks layar:** “Data akan dikunci setelah submit.”
- **Transisi:** Cut ke dashboard dengan status baru.

### S14 — Setelah Submit: Verifikasi, Hasil, dan LPJ

- **Visual Prompt:** Dashboard dengan status “Verifikasi Lintas Dinas”. Timeline menampilkan Dukcapil, Disdikbud, BKPSDM, dan dinas tambahan, dengan label “durasi verifikasi bervariasi”. Notifikasi muncul di lonceng. Cut ke halaman TMS dan menu “Sanggahan” dengan kolom uraian dan penghitung karakter. Cut ke halaman “Pengumuman Hasil Seleksi”, input nomor pengajuan/NIK, hasil “Diterima”. Cut ke halaman “Pengumuman” dengan SK. Cut ke menu “LPJ Beasiswa” dengan unggah bukti UKT.
- **VO:** “Setelah submit, status berubah menjadi Verifikasi Lintas Dinas. Dukcapil, Disdikbud, dan BKPSDM akan memeriksa berkas Anda; jenis beasiswa tertentu menambah Dinas Sosial, Dinas Kesehatan, atau Parsepor. Durasi verifikasi tidak sama untuk setiap pendaftar karena bergantung pada antrean dan kelengkapan berkas di masing-masing dinas, jadi pantau dashboard dan notifikasi di dalam aplikasi. Jika semua dinas menyatakan Memenuhi Syarat atau MS, pengajuan masuk Seleksi Kabupaten. Jika ada dinas menyatakan Tidak Memenuhi Syarat atau TMS, buka menu Sanggahan dan ajukan sanggahan satu kali selama masa sanggah dengan uraian 40 sampai 2.000 karakter; keputusan atas sanggahan bersifat final. Hasil akhir bisa dicek di dashboard dan halaman Pengumuman Hasil. Jika diterima, Anda wajib mengunggah LPJ agar tidak diblokir pada periode berikutnya.”
- **Teks layar:** “Durasi bervariasi · Sanggah sekali · Unggah LPJ.”
- **Transisi:** Fade ke closing.

---

## 4. Naskah Scene-by-Scene untuk Delapan Kombinasi

Setiap episode memakai bank scene S00-S14. Visual Prompt di bawah ini adalah penyesuaian khusus episode; VO harus dibacakan persis sebagai narasi utama.

---

### 4.1 Episode 1 — Jalur Reguler: Beasiswa Akademik

**Metadata**

| Item | Keterangan |
|---|---|
| Judul | “Reguler — Akademik: Daftar Beasiswa dari Awal sampai Hasil” |
| Target | Mahasiswa Reguler dengan prestasi akademik |
| Durasi | 8 menit |
| Dokumen khusus | Tidak ada; 9 dokumen umum |
| Dinas tambahan | Tidak ada |
| Fokus seleksi | IPK dan semester; tie-break tahun masuk dan akreditasi |
| Kuota default | 250 |

**Script**

| Scene | Visual Prompt Description | Voiceover (VO) |
|---|---|---|
| **SCENE 1 — Hook Kombinasi**<br>0:00–0:15 | Kartu judul “Reguler — Akademik”. Latar biru navy, ikon toga dan kartu KHS. Teks: “IPK jadi inti, tetapi dokumen tetap lengkap.” Kursor menyorot alur 7 langkah. | “Anda memilih jalur Reguler dengan jenis Beasiswa Prestasi Akademik. Jalur ini menilai IPK dan semester Anda, tetapi tetap mewajibkan sembilan dokumen umum. Ikuti tujuh langkah berikut agar pendaftaran Anda tidak terhenti.” |
| **SCENE 2 — Tahap 1: Pendaftaran Akun**<br>0:15–0:50 | Gunakan bank S01. Tambahkan label episode “Reguler — Akademik” di sudut atas. Kursor mengisi nama, email, password, konfirmasi, lalu klik “Daftar Akun”. | “Mulai dari membuat akun. Isi nama lengkap sesuai identitas, email aktif, dan kata sandi yang kuat. Gunakan email yang benar-benar Anda buka. Setelah menekan Daftar Akun, lanjutkan ke verifikasi email.” |
| **SCENE 3 — Tahap 2: Login dan Verifikasi Email**<br>0:50–1:20 | Gunakan bank S02–S04. Sorot email verifikasi, tautan, lalu dashboard. Sorot tombol “Daftar Periode [tahun]”. | “Buka email, klik tautan verifikasi, lalu masuk ke dashboard. Pastikan periode aktif sudah muncul. Klik Daftar Periode untuk memulai pendaftaran baru.” |
| **SCENE 4 — Tahap 3: Pemilihan Jalur**<br>1:20–1:45 | Halaman “Buat Pendaftaran Beasiswa”. Sorot kartu “Reguler”. Tampilkan deskripsi “perkuliahan tatap muka”. Kartu terpilih dengan centang biru. | “Di halaman Buat Pendaftaran, pilih kartu Reguler. Jalur ini untuk perkuliahan tatap muka sesuai jadwal kampus. Pilihan jalur akan mengunci kelompok pemeringkatan Anda, jadi pastikan sudah benar.” |
| **SCENE 5 — Tahap 4: Pemilihan Jenis**<br>1:45–2:15 | Sorot kartu “Beasiswa Prestasi Akademik”. Tampilkan deskripsi “berdasarkan IPK dan semester aktif”. Centang pernyataan, klik “Buat Draft Pendaftaran”. Muncul peringatan terkunci dan nomor pendaftaran. | “Pilih jenis Beasiswa Prestasi Akademik. Jenis ini menilai IPK dan semester aktif. Centang pernyataan, lalu klik Buat Draft Pendaftaran. Setelah draf dibuat, jalur dan jenis tidak dapat diubah.” |
| **SCENE 6 — Tahap 5: Pengisian Data**<br>2:15–4:15 | Bank S07–S10. Sorot NIK, KK, rekening, desa/kelurahan. Di Pendidikan, sorot NIM, IPK, semester, UKT, akreditasi. Di Orang Tua, sorot status hidup/meninggal dan NIK. Klik “Simpan & Lanjut” setiap tahap. | “Isi Data Pribadi: NIK dan KK 16 digit, rekening Bank Kalteng atas nama sendiri, alamat, dan desa/kelurahan dari daftar. Lanjut ke Pendidikan: NIM, kampus, jurusan, program studi, jenjang, semester, IPK, UKT, tahun masuk, dan akreditasi. Untuk jalur Akademik, IPK dan semester harus sesuai KHS. Lengkapi juga data orang tua atau wali.” |
| **SCENE 7 — Tahap 6: Unggah Dokumen dan Formulir**<br>4:15–5:45 | Bank S11–S12. Tampilkan checklist 9 dokumen. Sorot KHS/transkrip. Semua berubah centang hijau. Lalu unduh Surat Permohonan dan Pakta Integritas, tampilkan tanda tangan dan materai, unggah PDF, klik konfirmasi. | “Unggah sembilan dokumen: KTP, KK, KTM, sertifikat akreditasi, buku rekening Bank Kalteng, surat aktif kuliah, tangkapan layar PDDIKTI, tagihan UKT, dan KHS atau transkrip. Format PDF atau gambar, maksimal dua megabita. Setelah semua lengkap, unduh Surat Permohonan dan Pakta Integritas, cetak, tandatangani, beri materai sepuluh ribu pada Pakta, lalu unggah kembali sebagai PDF.” |
| **SCENE 8 — Tahap 7: Review Akhir dan Submit**<br>5:45–7:15 | Bank S12–S13. Sorot ringkasan data, tombol “Perbaiki”, formulir yang sudah diunggah, dua checkbox, tombol “Submit Pendaftaran”, dialog konfirmasi, halaman Bukti Pendaftaran. | “Di Review, periksa nama, NIK, kampus, IPK, dan data orang tua. Jika ada kesalahan, klik Perbaiki dan ulangi unggah formulir. Setelah benar, buka Submit, centang dua pernyataan, lalu klik Submit Pendaftaran. Data akan dikunci. Simpan nomor pendaftaran dan bukti pendaftaran.” |
| **SCENE 9 — Setelah Submit**<br>7:15–8:00 | Bank S14. Timeline Dukcapil, Disdikbud, BKPSDM. Status “Verifikasi Lintas Dinas”, lalu “Seleksi Kabupaten”. Tampilkan halaman hasil dan menu LPJ. | “Setelah submit, Dukcapil, Disdikbud, dan BKPSDM memeriksa berkas Anda. Jika semua menyatakan MS, Anda masuk Seleksi Kabupaten. Peringkat dihitung dari IPK, lalu tahun masuk dan akreditasi jika skor sama. Pantau dashboard dan notifikasi. Jika diterima, unggah LPJ agar tidak diblokir periode berikutnya.” |

---

### 4.2 Episode 2 — Jalur Reguler: Beasiswa Non-Akademik

**Metadata**

| Item | Keterangan |
|---|---|
| Judul | “Reguler — Non-Akademik: Prestasi dan Organisasi Jadi Nilai Tambah” |
| Target | Mahasiswa Reguler dengan prestasi lomba atau SK ormawa |
| Durasi | 8,5 menit |
| Dokumen khusus | Sertifikat/SK di tahap Prestasi |
| Dinas tambahan | Parsepor |
| Fokus seleksi | Tier prestasi/organisasi; tie-break tahun masuk dan akreditasi |
| Kuota default | 30 |

**Script**

| Scene | Visual Prompt Description | Voiceover (VO) |
|---|---|---|
| **SCENE 1 — Hook Kombinasi**<br>0:00–0:15 | Kartu “Reguler — Non-Akademik”. Ikon piala, sertifikat, dan SK ormawa. Teks: “Prestasi bukan cuma nilai.” Kursor menyorot tahap Prestasi yang muncul di antara Pendidikan dan Orang Tua. | “Anda memilih jalur Reguler dengan jenis Beasiswa Prestasi Non-Akademik. Di sini, sertifikat lomba atau SK kepengurusan organisasi menjadi bukti utama. Tahap Prestasi akan muncul khusus untuk jenis ini.” |
| **SCENE 2 — Tahap 1: Pendaftaran Akun**<br>0:15–0:50 | Bank S01 dengan label episode. Kursor mengisi formulir, klik “Daftar Akun”, lalu halaman verifikasi. | “Buat akun terlebih dahulu. Isi nama lengkap, email aktif, dan kata sandi kuat. Setelah mendaftar, verifikasi email agar bisa masuk ke dashboard.” |
| **SCENE 3 — Tahap 2: Login dan Verifikasi Email**<br>0:50–1:20 | Bank S02–S04. Sorot tautan verifikasi, login, dan tombol “Daftar Periode”. | “Klik tautan verifikasi di email, lalu masuk. Di dashboard, pilih periode aktif dan klik Daftar Periode.” |
| **SCENE 4 — Tahap 3: Pemilihan Jalur**<br>1:20–1:45 | Halaman Buat Pendaftaran. Sorot kartu “Reguler”, deskripsi tatap muka, centang terpilih. | “Pilih jalur Reguler jika perkuliahan Anda tatap muka. Jalur ini akan menjadi kelompok pemeringkatan Anda.” |
| **SCENE 5 — Tahap 4: Pemilihan Jenis**<br>1:45–2:15 | Sorot kartu “Beasiswa Prestasi Non Akademik”. Tampilkan deskripsi bidang keagamaan, olahraga, seni, kepemudaan, atau organisasi. Centang, klik Buat Draft Pendaftaran. | “Pilih Beasiswa Prestasi Non Akademik. Jenis ini untuk prestasi lomba atau kepengurusan organisasi. Centang pernyataan, lalu buat draf. Ingat, pilihan ini terkunci setelah draf dibuat.” |
| **SCENE 6 — Tahap 5: Pengisian Data + Prestasi**<br>2:15–4:45 | Bank S07–S10. Setelah Pendidikan, tampilkan S09: pilih “Kompetisi/Lomba” atau “Pengurus Organisasi Mahasiswa”. Kursor mengisi nama, tingkat, peringkat, penyelenggara, tahun, unggah sertifikat/SK, klik “Tambah ke Daftar”. Kartu prestasi muncul. Lalu Orang Tua. | “Isi Data Pribadi dan Pendidikan seperti biasa. Setelah itu, tahap Prestasi muncul. Pilih jenis bukti: lomba atau organisasi. Untuk lomba, isi nama kompetisi, tingkat, peringkat, penyelenggara, tahun, dan unggah sertifikat. Untuk organisasi, isi nama organisasi, posisi, tingkat, penyelenggara, tahun, dan unggah SK. Tambahkan minimal satu data, lalu konfirmasi. Lengkapi juga data orang tua.” |
| **SCENE 7 — Tahap 6: Unggah Dokumen dan Formulir**<br>4:45–6:15 | Bank S11–S12. Checklist 9 dokumen. Sorot bahwa sertifikat prestasi sudah ada di tahap Prestasi, bukan di Dokumen. Tampilkan unduh/tandatangan/materai/unggah formulir. | “Unggah sembilan dokumen umum. Sertifikat atau SK prestasi tidak diunggah di sini karena sudah Anda masukkan di tahap Prestasi. Setelah dokumen lengkap, unduh Surat Permohonan dan Pakta Integritas, tandatangani, beri materai pada Pakta, lalu unggah kembali sebagai PDF.” |
| **SCENE 8 — Tahap 7: Review Akhir dan Submit**<br>6:15–7:30 | Bank S12–S13. Sorot data prestasi di ringkasan, checkbox, submit, bukti. | “Di Review, periksa kembali data prestasi: nama lomba atau organisasi, tingkat, peringkat, dan tahun. Pastikan sertifikat atau SK terbaca. Setelah semua benar, centang pernyataan dan submit. Simpan nomor pendaftaran.” |
| **SCENE 9 — Setelah Submit**<br>7:30–8:30 | Bank S14. Timeline dengan Dukcapil, Disdikbud, BKPSDM, dan Parsepor. Tampilkan hasil dan LPJ. | “Setelah submit, Parsepor ikut memeriksa bukti prestasi Anda bersama Dukcapil, Disdikbud, dan BKPSDM. Jika semua MS, Anda masuk Seleksi Kabupaten. Pemeringkatan memakai tier prestasi atau organisasi, lalu tahun masuk dan akreditasi. Pantau dashboard dan notifikasi. Jika diterima, unggah LPJ.” |

---

### 4.3 Episode 3 — Jalur Reguler: Beasiswa Tidak Mampu

**Metadata**

| Item | Keterangan |
|---|---|
| Judul | “Reguler — Tidak Mampu: Lengkapi Data Keluarga dan Ikuti Verifikasi Dinsos” |
| Target | Mahasiswa Reguler dari keluarga kurang mampu |
| Durasi | 8 menit |
| Dokumen khusus | Tidak ada; SKTM hanya pendukung di luar sistem |
| Dinas tambahan | Dinas Sosial |
| Fokus seleksi | Desil sosial ekonomi; tie-break IPK, tahun masuk, akreditasi |
| Kuota default | 250 |

**Script**

| Scene | Visual Prompt Description | Voiceover (VO) |
|---|---|---|
| **SCENE 1 — Hook Kombinasi**<br>0:00–0:15 | Kartu “Reguler — Tidak Mampu”. Ikon rumah, keluarga, dan kartu desil. Teks: “Kondisi keluarga dinilai objektif.” Kursor menyorot alur verifikasi Dinsos. | “Anda memilih jalur Reguler dengan jenis Beasiswa Tidak Mampu. Jenis ini menilai kondisi sosial ekonomi keluarga melalui verifikasi Dinas Sosial. Siapkan data keluarga dan dokumen umum dengan lengkap.” |
| **SCENE 2 — Tahap 1: Pendaftaran Akun**<br>0:15–0:50 | Bank S01 dengan label episode. Kursor mengisi nama, email, password, konfirmasi, klik Daftar Akun. | “Buat akun dengan nama sesuai identitas dan email aktif. Gunakan kata sandi yang kuat, lalu verifikasi email.” |
| **SCENE 3 — Tahap 2: Login dan Verifikasi Email**<br>0:50–1:20 | Bank S02–S04. Sorot verifikasi, login, dashboard, tombol Daftar Periode. | “Klik tautan verifikasi, masuk ke dashboard, dan pilih periode aktif untuk mulai mendaftar.” |
| **SCENE 4 — Tahap 3: Pemilihan Jalur**<br>1:20–1:45 | Halaman Buat Pendaftaran. Sorot kartu Reguler dan deskripsi tatap muka. | “Pilih jalur Reguler jika kuliah Anda tatap muka. Jalur ini menentukan kelompok pemeringkatan.” |
| **SCENE 5 — Tahap 4: Pemilihan Jenis**<br>1:45–2:15 | Sorot kartu “Beasiswa Tidak Mampu”. Deskripsi “kondisi sosial ekonomi yang diverifikasi lintas dinas”. Centang, klik Buat Draft Pendaftaran. | “Pilih Beasiswa Tidak Mampu. Jenis ini memakai data sosial ekonomi yang akan diverifikasi Dinas Sosial. Setelah yakin, centang dan buat draf.” |
| **SCENE 6 — Tahap 5: Pengisian Data**<br>2:15–4:15 | Bank S07–S10. Sorot data orang tua: nama, status, NIK, pekerjaan, jabatan. Sorot “Memiliki Wali”. Tampilkan catatan “SKTM bukan dokumen wajib di aplikasi”. | “Isi Data Pribadi dan Pendidikan dengan benar. Bagian paling penting untuk jenis ini adalah data orang tua atau wali: nama, status hidup atau meninggal, NIK, pekerjaan, dan jabatan jika ASN. Data ini membantu Dinas Sosial menilai kondisi keluarga. SKTM bukan dokumen wajib di aplikasi; siapkan hanya jika petugas memintanya di luar sistem.” |
| **SCENE 7 — Tahap 6: Unggah Dokumen dan Formulir**<br>4:15–5:45 | Bank S11–S12. Checklist 9 dokumen. Sorot KK, KHS, dan surat aktif. Tampilkan formulir, tanda tangan, materai, unggah PDF. | “Unggah sembilan dokumen umum, terutama KK, KHS, dan surat aktif kuliah. Setelah lengkap, unduh Surat Permohonan dan Pakta Integritas, tandatangani, beri materai pada Pakta, lalu unggah kembali sebagai PDF.” |
| **SCENE 8 — Tahap 7: Review Akhir dan Submit**<br>5:45–7:15 | Bank S12–S13. Sorot data orang tua di ringkasan, checkbox, submit, bukti. | “Di Review, periksa kembali data orang tua dan dokumen. Jika ada kesalahan, klik Perbaiki. Setelah benar, centang dua pernyataan dan submit. Simpan nomor pendaftaran dan bukti.” |
| **SCENE 9 — Setelah Submit**<br>7:15–8:00 | Bank S14. Timeline dengan Dinas Sosial. Tampilkan status TMS dan menu Sanggahan, lalu hasil. | “Setelah submit, Dinas Sosial ikut menilai kondisi sosial ekonomi Anda. Jika semua dinas MS, Anda masuk Seleksi Kabupaten dan diperingkatkan berdasarkan desil, lalu IPK, tahun masuk, dan akreditasi. Jika TMS, baca catatan dan ajukan sanggahan satu kali selama masa sanggah. Jika diterima, unggah LPJ.” |

---

### 4.4 Episode 4 — Jalur Reguler: Beasiswa Disabilitas

**Metadata**

| Item | Keterangan |
|---|---|
| Judul | “Reguler — Disabilitas: Unggah Surat Keterangan dan Ikuti Verifikasi Dinkes” |
| Target | Mahasiswa Reguler penyandang disabilitas |
| Durasi | 8 menit |
| Dokumen khusus | Surat Keterangan Disabilitas (dokumen ke-10) |
| Dinas tambahan | Dinas Kesehatan |
| Fokus seleksi | IPK, semester, dan data disabilitas yang diverifikasi |
| Kuota default | 50 |

**Script**

| Scene | Visual Prompt Description | Voiceover (VO) |
|---|---|---|
| **SCENE 1 — Hook Kombinasi**<br>0:00–0:15 | Kartu “Reguler — Disabilitas”. Ikon aksesibilitas, surat keterangan, dan kartu KHS. Teks: “Hak yang sama, jalur yang ramah.” Kursor menyorot dokumen ke-10. | “Anda memilih jalur Reguler dengan jenis Beasiswa Mahasiswa Disabilitas. Jenis ini menyediakan jalur afirmasi dengan dokumen tambahan berupa Surat Keterangan Disabilitas dan verifikasi Dinas Kesehatan.” |
| **SCENE 2 — Tahap 1: Pendaftaran Akun**<br>0:15–0:50 | Bank S01 dengan label episode. Kursor mengisi formulir, klik Daftar Akun. | “Buat akun dengan data diri yang benar dan email aktif. Gunakan kata sandi yang kuat, lalu verifikasi email.” |
| **SCENE 3 — Tahap 2: Login dan Verifikasi Email**<br>0:50–1:20 | Bank S02–S04. Sorot tautan verifikasi, login, dashboard, tombol Daftar Periode. | “Klik tautan verifikasi di email, masuk ke dashboard, dan pilih periode aktif.” |
| **SCENE 4 — Tahap 3: Pemilihan Jalur**<br>1:20–1:45 | Halaman Buat Pendaftaran. Sorot kartu Reguler dan deskripsi tatap muka. | “Pilih jalur Reguler jika perkuliahan Anda tatap muka. Jalur ini menentukan kelompok pemeringkatan Anda.” |
| **SCENE 5 — Tahap 4: Pemilihan Jenis**<br>1:45–2:15 | Sorot kartu “Beasiswa Mahasiswa Disabilitas”. Deskripsi jalur afirmasi. Centang, klik Buat Draft Pendaftaran. | “Pilih Beasiswa Mahasiswa Disabilitas. Jenis ini khusus untuk mahasiswa penyandang disabilitas. Centang pernyataan dan buat draf; pilihan jenis akan terkunci.” |
| **SCENE 6 — Tahap 5: Pengisian Data**<br>2:15–4:15 | Bank S07–S10. Sorot Data Pribadi, Pendidikan, IPK, semester, dan Orang Tua. Tampilkan kotak info: “Surat Keterangan Disabilitas diunggah di tahap Dokumen”. Jangan tampilkan kolom jenis/derajat disabilitas. | “Isi Data Pribadi dan Pendidikan seperti biasa. IPK dan semester tetap diperlukan untuk penilaian. Lengkapi juga data orang tua atau wali. Surat Keterangan Disabilitas akan Anda unggah di tahap Dokumen, bukan di tahap ini.” |
| **SCENE 7 — Tahap 6: Unggah Dokumen dan Formulir**<br>4:15–5:45 | Bank S11–S12. Checklist 10 dokumen. Sorot “Surat Keterangan Disabilitas” sebagai dokumen tambahan. Tampilkan formulir, tanda tangan, materai, unggah PDF. | “Unggah sepuluh dokumen: sembilan dokumen umum plus Surat Keterangan Disabilitas berupa kartu penyandang disabilitas atau surat dari dokter atau tenaga profesional berwenang. Setelah lengkap, unduh Surat Permohonan dan Pakta Integritas, tandatangani, beri materai pada Pakta, lalu unggah kembali sebagai PDF.” |
| **SCENE 8 — Tahap 7: Review Akhir dan Submit**<br>5:45–7:15 | Bank S12–S13. Sorot dokumen disabilitas di ringkasan, checkbox, submit, bukti. | “Di Review, pastikan Surat Keterangan Disabilitas terbaca dan sesuai nama Anda. Periksa juga data pendidikan dan orang tua. Setelah benar, centang pernyataan dan submit. Simpan nomor pendaftaran.” |
| **SCENE 9 — Setelah Submit**<br>7:15–8:00 | Bank S14. Timeline dengan Dinas Kesehatan. Tampilkan hasil dan LPJ. | “Setelah submit, Dinas Kesehatan ikut memeriksa Surat Keterangan Disabilitas Anda. Jika semua dinas MS, Anda masuk Seleksi Kabupaten. Penilaian memakai IPK, semester, dan data disabilitas yang diverifikasi. Pantau dashboard dan notifikasi. Jika diterima, unggah LPJ.” |

---

### 4.5 Episode 5 — Jalur Non Reguler: Beasiswa Akademik

**Metadata**

| Item | Keterangan |
|---|---|
| Judul | “Non Reguler — Akademik: Daftar dari Kelas Daring sampai Hasil” |
| Target | Mahasiswa Non Reguler dengan prestasi akademik |
| Durasi | 8 menit |
| Dokumen khusus | Tidak ada; 9 dokumen umum |
| Dinas tambahan | Tidak ada |
| Fokus seleksi | IPK dan semester; tie-break tahun masuk dan akreditasi |
| Kuota default | 250 |

**Script**

| Scene | Visual Prompt Description | Voiceover (VO) |
|---|---|---|
| **SCENE 1 — Hook Kombinasi**<br>0:00–0:15 | Kartu “Non Reguler — Akademik”. Latar biru dengan ikon laptop, kelas daring, dan KHS. Teks: “Kuliah daring, pendaftaran tetap lengkap.” | “Anda memilih jalur Non Reguler dengan jenis Beasiswa Prestasi Akademik. Jalur ini untuk perkuliahan jarak jauh atau daring, dengan penilaian utama IPK dan semester.” |
| **SCENE 2 — Tahap 1: Pendaftaran Akun**<br>0:15–0:50 | Bank S01 dengan label episode. Kursor mengisi formulir, klik Daftar Akun. | “Buat akun dengan nama sesuai identitas dan email aktif. Gunakan kata sandi kuat, lalu verifikasi email.” |
| **SCENE 3 — Tahap 2: Login dan Verifikasi Email**<br>0:50–1:20 | Bank S02–S04. Sorot verifikasi, login, dashboard, tombol Daftar Periode. | “Klik tautan verifikasi, masuk ke dashboard, dan pilih periode aktif untuk memulai.” |
| **SCENE 4 — Tahap 3: Pemilihan Jalur**<br>1:20–1:45 | Halaman Buat Pendaftaran. Sorot kartu “Non Reguler” dan deskripsi “jarak jauh atau daring”. Kartu terpilih. | “Pilih kartu Non Reguler. Jalur ini untuk perkuliahan jarak jauh atau daring. Pilihan jalur akan mengunci kelompok pemeringkatan Anda.” |
| **SCENE 5 — Tahap 4: Pemilihan Jenis**<br>1:45–2:15 | Sorot kartu “Beasiswa Prestasi Akademik”. Deskripsi IPK dan semester. Centang, klik Buat Draft Pendaftaran. | “Pilih Beasiswa Prestasi Akademik. Jenis ini menilai IPK dan semester aktif. Centang pernyataan dan buat draf.” |
| **SCENE 6 — Tahap 5: Pengisian Data**<br>2:15–4:15 | Bank S07–S10. Sorot NIK, KK, rekening, desa. Sorot IPK, semester, UKT, akreditasi. Sorot data orang tua. | “Isi Data Pribadi dan Pendidikan dengan teliti. Pastikan IPK dan semester sesuai KHS terakhir. Lengkapi juga data orang tua atau wali. Gunakan rekening Bank Kalteng atas nama sendiri.” |
| **SCENE 7 — Tahap 6: Unggah Dokumen dan Formulir**<br>4:15–5:45 | Bank S11–S12. Checklist 9 dokumen. Sorot KHS. Tampilkan formulir, tanda tangan, materai, unggah PDF. | “Unggah sembilan dokumen umum, termasuk KHS atau transkrip. Setelah lengkap, unduh Surat Permohonan dan Pakta Integritas, tandatangani, beri materai pada Pakta, lalu unggah kembali sebagai PDF.” |
| **SCENE 8 — Tahap 7: Review Akhir dan Submit**<br>5:45–7:15 | Bank S12–S13. Sorot data akademik, checkbox, submit, bukti. | “Di Review, periksa IPK, semester, dan data kampus. Jika ada kesalahan, klik Perbaiki. Setelah benar, centang dua pernyataan dan submit. Simpan nomor pendaftaran.” |
| **SCENE 9 — Setelah Submit**<br>7:15–8:00 | Bank S14. Timeline tiga dinas. Tampilkan hasil dan LPJ. | “Setelah submit, Dukcapil, Disdikbud, dan BKPSDM memeriksa berkas. Jika semua MS, Anda masuk Seleksi Kabupaten. Peringkat memakai IPK, lalu tahun masuk dan akreditasi. Pantau dashboard dan notifikasi. Jika diterima, unggah LPJ.” |

---

### 4.6 Episode 6 — Jalur Non Reguler: Beasiswa Non-Akademik

**Metadata**

| Item | Keterangan |
|---|---|
| Judul | “Non Reguler — Non-Akademik: Prestasi Tetap Bersinar” |
| Target | Mahasiswa Non Reguler dengan prestasi lomba atau SK ormawa |
| Durasi | 8,5 menit |
| Dokumen khusus | Sertifikat/SK di tahap Prestasi |
| Dinas tambahan | Parsepor |
| Fokus seleksi | Tier prestasi/organisasi; tie-break tahun masuk dan akreditasi |
| Kuota default | 30 |

**Script**

| Scene | Visual Prompt Description | Voiceover (VO) |
|---|---|---|
| **SCENE 1 — Hook Kombinasi**<br>0:00–0:15 | Kartu “Non Reguler — Non-Akademik”. Ikon laptop, piala, dan sertifikat. Teks: “Dari kelas daring, prestasi tetap dihitung.” | “Anda memilih jalur Non Reguler dengan jenis Beasiswa Prestasi Non-Akademik. Jalur ini untuk kuliah daring atau jarak jauh, dengan bukti prestasi lomba atau organisasi.” |
| **SCENE 2 — Tahap 1: Pendaftaran Akun**<br>0:15–0:50 | Bank S01 dengan label episode. Kursor mengisi formulir, klik Daftar Akun. | “Buat akun dengan data yang benar dan email aktif. Gunakan kata sandi kuat, lalu verifikasi email.” |
| **SCENE 3 — Tahap 2: Login dan Verifikasi Email**<br>0:50–1:20 | Bank S02–S04. Sorot verifikasi, login, dashboard, tombol Daftar Periode. | “Klik tautan verifikasi, masuk ke dashboard, dan pilih periode aktif.” |
| **SCENE 4 — Tahap 3: Pemilihan Jalur**<br>1:20–1:45 | Halaman Buat Pendaftaran. Sorot kartu Non Reguler dengan deskripsi daring/jarak jauh. | “Pilih kartu Non Reguler sesuai metode kuliah Anda. Jalur ini menjadi kelompok pemeringkatan.” |
| **SCENE 5 — Tahap 4: Pemilihan Jenis**<br>1:45–2:15 | Sorot kartu “Beasiswa Prestasi Non Akademik”. Deskripsi bidang prestasi. Centang, klik Buat Draft Pendaftaran. | “Pilih Beasiswa Prestasi Non Akademik. Jenis ini menilai prestasi lomba atau kepengurusan organisasi. Centang dan buat draf.” |
| **SCENE 6 — Tahap 5: Pengisian Data + Prestasi**<br>2:15–4:45 | Bank S07–S10, lalu S09. Sorot pilihan “Kompetisi/Lomba” dan “Pengurus Organisasi Mahasiswa”. Kursor mengisi data, unggah sertifikat/SK, klik Tambah ke Daftar, lalu konfirmasi. | “Isi Data Pribadi dan Pendidikan, lalu lengkapi tahap Prestasi. Pilih lomba atau organisasi. Isi nama, tingkat, peringkat atau posisi, penyelenggara, tahun, dan unggah sertifikat atau SK. Tambahkan minimal satu data, lalu konfirmasi. Setelah itu lengkapi data orang tua.” |
| **SCENE 7 — Tahap 6: Unggah Dokumen dan Formulir**<br>4:45–6:15 | Bank S11–S12. Checklist 9 dokumen. Sorot bahwa sertifikat sudah di Prestasi. Tampilkan formulir, materai, unggah PDF. | “Unggah sembilan dokumen umum. Sertifikat prestasi tidak diunggah lagi di sini karena sudah masuk di tahap Prestasi. Setelah lengkap, unduh Surat Permohonan dan Pakta Integritas, tandatangani, beri materai pada Pakta, lalu unggah sebagai PDF.” |
| **SCENE 8 — Tahap 7: Review Akhir dan Submit**<br>6:15–7:30 | Bank S12–S13. Sorot data prestasi, checkbox, submit, bukti. | “Di Review, periksa data prestasi dan dokumennya. Jika ada kesalahan, perbaiki. Setelah benar, centang dua pernyataan dan submit. Simpan nomor pendaftaran.” |
| **SCENE 9 — Setelah Submit**<br>7:30–8:30 | Bank S14. Timeline dengan Parsepor. Tampilkan hasil dan LPJ. | “Setelah submit, Parsepor ikut menilai bukti prestasi Anda. Jika semua MS, Anda masuk Seleksi Kabupaten. Pemeringkatan memakai tier prestasi atau organisasi, lalu tahun masuk dan akreditasi. Pantau dashboard dan notifikasi. Jika diterima, unggah LPJ.” |

---

### 4.7 Episode 7 — Jalur Non Reguler: Beasiswa Tidak Mampu

**Metadata**

| Item | Keterangan |
|---|---|
| Judul | “Non Reguler — Tidak Mampu: Data Keluarga dan Verifikasi Dinsos” |
| Target | Mahasiswa Non Reguler dari keluarga kurang mampu |
| Durasi | 8 menit |
| Dokumen khusus | Tidak ada; SKTM hanya pendukung di luar sistem |
| Dinas tambahan | Dinas Sosial |
| Fokus seleksi | Desil sosial ekonomi; tie-break IPK, tahun masuk, akreditasi |
| Kuota default | 250 |

**Script**

| Scene | Visual Prompt Description | Voiceover (VO) |
|---|---|---|
| **SCENE 1 — Hook Kombinasi**<br>0:00–0:15 | Kartu “Non Reguler — Tidak Mampu”. Ikon laptop, rumah, dan keluarga. Teks: “Kuliah daring, data keluarga tetap lengkap.” | “Anda memilih jalur Non Reguler dengan jenis Beasiswa Tidak Mampu. Jenis ini menilai kondisi sosial ekonomi keluarga melalui Dinas Sosial.” |
| **SCENE 2 — Tahap 1: Pendaftaran Akun**<br>0:15–0:50 | Bank S01 dengan label episode. Kursor mengisi formulir, klik Daftar Akun. | “Buat akun dengan nama sesuai identitas dan email aktif. Gunakan kata sandi kuat, lalu verifikasi email.” |
| **SCENE 3 — Tahap 2: Login dan Verifikasi Email**<br>0:50–1:20 | Bank S02–S04. Sorot verifikasi, login, dashboard, tombol Daftar Periode. | “Klik tautan verifikasi, masuk ke dashboard, dan pilih periode aktif.” |
| **SCENE 4 — Tahap 3: Pemilihan Jalur**<br>1:20–1:45 | Halaman Buat Pendaftaran. Sorot kartu Non Reguler dengan deskripsi daring/jarak jauh. | “Pilih jalur Non Reguler sesuai metode kuliah Anda. Jalur ini menentukan kelompok pemeringkatan.” |
| **SCENE 5 — Tahap 4: Pemilihan Jenis**<br>1:45–2:15 | Sorot kartu “Beasiswa Tidak Mampu”. Deskripsi kondisi sosial ekonomi. Centang, klik Buat Draft Pendaftaran. | “Pilih Beasiswa Tidak Mampu. Jenis ini memakai data sosial ekonomi yang diverifikasi Dinas Sosial. Centang dan buat draf.” |
| **SCENE 6 — Tahap 5: Pengisian Data**<br>2:15–4:15 | Bank S07–S10. Sorot data orang tua dan wali. Tampilkan info “SKTM bukan dokumen wajib di aplikasi”. | “Isi Data Pribadi dan Pendidikan. Lengkapi data ayah, ibu, dan wali jika ada. Data ini membantu Dinas Sosial menilai kondisi keluarga. SKTM bukan dokumen wajib di aplikasi; siapkan hanya jika petugas meminta.” |
| **SCENE 7 — Tahap 6: Unggah Dokumen dan Formulir**<br>4:15–5:45 | Bank S11–S12. Checklist 9 dokumen. Sorot KK dan KHS. Tampilkan formulir, materai, unggah PDF. | “Unggah sembilan dokumen umum, terutama KK dan KHS. Setelah lengkap, unduh Surat Permohonan dan Pakta Integritas, tandatangani, beri materai pada Pakta, lalu unggah sebagai PDF.” |
| **SCENE 8 — Tahap 7: Review Akhir dan Submit**<br>5:45–7:15 | Bank S12–S13. Sorot data orang tua, checkbox, submit, bukti. | “Di Review, periksa data orang tua dan dokumen. Jika ada kesalahan, perbaiki. Setelah benar, centang dua pernyataan dan submit. Simpan nomor pendaftaran.” |
| **SCENE 9 — Setelah Submit**<br>7:15–8:00 | Bank S14. Timeline dengan Dinas Sosial. Tampilkan hasil dan LPJ. | “Setelah submit, Dinas Sosial menilai kondisi sosial ekonomi Anda. Jika semua MS, Anda masuk Seleksi Kabupaten dan diperingkatkan berdasarkan desil, lalu IPK, tahun masuk, dan akreditasi. Jika TMS, ajukan sanggahan satu kali selama masa sanggah. Jika diterima, unggah LPJ.” |

---

### 4.8 Episode 8 — Jalur Non Reguler: Beasiswa Disabilitas

**Metadata**

| Item | Keterangan |
|---|---|
| Judul | “Non Reguler — Disabilitas: Jalur Afirmasi untuk Kuliah Daring” |
| Target | Mahasiswa Non Reguler penyandang disabilitas |
| Durasi | 8 menit |
| Dokumen khusus | Surat Keterangan Disabilitas (dokumen ke-10) |
| Dinas tambahan | Dinas Kesehatan |
| Fokus seleksi | IPK, semester, dan data disabilitas yang diverifikasi |
| Kuota default | 50 |

**Script**

| Scene | Visual Prompt Description | Voiceover (VO) |
|---|---|---|
| **SCENE 1 — Hook Kombinasi**<br>0:00–0:15 | Kartu “Non Reguler — Disabilitas”. Ikon laptop, aksesibilitas, dan surat keterangan. Teks: “Kuliah daring, hak tetap sama.” | “Anda memilih jalur Non Reguler dengan jenis Beasiswa Mahasiswa Disabilitas. Jalur afirmasi ini menyediakan dokumen tambahan dan verifikasi Dinas Kesehatan.” |
| **SCENE 2 — Tahap 1: Pendaftaran Akun**<br>0:15–0:50 | Bank S01 dengan label episode. Kursor mengisi formulir, klik Daftar Akun. | “Buat akun dengan data yang benar dan email aktif. Gunakan kata sandi kuat, lalu verifikasi email.” |
| **SCENE 3 — Tahap 2: Login dan Verifikasi Email**<br>0:50–1:20 | Bank S02–S04. Sorot verifikasi, login, dashboard, tombol Daftar Periode. | “Klik tautan verifikasi, masuk ke dashboard, dan pilih periode aktif.” |
| **SCENE 4 — Tahap 3: Pemilihan Jalur**<br>1:20–1:45 | Halaman Buat Pendaftaran. Sorot kartu Non Reguler dengan deskripsi daring/jarak jauh. | “Pilih jalur Non Reguler sesuai metode kuliah Anda. Jalur ini menentukan kelompok pemeringkatan.” |
| **SCENE 5 — Tahap 4: Pemilihan Jenis**<br>1:45–2:15 | Sorot kartu “Beasiswa Mahasiswa Disabilitas”. Deskripsi jalur afirmasi. Centang, klik Buat Draft Pendaftaran. | “Pilih Beasiswa Mahasiswa Disabilitas. Jenis ini khusus untuk mahasiswa penyandang disabilitas. Centang dan buat draf.” |
| **SCENE 6 — Tahap 5: Pengisian Data**<br>2:15–4:15 | Bank S07–S10. Sorot Data Pribadi, Pendidikan, IPK, semester, dan Orang Tua. Tampilkan info “Surat Keterangan Disabilitas diunggah di tahap Dokumen”. Jangan tampilkan kolom jenis/derajat disabilitas. | “Isi Data Pribadi dan Pendidikan dengan teliti. IPK dan semester tetap diperlukan. Lengkapi data orang tua atau wali. Surat Keterangan Disabilitas akan diunggah di tahap Dokumen.” |
| **SCENE 7 — Tahap 6: Unggah Dokumen dan Formulir**<br>4:15–5:45 | Bank S11–S12. Checklist 10 dokumen. Sorot Surat Keterangan Disabilitas. Tampilkan formulir, materai, unggah PDF. | “Unggah sepuluh dokumen: sembilan dokumen umum plus Surat Keterangan Disabilitas. Setelah lengkap, unduh Surat Permohonan dan Pakta Integritas, tandatangani, beri materai pada Pakta, lalu unggah sebagai PDF.” |
| **SCENE 8 — Tahap 7: Review Akhir dan Submit**<br>5:45–7:15 | Bank S12–S13. Sorot dokumen disabilitas, checkbox, submit, bukti. | “Di Review, pastikan Surat Keterangan Disabilitas terbaca dan sesuai nama Anda. Periksa juga data pendidikan dan orang tua. Setelah benar, centang dua pernyataan dan submit. Simpan nomor pendaftaran.” |
| **SCENE 9 — Setelah Submit**<br>7:15–8:00 | Bank S14. Timeline dengan Dinas Kesehatan. Tampilkan hasil dan LPJ. | “Setelah submit, Dinas Kesehatan ikut memeriksa Surat Keterangan Disabilitas Anda. Jika semua MS, Anda masuk Seleksi Kabupaten. Penilaian memakai IPK, semester, dan data disabilitas yang diverifikasi. Pantau dashboard dan notifikasi. Jika diterima, unggah LPJ.” |

---

## 5. Checklist Produksi

### 5.1 Checklist Akurasi Setiap Episode

- [ ] Jalur yang dipilih sesuai judul: Reguler atau Non Reguler.
- [ ] Kartu jenis yang disorot sesuai judul.
- [ ] Tahap Prestasi hanya muncul pada episode Non-Akademik.
- [ ] Jumlah dokumen benar: 9 untuk Akademik, Tidak Mampu, dan Non-Akademik; 10 untuk Disabilitas.
- [ ] Sertifikat/SK Non-Akademik diunggah di tahap Prestasi, bukan di Dokumen.
- [ ] SKTM tidak ditampilkan sebagai unggahan wajib.
- [ ] Formulir Surat Permohonan dan Pakta Integritas ditandatangani; Pakta bermaterai Rp10.000 oleh mahasiswa.
- [ ] Orang tua/wali menandatangani Pakta Integritas, tetapi tidak perlu materai.
- [ ] Formulir diunggah sebagai PDF maksimal 2 MB.
- [ ] Dua checkbox submit ditampilkan.
- [ ] Data dikunci setelah submit.
- [ ] Notifikasi hasil hanya di dalam aplikasi, bukan email.
- [ ] Hasil publik hanya Diterima dan Ditolak; TMS di dashboard dan Sanggahan.
- [ ] Sanggahan hanya untuk TMS, satu kali, selama masa sanggah.
- [ ] LPJ hanya untuk yang diterima; LPJ belum disetujui dapat memblokir NIK.

### 5.2 Checklist Visual

- [ ] Semua label tombol persis seperti aplikasi.
- [ ] Kursor terlihat dan klik diberi ripple.
- [ ] Ada progress “Langkah X dari 7”.
- [ ] Tidak ada layar kosong saat menunggu.
- [ ] NIK, KK, rekening, dan alamat disamarkan.
- [ ] Dokumen contoh terlihat jelas tetapi tidak memuat data nyata.
- [ ] Subtitle tidak menutupi tombol.
- [ ] Warna kontras dan teks minimal 24 px pada 1080p.
- [ ] Ada cutaway tanda tangan/materai yang halus, tidak berlebihan.
- [ ] Closing mengingatkan untuk membaca pengumuman resmi.

### 5.3 Checklist Teknis Sebelum Publikasi

- [ ] Cocokkan tanggal periode dengan data aktif di aplikasi.
- [ ] Ganti `[KUOTA RESMI]` dengan kuota yang berlaku atau hapus jika belum pasti.
- [ ] Pastikan build aplikasi yang direkam sama dengan build yang dipakai pendaftar.
- [ ] Uji akun demo untuk setiap jenis dan pastikan alur sampai submit berhasil.
- [ ] Perbaiki dahulu gap data disabilitas (lihat Bagian 6) sebelum mempublikasikan episode Disabilitas sebagai alur yang fully working.
- [ ] Pastikan kebijakan kuota per jalur sudah final (lihat Bagian 6).
- [ ] Siapkan versi 16:9 dan 9:16.
- [ ] Siapkan thumbnail dengan judul kombinasi yang jelas.
- [ ] Siapkan deskripsi video berisi tautan resmi dan disclaimer.

---

## 6. Catatan Teknis untuk Tim Pengembang

Bagian ini bukan untuk narasi video. Ini temuan dari kode yang perlu diselesaikan sebelum video dipublikasikan sebagai panduan operasional.

### 6.1 Gap Kritis: Data Disabilitas Tidak Punya Formulir Mahasiswa

`app/Services/Scoring/DisabilityScoring.php` mewajibkan `disability_type`. Jika kosong, sistem melempar:

> “Jenis disabilitas wajib diisi untuk jalur Disabilitas.”

Namun:

- Tidak ada field `disability_type` atau `disability_grade` di form Data Pribadi/Pendidikan.
- `app/Services/ApplicationWorkflowService.php` tidak menyalin kedua field itu saat `synchronizeStudentProfile`.
- Pencarian di `app/Http` dan `resources/views/mahasiswa` tidak menemukan input untuk kedua field tersebut.
- Nilai `disability_type`/`disability_grade` hanya ada di seeder/demo dan ditampilkan di halaman operator.

**Dampak:** Pendaftar Disabilitas sungguhan dapat lolos submit, tetapi gagal saat skoring seleksi karena `disability_type` kosong.

**Rekomendasi:**

1. Tambahkan field kondisional di wizard Data Pribadi atau Pendidikan saat jenis = Disabilitas:
   - Jenis Disabilitas: pilihan dari `MahasiswaProfile::DISABILITY_TYPES`.
   - Derajat Disabilitas: `RINGAN`, `SEDANG`, `BERAT`.
2. Simpan ke `mahasiswa_profiles` saat submit.
3. Atau sediakan form khusus operator Dinkes untuk mengisi kedua field sebelum skoring.
4. Tambahkan validasi agar submit Disabilitas tidak lolos tanpa data tersebut.

### 6.2 Konsistensi Kuota dan Pemeringkatan

- `SelectionScoringService::recalculateRanking()` mengelompokkan peringkat berdasarkan `kabupaten + jalur_beasiswa_id`, sehingga peringkat Reguler dan Non Reguler dimulai dari 1 lagi.
- `SelectionWorkflowService::publishDecisions()` memakai `rank <= ApplicationType::quota()` untuk keputusan otomatis.
- Akibatnya, jika kuota Akademik 250, jalur Reguler dan Non Reguler masing-masing bisa mengirim 250 kandidat berperingkat 1–250, sehingga total penerima berpotensi 500 — melebihi kuota jenis.
- `quotaAvailable()` menghitung penerima per jenis, sehingga keputusan manual akan berhenti di 250. Ini tidak konsisten dengan publikasi otomatis.

**Rekomendasi:** Tentukan kebijakan resmi:

- **Opsi A:** Kuota per jalur. Tambahkan konfigurasi kuota per jalur dan pakai di publikasi otomatis.
- **Opsi B:** Kuota per jenis. Hitung peringkat global per jenis, bukan per jalur, atau bandingkan total penerima per jenis sebelum publikasi.

Jangan mempublikasikan video yang menyebut “kuota per jenis” sebelum kebijakan ini dipastikan.

### 6.3 SKTM Bukan Dokumen Unggahan

`SKTM` ada di master dokumen dengan `is_required = false` dan tidak terpasang pada pivot kategori. `DokumenController` hanya menerima jenis dokumen yang ada di `requiredDocumentTypes()`. Jadi SKTM tidak bisa diunggah sebagai dokumen di aplikasi. Video harus mengatakan “siapkan jika petugas meminta di luar sistem”, bukan “unggah SKTM”.

### 6.4 Notifikasi Hanya In-App

`ApplicationStatusChanged` dikirim melalui kanal database. Tidak ada email hasil. Video tidak boleh menampilkan email sebagai kanal pengumuman.

### 6.5 Lokasi Dokumen

`/docs` ada di `.gitignore`. Jika tim ingin menyimpan panduan ini di repositori, letakkan di root atau ubah `.gitignore`. Dokumen ini sengaja dibuat di root agar mudah ditemukan dan tidak ikut terabaikan.

---

## 7. Ringkasan Fakta Aplikasi yang Dipakai dalam Video

| Fakta | Nilai |
|---|---|
| Jalur | Reguler, Non Reguler |
| Jenis | Akademik, Tidak Mampu, Disabilitas, Non-Akademik |
| Email diizinkan | Gmail, Googlemail, Yahoo, Ymail, Outlook, Hotmail, Live, MSN, iCloud, Me, Mac, Proton, AOL, Zoho, Mail.com; suffix `.ac.id`, `.go.id`, `.sch.id`, `.edu` |
| Password | Minimal 8 karakter, huruf besar, huruf kecil, simbol |
| Dokumen umum | 9 dokumen |
| Dokumen Disabilitas | 10 dokumen |
| Format dokumen | PDF, JPG, JPEG, PNG |
| Ukuran dokumen | Maksimal 2 MB (2.048 KB) |
| Formulir | PDF, maksimal 2 MB; tombol unduh aktif setelah Data Pribadi, Pendidikan, dan Orang Tua/Wali lengkap |
| Materai | Rp10.000 pada Pakta Integritas, tanda tangan mahasiswa |
| NIK | 16 digit; satu NIK hanya boleh terdaftar pada satu akun |
| Peringkat | Dikelompokkan per kabupaten, jalur, dan jenis; kuota ditetapkan per jenis |
| Dinas wajib | Dukcapil, Disdikbud, BKPSDM |
| Dinas tambahan | Dinsos (Tidak Mampu), Dinkes (Disabilitas), Parsepor (Non-Akademik) |
| Kuota default | 250 Akademik, 250 Tidak Mampu, 50 Disabilitas, 30 Non-Akademik |
| Status | Draf, Verifikasi Lintas Dinas, Seleksi Kabupaten, TMS, Diterima, Ditolak |
| Notifikasi | Dalam aplikasi |
| Hasil publik | Hanya Diterima/Ditolak yang sudah dipublikasikan |
| Sanggahan | Hanya TMS, sekali, selama masa sanggah, uraian 40–2.000 karakter |
| LPJ | Hanya penerima; PDF/JPG/JPEG/PNG maksimal 2 MB; LPJ belum disetujui dapat menahan pendaftaran periode berikutnya |

---

## 8. Penutup Video

**Visual:** Kembali ke dashboard dengan status “Diterima” dan tombol “LPJ Beasiswa”. Muncul teks “Langkah lengkap. Data aman. Hasil transparan.” Logo dan tautan resmi.

**VO:** “Itulah panduan lengkap pendaftaran beasiswa Kartu Hebat Mahasiswa. Mulai dari akun, jalur, jenis, data, dokumen, review, submit, sampai hasil. Pastikan selalu membaca pengumuman resmi dan memantau dashboard serta notifikasi. Jika ada kendala, catat nomor pendaftaran dan hubungi kanal resmi Pemerintah Kabupaten Murung Raya.”

**Teks layar:** “Simpan nomor pendaftaran · Pantau notifikasi · Baca pengumuman resmi.”

---

*Dokumen ini disusun dari alur kode aplikasi. Ketentuan resmi mengenai tanggal, kuota, persyaratan, dan penetapan penerima tetap mengikuti pengumuman serta Surat Keputusan yang diterbitkan Pemerintah Kabupaten Murung Raya.*
