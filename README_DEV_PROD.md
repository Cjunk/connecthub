# Dev vs Prod setup (Quick Guide)

This project supports a clean separation between development and production environments.

## Files that control environments

- `config/env_loader.php` — loads .env and .env.production depending on APP_ENV
- `config/constants.php` — prefers `production_config.php` (server-only), then `config/local_config.php` (dev)
- `production_config.php` (ignored by git) — server-only secrets
- `.env` — optional local defaults (dev)
- `.env.production` — optional server overrides (prod)

## Recommended usage

### Development (local)
- Keep `APP_ENV=development` (default)
- Set local DB and test keys via `config/local_config.php` or `.env`
- Do not commit secrets

### Production (server)
- Put `production_config.php` in project root with real DB/SMTP/API keys
- Optional: add `.env.production` for environment overrides
- Ensure `config/local_config.php` is not present on server

## Deployment tip
- Use a Git workflow (main branch → production). Keep secrets only on the server via `production_config.php` and/or `.env.production`.

## Cleanup note
- Avoid duplicate public endpoints. Root `register.php` and `auth/register.php` are the canonical routes; the legacy copies under `connecthub/public/` were removed.
