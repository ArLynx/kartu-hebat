---
paths:
  - 'app/Services/**'
---

# Services

## Services use domain-named methods + constructor DI
Business logic lives in app/Services classes invoked via constructor/action-param DI only — never `new` or `app()` except when resolving a class name from a data map (e.g. ScoringStrategyResolver). Name service methods after the domain action (`submit`, `verify`, `calculate`), not `handle`/`execute`. There is no Action-class pattern beyond the Fortify/Jetstream vendor scaffolding.

## Use DB::transaction(closure)
Wrap multi-step writes in `DB::transaction(function () use (...) { ... })`; never manual begin/commit/rollBack.

## Alur verifikasi hanya lintas dinas — tanpa desa/kecamatan & BTL
Submit mahasiswa langsung ke VERIFIKASI_DINAS. Tiga dinas (Dukcapil/Sosial/Pendidikan) verifikasi paralel dengan keputusan MS/TMS saja — tidak ada BTL. Dinas Kesehatan (Dinkes) ikut memverifikasi **hanya jalur Disabilitas**, sehingga DISABILITAS butuh 4 dinas. Jumlah dinas wajib ditentukan oleh `DocumentVerificationService::requiredAgencies()` — jangan hardcode `count(config('kartu_hebat.agencies'))`. Semua MS → SELEKSI_KABUPATEN; ada satu TMS → TMS. Perbaikan: aplikasi di-set DRAFT lalu submit ulang langsung ke dinas. Role operator_desa/operator_kecamatan dipertahankan hanya untuk laporan/riwayat, tidak bisa verify (ApplicationPolicy::verify hanya role dinas yang tercantum).

## Jumlah dinas wajib per jalur via requiredAgencies()
Jangan hardcode jumlah dinas (mis. `count(config('kartu_hebat.agencies'))`) saat memutuskan aplikasi lengkap/verifikasi. Dinkes hanya terlibat pada jalur DISABILITAS; jumlah dinas wajib ditentukan per aplikasi oleh `DocumentVerificationService::requiredAgencies()` (3 utk jalur lain, 4 utk Disabilitas).
