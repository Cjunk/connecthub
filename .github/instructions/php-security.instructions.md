---
applyTo: "**/*.php"
---

When editing PHP files in this repository:

- Keep edits small and localized.
- Maintain current include/require path conventions.
- Validate all incoming request values before use.
- Use strict checks for authentication and authorization paths.
- Use prepared SQL statements; avoid dynamic SQL with user-controlled values.
- Keep logs free of secrets and payment-sensitive values.
- Prefer existing helper functions/config files instead of introducing new patterns.
