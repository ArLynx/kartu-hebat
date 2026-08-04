# Workflow Verifikasi

## 1. Mahasiswa dan jalur pengajuan

Mahasiswa memilih satu jalur untuk satu periode aktif:

- `AKADEMIK`: dasar seleksi IPK dan semester aktif.
- `TIDAK_MAMPU`: dasar seleksi desil sosial ekonomi terverifikasi.

Mahasiswa kemudian melengkapi profil dan dokumen wajib sesuai jalur. KHS diwajibkan untuk jalur Akademik, SKTM diwajibkan untuk jalur Tidak Mampu, sedangkan KTP, KK, dan KTM berlaku untuk kedua jalur. Pengajuan hanya dapat dikirim saat status `DRAFT`, `BTL_DESA`, atau `BTL_KECAMATAN`.

- Dari `DRAFT` atau `BTL_DESA`, pengajuan masuk ke `VERIFIKASI_DESA`.
- Dari `BTL_KECAMATAN`, pengajuan kembali ke `VERIFIKASI_KECAMATAN`.
- Saat koreksi tingkat kecamatan/dinas dikirim ulang, hasil verifikasi dinas dan skor lama dihapus agar berkas yang diperbaiki dinilai ulang.
- Perubahan jalur selama pengajuan masih dapat diedit menghapus skor dan selection lama.

## 2. Desa/Kelurahan

Operator hanya melihat mahasiswa dari desa/kelurahannya.

- `MS` → `VERIFIKASI_KECAMATAN`
- `BTL` → `BTL_DESA`
- `TMS` → `TMS`

## 3. Kecamatan

Operator hanya melihat seluruh desa dalam kecamatannya.

- `MS` → `VERIFIKASI_DINAS`
- `BTL` → `BTL_KECAMATAN`
- `TMS` → `TMS`

## 4. Dinas paralel

Tiga role melakukan verifikasi terhadap pengajuan yang sama: Dukcapil, Dinas Sosial, serta Dinas Pendidikan dan Kebudayaan. Kombinasi hasil diselesaikan setelah ketiganya memberi keputusan.

- Ada satu `TMS` → `TMS`
- Tidak ada `TMS`, tetapi ada `BTL` → `BTL_KECAMATAN`
- Seluruhnya `MS` → hitung skor sesuai jalur dan `SELEKSI_KABUPATEN`

Untuk jalur Tidak Mampu, Dinas Sosial dan Dinas Pendidikan wajib mengisi desil pada keputusan `MS`. Untuk jalur Akademik, desil tidak menjadi bagian rumus seleksi.

## 5. Perhitungan dan Kabupaten

Sistem menghitung skor otomatis sesuai jalur:

- **Akademik:** IPK 75% dan semester 25%. IPK dinormalisasi terhadap skala 4,00. Semester dinormalisasi sampai semester maksimum yang dikonfigurasi, default semester 8.
- **Tidak Mampu:** rata-rata desil Dinas Sosial dan Dinas Pendidikan menjadi 100% skor. Desil 1 bernilai 100 dan desil 10 bernilai 0 secara linear.

Ranking dan kuota dihitung terpisah untuk setiap kombinasi kabupaten, periode, dan jalur. Operator kabupaten menyimpan keputusan internal `DITERIMA` atau `DITOLAK`. Keputusan internal belum terlihat sebagai hasil resmi oleh mahasiswa.

Saat tombol publikasi dijalankan:

- `selections.published_at` diisi.
- Status pengajuan diubah sesuai keputusan manual.
- Verification log dan notifikasi mahasiswa dibuat.
- Pengumuman hasil diaktifkan.
- Hasil dapat dicari pada halaman publik dengan nomor pengajuan atau NIK.

## 6. Pengamanan transisi

Setiap keputusan operator diperiksa oleh policy, cakupan wilayah, role, status saat ini, validasi input, dan transaksi database. Catatan wajib untuk `BTL` atau `TMS`.
