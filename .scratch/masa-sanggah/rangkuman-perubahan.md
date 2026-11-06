# Rangkuman Perubahan Masa Sanggah

**Rentang:** commit `89b038f` (5 Nov 2026) sampai `c0ca006` (6 Nov 2026), ditambah perubahan working tree yang belum di-commit.
**Branch:** `try`
**Status:** keenam tiket Masa Sanggah selesai; penyempurnaan terakhir masih berupa perubahan lokal.

Dokumen ini merangkum apa yang berubah, bukan cara memakainya. Panduan mencoba tersedia di `.scratch/masa-sanggah/uji-manual.md`, sedangkan keputusan desain lengkap ada di `.scratch/masa-sanggah/spec.md` dan `docs/adr/0001-sanggahan-hanya-tms-teks-sekali-periode-aktif.md`.

---

## Ringkasan singkat

Sebelumnya keputusan **TMS** bersifat final tanpa saluran koreksi. Masa Sanggah menambahkan jendela waktu pada periode dan **Sanggahan** sebagai keberatan tertulis mahasiswa atas satu application TMS.

Alurnya:

1. Superadmin mengatur jadwal masa sanggah pada periode.
2. Mahasiswa TMS mengajukan satu sanggahan berupa teks selama jendela terbuka.
3. Operator kabupaten memeriksa bukti lalu memutuskan **Kabulkan** atau **Tolak**.
4. Pengabulan membuka kembali **hanya dinas yang memutuskan TMS** untuk menilai ulang.
5. Setelah dinas menyatakan MS, application masuk Seleksi Kabupaten seperti biasa.
6. Hasil perbaikan dipublikasikan secara inkremental tanpa menimpa SK lama.
7. Halaman pengumuman publik menampilkan jadwal sanggah yang sebenarnya.

Penyempurnaan terbaru menambahkan **Antrean Sanggahan khusus operator dinas** dan **penilaian dokumen satu klik berbasis AJAX** di halaman verifikasi.

---

## Riwayat commit

| Commit | Tanggal | Cakupan |
|---|---|---|
| `89b038f` | 5 Nov 2026 14:11 | Tiket 01 — jadwal masa sanggah pada periode |
| `d3dff4c` | 5 Nov 2026 15:22 | Tiket 02 — mahasiswa mengajukan sanggahan TMS |
| `94a86f0` | 5 Nov 2026 21:46 | Tiket 03 — antrean dan detail sanggahan untuk operator kabupaten |
| `f693427` | 5 Nov 2026 22:35 | Tiket 04 — keputusan sanggahan dan verifikasi ulang tertarget |
| `1616cc5` | 5 Nov 2026 23:29 | Perbaikan antrean verifikasi ulang, peringkat periode, dan penanda hasil sanggahan |
| `9091de1` | 5 Nov 2026 23:30 | Dokumentasi — tandai tiket 04 selesai |
| `8fa4ef7` | 6 Nov 2026 08:26 | Tiket 06 — halaman pengumuman hasil menampilkan masa sanggah sebenarnya |
| `3d4a571` | 6 Nov 2026 08:26 | Dokumentasi — tandai tiket 06 selesai |
| `c0ca006` | 6 Nov 2026 09:04 | Tiket 05 — publikasi inkremental dan penanda hasil sanggahan |

Catatan: tiket 05 di-commit setelah tiket 06, meskipun secara ketergantungan tiket 05 melanjutkan tiket 04.

---

## Perubahan per tiket

### 01 — Jadwal Masa Sanggah pada periode

- Menambah kolom nullable `tanggal_mulai_sanggah` dan `tanggal_selesai_sanggah` pada `periodes`, mengikuti pola tanggal LPJ.
- `Periode::masaSanggahStatus()` menjadi satu-satunya tempat logika "hari ini di antara dua tanggal".
- Enum baru `MasaSanggahStatus` dengan empat keadaan: belum dijadwalkan, belum dimulai, sedang berlangsung, telah berakhir, lengkap dengan label, tone, dan kalimat penjelas.
- Form periode menolak jadwal setengah jadi dan tanggal selesai yang lebih awal dari tanggal mulai; kedua tanggal boleh dikosongkan.
- Daftar periode menampilkan rentang tanggal dan badge status hari ini.
- Superadmin tidak dapat menutup periode yang masih punya sanggahan menggantung atau menghapus periode yang memiliki riwayat sanggahan.
- Istilah **Masa Sanggah** dan **Sanggahan** masuk `GLOSSARY.md`; batasan domain dicatat sebagai ADR.
- Test: `MasaSanggahScheduleTest` (6 test).

