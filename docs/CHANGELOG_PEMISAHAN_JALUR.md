# Changelog Pemisahan Jalur Pengajuan

Tanggal implementasi: 18 Juli 2026.

## Perubahan domain

- Menambahkan enum `ApplicationType` dengan nilai `AKADEMIK` dan `TIDAK_MAMPU`.
- Menambahkan `applications.application_type` untuk menyimpan jalur setiap pengajuan.
- Menambahkan cakupan jalur pada `document_types` dan `criteria`.
- Pengajuan lama dimigrasikan ke jalur Akademik agar data yang sudah ada tetap dapat dibaca.

## Dokumen

- KHS wajib hanya untuk jalur Akademik.
- SKTM wajib hanya untuk jalur Tidak Mampu.
- KTP, KK, dan KTM tetap wajib untuk kedua jalur.
- Persentase kelengkapan dan validasi submit memakai daftar dokumen sesuai jalur.

## Skor dan ranking

- Jalur Akademik: IPK 75% dan semester 25%.
- Jalur Tidak Mampu: rata-rata desil Dinas Sosial dan Dinas Pendidikan sebesar 100%.
- Desil dikonversi linear: desil 1 = skor 100 dan desil 10 = skor 0.
- Ranking diulang dari peringkat 1 pada masing-masing kombinasi jalur, kabupaten, dan periode.
- Skor dan ranking data seleksi lama dihitung ulang sebagai jalur Akademik saat migrasi dijalankan.
- Kuota dipisahkan melalui `KHM_QUOTA_AKADEMIK` dan `KHM_QUOTA_TIDAK_MAMPU`.

## Antarmuka dan laporan

- Formulir mahasiswa memuat pilihan jalur dan penjelasan rumus.
- Antrean operator dapat difilter berdasarkan jalur.
- Rekonsiliasi menampilkan jalur pengajuan.
- Halaman seleksi memakai tab Akademik/Tidak Mampu dengan statistik, kuota, formula, dan ranking terpisah.
- PDF, XLSX, dashboard mahasiswa, riwayat, dan hasil publik menampilkan jalur pengajuan.

## Pengujian

- Menambahkan test skor Akademik.
- Menambahkan test skor Tidak Mampu.
- Menambahkan test bahwa ranking dimulai dari 1 pada setiap jalur.

