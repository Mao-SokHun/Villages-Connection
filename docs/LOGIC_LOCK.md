# Logic lock — Village Connect

This document defines **protected zones**. Changes here can break login, posts, admin, or database integrity.

Before editing locked files, confirm the change is intentional and test locally.

---

## 🔒 Locked zones

| Path | Why locked |
|------|------------|
| `app/Models/*.php` | All business logic (auth, posts, admin, mail, …) |
| `config/config.php` | Session, env, `requireLogin()`, `requireStaff()` |
| `config/database.php` | PDO connection, SSL, timezone |
| `database/schema.sql` | Base schema |
| `database/migrations/*.php` | Applied migrations (append-only) |
| `app/bootstrap/core.php` | Core load order (must stay stable) |
| `bootstrap.php`, `bootstrap-api.php` | Application boot sequence |

---

## ✅ Safe zones (UI / structure) — customize freely

| Path | Typical changes |
|------|-----------------|
| `app/Views/**` | HTML, layout, partials — **no business rules** |
| `public/css/`, `public/js/` | Styles, UX |
| `public/icons/` | Logo, favicon |
| `app/Lang/*.php` | Translations |
| `docs/**` | Documentation |

### `public/` vs `app/Views/`

| Folder | Role |
|--------|------|
| **`public/`** | URL entry + forms/POST (the “door”) |
| **`app/Views/`** | HTML templates only (the “face”) |

Example: `public/contact.php` handles submit → `Views/pages/contact.php` shows the form.  
See [README — public vs Views](../README.md#public-vs-appviews--whats-the-difference).

---

## ⚠️ Careful zones

| Path | Notes |
|------|-------|
| `public/*.php` | Mix of view + POST handlers — change forms/redirects carefully |
| `public/admin/*.php` | Admin UI + inline actions |
| `public/api/*.php` | JSON contracts — don't change response shape without need var |

---

## Core module registry

Registered in `app/bootstrap/core.php` (full boot):

1. `uploads.php`
2. `helpers.php`
3. `permissions.php`
4. `urls.php` *(skipped in API lite boot)*
5. `admin.php`
6. `member.php`
7. `i18n.php` *(skipped in API lite boot)*
8. `verification.php` *(skipped in API lite boot)*
9. `features.php`
10. `push.php`

Other Core files (`mail.php`, `oauth.php`, …) are loaded on demand by the modules above.

---

## Change checklist

- [ ] PHPUnit: `vendor/bin/phpunit`
- [ ] Login / register / logout
- [ ] Create post (author)
- [ ] Admin dashboard loads
- [ ] API like/comment still returns JSON

---

## For AI / Cursor

See `.cursor/rules/logic-lock.mdc` — rules apply automatically when editing locked paths.
