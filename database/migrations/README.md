# Database migrations

Incremental schema changes. **Append-only** — do not edit migrations already applied in production.

## Runner

`database/migrate.php` applies tracked migrations and records them in `schema_migrations`.

## Standalone scripts

Run once per environment (also available via wrappers at `database/migrate_*.php`):

- `migrate_user_preferences.php`
- `migrate_incident_reports.php`
- `migrate_phase25.php`

## Adding a migration

1. Create `migrate_my_feature.php` in this folder.
2. Use paths: `require_once __DIR__ . '/../../config/config.php';`
3. If tracked, add filename to `$migrations` array in `database/migrate.php`.
4. Add wrapper at `database/migrate_my_feature.php` if CLI backward compat is needed.
