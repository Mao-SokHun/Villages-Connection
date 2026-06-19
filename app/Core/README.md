# Core modules (logic locked)

Business logic for Village Connect. **Do not refactor casually** — see `docs/LOGIC_LOCK.md`.

Boot order for web requests is defined in `../bootstrap/core.php`.

| Module | Responsibility |
|--------|----------------|
| `admin.php` | Users, posts admin, settings, bans, categories |
| `analytics.php` | Dashboard stats, CSV exports |
| `backup.php` | SQL backup generation |
| `cloudinary.php` | Optional CDN uploads |
| `Controller.php` | Base MVC controller |
| `View.php` | Template renderer |
| `features.php` | Comments, post helpers |
| `helpers.php` | Cache, flash, UI helpers, formatting |
| `i18n.php` | `__()` translations |
| `mail.php` | Email templates & SMTP |
| `member.php` | Notifications, follows, bookmarks, feed |
| `oauth.php` | Google/Facebook OAuth |
| `permissions.php` | Roles, session checks |
| `push.php` | Web push subscriptions |
| `rate_limit.php` | Request throttling |
| `routes.php` | CSRF, API security, HTTP method guards |
| `security.php` | Security headers |
| `uploads.php` | File upload validation |
| `urls.php` | Pretty URL helpers |
| `verification.php` | Email verification, password OTP |

Loaded on demand (not in default boot): `analytics.php`, `backup.php`, `cloudinary.php`, `mail.php`, `oauth.php`, `rate_limit.php`, `security.php`.
