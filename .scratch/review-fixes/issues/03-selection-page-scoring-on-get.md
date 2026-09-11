# Halaman seleksi menghitung skor & ranking ulang pada setiap GET

Status: resolved
Type: task

## Masalah

`app/Http/Controllers/Operator/SelectionController.php` → `index()` (± baris 28–42):

- Setiap buka halaman, semua application berstatus SELEKSI_KABUPATEN/DITERIMA/DITOLAK yang belum punya `selection` di-skoring satu per satu (`$scoring->calculate()` per aplikasi).
- `recalculateRanking($kabupatenId)` **selalu** dipanggil: memuat seluruh selections (dengan eager load nested `application.mahasiswa.profile.village`) lalu update `rank` satu per satu.

Akibatnya request GET bersifat menulis, O(N) query per kunjungan, dan makin lambat seiring jumlah kandidat.

Skoring sudah dipanggil di tempat yang benar saat verifikasi dinas selesai (`ApplicationWorkflowService::verifyAgency`) dan saat keputusan manual (`SelectionController::store`).

## Arah perbaikan

1. Hapus blok backfill + `recalculateRanking` dari `index()`. Jika perlu jaring pengaman untuk data yang terlewat, sediakan artisan command terpisah (mis. `selection:rescore`) yang dipanggil manual/terjadwal — jangan di jalankan baca.
2. Kalau ingin tetap hemat: `rank` bisa dibatch dengan satu UPDATE CASE per kabupaten+jalur, tapi prioritasnya menghapus pemanggilan dari GET.

## Test

Feature test halaman seleksi: membuka `operator.selection` tidak lagi mengubah tabel `selections` (skor/rank stabil), respons tetap menampilkan daftar terurut rank. Pastikan `SelectionDecisionTest` dan alur publikasi tetap hijau.

## Catatan penutup

- Hapus backfill scoring + recalculateRanking dari `SelectionController::index()` sehingga GET `operator.selection` bersifat read-only (tidak lagi membuat/mengubah `selections`).
- Scoring tetap di tempat yang benar: `ApplicationWorkflowService::verifyAgency` saat verifikasi dinas final dan `SelectionController::store` saat keputusan manual.
- Jaring pengaman: artisan `selection:rescore` (--kabupaten, --type, --period) untuk backfill skor terlewat + recalculate ranking secara manual.
- Proof: TDD merah->hijau `SelectionPageScoringTest` (GET tidak menambah baris, skor/rank & updated_at stabil, view tetap render terurut rank); `SelectionDecisionTest`, `SelectionScoringServiceTest`, `ApplicationWorkflowTest` hijau (13 tests).
- Pint: vendor/bin/pint --dirty passed.
