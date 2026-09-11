# Pencarian desa memuat seluruh tabel villages ke memori

Status: resolved
Type: task

## Masalah

`app/Services/PendaftaranWorkflowBridgeService.php` → `resolveVillage()` (± baris 84–92): ketika `data_pribadi.village_id` kosong, kode menjalankan:

```php
$matches = Village::query()->with('kecamatan.kabupaten')->get()  // seluruh tabel!
    ->filter(fn (Village $village) => ... nama dicocokkan di PHP ...)
```

lalu mencocokkan nama desa/kecamatan/kabupaten yang dinormalisasi **di PHP**. Berat dan memburuk seiring jumlah master wilayah; dieksekusi di jalur submit mahasiswa.

## Arah perbaikan

1. Dorong filter ke DB: normalisasi dulu inputnya, lalu `Village::whereHas('kecamatan', ...)`, `whereHas('kecamatan.kabupaten', ...)` dengan pencocokan nama.
2. Normalisasi `^kabupaten|kab\.?|kota|...` saat ini regex PHP; untuk DB cukup bandingkan dengan LIKE/eksak setelah prefix dilepas di sisi input — jika presisi PHP benar-benar dibutuhkan, batasi kandidat lewat where nama desa dulu (`->where('name', 'like', '%'.$villageName.'%')`) sehingga yang masuk memori tinggal segelintir baris.

## Test

Feature test: submit pendaftaran tanpa `village_id` tapi dengan teks kecamatan/desa/kabupaten valid → tetap menemukan desa yang sama dan mengisi `village_id`; teks ambigu/tidak ada → ValidationException seperti sekarang. Bandingkan jumlah query sebelum/sesudah bila mudah.

## Catatan penutup

`resolveVillage()` tidak lagi memuat seluruh tabel `villages`. Kandidat dibatasi di DB lewat `whereHas('kecamatan', ...)` + `whereHas('kecamatan.kabupaten', ...)` + `where('name', 'like', ...)` dengan input yang sudah dinormalisasi (LIKE di-escape `\ % _`), lalu presisi eksak yang normalisasi PHP tetap dipakai atas kandidat yang tersisa — menggabungkan arah perbaikan 1 dan 2.

Test: `tests/Feature/VillageLookupTest.php` (merah→hijau):
- `test_submit_resolves_village_from_text_when_village_id_missing` — teks wilayah valid → `village_id` dan kolom teks diisi, status `VERIFIKASI_DESA`.
- `test_submit_rejects_unknown_village_text` — teks tak dikenal → `ValidationException` "tidak ditemukan".
- `test_submit_rejects_ambiguous_village_text` — dua kabupaten/kecamatan/desa sama nama → `ValidationException` "ambigu".
- `test_village_lookup_is_filtered_in_database_not_full_table_scan` — `DB::listen` memastikan semua query ke `villages` memuat `WHERE`.

Semua 4 test baru lulus; suite lengkap 82 lulus / 4 skip (skip sudah ada sebelumnya). `vendor/bin/pint --dirty` dijalankan.
