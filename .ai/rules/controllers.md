---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Group controllers by user role namespace
Place controllers under `app/Http/Controllers/<Role>/` matching the route role group (Mahasiswa, Operator, Superadmin, Student, Public). Keep services flat in app/Services; use a subfolder only for a family of related classes (e.g. Scoring).

## Eager-load per query with ->with(), no $with defaults
Pass `->with(['relations'])` explicitly per query in controllers/services; do not set `$with` defaults on models.

## Prefer plain multi-method controllers over resource scaffolding
Hand-route each action with explicit verb methods; only use `__invoke` for single-action controllers (dashboards, landings) and `Route::resource` for Superadmin CRUD, always with `->except('show')` and camelCase `->parameters()`.

## Use implicit route model binding for route params
Type-hint the model in the controller signature and match the route param name to the model instance; reserve `findOrFail` for lookups that aren't route parameters (relation/child queries scoped to the authenticated user).

## Authorize per-object via policies; gate routes by role middleware
Use `$this->authorize()` / `$user->can()` against policy classes for object-level checks, and `role:...` middleware on route groups for role gating.

## Typed request input accessors
Retrieve input via typed accessors (`$request->string()`, `->integer()`, `->boolean()`, `->validated()`); avoid raw `->input()` except when string-normalizing codes (Str::upper/trim).

## Named routes everywhere; no url()/action()
Generate links/redirects with `route('name')` (and `redirect()->route(...)`); avoid `url('/path')` and `action([...])`.
