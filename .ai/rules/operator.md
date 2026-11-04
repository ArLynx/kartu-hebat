---
paths:
  - 'app/Http/Controllers/Operator/**'
---

# Operator

## Formulir files are separate from application documents
Surat Permohonan and Pakta Integritas live on `formulir_pendaftarans` via `pendaftaran.formulirPendaftaran`, not `application.documents`. Serve them through `ApplicationFormulirController` with `authorize('view', $application)` and the private document disk.
