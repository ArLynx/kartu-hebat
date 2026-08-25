---
paths:
  - 'app/Traits/**'
---

# Traits

## Model events via trait bootXxx() hooks, not observers
Add model-event logic as a `bootXxx()` trait method (see Auditable) or a `booted()` closure in the model. No Observers.
