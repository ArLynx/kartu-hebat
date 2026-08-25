---
paths:
  - 'app/Enums/**'
---

# Enums

## Backed string enums with match-based helper methods
Declare enums as `enum X: string` with SCREAMING_SNAKE cases, cast them in model `casts()`, and add behavior as `match ($this)` methods (`label()`, `tone()`, domain predicates) instead of switch/if chains. Store value = case name unless it must be lowercase (user roles).
