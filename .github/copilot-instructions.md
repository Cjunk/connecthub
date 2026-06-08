# ConnectHub Copilot Instructions

You are working in a PHP web application with mixed legacy and modern files.

## Core priorities
- Preserve behavior unless a change request explicitly asks for functional changes.
- Prefer minimal, targeted edits over broad refactors.
- Follow existing naming, folder, and include/bootstrap patterns.
- Reuse existing utilities/config/bootstrap files before adding new helpers.

## Security and data handling
- Validate and sanitize all request input (`$_GET`, `$_POST`, `$_REQUEST`).
- Use prepared statements for all database access. Never concatenate SQL with user input.
- Enforce auth and role checks for dashboard/admin/group/payment actions.
- Do not leak secrets, API keys, tokens, session IDs, or database credentials.
- For payment code, fail closed and log safely (no sensitive card/token values in logs).

## PHP and database style
- Keep compatibility with the current project PHP style in each file.
- Avoid introducing new dependencies unless clearly justified.
- Add concise comments only for non-obvious logic.
- Keep error messages useful but not sensitive.

## Testing and verification
- After edits, suggest practical verification steps for changed flows.
- For risky areas (auth, payments, membership), call out regression risks explicitly.
