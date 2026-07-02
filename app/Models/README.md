# app/Models/ — Business logic (Model layer) 🔒

**Do not edit casually** — login, posts, admin, mail, OAuth live here as PHP functions.

This is the **Model** layer in MVC (not OOP classes — procedural modules + `$pdo`).

| Module | Domain |
|--------|--------|
| `permissions.php` | Auth, roles |
| `features.php` | Posts, comments, likes |
| `admin.php` | Admin operations |
| `member.php` | Notifications, follows |

See [docs/LOGIC_LOCK.md](../../docs/LOGIC_LOCK.md) and [docs/STRUCTURE.md](../../docs/STRUCTURE.md).
