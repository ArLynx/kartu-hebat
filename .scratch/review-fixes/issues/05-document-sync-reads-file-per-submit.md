# Sinkronisasi dokumen membaca seluruh isi file pada setiap submit

Status: resolved
Type: task

## Masalah

`app/Services/PendaftaranWorkflowBridgeService.php` → `synchronizeDocuments()` (± baris 262–279): untuk setiap dokumen pendaftaran, setiap kali submit (termasuk resubmit setelah BTL):

```php
$contents = $disk->get($source->file_path);            // seluruh isi file di memori
'size' => $source->ukuran_file ?: strlen($contents),
'checksum' => hash('sha256', $contents),
```

File dibaca penuh hanya untuk ukuran + checksum — juga saat `path` tidak berubah sama sekali.

## Arah perbaikan

1. Ukuran: pakai `$disk->size($path)`; kolom `ukuran_file` sudah tersimpan sejak upload, jadi fallback jarang diperlukan.
2. Checksum: hitung hanya saat file benar-benar baru/berubah (`$existing === null || $existing->path !== $source->file_path`). Saat path sama, pertahankan checksum lama.

## Test

Feature test alur submit → BTL → resubmit tanpa ganti file: dokumen tetap tersinkron dengan `checksum`/`size` yang sama; lalu ganti file → checksum berubah. (Alur bahagia sudah ditutup `BeasiswaRegistrationFlowTest`.)

## Catatan penutup

Diselesaikan 2026-08-25. `$disk->get()` (baca penuh file ke memori) dihapus dari `synchronizeDocuments()`:

- Ukuran kini `$source->ukuran_file ?: $disk->size($path)` — nilai metadata, bukan baca isi file.
- Checksum hanya dihitung saat file baru/berubah (`existing === null || path !== file_path`) via `$disk->checksum($path, ['checksum_algo' => 'sha256'])` yang streaming (`hash_file`); saat path sama, checksum lama dipertahankan.
- Versi & reset verifikasi dokumen tetap berjalan seperti sebelumnya.

Test merah→hijau di `DocumentSyncOptimizationTest` (3 test): resubmit tanpa ganti file mempertahankan checksum/size dan tidak membaca isi file ulang; ganti file → checksum & size diperbarui. Suite terkait (`BeasiswaRegistrationFlowTest`, `VillageLookupTest`, `ApplicationWorkflowTest`, `DocumentVerificationTest`) tetap hijau.
