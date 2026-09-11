---
paths:
  - 'tests/**'
---

# Tests

## PHPUnit, never Pest
Write tests as PHPUnit classes extending `TestCase`; do not introduce Pest syntax.

## Use RefreshDatabase in tests
Apply the `RefreshDatabase` trait for DB reset in feature tests; avoid `DatabaseTruncation`/`DatabaseMigrations`.

## Factories for test fixtures; integration-style tests
Build fixtures with model factories (`Model::factory()->create()`); manual array `create([` belongs in seeders, not tests. Tests are integration-style — no Mockery, no facade fakes.
