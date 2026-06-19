# Village Connect

**Community Social CMS** — self-hosted platform for villages, neighborhoods, and local communities.

Members share posts with photos and video. Admins moderate content, manage users, handle incident reports, and run community challenges. Built with **PHP 8.3**, **PostgreSQL 16**, and **Docker**.

---

## Documentation (Sale Package)

| Document | Description |
|----------|-------------|
| **[Architecture](docs/ARCHITECTURE.md)** | Folder layout, request flow, where to add code |
| **[Logic lock](docs/LOGIC_LOCK.md)** | Protected files — avoid accidental logic changes |
| **[Installation Guide](docs/INSTALLATION.md)** | Full Docker setup, migrations, troubleshooting |
| **[Features](docs/FEATURES.md)** | Complete honest feature list |
| **[Demo Guide](docs/DEMO.md)** | Demo accounts, URLs, buyer walkthrough |
| **[Database Schema](docs/DATABASE.md)** | Tables, relationships, migration reference |
| **[Screenshot Checklist](docs/screenshots/README.md)** | What to capture for marketplace listings |
| **[YouTube Video Guide](docs/YOUTUBE-GUIDE.md)** | Add YouTube videos to posts |

---

## Quick Start

```bash
cp .env.example .env
# Edit .env — set DB_PASSWORD, keep DB_HOST=db for Docker

docker compose up -d --build
docker compose exec -T app php database/migrate.php
docker compose exec -T app php database/migrate_user_preferences.php
docker compose exec -T app php database/migrate_incident_reports.php
docker compose exec -T app php database/migrate_phase25.php
docker compose exec -T app php database/seed_demo.php
```

Open **http://localhost:8080**

---

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@admin.com` | `admin123` |
| Author | `author@author.com` | `author123` |

**Change these before production.** See [docs/DEMO.md](docs/DEMO.md) for a full evaluation walkthrough.

---

## Requirements

- Docker Desktop (Windows / macOS / Linux)
- Git
- ~2 GB disk space

No local PHP or PostgreSQL installation required.

---

## Key Features

### Public
- Community feed (latest, popular, following)
- Categories, search, bookmarks
- Posts with images, video, Markdown
- Likes, threaded comments, follow authors
- Notifications (in-app + email)
- Mood feed filter + nearby geo feed
- Incident quick report + community challenges
- Dark/light theme, responsive design, PWA basics

### Admin
- Dashboard, analytics, activity log
- Post/comment moderation
- User management (roles, ban)
- Content reports + incident triage
- Community challenges CRUD
- Contact inbox + announcements
- Site settings (SEO, moderation toggles)

### Security
- CSRF, bcrypt passwords, rate limiting
- Secure uploads, session hardening
- HTTPS-ready nginx snippets

Full list: [docs/FEATURES.md](docs/FEATURES.md)

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3 |
| Database | PostgreSQL 16 |
| Web server | Nginx |
| Containers | Docker Compose |
| Frontend | Bootstrap 5, vanilla JS |
| Auth | Email/password + Google/Facebook OAuth |
| Media | Local uploads + optional Cloudinary |

---

## Project Structure

```
app/
  bootstrap/      Boot sequence (paths, core loader, autoload)
  Core/           Business logic (locked — see docs/LOGIC_LOCK.md)
  Controllers/    Page controllers
  Views/          Layouts, pages, partials
  Lang/           Translations (en, km)
config/           Environment & database
database/
  migrations/     Schema migrations (locked)
  seeds/          Demo data
  migrate.php     Migration runner
