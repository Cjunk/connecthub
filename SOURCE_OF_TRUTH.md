# Source Of Truth Policy

This workspace has two app trees:
- Root app: `F:\connecthub` (authoritative)
- Nested copy: `F:\connecthub\connecthub` (legacy/secondary)

## Rule
Always edit and deploy from the root app only.

## Why
Production packaging and deployment scripts already use the root app and exclude the nested copy.

## Quick Checks
1. Run drift check:
   - `powershell -ExecutionPolicy Bypass -File .\check-source-sync.ps1`
2. Confirm production package excludes nested folder:
   - `create_production_zip.ps1` removes `connecthub` from temp package.
3. Confirm changed files exist in root before deploy.

## If Drift Is Found
1. Treat root as canonical.
2. Do not copy nested files back into root.
3. Optionally mirror root updates into nested only if legacy tooling still reads nested paths.

## Minimum Pre-Deploy Verification
1. `php -l` on changed PHP files.
2. Open login, dashboard, membership flow locally.
3. Deploy from root paths only.
4. Smoke test live endpoints:
   - `/login.php`
   - `/dashboard.php`
   - `/payment/create-payment-intent.php` (authenticated)