### 02 — Mahasiswa mengajukan sanggahan

- Tabel baru `sanggahans`: satu baris per application (unique constraint), berisi periode, pengaju, status, uraian, catatan keputusan, pemutus, dan waktu keputusan.
- Halaman `/mahasiswa/sanggahan` memakai controller sendiri sehingga tetap dapat diakses setelah tanggal pendaftaran berakhir.
- Halaman Submit menampilkan kartu TMS berisi dinas penolak dan catatan verifikator, dengan tautan ke halaman sanggahan.
- Aturan kelayakan dipusatkan di `SanggahanService::ineligibilityReason()`: pemilik application, status TMS, periode aktif, jendela terbuka, dan belum pernah menyanggah.
- Uraian wajib 40–2000 karakter dan pernyataan kebenaran wajib dicentang; tidak ada unggah berkas.
- Setelah dikirim, sanggahan terkunci: tidak ada route atau aksi untuk mengubah atau menariknya.
- Pengajuan menulis `VerificationLog` action `sanggahan_submitted` dan mengirim notifikasi ke seluruh operator kabupaten di wilayah application.
- Menu Sanggahan ditambahkan di sidebar portal mahasiswa.
- Test: `MahasiswaSanggahanTest` (24 test).

### 03 — Antrean dan detail sanggahan untuk operator kabupaten

- Route baru `operator.sanggahan.index` dan `operator.sanggahan.show`, hanya untuk role operator kabupaten.
- Antrean dibatasi wilayah lewat `Application::visibleTo($user)`, bukan filter kabupaten manual.
- Ringkasan jumlah diajukan/dikabulkan/ditolak, filter periode dan status, pencarian nomor pengajuan/nama, serta paginasi yang membawa filter.
- Halaman detail menampilkan uraian mahasiswa, dinas yang memutuskan TMS beserta catatan, dokumen TMS pada putaran verifikasi berjalan, dan riwayat verifikasi.
- Pada tahap ini belum ada tombol keputusan; status final hanya ditampilkan sebagai teks.
- Test: `OperatorSanggahanQueueTest` (10 test).

### 04 — Keputusan sanggahan dan verifikasi ulang tertarget

- Route `POST operator.sanggahan.decide` dengan validasi keputusan `DIKABULKAN`/`DITOLAK` dan catatan wajib maksimal 2000 karakter.
- Keputusan bersifat final dan dijalankan dalam satu transaksi dengan `lockForUpdate`.
- **Kabulkan**:
  - menghapus hanya `agency_verifications` TMS milik dinas yang salah beserta `document_verifications` dinas itu pada putaran berjalan;
  - mengembalikan mutasi profil yang berasal dari keputusan TMS tersebut;
  - mengembalikan application ke `VERIFIKASI_DINAS`, mengosongkan catatan, dan mengisi `locked_at`;
  - menulis log `sanggahan_granted` dengan metadata dinas dan keputusan yang dianulir;
  - mengirim notifikasi ke mahasiswa dan operator dinas terkait.
- **Tolak**: application tetap TMS, log `sanggahan_rejected`, notifikasi ke mahasiswa.
- Dinas yang dibuka kembali dapat menilai ulang meskipun tanggal pendaftaran sudah lewat, asalkan ada sanggahan dikabulkan pada periode aktif.
- Penetapan penerima mengizinkan application hasil sanggahan dari periode historis.
- Halaman detail pengajuan menampilkan badge **Hasil Sanggahan** dan tautan ke detail sanggahan.
- Test: `OperatorSanggahanDecisionTest` (13 test).

### 05 — Publikasi perbaikan dan penanda Penetapan Penerima

- Publikasi menjadi inkremental: kuota dan peringkat tetap dihitung untuk seluruh kandidat, tetapi hanya kandidat dengan `selection.published_at` kosong yang ditulis.
- Penerima lama tidak disentuh dan tidak menerima notifikasi ulang.
- Publikasi pertama tetap memakai pengumuman periode yang ada; publikasi perbaikan memakai slug baru, judul perbaikan, dan berkas SK sendiri sehingga SK lama tidak pernah ditimpa.
- Bila tidak ada kandidat baru, operator mendapat pesan "Tidak ada hasil baru untuk dipublikasikan".
- Nilai `manual_decision` dibaca tidak peka huruf besar/kecil; nilai yang tidak dikenali menggagalkan publikasi dengan pesan jelas, bukan diam-diam menjadi DITOLAK.
- Tabel Penetapan Penerima menampilkan badge **Hasil Sanggahan** dan tautan ke detail sanggahan.
- Publikasi periode tertutup dibuka khusus untuk kandidat dengan sanggahan dikabulkan.
- Test: `SelectionPublicationTest` (12 test) dan tambahan pada `SelectionWorkbookExportTest`.

