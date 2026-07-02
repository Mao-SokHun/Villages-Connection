# public/ — Web root

Only **`router.php`** handles PHP requests here. Static assets live alongside it.

| Path | Role |
|------|------|
| `router.php` | Front controller — bootstrap + dispatch to `app/Http/Controllers/` |
| `css/`, `js/`, `icons/`, `uploads/` | Static files (served directly) |

```bash
php -S localhost:8080 -t public public/router.php
```

Guide: [docs/STRUCTURE.md](../docs/STRUCTURE.md)
