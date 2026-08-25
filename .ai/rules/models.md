---
paths:
  - 'app/Models/**'
---

# Models

## Mass assignment via $guarded = [] on new models
Use `protected $guarded = []` on new models rather than a `$fillable` allow-list.

## Legacy accessors, not Attribute-class casts
Use the legacy magic-method style (`getXxxAttribute()`) for read-only model accessors; do not introduce the `Attribute` class.

## Casts via casts() with built-in strings and enum classes
Define `protected function casts(): array` with built-in cast strings and enum class casts. No custom CastsAttributes classes.
