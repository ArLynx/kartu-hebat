# Bundle kebersihan kecil (DI, import, query count, konfirmasi scope)

Status: resolved
Type: task

Kumpulan temuan minor dari review — masing-masing kecil, boleh dikerjakan satu PR.

## 1. Ganti `app(...)` dengan constructor DI

Melanggar aturan proyek `.ai/rules/services.md` ("never `app()`"):

- `app/Services/PendaftaranWorkflowBridgeService.php` ± baris 26 dan 268 → `app(DocumentVerificationService::class)`
- `app/Http/Controllers/Mahasiswa/DokumenController.php` sudah diperbaiki saat bug #1; cari sisa pemakaian lain dengan grep `app\(.*Service::class\)`.

## 2. FQCN inline → import

`app/Services/ApplicationWorkflowService.php` → `assertAllDocumentsVerified()` memakai `\App\Models\Document::query()` dan `\App\Models\DocumentVerification::query()` inline; pindah ke blok `use`.

## 3. typeCounts: 4 query COUNT terpisah

`SelectionController::index()` menjalankan satu COUNT per `ApplicationType`. Bisa satu query `groupBy('application_type')` lalu dipetakan ke semua cases.

## 4. Konfirmasi: SUPERADMIN melihat nol application

`app/Models/Application.php` → `scopeVisibleTo()` memberi `whereRaw('1 = 0')` untuk SUPERADMIN. Kemungkinan disengaja (superadmin tidak memproses pengajuan) tapi perlu dikonfirmasi ke pemilik produk; kalau tidak disengaja, ubah agar superadmin melihat semua.

## 5. Catatan audit mass delete

`Auditable` hanya mencatat delete lewat event Eloquent; `query()->delete()` massal tidak ter-audit. Tidak wajib diperbaiki — cukup didokumentasikan sebagai keterbatasan di CONTEXT.md/ADR jika sistem audit dianggap kritikal.

## Catatan penutup

- **#1** — `PendaftaranWorkflowBridgeService` kini menerima `DocumentVerificationService` via constructor DI (`$this->documentVerification->resetForApplication()` / `resetForDocument()`); kedua pemakaian `app(...)` dihapus. Grep `app\(.*Service::class\)` di `app/` sudah bersih (sisa hanya `app()` yang memang sah di enum `ApplicationType::scoringStrategyClass()`).
- **#2** — `ApplicationWorkflowService::assertAllDocumentsVerified()` sudah memakai import `Document` / `DocumentVerification` (tanpa FQCN inline) sejak sebelum tiket ini; tidak ada perubahan yang diperlukan.
- **#3** — `SelectionController::index()` kini menghitung `typeCounts` dengan satu query `selectRaw('application_type, count(*)') -> groupBy('application_type')` lalu dipetakan ke semua `ApplicationType::cases()`, menggantikan 1 COUNT per jalur.
- **#4** — Tidak diubah. `scopeVisibleTo()` memberi `whereRaw('1 = 0')` untuk SUPERADMIN, tetapi semua pemakaian `visibleTo()` berada di route group `role:operator_*` (`routes/web.php`) sehingga superadmin tidak pernah menjangkau kode itu lewat UI — jalur ini praktis dead code dan konfirmasi ke pemilik produk tetap dibuka.
- **#5** — Tidak diperbaiki; keterbatasan audit mass delete didokumentasikan di `CONTEXT.md` (Keterbatasan yang disengaja).

Test: `tests/Feature/HygieneBundleTest.php` (3 test) — DI service ter-inject via constructor, `typeCounts` per jalur benar (2/1/0/0), dan `typeCounts` dihitung dari tepat satu query groupBy. Seluruh suite hijau (92 test, 4 skip).
