---
paths:
  - 'database/migrations/**'
---

# Migrations

## Foreign keys use foreignId()->constrained()
Declare FKs with `$table->foreignId('x_id')->constrained()` (+ chained `->cascadeOnDelete()`/`->nullOnDelete()`); avoid manual `foreign()->references()->on()` and `foreignIdFor()`.

## Migrations always reverse in down()
Every migration must implement `down()` with real reversal (dropIfExists / dropColumn / dropConstrainedForeignId), never an empty body.

## DB enum only for fixed lookup columns; state enums are string + cast
Use DB `enum()` only for genuinely fixed vocabularies (gender, document type); store workflow state as a `string()` column and cast it to a PHP enum (ApplicationStatus::class) on the model so state can evolve without schema churn.
