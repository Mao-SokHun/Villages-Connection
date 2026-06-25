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
# First time only — creates tables on empty local Postgres, then applies migrations:
docker compose exec -T app php database/migrate.php
docker compose exec -T app php database/seed_demo.php
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

## Deploy on Northflank (recommended)

Stack:

```text
GitHub → Northflank (Dockerfile.prod) → Supabase or Neon (DB) → Cloudinary (images)
```

Repo: https://github.com/Mao-SokHun/Villages-Connection

| Item | Value |
|------|-------|
| Cost | **$0** (Developer Sandbox) |
| Database | **Supabase** (you have this) or **Neon** |
| Images | **Cloudinary** (required — no persistent disk on free tier) |
| Sleep | **None** — always on (unlike Render free) |
| Card | Sandbox may require a credit card to activate |

**Do not deploy** `docker-compose.yml` or a `postgres` container. Use external Postgres only.

| File | Northflank |
|------|------------|
| `Dockerfile.prod` | ✅ Build this |
| `docker-compose.yml` | ❌ Local dev only |
| Root `Dockerfile` | ❌ PHP-FPM only (local compose) |

### Step 1 — Push code to GitHub

```bash
git add .
git commit -m "Prepare Northflank deploy"
git push origin main
```

*(Skip if `main` is already up to date on GitHub.)*

### Step 2 — Create Northflank project

1. Go to https://app.northflank.com
2. Sign up → open **Developer Sandbox** (free)
3. **Create project** (e.g. `villages-connection`)
4. **Link GitHub** → allow access to **`Mao-SokHun/Villages-Connection`**

### Step 3 — Create service

1. **Add new** → **Combined service** (build + run in one)
2. **Source:** GitHub → repo **`Villages-Connection`** → branch **`main`**
3. **Build:**
   - Method: **Dockerfile**
   - Dockerfile path: **`Dockerfile.prod`**
   - Build context: **`/`** (repo root)
4. **Resources:** Sandbox defaults are fine to start (e.g. 1 vCPU / 1 GB RAM)
5. **Networking → Ports:**
   - Port **`8000`**
   - Protocol: **HTTP**
   - **Publicly expose** ✅
   - Northflank auto-detects `EXPOSE 8000` from `Dockerfile.prod` — verify it matches
6. **Environment variables** — copy from your local `.env` (never commit `.env`):

| Variable | Value |
|----------|-------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `TRUST_PROXY` | `true` |
| `PRETTY_URLS` | `true` |
| `APP_URL` | `https://p8000--YOUR-SERVICE--YOUR-PROJECT.code.run` *(set after first deploy — see Step 4)* |
| `OAUTH_BASE_URL` | same as `APP_URL` |
| `DB_HOST` | Supabase pooler host |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `postgres` |
| `DB_USERNAME` | `postgres.xxxxx` |
| `DB_PASSWORD` | your Supabase password |
| `DB_SSLMODE` | `require` |
| `MAIL_HOST` / `MAIL_PORT` / … | your SMTP |
| `CLOUDINARY_CLOUD_NAME` | from Cloudinary dashboard |
| `CLOUDINARY_API_KEY` | … |
| `CLOUDINARY_API_SECRET` | … |
| `GOOGLE_CLIENT_ID` / `SECRET` | optional OAuth |
| `FACEBOOK_APP_ID` / `SECRET` | optional OAuth |

**Neon instead of Supabase:** set one variable:

```env
DATABASE_URL=postgresql://user:password@ep-xxxx.region.aws.neon.tech/neondb?sslmode=require
```

7. **Create & deploy** — first build takes ~5–10 minutes

Migrations run automatically on container start (`docker/prod/start.sh`).

### Step 4 — After first deploy

1. Open **Ports & DNS** → copy the public URL (e.g. `https://p8000--villages-connection--myproject.code.run`)
2. **Environment** → set `APP_URL` and `OAUTH_BASE_URL` to that URL → **Save** → redeploy
3. OAuth redirect URLs (if used):
   - `https://YOUR-URL/auth/google-callback.php`
   - `https://YOUR-URL/auth/facebook-callback.php`

### Step 5 — Test

| URL | Expected |
|-----|----------|
| `/health.php` | `ok` |
| `/` | Home feed |
| `/login.php` | Login page |

Demo login: `admin@admin.com` / `admin123` — **change before public launch**.

### Troubleshooting

| Problem | Fix |
|---------|-----|
| Build fails | Check **Build logs** — usually Composer or Dockerfile path wrong |
| 502 / site down | **Runtime logs** — confirm port **8000** is public HTTP |
| DB connection error | `DB_SSLMODE=require` for Supabase/Neon; check password |
| Images missing after restart | Set all `CLOUDINARY_*` vars — local `/uploads/` is ephemeral |
| Wrong links / OAuth | `APP_URL` must match Northflank public URL exactly |

