# Uji Manual Masa Sanggah

Jalankan sekali:

```
php artisan db:seed --class=MasaSanggahDemoSeeder --no-interaction
```

Seeder aman dijalankan berulang. Setiap kali dijalankan, keempat skenario
dikembalikan ke keadaan awal, jadi kamu bisa mencoba ulang dari nol.

## Akun

Semua akun memakai kata sandi `password`.

| Email | Peran |
|---|---|
| `sanggahan.ajukan@kartuhebat.test` | Mahasiswa TMS, belum menyanggah (KHM-SG-001) |
| `sanggahan.antre@kartuhebat.test` | Mahasiswa TMS, sanggahan menunggu keputusan (KHM-SG-002) |
| `sanggahan.dikabulkan@kartuhebat.test` | Mahasiswa, sanggahan dikabulkan, menunggu verifikasi ulang (KHM-SG-003) |
| `sanggahan.arsip@kartuhebat.test` | Mahasiswa penerima periode arsip (KHM-ARSIP-001) |
| `kabupaten@kartuhebat.test` | Operator kabupaten, memutuskan sanggahan |
| `dukcapil@kartuhebat.test` | Operator Dukcapil, menilai ulang KHM-SG-003 lewat Antrean Sanggahan |
| `superadmin@kartuhebat.test` | Superadmin, mengatur jadwal masa sanggah |

## Alur yang bisa dicoba

### Tiket 01 — Jadwal masa sanggah

1. Login `superadmin@kartuhebat.test`.
2. Buka **Periode Beasiswa** → edit periode `2026/2027 Ganjil`.
3. Jadwal masa sanggah sudah terisi 05–12 Nov 2026 (relatif terhadap hari ini).
4. Coba isi tanggal selesai lebih awal dari tanggal mulai → harus ditolak.
5. Kosongkan kedua tanggal → harus tetap bisa disimpan.

### Tiket 02 — Mahasiswa mengajukan sanggahan

1. Login `sanggahan.ajukan@kartuhebat.test`.
2. Buka **Submit**: akan terlihat kartu TMS dengan Dukcapil dan catatan
   "Foto KTP yang diunggah terlihat buram…".
3. Buka menu **Sanggahan**: form aktif karena jendela sedang berlangsung.
4. Kirim uraian minimal 40 karakter + centang pernyataan.
5. Setelah terkirim: form hilang, status menjadi **Diajukan**, tidak ada tombol
   ubah atau tarik.
6. Coba buka `/mahasiswa/sanggahan` lagi → sanggahan tetap terkunci.

Sekarang coba guard periode:

7. Login `superadmin@kartuhebat.test`.
8. Ubah status periode menjadi **Ditutup** → harus ditolak dengan pesan bahwa
   masih ada sanggahan belum selesai.

### Tiket 03 — Antrean dan detail sanggahan

1. Login `kabupaten@kartuhebat.test`.
2. Buka menu **Sanggahan**.
3. Terlihat ringkasan jumlah Diajukan / Dikabulkan / Ditolak.
4. Coba filter status **Diajukan**, filter periode, dan cari `KHM-SG-002`.
5. Buka detail: tampil uraian mahasiswa, catatan TMS Dinas Pendidikan, KHS yang
   ditandai tidak memenuhi beserta catatannya, dan riwayat verifikasi.

### Tiket 04 — Keputusan dan verifikasi ulang tertarget

1. Masih sebagai `kabupaten@kartuhebat.test`, buka detail `KHM-SG-002`.
2. Pilih **Kabulkan** tanpa catatan → harus ditolak.
3. Isi catatan, lalu Kabulkan.
4. Login `pendidikan@kartuhebat.test` → buka **Antrean Sanggahan** → `KHM-SG-002`
   tampil di sana (dan tidak lagi di **Antrean Pengajuan**).
5. Buka `KHM-SG-002` dari Antrean Sanggahan → terlihat konteks sanggahan,
   catatan keputusan kabupaten, dan catatan TMS Dinas Pendidikan yang dianulir.
   Kembali ke daftar lewat tautan "Kembali ke antrean sanggahan".
6. Nilai semua dokumen → putuskan **MS** → application masuk
   **Seleksi Kabupaten** dan hilang dari Antrean Sanggahan.
7. Login `dukcapil@kartuhebat.test` → `KHM-SG-003` tampil di **Antrean Sanggahan**
   (bukan Antrean Pengajuan) dan dapat dinilai ulang meskipun tanggal pendaftaran
   sudah lewat.
8. Coba kirim sanggahan kedua untuk `KHM-SG-002` lewat akun mahasiswanya →
   ditolak.

### Tiket 05 — Publikasi perbaikan

1. Login `kabupaten@kartuhebat.test` → **Penetapan Penerima**.
2. Kandidat hasil sanggahan punya badge **Hasil Sanggahan** dengan tautan ke
   detail sanggahannya.
3. Tetapkan **Diterima** untuk kandidat tersebut.
4. Klik **Publikasikan** dan unggah PDF apa pun sebagai SK.
5. Buka **Pengumuman** → SK lama untuk periode arsip tetap ada; publikasi
   perbaikan menjadi pengumuman terpisah dengan berkas sendiri.
6. Publikasikan lagi tanpa kandidat baru → muncul pesan "Tidak ada hasil baru
   untuk dipublikasikan".

### Tiket 06 — Halaman pengumuman hasil

1. Buka `http://localhost:8000/pengumuman-hasil` tanpa login.
2. Panel masa sanggah menampilkan 05–12 Nov 2026 dan status
   **Sedang berlangsung**.
3. Cari `KHM-ARSIP-001` → hasil **Diterima** dari periode `2025/2026 Ganjil`
   (periode sudah ditutup) tetap ditemukan.
4. Panel masa sanggah mengikuti periode hasil tersebut, yaitu 10–12 Nov 2025
   dengan status **Telah berakhir**.
5. Tombol **Ajukan Sanggahan** mengarah ke `/login`.
6. Tidak ada lagi teks "data simulasi" atau tanggal hardcode.

## Kalau ingin mengulang dari awal

```
php artisan db:seed --class=MasaSanggahDemoSeeder --no-interaction
```

Perintah itu mengembalikan keempat skenario ke keadaan awal tanpa menyentuh
data lain di database lokal.