### 06 — Halaman pengumuman hasil yang jujur

- Pencarian hasil tidak lagi dibatasi periode aktif; hasil dari periode tertutup tetap ditemukan lewat nomor pengajuan atau NIK.
- Bila ada beberapa hasil yang cocok, yang paling baru dipublikasikan yang ditampilkan.
- Panel masa sanggah memakai jadwal periode hasil yang ditemukan; bila belum ada hasil, memakai periode aktif terbaru.
- Panel selalu tampil dengan empat label jujur: belum dijadwalkan, belum dimulai, sedang berlangsung, telah berakhir.
- Tombol **Ajukan Sanggahan** selalu tampil tetapi mengarah ke halaman login.
- Teks "data simulasi", jam hardcode, dan tanggal simulasi dihapus.
- Panel dipindah ke komponen `x-sanggahan.public-schedule`; kalimat status dipusatkan di enum.
- Test: `PublicResultTest` (15 test).

---

## Perubahan terbaru (working tree, belum di-commit)

### 1. Antrean Sanggahan khusus operator dinas

Sebelumnya application yang dibuka kembali lewat sanggahan muncul di **Antrean Pengajuan** biasa bersama verifikasi normal. Sekarang dipisahkan:

- Route baru `GET /operator/antrean-sanggahan` (`operator.sanggahan-queue.index`) dengan `SanggahanQueueController`, hanya untuk operator dinas: Dukcapil, Sosial, Pendidikan, BKPSDM, Dinkes, dan Parsepor.
- Tiga scope baru pada `Application`:
  - `whereReopenedForVerification()` — padanan SQL dari pengecekan "sanggahan dikabulkan pada periode aktif";
  - `whereAwaitingReverificationBy($user)` — menyaring status `VERIFIKASI_DINAS`, wilayah, jalur `verifiedTrack`, dan dinas yang belum mengirim keputusan baru;
  - `whereNotAwaitingReverification()` — mengeluarkan application tersebut dari antrean pengajuan biasa.
- `ApplicationController::index()` memakai scope pengecualian untuk role dinas dan mengirim `sanggahanQueueCount` untuk spanduk peringatan di halaman daftar pengajuan.
- Sidebar portal operator dinas mendapat menu **Antrean Sanggahan**; operator kabupaten tetap memakai menu **Sanggahan** untuk memutuskan.
- Halaman detail pengajuan menampilkan komponen `x-sanggahan.reopened-context` berisi waktu dan pemutus sanggahan, uraian mahasiswa, catatan keputusan kabupaten, serta catatan TMS yang dianulir.
- Tautan kembali pada detail pengajuan mengarah ke Antrean Sanggahan bila application berasal dari sanggahan yang dikabulkan.
- `sanggahan.decidedBy` di-eager-load agar nama pemutus tidak menimbulkan query tambahan.
- Aturan proyek dicatat di `.ai/rules/operator.md`.
- Test: `OperatorSanggahanReverificationQueueTest` (11 test), dan satu test lama di `OperatorSanggahanDecisionTest` disesuaikan agar memeriksa perpindahan ke antrean baru.

### 2. Verifikasi dokumen satu klik dan AJAX

- Tombol **✓ MS** kini langsung menyimpan keputusan Memenuhi Syarat tanpa membuka panel catatan. Catatan MS bersifat opsional dan tidak lagi memaksa dialog.
- Tombol **✕ TMS** tetap membuka kolom catatan wajib sebelum disimpan.
- `DocumentVerificationController` mengembalikan JSON untuk request AJAX pada simpan dan batal penilaian (`result`, `label`, `notes`), sementara request form biasa tetap memakai redirect dan flash message.
- Halaman detail operator memakai state Alpine untuk:
  - memperbarui badge Memenuhi/Tidak Memenuhi/Belum Dinilai tanpa reload;
  - menampilkan catatan penilaian secara langsung;
  - membatalkan penilaian tanpa reload;
  - menampilkan status penyimpanan per dokumen dan pesan error;
  - memblokir keputusan MS/TMS bila masih ada dokumen belum dinilai;
  - memblokir TMS bila seluruh dokumen sudah MS;
  - meminta konfirmasi bila masih ada dokumen TMS sebelum keputusan disimpan;
  - menampilkan daftar dokumen yang belum dinilai secara dinamis.
