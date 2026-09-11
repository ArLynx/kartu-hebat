---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Anonymous Blade components; @include only for shared form partials
Create new UI pieces as anonymous components in resources/views/components/; reserve @include for `_form`-style partials reused within one CRUD resource; only AppLayout/GuestLayout are class components.