---

## Auto deploy (Render + Vercel)

Push to **`main`** should deploy **both** hosts. One-time setup:

### 1. Render (Blueprint — recommended)

1. https://dashboard.render.com → **New** → **Blueprint**
2. Connect GitHub → repo **`Mao-SokHun/Villages-Connection`**
3. Render reads **`render.yaml`** (`branch: main`, `autoDeployTrigger: checksPass`)
4. Enter secret env vars when prompted → **Apply**
5. **Settings → Build & Deploy** → confirm **Auto-Deploy** = **After CI checks pass** (or **On commit**)

**Deploy hook (backup):** Service → **Settings** → **Deploy Hook** → copy URL → GitHub repo → **Settings → Secrets → Actions** → add `RENDER_DEPLOY_HOOK`.

### 2. Vercel (GitHub — already connected)

Project **`villages-connection`** is linked to **`Mao-SokHun/Villages-Connection`** on branch **`main`**.

Every `git push main` deploys Vercel automatically. Check: https://vercel.com/dashboard → **Deployments**.

After **CI passes**, the same push triggers Render (if `RENDER_DEPLOY_HOOK` secret is set).

### 3. How it works

```text
git push main
    → GitHub Actions CI (tests) — must pass
    → deploy job → Render (RENDER_DEPLOY_HOOK)
    → Vercel auto-deploy (Git integration)
```

If **Deploy** shows **skipped**: CI failed — open the **CI** workflow and fix the error first.

| Check | Where |
|-------|--------|
| CI passed | https://github.com/Mao-SokHun/Villages-Connection/actions |
| Render deploy | Render dashboard → **Events** |
| Vercel deploy | Vercel dashboard → **Deployments** |

If Render never deploys: Blueprint not applied yet, or **Auto-Deploy** is off, or CI failed.

---

## Deploy on Render (free — no credit card)

Stack:

```text
GitHub → Render (Dockerfile.prod) → Supabase (DB) → Cloudinary (images)
```

Repo: https://github.com/Mao-SokHun/Villages-Connection

| Item | Value |
|------|-------|
| Cost | **$0** (free web service) |
| Card | **Not required** |
| Sleep | After **15 min** idle; first visit ~**30s** |
| Port | Render sets `PORT=10000` — `start.sh` reads it automatically |
| Region | `singapore` *(in `render.yaml`)* |

### Step 1 — Blueprint deploy

1. https://dashboard.render.com → **New** → **Blueprint**
2. Connect GitHub → repo **`Mao-SokHun/Villages-Connection`**
3. Render reads **`render.yaml`** automatically
4. When prompted, enter **secret** values *(from your local `.env`)*:

| Variable | Value |
|----------|-------|
| `APP_URL` | `https://villages-connection.onrender.com` *(update after first deploy)* |
| `OAUTH_BASE_URL` | same as `APP_URL` |
| `DB_HOST` | `aws-1-ap-southeast-1.pooler.supabase.com` |
| `DB_DATABASE` | `postgres` |
| `DB_USERNAME` | `postgres.mnwjhmnvsnjafbkoucyp` |
| `DB_PASSWORD` | your Supabase password |
| `MAIL_HOST` | `smtp.gmail.com` |
| `MAIL_PORT` | `587` |
| `MAIL_ENCRYPTION` | `tls` |
| `MAIL_USERNAME` | your Gmail |
| `MAIL_PASSWORD` | Gmail app password |
| `MAIL_FROM` | your Gmail |
| `SITE_CONTACT_EMAIL` | your Gmail |
| `CLOUDINARY_URL` | from Cloudinary dashboard |
| `CLOUDINARY_CLOUD_NAME` | … |
| `CLOUDINARY_API_KEY` | … |
| `CLOUDINARY_API_SECRET` | … |
| `GOOGLE_CLIENT_ID` / `SECRET` | optional |
| `FACEBOOK_APP_ID` / `SECRET` | optional |

5. Click **Apply** / **Deploy Blueprint**

**Or manual:** **New → Web Service → Docker → `Dockerfile.prod` → Free → Singapore**

### Step 2 — After deploy

1. Copy Render URL (e.g. `https://villages-connection.onrender.com`)
2. **Environment** → set `APP_URL` + `OAUTH_BASE_URL` → **Save & redeploy**
3. OAuth redirect URLs:
   - `https://YOUR-APP.onrender.com/auth/google-callback.php`
   - `https://YOUR-APP.onrender.com/auth/facebook-callback.php`