- Test: 4 test baru di `DocumentVerificationTest` (render satu klik MS, JSON simpan, JSON batal, MS tanpa catatan).

### 3. Data demo dan panduan uji manual

- `MasaSanggahDemoSeeder` menyiapkan empat skenario deterministik:
  1. `KHM-SG-001` — TMS, belum menyanggah;
  2. `KHM-SG-002` — TMS, sanggahan menunggu keputusan;
  3. `KHM-SG-003` — sanggahan dikabulkan, menunggu verifikasi ulang;
  4. `KHM-ARSIP-001` — hasil seleksi periode arsip yang sudah terbit.
- Seeder menyediakan akun mahasiswa, operator kabupaten, operator dinas, dan superadmin dengan kata sandi `password`.
- Seeder idempotent: setiap kali dijalankan, keempat skenario dikembalikan ke keadaan awal tanpa menyentuh data lain, dan menolak berjalan di luar `local`/`testing`.
- `.scratch/masa-sanggah/uji-manual.md` memandu pengujian Tiket 01–06 langkah demi langkah.
- Test: `MasaSanggahDemoSeederTest` (9 test) dan `MasaSanggahDemoJourneyTest` (4 test perjalanan end-to-end di atas data demo).

### 4. Status pengujian

Seluruh test yang berkaitan langsung dengan fitur Masa Sanggah dan perubahan terbaru lulus:

```
php artisan test --compact \
  tests/Feature/MasaSanggahScheduleTest.php \
  tests/Feature/MahasiswaSanggahanTest.php \
  tests/Feature/OperatorSanggahanQueueTest.php \
  tests/Feature/OperatorSanggahanDecisionTest.php \
  tests/Feature/SelectionPublicationTest.php \
  tests/Feature/PublicResultTest.php \
  tests/Feature/OperatorSanggahanReverificationQueueTest.php \
  tests/Feature/MasaSanggahDemoSeederTest.php \
  tests/Feature/MasaSanggahDemoJourneyTest.php \
  tests/Feature/DocumentVerificationTest.php
```

Hasil: **119 test lulus, 678 assertion**.

---

## Peta berkas penting

| Area | Berkas |
|---|---|
| Migrasi | `database/migrations/*_add_sanggah_dates_to_periodes_table.php`, `*_create_sanggahans_table.php` |
| Enum | `app/Enums/MasaSanggahStatus.php`, `app/Enums/SanggahanStatus.php`, `ApplicationStatus::finalValues()` |
| Model | `app/Models/Periode.php`, `Sanggahan.php`, `Application.php`, `Selection.php`, `VerificationLog.php` |
| Service | `app/Services/SanggahanService.php`, `SelectionWorkflowService.php`, `AgencyVerificationService.php` |
| Controller | `Mahasiswa/SanggahanController.php`, `Operator/SanggahanController.php`, `Operator/SanggahanQueueController.php`, `Public/ResultController.php`, `Superadmin/PeriodeController.php` |
| Request & Policy | `app/Http/Requests/SanggahanDecisionRequest.php`, `app/Policies/ApplicationPolicy.php` |
| View | `resources/views/mahasiswa/sanggahan/`, `operator/sanggahan/`, `operator/sanggahan-queue/`, `components/sanggahan/`, `public/results.blade.php` |
| Route | `routes/web.php` |
| Demo | `database/seeders/MasaSanggahDemoSeeder.php`, `.scratch/masa-sanggah/uji-manual.md` |
| Dokumentasi | `GLOSSARY.md`, `docs/WORKFLOW.md`, `docs/adr/0001-sanggahan-hanya-tms-teks-sekali-periode-aktif.md`, `.ai/rules/operator.md`, `.ai/rules/services.md` |

---

## Batasan yang tetap berlaku

- Sanggahan hanya untuk application berstatus **TMS**, bukan DITERIMA/DITOLAK yang sudah ber-SK.
- Sanggahan hanya berupa teks; tidak ada unggah berkas atau unggah ulang dokumen.
- Satu application hanya boleh menyanggah satu kali; keputusan operator final.
- Sanggahan hanya untuk periode aktif dan selama jendela masa sanggah terbuka.
- Tidak ada eskalasi ke superadmin, banding berjenjang, atau kanal walk-in.
- Publikasi perbaikan selalu menerbitkan SK baru; SK lama tidak pernah ditimpa.
