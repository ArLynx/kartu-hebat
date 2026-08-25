# Bundle kebersihan kecil (DI, import, query count, konfirmasi scope)

Status: ready-for-agent
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
