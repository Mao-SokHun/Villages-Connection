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

## Deploy on Render (free)

Stack:

```text
GitHub → Render (Dockerfile.prod) → Supabase (DB) → Cloudinary (images)
```

Repo: https://github.com/Mao-SokHun/Villages-Connection

| Item | Value |
|------|-------|
| Cost | **$0** (Render free web service) |
| Database | **Supabase** (you already have this) |
| Images | **Cloudinary** (required — free tier has no persistent disk) |
| Sleep | After **15 min** idle; first visit ~**30s** (normal on free) |

### Step 1 — Render Blueprint

1. Go to https://dashboard.render.com
2. **New** → **Blueprint**
3. Connect GitHub → repo **`Mao-SokHun/Villages-Connection`**
4. Render reads `render.yaml` automatically
5. When prompted, enter **secret** env vars (from your local `.env`):

| Variable | Example |
|----------|---------|
| `APP_URL` | `https://villages-connection.onrender.com` *(update after first deploy)* |
| `DB_HOST` | `aws-1-ap-southeast-1.pooler.supabase.com` |
| `DB_DATABASE` | `postgres` |
| `DB_USERNAME` | `postgres.xxxxx` |
| `DB_PASSWORD` | your Supabase password |
| `MAIL_*` | Gmail SMTP |
| `CLOUDINARY_*` | your Cloudinary keys |
| `OAUTH_BASE_URL` | same as `APP_URL` |
| `GOOGLE_CLIENT_ID` / `SECRET` | ... |
| `FACEBOOK_APP_ID` / `SECRET` | ... |

6. Click **Apply** / **Deploy Blueprint**

### Step 2 — After deploy

1. Copy your Render URL (e.g. `https://villages-connection-xxxx.onrender.com`)
2. **Environment** → set `APP_URL` and `OAUTH_BASE_URL` to that URL → **Save & redeploy**
3. Update **Google / Facebook** OAuth redirect URLs:
   - `https://YOUR-APP.onrender.com/auth/google-callback.php`
   - `https://YOUR-APP.onrender.com/auth/facebook-callback.php`

### Step 3 — Test

- Health: `https://YOUR-APP.onrender.com/health.php` → `ok`
- Home: `https://YOUR-APP.onrender.com`
- Login: `admin@admin.com` / `admin123` *(change before public launch)*

Migrations run automatically when the container starts.

### Manual deploy (without Blueprint)

1. **New** → **Web Service** → connect GitHub
2. **Runtime:** Docker
3. **Dockerfile path:** `Dockerfile.prod`
4. **Plan:** Free
5. Add the same env vars as above

### Free tier notes

- Service **sleeps** after 15 minutes — wait ~30s on first load after idle
- **No disk** for uploads — use **Cloudinary** for images
- Upgrade to **Starter ($7/mo)** later if you want no sleep

---

## Other hosts (optional)

<details>
<summary>Oracle Cloud Always Free (always on, $0 VM)</summary>

See `scripts/oracle-deploy.sh` and `docker-compose.prod.yml` if you move off Render later.

</details>

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
