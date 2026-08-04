# Matriks Implementasi

Dokumen ini memetakan kebutuhan fungsional ke implementasi kode.

| Kebutuhan | Implementasi utama | Status |
|---|---|---|
| Tujuh role sistem | `app/Enums/UserRole.php`, `users.role` | Selesai |
| Pembatasan wilayah | `Application::scopeVisibleTo`, `ApplicationPolicy` | Selesai |
| Login email dan password | Fortify/Jetstream | Selesai |
| Verifikasi email dan reset password | Fortify | Selesai |
| 2FA wajib operator | `EnsureTwoFactorIsEnabled`, halaman setup 2FA | Selesai |
| Profil mahasiswa | `mahasiswa_profiles`, modul pendaftaran | Selesai |
| Multi-pengajuan per mahasiswa | `applications` dengan periode dan audit historis | Selesai |
| Dokumen persyaratan per jalur | `document_types.application_type`, `documents`, private storage | Selesai |
| Verifikasi Desa | `village_verifications` dan workflow service | Selesai |
| Verifikasi Kecamatan | `district_verifications` dan workflow service | Selesai |
| Verifikasi paralel tiga dinas | `agency_verifications` unik per pengajuan/dinas | Selesai |
| MS, BTL, TMS | enum, policy, request validation, workflow service | Selesai |
| Rekonsiliasi Kabupaten | `ReconciliationController` dan view | Selesai |
| Jalur Akademik dan Tidak Mampu | `applications.application_type`, enum `ApplicationType` | Selesai |
| Rumus seleksi per jalur | `criteria.application_type`, `SelectionScoringService` | Selesai |
| Ranking dan kuota per jalur | `SelectionScoringService`, `config/kartu_hebat.php` | Selesai |
| Keputusan manual | modul penetapan operator kabupaten | Selesai |
| Publikasi hasil | announcement, published selection, pencarian publik | Selesai |
| PDF dan Excel | DOMPDF, rekap kandidat, dan rekap operasional kecamatan/kabupaten | Selesai |
| Notifikasi | database notifications | Selesai |
| Audit trail | `verification_logs`, `audit_logs`, trait `Auditable` | Selesai |
| UI desain Stitch | landing, portal, dashboard, tabel, formulir | Selesai |

## Keputusan implementasi

Satu tabel `users` digunakan untuk mahasiswa dan operator. Role serta foreign key wilayah menentukan cakupan akses. Pendekatan ini lebih konsisten daripada guard dan tabel akun terpisah karena autentikasi, verifikasi email, password reset, 2FA, notifikasi, dan policy dapat digunakan bersama.

Transisi workflow ditempatkan dalam `ApplicationWorkflowService`. Controller tidak mengubah status secara langsung, kecuali publikasi keputusan seleksi yang merupakan proses khusus operator kabupaten. Tujuannya adalah menghindari transisi ilegal dan mengurangi duplikasi aturan.

Keputusan manual seleksi disimpan terlebih dahulu dalam `selections.manual_decision`. Status mahasiswa baru berubah menjadi `DITERIMA` atau `DITOLAK` ketika hasil dipublikasikan. Ini mencegah bocornya keputusan internal sebelum pengumuman resmi.

## Bagian yang wajib dikonfigurasi sebelum produksi

- Dataset wilayah resmi lengkap.
- Periode, tanggal pendaftaran, kuota Akademik, kuota Tidak Mampu, dan semester maksimum normalisasi pada `.env`.
- Bobot IPK/semester serta formula konversi desil yang disahkan melalui regulasi/SOP.
- Integrasi data eksternal Dukcapil, Dinas Sosial, dan Dinas Pendidikan apabila tersedia.
- Template laporan resmi, kop surat, nomor keputusan, dan tanda tangan elektronik.
- Mail transport, backup, observability, dan kebijakan retensi dokumen.
