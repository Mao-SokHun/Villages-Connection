# Database migrations

Incremental schema changes. **Append-only** — do not edit migrations already applied in production.

## Runner

`database/migrate.php` applies tracked migrations from this folder and records them in `schema_migrations`.

```bash
php database/migrate.php
```

## Adding a migration

1. Create `migrate_my_feature.php` in this folder.
2. Use paths: `require_once __DIR__ . '/../../config/config.php';`
3. Add the filename to the `$migrations` array in `database/migrate.php`.
