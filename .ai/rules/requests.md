---
paths:
  - 'app/Http/Requests/**'
---

# Requests

## Form Requests for operator verification flows, inline elsewhere
Use Form Request classes for the operator document-verification/selection features (VerificationRequest, SelectionRequest, DocumentVerificationRequest); other areas validate inline with `$request->validate()`.
