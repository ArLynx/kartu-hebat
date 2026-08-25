---
paths:
  - 'app/**'
  - 'app/**/*.php'
---

# App

## No repository/query-object layer
Call Eloquent directly from controllers and services (scopes, `->with()`, `DB::transaction` inline). Do not introduce repositories or query objects exposing `builder()`. There is also no DTO layer and no event/listener bus — pass models/arrays and orchestrate synchronously in services.

## Global helpers over facades (DB and Hash excepted)
Use global helpers (`config()`, `auth()`, `route()`, `redirect()`, `now()`, `collect()`) in new code; reach for facades only for DB and Hash. App config lives in config/kartu_hebat.php and is read via `config('kartu_hebat.*')`.

## Idempotent writes use updateOrCreate
Prefer `Model::updateOrCreate([...], [...])` over manual find-then-save for upserts; `upsert()` only for bulk writes.

## Use now()/today() helpers, not Carbon::
Use the global helpers `now()`/`today()`; don't call `Carbon::` directly and don't switch the Date facade to CarbonImmutable.
