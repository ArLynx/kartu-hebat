---
paths:
  - 'app/Http/**'
---

# Http

## Custom validation via Rule:: inline, no rule classes
Express custom validation needs with `Illuminate\Validation\Rule` (unique/exists/in/enum/requiredIf) inline in rule arrays; do not create invokable rule classes or closures.
