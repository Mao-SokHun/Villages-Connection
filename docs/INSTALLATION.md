# Installation Guide — Village Connect

Step-by-step setup for local development or evaluation on **Windows, macOS, or Linux** using Docker.

## Requirements

| Requirement | Version |
|-------------|---------|
| Docker Desktop | Latest stable |
| Git | Any recent version |
| Free disk space | ~2 GB |
| RAM | 4 GB minimum (8 GB recommended) |

No local PHP or PostgreSQL install is required — everything runs in containers.

---

## 1. Clone the project

```bash
git clone <your-repo-url> village-connect
cd village-connect
```

---

## 2. Configure environment

Copy the example environment file and edit passwords:

```bash
cp .env.example .env
```

**Important for Docker:** use `DB_HOST=db` (not `127.0.0.1`). The app container connects to the database service by name.

Minimum `.env` values for local Docker:

```env
APP_NAME=Village Connect
APP_URL=http://localhost:8080
APP_DEBUG=true

DB_HOST=db
DB_PORT=5432
DB_DATABASE=project_cms
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password

PRETTY_URLS=true
```

Optional (see `.env.example`):

- `MAIL_*` — SMTP for real email, or leave defaults for development
- `GOOGLE_*` / `FACEBOOK_*` — social login
- `CLOUDINARY_*` — CDN media uploads

---

## 3. Start Docker services

```bash
docker compose up -d --build
```

Wait until all services are healthy:

```bash
docker compose ps
```

Expected:

| Service | Port | Purpose |
|---------|------|---------|
| `web` | `127.0.0.1:8080` | Nginx (public site) |
| `app` | internal | PHP-FPM |
| `db` | internal | PostgreSQL 16 |

Open **http://localhost:8080**

> **Note:** Local dev uses HTTP on port **8080**, not HTTPS on 8443. If your browser redirects to HTTPS, clear HSTS for `localhost` (`chrome://net-internals/#hsts`).

---

## 4. Run database migrations

Run inside the **app** container so `DB_HOST=db` resolves correctly:

```bash
docker compose exec -T app php database/migrate.php
docker compose exec -T app php database/migrate_user_preferences.php
docker compose exec -T app php database/migrate_incident_reports.php
docker compose exec -T app php database/migrate_phase25.php
```

Migrations are tracked in the `schema_migrations` table.

---

## 5. Seed demo data (recommended for evaluation)

```bash
docker compose exec -T app php database/seed_demo.php
```

This creates:

- 2 demo users (admin + author)
- 5 sample published posts
- Default categories (from schema)

See [DEMO.md](DEMO.md) for login credentials.

---

## 6. Verify installation

| Check | URL / command |
|-------|----------------|
| Homepage loads | http://localhost:8080 |
| Login works | http://localhost:8080/login |
| Admin panel | http://localhost:8080/admin |
| Database tables | `docker compose exec -T db psql -U postgres -d project_cms -c "\dt"` |

---

## Fresh database reset (development only)

`database/setup.php` **drops and recreates** all tables, then seeds users and posts. Use only on empty/dev databases:

```bash
docker compose exec -T app php database/setup.php
docker compose exec -T app php database/migrate.php
docker compose exec -T app php database/migrate_user_preferences.php
docker compose exec -T app php database/migrate_incident_reports.php
docker compose exec -T app php database/migrate_phase25.php
```

---

## Troubleshooting

### Page Not Found (404) on `/admin/incidents` or `/admin/challenges`

Restart nginx after route config changes:

```bash
docker compose restart web
```

### Blank homepage / PHP errors

Database may be empty. Run migrations and seed (steps 4–5).

Check logs:

```bash
docker compose logs app --tail 50
docker compose logs web --tail 50
```

### Migrations fail from host (`php database/migrate.php`)

Host PHP uses `DB_HOST=127.0.0.1` from `.env`, but PostgreSQL only runs inside Docker. Always run migrations via:

```bash
docker compose exec -T app php database/migrate.php
```

### Browser opens wrong port (8443 / HTTPS)

Use `http://localhost:8080` explicitly. Clear browser HSTS cache for `localhost`.

---

## Database schema reference

Full schema: [`database/schema.sql`](../database/schema.sql)

Overview: [DATABASE.md](DATABASE.md)
