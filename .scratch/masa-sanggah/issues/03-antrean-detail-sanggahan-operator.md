# 03: Antrean dan detail sanggahan untuk operator kabupaten

**What to build:** Operator kabupaten mendapat menu **Sanggahan** berisi antrean seluruh sanggahan di wilayahnya, dengan ringkasan jumlah per status dan filter periode, status, serta pencarian nomor pengajuan atau nama. Membuka satu sanggahan menampilkan uraian mahasiswa, keputusan TMS tiap dinas beserta catatannya, dokumen yang dinilai tidak memenuhi, dan riwayat verifikasi application, sehingga operator dapat menilai klaim mahasiswa dengan bukti. Pada tiket ini belum ada tombol keputusan.

**Blocked by:** 02 (Mahasiswa mengajukan sanggahan)

**Status:** resolved

- [x] Operator kabupaten melihat menu Sanggahan di sidebar portal operator.
- [x] Antrean hanya menampilkan sanggahan atas application yang berada di wilayah kabupaten operator tersebut; sanggahan wilayah lain tidak pernah terlihat.
- [x] Antrean menampilkan ringkasan jumlah sanggahan yang diajukan, dikabulkan, dan ditolak untuk data yang sedang difilter.
- [x] Antrean dapat difilter berdasarkan periode dan status, serta dicari berdasarkan nomor pengajuan atau nama mahasiswa, dan filter tetap terbawa saat berpindah halaman.
- [x] Halaman detail menampilkan uraian mahasiswa, daftar dinas yang memutuskan TMS beserta catatan verifikatornya, dokumen yang dinilai tidak memenuhi beserta catatannya, dan riwayat verifikasi application.
- [x] Halaman detail menampilkan status sanggahan saat ini; sanggahan yang sudah diputuskan tidak menawarkan aksi apa pun pada tiket ini.
- [x] Feature test menutup: batas wilayah, isi antrean, filter dan pencarian, ringkasan jumlah, serta isi halaman detail.

## Comments

Implementasi:

- Route `GET operator.sanggahan.index` dan `GET operator.sanggahan.show` (`operator.sanggahan.*`) di dalam group middleware `role:operator_kabupaten`, memakai `Operator\SanggahanController`. Route lain tidak bisa memakainya karena middleware role menolak sebelum controller.
- Batas wilayah memakai `Application::visibleTo($user)` lewat `whereHas('application', ...)` di query `Sanggahan`, bukan filter kabupaten manual. Ini membuat aturan kewenangan tetap satu sumber dengan halaman operator lain (dan otomatis mengecualikan superadmin sesuai `visibleTo`).
- Filter: `periode_id` (kolom `sanggahans.periode_id`), `status` (enum `SanggahanStatus`), dan `q` (nomor pengajuan atau nama mahasiswa). `paginate(15)->withQueryString()` membawa seluruh filter ke halaman berikutnya. Nilai status divalidasi lewat `SanggahanStatus::tryFrom()` sehingga nilai asing diabaikan, bukan menghasilkan daftar kosong yang membingungkan.
- Ringkasan dihitung dari query dasar (wilayah + periode + pencarian) dan sengaja **tidak** dipersempit filter status, karena tiga angka yang saling melengkapi akan selalu bernilai nol semua bila filter status ikut diterapkan. Keputusan ini ditulis eksplisit di halaman ("Filter status tidak mempersempit ringkasan ini.").
- Halaman detail memuat uraian + pengaju, `x-sanggahan.tms-agencies` untuk dinas TMS, daftar dokumen `TIDAK_MEMENUHI` **pada putaran verifikasi berjalan** (`AgencyVerificationService::currentRound()`), dan seluruh `verificationLogs` dengan label aksi. Dokumen dibatasi per putaran agar penilaian lama dari pengiriman sebelumnya tidak tampil sebagai bukti yang berlaku; nomor putaran ditampilkan di header.
- `VerificationLog::getActionLabelAttribute()` menerjemahkan action yang sudah ada menjadi label Indonesia; penambahan action keputusan sanggahan ditunda ke tiket 04 saat aksinya ditulis.
- Tidak ada tombol/route keputusan pada tiket ini; status `DIAJUKAN` menampilkan keterangan bahwa formulir keputusan datang pada tahap berikutnya, dan status final menampilkan catatan + pemutus + waktu sebagai teks saja.
- Test: `tests/Feature/OperatorSanggahanQueueTest.php` (10 test) — menu sidebar, isolasi wilayah pada antrean, 403 lintas kabupaten dan 403 role non-kabupaten, filter periode/status/pencarian, ringkasan mengikuti periode & pencarian, paginasi membawa filter, isi halaman detail (uraian, dinas TMS, dokumen TMS putaran berjalan, riwayat), penolakan dokumen putaran lama, status final tanpa aksi, dan empty state.
