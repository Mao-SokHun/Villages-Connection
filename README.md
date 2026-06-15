# Village Connect CMS

Community social CMS built with PHP 8.3, PostgreSQL, and Docker. Members share posts with photos/videos; admins moderate content, users, and site settings.

## Requirements

- Docker Desktop
- Git

## Quick Start

```bash
docker compose up -d --build
docker exec project_cms_app php database/migrate.php
```

Open **http://localhost:8080**

- Mail testing UI: **http://localhost:8025** (Mailpit)

## Local Demo Accounts

Shown on login page only when `APP_DEBUG=true`:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@admin.com | admin123 |
| Author | author@author.com | author123 |

Change these before any public deployment.

## Environment (`.env`)

Copy `.env.example` to `.env` and adjust values:

```bash
cp .env.example .env
```

Core variables: `DB_*`, `APP_URL`, `APP_DEBUG`, `MAIL_*`, `SITE_CONTACT_EMAIL`.  
Optional: `GOOGLE_*`, `FACEBOOK_*`, `OAUTH_BASE_URL`, `CLOUDINARY_*` — see `.env.example` for the full list.

```env
DB_HOST=db
DB_PORT=5432
DB_DATABASE=project_cms
DB_USERNAME=postgres
DB_PASSWORD=change_me
APP_URL=http://localhost:8080
APP_DEBUG=true
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_ENCRYPTION=none
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME=Village Connect
SITE_CONTACT_EMAIL=admin@example.com
```

## Database Migrations

Run all migrations (tracked in `schema_migrations`):

```bash
docker exec project_cms_app php database/migrate.php
```

Fresh destructive reset (dev only):

```bash
docker exec project_cms_app php database/setup.php
docker exec project_cms_app php database/migrate.php
```

## Project Structure

```
public/          Web root (index, post, admin, api)
app/Core/        Business logic (helpers, admin, member, mail, security)
app/Views/       Layouts and partials
database/        Schema + migrations
docker/          Nginx config
tests/           PHPUnit tests
```

## Features

- Public feed, categories, search, popular/following feeds
- Posts with images, YouTube/upload video, Markdown content
- Likes (toggle), comments, follow, notifications (+ email alerts)
- Author dashboard and admin panel (moderation, analytics, settings)
- SEO: sitemap, meta tags, JSON-LD, clean URLs `/post/{slug}`
- Security: CSRF, session hardening, POST admin actions, upload permissions

## Guides

- **[YouTube Video Guide](docs/YOUTUBE-GUIDE.md)** — How to add a YouTube link so thumbnails and video display on posts (feed, profile, post page)

## Tests

```bash
docker exec project_cms_app composer install
docker exec project_cms_app vendor/bin/phpunit
```

## Architecture Note

Pages use procedural PHP entry scripts in `public/`. `PageController` serves static info pages (About, FAQ, etc.). A unused MVC route map was removed to avoid confusion.
