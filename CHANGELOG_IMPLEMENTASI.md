# Ringkasan Implementasi

## Fondasi domain

- Domain Kominfo lama diganti dengan Sistem Kartu Hebat Mahasiswa.
- Tujuh role berada pada satu model pengguna dan dibatasi dengan role middleware, policy, serta scope wilayah.
- Skema wilayah, profil mahasiswa, pengajuan per periode, dokumen, verifikasi, seleksi, pengumuman, notifikasi, dan audit dibangun melalui migrasi baru.

## Workflow

- Pengajuan mahasiswa mendukung DRAFT, perbaikan BTL, submit ulang, dan penguncian data.
- Verifikasi Desa/Kelurahan dan Kecamatan mendukung MS, BTL, dan TMS.
- Verifikasi Dukcapil, Dinas Sosial, dan Dinas Pendidikan berjalan paralel.
- Dukcapil memperbarui status kependudukan; Dinas Sosial dan Pendidikan mencatat desil.
- Skor seleksi dihitung dari kriteria aktif, ranking dipisahkan per kabupaten dan periode, lalu operator Kabupaten menetapkan keputusan manual.
- Keputusan internal tidak terlihat publik sebelum proses publikasi.

## Antarmuka

- Landing page, jadwal, persyaratan, pengumuman, dan pencarian hasil publik.
- Dashboard mahasiswa, formulir profil, unggah dokumen, progres, notifikasi, dan riwayat verifikasi.
- Dashboard serta antrean khusus setiap role operator.
- Lembar verifikasi, rekonsiliasi tiga dinas, ranking kandidat, penetapan, publikasi, dan reporting.
- Gaya visual, struktur kartu, tabel, navigasi, dan hero mengadaptasi paket desain Stitch.

## Keamanan

- Email verification, password reset, session security, dan 2FA wajib operator.
- Dokumen tersimpan pada private disk dan hanya dapat diunduh melalui controller ber-policy.
- Pengajuan historis dikunci dari edit/verifikasi/penetapan ulang.
- Hasil publik dibatasi pada periode aktif, keputusan yang telah dipublikasikan, rate limit, serta masking nama/NIK.
- Audit trail menyaring password dan secret 2FA.
- Seeder data contoh dibatasi pada environment lokal/pengujian.

## Reporting

- Rekap kandidat PDF dan XLSX.
- PDF daftar penerima yang telah dipublikasikan.
- Rekap operasional XLSX untuk operator Kecamatan dan Kabupaten.
