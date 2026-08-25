# Sistem Kartu Hebat

Implementasi Laravel untuk pendaftaran bantuan pendidikan mahasiswa dengan verifikasi berjenjang berdasarkan wilayah administrasi. Antarmuka mengadaptasi desain Stitch yang disertakan, sedangkan domain, workflow, keamanan, basis data, reporting, dan notifikasi mengikuti dokumentasi proyek.

## Teknologi

- Laravel 13, PHP 8.3+
- Laravel Jetstream, Fortify, Livewire 3
- Tailwind CSS dan Vite
- MySQL, PostgreSQL, atau SQLite
- Laravel Excel untuk ekspor XLSX
- DOMPDF untuk laporan PDF

## Alur Kerja (Ringkas)

Mahasiswa mengisi wizard 7 langkah → application diverifikasi berjenjang:

```
Pendaftaran → Verifikasi Desa → Verifikasi Kecamatan → Verifikasi Lintas Dinas (3 dinas paralel) → Seleksi Kabupaten → Publikasi Hasil
```

Keputusan tiap operator: **MS** (lanjut), **BTL** (kembali ke mahasiswa untuk diperbaiki), **TMS** (ditolak).

Dokumentasi lengkap: [docs/workflow.md](docs/workflow.md).

