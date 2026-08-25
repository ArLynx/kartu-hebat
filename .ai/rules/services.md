---
paths:
  - 'app/Services/**'
---

# Services

## Services use domain-named methods + constructor DI
Business logic lives in app/Services classes invoked via constructor/action-param DI only — never `new` or `app()` except when resolving a class name from a data map (e.g. ScoringStrategyResolver). Name service methods after the domain action (`submit`, `verify`, `calculate`), not `handle`/`execute`. There is no Action-class pattern beyond the Fortify/Jetstream vendor scaffolding.

## Use DB::transaction(closure)
Wrap multi-step writes in `DB::transaction(function () use (...) { ... })`; never manual begin/commit/rollBack.
