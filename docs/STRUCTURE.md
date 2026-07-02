# Village Connect — Structure (MVC guide)

**Read this first.** This project uses a clear **MVC-style layout**.  
Business logic file contents are **locked** — you may reorganize folders, not feature logic.

---

## MVC map

```text
┌──────────────────────────────────────────────────────────────────┐
│  public/router.php          ENTRY — one front controller           │
│       ↓                                                          │
│  app/Http/Controllers/      CONTROLLER — forms, POST, redirects  │
│       ↓                                                          │
│  app/Models/                MODEL — features & rules (🔒 locked) │
│       ↓                                                          │
│  app/Views/                 VIEW — HTML templates                │
└──────────────────────────────────────────────────────────────────┘
```

| MVC | Folder | Job |
|-----|--------|-----|
| **Entry** | `public/router.php` | Match URL → bootstrap → load controller |
| **Controller** | `app/Http/Controllers/` | `Public/`, `Admin/`, `Api/`, `Auth/` |
| **Model** | `app/Models/` | All business logic (was `app/Core/`) |
| **View** | `app/Views/` | Layouts, pages, partials |

**Support:** `config/` (.env), `app/Lang/` (translations), `database/` (schema), `vendor/` (Composer).

---

## One request

```text
GET /login
  → public/router.php
  → app/Http/Controllers/Public/login.php
  → app/Models/permissions.php (login logic)
  → app/Views/... (HTML)
```

---

## What you CAN edit

| Layer | Path | Safe for UI? |
|-------|------|--------------|
| View | `app/Views/`, `public/css/`, `public/js/` | ✅ Yes |
| Text | `app/Lang/` | ✅ Yes |
| Controller | `app/Http/Controllers/` | 🟡 HTML OK, careful with POST |
| Model | `app/Models/` | 🔒 **No** — logic lock |

---

## Find files from URL

| URL | Controller file |
|-----|-----------------|
| `/` | `Http/Controllers/Public/index.php` |
| `/login` | `Http/Controllers/Public/login.php` |
| `/admin/posts` | `Http/Controllers/Admin/posts.php` |
| `/api/like.php` | `Http/Controllers/Api/like.php` |

Pretty paths are defined in `app/Models/route_registry.php`.

---

## Model modules (`app/Models/`)

| File | Domain |
|------|--------|
| `permissions.php` | Login, roles, session |
| `features.php` | Posts, comments, likes |
| `admin.php` | Admin CRUD |
| `member.php` | Notifications, follows |
| `mail.php`, `oauth.php`, `uploads.php`, … | See [LOGIC_LOCK.md](LOGIC_LOCK.md) |

---

## Tell others in one line

> **MVC: `router.php` → `Http/Controllers` → `Models` (don't touch) → `Views` (HTML).**
