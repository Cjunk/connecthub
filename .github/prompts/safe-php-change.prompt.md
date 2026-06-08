# Safe PHP Change Request

Use this prompt when requesting PHP changes in this repository.

## Goal
Describe the change in one sentence.

## Scope
- Only edit these files: <list files>
- Do not refactor unrelated code.

## Constraints
- Preserve existing behavior except for requested change.
- Validate and sanitize all request input.
- Use prepared statements for DB access.
- Keep auth and role checks intact.
- Avoid logging secrets or payment-sensitive data.

## Output format
- Summary of changes
- Files touched
- Risk and regression notes
- Manual verification checklist
