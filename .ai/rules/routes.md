---
paths:
  - routes/web.php
---

# Routes

## Route handlers are controller classes, never closures
Register every route to a controller via `[Controller::class, 'method']` or `Controller::class` for invokables; closures are reserved for legacy redirect shims only.

## Assign middleware via route groups, not controllers
Apply auth/verified/role/2fa middleware through nested `Route::middleware()->group()` blocks in web.php; register custom middleware (role, 2fa.ensure, nocache) as aliases in bootstrap/app.php. Never put middleware in controllers.
