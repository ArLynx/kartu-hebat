# Jenis dokumen wajib bisa bocor saat submit application

Status: resolved
Type: task

## Masalah

Saat submit, daftar dokumen wajib dibangun dari dua tabel berbeda tanpa validasi kecocokan:

`app/Services/ApplicationWorkflowService.php` → `submit()` (± baris 70–83):

```php
$requiredSourceTypes = collect(...jenisDokumens...)->where('aktif', true);
$requiredTypes = DocumentType::query()
    ->whereIn('code', $requiredSourceTypes->pluck('kode'))
    ->where('is_active', true)
    ->get();
```

Jika `kode` pada `jenis_dokumens` tidak punya padanan di `document_types.code`, jenis itu **hilang diam-diam** dari daftar wajib → mahasiswa bisa submit tanpa dokumen tersebut.

Logika missing juga convoluted:

```php
$missing = $requiredTypes->whereIn('id', $requiredIds->diff($uploadedIds))->pluck('name');
```

cukup `$requiredIds->diff($uploadedIds)` lalu ambil nama dari `$requiredTypes`.

## Arah perbaikan

1. Bandingkan kode sumber vs hasil lookup: jika ada kode yang tidak menemukan `DocumentType`, lempar ValidationException yang jelas (data master belum disinkron), jangan diam-diam melewatinya.
2. Sederhanakan hitung missing dengan diff ID + map nama.

## Test

Feature test: buat kondisi satu `kode` tidak punya `DocumentType` aktif → submit harus gagal dengan pesan eksplisit (bukan lolos). Dan: semua cocok → perilaku lama tetap hijau (`tests/Feature/BeasiswaRegistrationFlowTest.php` sudah menutup jalur bahagia).

## Comments

2026-08-25 — Resolved.

- Guard baru di `ApplicationWorkflowService::submit()`: kode sumber yang tidak menemukan `DocumentType` aktif kini melempar `ValidationException` (`documents`: "Jenis dokumen wajib X tidak ditemukan pada data master dokumen...") alih-alih bocor diam-diam. Ini juga menutup kasus `DocumentType` ada tapi `is_active = false`.
- Hitung missing disederhanakan jadi `$requiredTypes->except($uploadedIds->all())->pluck('name')`; `$requiredIds` dihapus.
- Test merah→hijau: `ApplicationWorkflowTest::test_submit_rejects_when_required_jenis_dokumen_has_no_master_match` (sebelum fix submit lolos diam-diam; sesudah fix gagal + status tetap DRAFT).
- Jalur bahagia tetap hijau: `ApplicationWorkflowTest` (6 tests) dan `BeasiswaRegistrationFlowTest` (3 tests). Pint bersih.
