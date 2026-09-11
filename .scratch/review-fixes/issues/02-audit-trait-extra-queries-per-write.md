# Trait Auditable menambah 2 query ekstra pada setiap write

Status: resolved
Type: task

## Masalah

`app/Traits/Auditable.php` → `writeAudit()` (± baris 45–52) berjalan pada setiap `created`/`updated`/`deleted` semua model auditable, dan masing-masing menambah roundtrip DB:

1. `Schema::hasTable('audit_logs')` — query information_schema di **setiap** event. Tabel ini tidak pernah hilang saat runtime.
2. `$model->fresh() === null` — SELECT penuh satu baris pada setiap created/updated hanya untuk cek eksistensi.

Efek: beban tulis seluruh aplikasi terdobel untuk operasi yang diaudit.

## Arah perbaikan

1. Cache hasil `Schema::hasTable('audit_logs')` dalam `private static ?bool $tableExists = null;` — evaluasi sekali per proses.
2. Ganti guard `$model->fresh() === null` dengan cara murah: event `deleted` saja yang butuh perlakuan khusus; untuk created/updated baris pasti ada (baru saja ditulis). Jika guard memang diperlukan untuk kasus update-remember_token-setelah-delete, batasi ke kasus itu atau pakai pengecekan kolom `exists` tanpa re-query.

## Test

Unit/feature test: hitung query (`DB::enableQueryLog()` atau `assertDatabaseCount`) bahwa satu `update` model auditable tidak lagi menghasilkan query audit tambahan berupa information_schema / select ulang model.

## Comments

2026-08-25 — Resolved.

- `Schema::hasTable('audit_logs')` kini dicache di `private static ?bool $tableExists` (dievaluasi sekali per proses per kelas pemakai trait).
- Guard `$model->fresh() === null` diganti guard sempit sesuai kasus FK yang didokumentasikan: hanya untuk `User` yang memperbarui baris dirinya sendiri (`instanceof User` + key == `auth()->id()`), dicek dengan `doesntExist()` (SELECT 1 terindeks) alih-alih `fresh()` (SELECT * + hydrate). Alasannya: satu-satunya FK di `audit_logs` adalah `user_id → users.id`; kolom morph `auditable` tidak berkendala FK, dan baris created/updated pasti ada karena baru ditulis instance yang sama. Model non-User dan update user lain kini bebas query ekstra sepenuhnya.
- Test baru `tests/Feature/AuditableTraitTest.php`: (1) hitung query — create membayar probe sekali, dua update berturut-turut 0 probe & 0 re-select, audit tetap tercatat 2 baris; (2) skenario stale row (baris user dihapus di belakang instance lalu `save()`) → tanpa error dan tanpa audit `updated`.
- Catatan: cache statis trait punya storage terpisah per kelas pemakai; test mereset via reflection pada `User::class`, bukan nama trait.
- Full suite hijau: 80 tests (76 passed, 4 skipped pre-existing). Pint bersih.