public/           Web root (pages, admin, api, assets)
storage/          Cache & backups (runtime)
docker/           Nginx + PHP configs
docs/             Guides (ARCHITECTURE, LOGIC_LOCK, INSTALLATION, …)
tests/            PHPUnit
bootstrap.php     Full app bootstrap
bootstrap-api.php Lite bootstrap for API
```

Full map: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)

---

## Environment

Copy `.env.example` to `.env`. Key variables:

```env
DB_HOST=db
DB_PASSWORD=change_me
APP_URL=http://localhost:8080
APP_DEBUG=true
PRETTY_URLS=true
```

Optional: `MAIL_*`, `GOOGLE_*`, `FACEBOOK_*`, `CLOUDINARY_*` — see `.env.example`.

---

## Database

- Schema: `database/schema.sql`
- Migrations: `database/migrate.php` (+ feature-specific scripts)
- Demo seed: `database/seed_demo.php`
- Reference: [docs/DATABASE.md](docs/DATABASE.md)

---

## Tests

```bash
docker compose exec -T app composer install
docker compose exec -T app vendor/bin/phpunit
```

---

## Deploy free (MVP / portfolio)

Recommended stack:

```text
GitHub → Koyeb (Dockerfile.prod) → Neon or Supabase (PostgreSQL) → Cloudinary (images)
```

| Platform | Free sleep? | Cold start | Docker PHP | Persistent disk |
|----------|-------------|------------|------------|-----------------|
| **Koyeb** ⭐ | 1 hour idle | ~1–5s | ✅ | ❌ (use Cloudinary) |
| Render | 15 min idle | ~30s | ✅ | ✅ (paid/starter) |
| Vercel | No | ~250ms | ⚠️ limited | ❌ |

---

## Koyeb (recommended)

[Koyeb](https://www.koyeb.com) + external Postgres (Neon or Supabase) + Cloudinary.

### 1. Database (pick one)

- **[Neon](https://neon.tech)** — free PostgreSQL, copy `DATABASE_URL` or `DB_*` vars
- **Supabase** — you already use this; keep the same pooler credentials

Run migrations once from your PC:

```bash
php database/migrate.php
```

### 2. Deploy on Koyeb

1. Push repo to GitHub
2. [Koyeb Dashboard](https://app.koyeb.com) → **Create Web Service** → **GitHub**
3. Settings:
   - **Builder:** Dockerfile
   - **Dockerfile:** `Dockerfile.prod`
   - **Port:** `8000` (HTTP)
   - **Route:** `/` → port `8000`
   - **Instance:** Free (Frankfurt or Washington)
4. **Environment variables** (same as `.env.example`):

| Key | Value |
|-----|-------|
| `PORT` | `8000` |
| `APP_URL` | `https://your-app.koyeb.app` |
| `TRUST_PROXY` | `true` |
| `DB_*` or `DATABASE_URL` | Neon / Supabase connection |
| `CLOUDINARY_*` | required on free tier (no persistent disk) |
| `MAIL_*`, OAuth keys | your values |

5. Deploy — migrations run on each start (`docker/prod/start.sh`)

### CLI (optional)

```bash
koyeb app init villages-connection \
  --git github.com/YOUR_USER/Viilages_Connection \
  --git-branch main \
  --git-builder docker \
  --dockerfile Dockerfile.prod \
  --ports 8000:http \
  --routes /:8000 \
  --env PORT=8000 \
  --env TRUST_PROXY=true
```

Then add secrets in the Koyeb dashboard (`DB_*`, `MAIL_*`, etc.).

---

## Render

[Render](https://render.com) also works with `Dockerfile.prod` + `render.yaml` Blueprint.

⚠️ **Free tier sleeps after 15 minutes** — first request can take ~30s. Use **Starter ($7/mo)** to avoid sleep, or prefer **Koyeb** for free MVP.

Migrations run automatically on container start (`docker/prod/start.sh`).

---

## Vercel (serverless PHP)

This project can deploy to [Vercel](https://vercel.com) using the community [`vercel-php`](https://github.com/vercel-community/php) runtime.

1. Push the repo to GitHub and import it in Vercel.
2. Framework preset: **Other**
3. Add environment variables from `.env.example` (Supabase `DB_*`, `MAIL_*`, `APP_URL`, `CLOUDINARY_*`, OAuth keys, etc.)
4. Set `APP_URL=https://your-project.vercel.app` (or your custom domain)
5. Set `TRUST_PROXY=true`
6. Deploy

```bash
npm i -g vercel
vercel login
vercel
```

**Limits on Vercel:** no persistent local uploads (use Cloudinary), no `ffmpeg` video processing, admin DB backups may fail, sessions can reset on cold starts. Supabase as external Postgres works well.

---

## License

Specify your license before selling (e.g. regular/extended for marketplaces). Add a `LICENSE` file if distributing commercially.

---

## Support for Buyers

Recommended: include 7–14 days of setup support and link to `docs/INSTALLATION.md` + `docs/DEMO.md`.
