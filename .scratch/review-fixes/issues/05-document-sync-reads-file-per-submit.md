# Sinkronisasi dokumen membaca seluruh isi file pada setiap submit

Status: ready-for-agent
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
