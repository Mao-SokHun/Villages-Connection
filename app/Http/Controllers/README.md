# app/Http/Controllers/ — Controller layer

Handles HTTP: read `$_POST`, validate, call **Models**, render **Views**.

| Subfolder | Pages |
|-----------|--------|
| `Public/` | Feed, login, profile, contact, … |
| `Admin/` | Dashboard, posts, users, settings, … |
| `Api/` | JSON: like, comment, follow, … |
| `Auth/` | Google / Facebook OAuth |

Dispatched by `public/router.php`.  
Logic stays in `app/Models/` — controllers should not add new business rules.

Guide: [docs/STRUCTURE.md](../../docs/STRUCTURE.md)
