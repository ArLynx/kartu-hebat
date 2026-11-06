---
paths:
  - 'app/Http/Controllers/Operator/**'
---

# Operator

## Formulir files are separate from application documents
Surat Permohonan and Pakta Integritas live on `formulir_pendaftarans` via `pendaftaran.formulirPendaftaran`, not `application.documents`. Serve them through `ApplicationFormulirController` with `authorize('view', $application)` and the private document disk.

## Pengajuan hasil sanggahan punya antrean sendiri untuk operator dinas
Application VERIFIKASI_DINAS dengan sanggahan DIKABULKAN adalah 'menunggu verifikasi ulang'. Operator dinas melihatnya di operator.sanggahan-queue.index (SanggahanQueueController), bukan di operator.applications.index. Antrean biasa mengecualikannya lewat Application::whereNotAwaitingReverification() untuk role dinas; antrean sanggahan memakai whereAwaitingReverificationBy($user) yang juga menyaring jalur verifiedTrack dan dinas yang belum mengirim keputusan baru. Jangan menambah kondisi DIKABULKAN + periode aktif secara manual — pakai scope whereReopenedForVerification().
