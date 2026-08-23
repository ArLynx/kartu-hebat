# ~~Verifikasi dokumen bersifat implisit (keputusan keseluruhan, bukan per-dokumen)~~ — SUPERSEDED

> **Status: digantikan oleh [ADR-0003](0003-verifikasi-dokumen-per-berkas.md).**
> Verifikasi dokumen kini bersifat per-berkas (checklist MS/TMS) di tiap tahap.

## (arsip) Konteks asli

Saat operator (desa, kecamatan, atau dinas) memverifikasi pengajuan, mereka memberi satu keputusan keseluruhan — MS/BTL/TMS — atas semua dokumen sekaligus, bukan status terpisah per dokumen. Ini disengaja: memecah keputusan per-dokumen menambah biaya operasional tanpa mengubah hasil alur (BTL tetap mengembalikan pengajuan ke mahasiswa). Status per-dokumen tidak disimpan; koreksi spesifik dicatat lewat catatan verifikasi (notes) pada `village_verifications`, `district_verifications`, dan `agency_verifications`.
