# Review Fixes — Kartu Hebat

Hasil review file utama (services, controllers, models) pada sesi 2026-08-25: bug dan inefisiensi beserta tiket perbaikannya.

## Sudah dikerjakan

- **#1 Reset penilaian dokumen salah sasaran** — `DokumenController::store` memakai `jenis_dokumens.id` untuk kolom `documents.document_type_id` (FK ke `document_types`). Fix: mapping via `kode`, test merah→hijau di `BeasiswaRegistrationFlowTest`.
- **#2 Race cek kuota seleksi** — cek kuota dipindah ke dalam transaksi dengan `lockForUpdate()` (`SelectionController::quotaAvailable`), kontrak dites di `SelectionDecisionTest`.

- **#2 Race cek kuota seleksi** — cek kuota dipindah ke dalam transaksi dengan `lockForUpdate()` (`SelectionController::quotaAvailable`), kontrak dites di `SelectionDecisionTest`.
- **#1 required-document-leak-on-submit** — `submit()` kini menolak pengajuan saat `jenis_dokumens.kode` tidak punya padanan `document_types.code` aktif (ValidationException eksplisit); test di `ApplicationWorkflowTest`.
- **#03 selection-page-scoring-on-get** — `SelectionController::index()` tidak lagi backfill skor / `recalculateRanking()` di GET (read-only); scoring tetap di `ApplicationWorkflowService::verifyAgency` dan `SelectionController::store`; jaring pengaman `selection:rescore` (--kabupaten/--type/--period); test di `SelectionPageScoringTest` (merah->hijau).
- **#2 audit-trait-extra-queries-per-write** — `Schema::hasTable` dicache statis; guard `fresh()` diganti cek `doesntExist()` hanya untuk update baris User sendiri; test di `AuditableTraitTest`.
- **#04 village-lookup-full-table-scan** — `PendaftaranWorkflowBridgeService::resolveVillage()` tidak lagi `Village::get()` seluruh tabel; kandidat dibatasi di DB (`whereHas('kecamatan')` + `whereHas('kecamatan.kabupaten')` + `where('name','like',...)` pada input ternormalisasi yang di-escape), presisi eksak PHP tetap berlaku atas kandidat tersisa; test merah→hijau di `VillageLookupTest` (4 test, termasuk `DB::listen` yang memastikan query villages ber-WHERE).

## Tiket terbuka

| # | Tiket | Ringkas |
|---|-------|---------|
| 05 | document-sync-reads-file-per-submit | Isi file dibaca penuh tiap submit hanya untuk size/checksum |
| 06 | minor-hygiene-bundle | DI via constructor, import FQCN, COUNT groupBy, konfirmasi scope superadmin |
