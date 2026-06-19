# Database Schema — Village Connect

PostgreSQL 16. Full DDL: [`database/schema.sql`](../database/schema.sql)

Migrations extend the base schema incrementally — see [`database/migrate.php`](../database/migrate.php).  
Migration files live in [`database/migrations/`](../database/migrations/); wrappers at `database/migrate_*.php` keep old CLI commands working.

---

## Core tables

| Table | Purpose |
|-------|---------|
| `users` | Accounts, roles, OAuth, profile, UI prefs, ban status |
| `categories` | Post categories with icons and slugs |
| `posts` | Main content: text, media, location, geo, expiry, mood, knowledge labels |
| `post_likes` | User/visitor likes |
| `post_comments` | Threaded comments with moderation status |
| `post_bookmarks` | Saved posts per user |
| `user_follows` | Follower relationships |
| `notifications` | In-app alerts |

---

## Community & moderation

| Table | Purpose |
|-------|---------|
| `community_challenges` | Weekly/monthly community goals + leaderboard |
| `incident_reports` | Local incident quick reports |
| `content_reports` | User-reported content |
| `contact_messages` | Contact form + admin replies |
| `announcements` | Site-wide announcements |
| `activity_logs` | Admin audit trail |
| `site_settings` | Key-value site configuration |

---

## Auth & security

| Table | Purpose |
|-------|---------|
| `password_reset_otps` | Email OTP for password reset |
| `email_verification_tokens` | Email verification flow |
| `rate_limit_hits` | Rate limiting storage |
| `schema_migrations` | Applied migration tracking |

---

## Posts table — extended fields (Phase 2.5)

| Column | Type | Description |
|--------|------|-------------|
| `post_kind` | VARCHAR | `general`, `knowledge`, `challenge_update` |
| `knowledge_label` | VARCHAR | `solved`, `useful`, `tutorial`, `verified` |
| `mood_tag` | VARCHAR | `helpful`, `urgent`, `celebration`, `question`, `alert` |
| `latitude` / `longitude` | NUMERIC | Geo coordinates for nearby feed |
| `expires_at` | TIMESTAMP | Auto-hide after date |
| `archive_on_expiry` | BOOLEAN | Move to Archived status when expired |
| `challenge_id` | INT | Link to `community_challenges` |

---

## Entity relationship (simplified)

```
users ──┬── posts ──┬── post_likes
        │           ├── post_comments
        │           └── post_bookmarks
        ├── user_follows
        ├── notifications
        ├── incident_reports
        └── contact_messages

categories ── posts
community_challenges ── posts (optional challenge_id)
```

---

## Migration commands

```bash
# All tracked migrations
docker compose exec -T app php database/migrate.php

# Feature-specific (also safe to re-run)
docker compose exec -T app php database/migrate_user_preferences.php
docker compose exec -T app php database/migrate_incident_reports.php
docker compose exec -T app php database/migrate_phase25.php
```

---

## Seed demo data

```bash
docker compose exec -T app php database/seed_demo.php
```

---

## Access database directly

```bash
docker compose exec -T db psql -U postgres -d project_cms
```

Example queries:

```sql
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM posts WHERE status = 'Published';
SELECT * FROM schema_migrations ORDER BY applied_at;
```
