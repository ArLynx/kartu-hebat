---
paths:
  - 'app/Services/**'
  - app/Services/SanggahanService.php
---

# Services

## Services use domain-named methods + constructor DI
Business logic lives in app/Services classes invoked via constructor/action-param DI only — never `new` or `app()` except when resolving a class name from a data map (e.g. ScoringStrategyResolver). Name service methods after the domain action (`submit`, `verify`, `calculate`), not `handle`/`execute`. There is no Action-class pattern beyond the Fortify/Jetstream vendor scaffolding.

## Use DB::transaction(closure)
Wrap multi-step writes in `DB::transaction(function () use (...) { ... })`; never manual begin/commit/rollBack.

## Alur verifikasi hanya lintas dinas — tanpa desa/kecamatan & BTL
Submit mahasiswa langsung ke VERIFIKASI_DINAS. Tiga dinas inti (Dukcapil/Pendidikan/BKPSDM) memverifikasi paralel untuk semua jalur dengan keputusan MS/TMS saja — tidak ada BTL. Dinas Sosial hanya jalur TIDAK_MAMPU; Dinas Kesehatan (Dinkes) hanya jalur Disabilitas; Parsepor hanya jalur Prestasi. Role track-bound difilter lewat `UserRole::verifiedTrack()`. Jumlah dinas wajib ditentukan oleh `AgencyVerificationService::requiredAgencies()` — jangan hardcode. Semua MS → SELEKSI_KABUPATEN; ada satu TMS → TMS. Perbaikan: aplikasi di-set DRAFT lalu submit ulang langsung ke dinas. Role operator_desa/operator_kecamatan dipertahankan hanya untuk laporan/riwayat, tidak bisa verify (ApplicationPolicy::verify hanya role dinas yang tercantum).

## Jumlah dinas wajib per jalur via requiredAgencies()
Jangan hardcode jumlah dinas (mis. `count(config('kartu_hebat.agencies'))`) saat memutuskan aplikasi lengkap/verifikasi. Sosial hanya jalur TIDAK_MAMPU; Dinkes hanya DISABILITAS; Parsepor hanya NON_AKADEMIK. Jumlah dinas wajib ditentukan per aplikasi oleh `AgencyVerificationService::requiredAgencies()` (3 utk AKADEMIK, 4 utk TIDAK_MAMPU/Disabilitas/Prestasi).

## Sanggahan hanya membuka kembali dinas yang memutuskan TMS
Pengabulan sanggahan menghapus hanya agency_verifications berstatus TMS pada dinas wajib, beserta document_verifications dinas itu pada putaran berjalan. Dinas MS tidak boleh disentuh. Gerbang verifikasi ulang dan penetapan penerima memakai Application::isReopenedForVerification() / isVerificationWindowOpen(), jangan menduplikasi kondisi sanggahan DIKABULKAN + periode aktif di tempat lain.
