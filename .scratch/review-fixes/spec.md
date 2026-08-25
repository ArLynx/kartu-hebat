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
- **#05 document-sync-reads-file-per-submit** — `PendaftaranWorkflowBridgeService::synchronizeDocuments()` tidak lagi membaca seluruh isi file (`$disk->get()`) hanya untuk size/checksum. Ukuran pakai `$source->ukuran_file ?: $disk->size($path)`; checksum dihitung hanya saat file baru/berubah via `$disk->checksum($path, ['checksum_algo' => 'sha256'])` (streaming `hash_file`), dipertahankan saat path sama; test merah→hijau di `DocumentSyncOptimizationTest` (3 test).
- **#06 minor-hygiene-bundle** — `PendaftaranWorkflowBridgeService` menerima `DocumentVerificationService` via constructor DI (dua `app(...)` dihapus); import FQCN di `ApplicationWorkflowService` sudah bersih sejak awal; `SelectionController::index()` menghitung `typeCounts` dengan satu query `groupBy('application_type')` menggantikan 1 COUNT per jalur; batasan audit mass delete didokumentasikan di `CONTEXT.md`. Konfirmasi scope superadmin (#4) dibiarkan terbuka — jalur `whereRaw('1 = 0')` hanya tercapai dari route `role:operator_*`. Test merah→hijau di `HygieneBundleTest` (3 test, termasuk `DB::listen` yang memastikan satu query groupBy).