### Step 3 — Test

| URL | Expected |
|-----|----------|
| `/health.php` | `ok` |
| `/` | Home feed |
| `/login.php` | Login |

Migrations run automatically on container start (`docker/prod/start.sh`).

### Free tier notes

- **Sleeps** after 15 min idle — wait ~30s on first load
- **No disk** for uploads — **Cloudinary required**
- Can run **alongside Vercel** — same Supabase + Cloudinary

---

## Other hosts (optional)

<details>
<summary>Oracle Cloud Always Free (always on, $0 VM)</summary>

See `scripts/oracle-deploy.sh` and `docker-compose.prod.yml` if you move off Render later.

</details>

---

## Deploy on Vercel (free — no credit card)

Stack:

```text
GitHub → Vercel (vercel-php) → Supabase (DB) → Cloudinary (images)
```

Repo: https://github.com/Mao-SokHun/Villages-Connection

| Item | Value |
|------|-------|
| Cost | **$0** (Hobby) |
| Card | **Not required** |
| Sleep | Serverless — cold start ~1–3s after idle |
| DB | Supabase (external) |
| Images | **Cloudinary required** |

### Step 1 — Import project

1. https://vercel.com → Sign up / Log in (GitHub)
2. **Add New** → **Project**
3. Import **`Mao-SokHun/Villages-Connection`**
4. **Framework Preset:** **Other**
5. **Root Directory:** `./` (default)
6. **Build Command:** leave empty *(uses `installCommand` in `vercel.json`)*
7. **Output Directory:** leave empty

### Step 2 — Environment variables

**Settings → Environment Variables** — add for **Production** (and Preview if you want):

```env
APP_NAME=CMS
APP_URL=https://YOUR-PROJECT.vercel.app
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Phnom_Penh
TRUST_PROXY=true
PRETTY_URLS=true
DB_PERSISTENT=false
SESSION_DRIVER=database
RATE_LIMIT_DRIVER=database

DB_HOST=aws-1-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.xxxxx
DB_PASSWORD=...
DB_SSLMODE=require

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM=...
MAIL_FROM_NAME=Village Connect
SITE_CONTACT_EMAIL=...

OAUTH_BASE_URL=https://YOUR-PROJECT.vercel.app
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
FACEBOOK_APP_ID=...
FACEBOOK_APP_SECRET=...

CLOUDINARY_URL=cloudinary://...
CLOUDINARY_CLOUD_NAME=...
CLOUDINARY_API_KEY=...
CLOUDINARY_API_SECRET=...
CLOUDINARY_FOLDER=village-connect
```

Skip `GOOGLE_REDIRECT_URI` / `FACEBOOK_REDIRECT_URI` — app builds them from `OAUTH_BASE_URL`.

**Node.js Version:** set **22.x** in Project Settings *(helps `pdo_pgsql` load)*.

### Step 3 — Deploy

Click **Deploy** → wait ~2–5 minutes.

### Step 4 — After deploy

1. Copy URL: `https://villages-connection-xxxx.vercel.app`
2. Update `APP_URL` + `OAUTH_BASE_URL` → **Redeploy**
3. OAuth redirect URLs:
   - `https://YOUR-APP.vercel.app/auth/google-callback.php`
   - `https://YOUR-APP.vercel.app/auth/facebook-callback.php`

### Step 5 — Database migrations

Vercel does **not** run migrations automatically. From your PC *(with PHP)*:

```bash
# .env already points at Supabase
php database/migrate.php
php database/migrate_user_preferences.php
php database/migrate_incident_reports.php
php database/migrate_phase25.php
```

`migrate.php` includes **`migrate_php_sessions.php`** — required so login survives page changes and language switch on Vercel.

Or use Docker: `docker compose run --rm app php database/migrate.php`

### Step 6 — Test

| URL | Expected |
|-----|----------|
| `/health.php` | `ok` |
| `/` | Home feed |
| `/login.php` | Login |

### Vercel limits

- No local `/uploads/` persistence → **Cloudinary required**
- No `ffmpeg` in serverless → video processing limited
- **Sessions:** use `SESSION_DRIVER=database` + run migrations — login persists across requests (required for language switch)
- Admin DB backup download may not work on serverless
- Max upload ~64MB (`api/php.ini`)

### CLI deploy (optional)

```bash
npm i -g vercel
vercel login
vercel --prod
```

---

## License

Specify your license before selling (e.g. regular/extended for marketplaces). Add a `LICENSE` file if distributing commercially.

---

## Support for Buyers

Recommended: include 7–14 days of setup support and link to `docs/INSTALLATION.md` + `docs/DEMO.md`.
