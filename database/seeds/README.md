# Database seeds

Demo and development data — **not for production** with real users.

| Script | Purpose |
|--------|---------|
| `seed_demo.php` | Sample users, posts, challenges (idempotent skip if already seeded) |

Run: `php database/seed_demo.php` (wrapper) or `php database/seeds/seed_demo.php`

Fresh install with schema + users: `php database/setup.php`
