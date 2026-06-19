# Demo Guide — Village Connect

Use this guide to evaluate the project locally or to prepare a live demo for buyers.

---

## Quick start (5 minutes)

```bash
docker compose up -d --build
docker compose exec -T app php database/migrate.php
docker compose exec -T app php database/migrate_user_preferences.php
docker compose exec -T app php database/migrate_incident_reports.php
docker compose exec -T app php database/migrate_phase25.php
docker compose exec -T app php database/seed_demo.php
```

Open: **http://localhost:8080**

---

## Demo accounts

| Role | Email | Password | Access |
|------|-------|----------|--------|
| **Admin** | `admin@admin.com` | `admin123` | Full admin panel + all features |
| **Author** | `author@author.com` | `author123` | Create/edit own posts, author dashboard |

> Change these passwords before any public deployment. Do not use on production with real users.

---

## Demo walkthrough (recommended order)

### 1. Public site (no login)

| Page | URL |
|------|-----|
| Homepage / feed | http://localhost:8080 |
| Search | http://localhost:8080/search |
| Community challenges | http://localhost:8080/challenges |
| Incident report | http://localhost:8080/incident-report |
| Announcements | http://localhost:8080/announcements |
| About | http://localhost:8080/about |

**Try:** category filter, mood filter (sidebar), popular sort, open a sample post.

### 2. Member experience (login as author)

1. Login: http://localhost:8080/login
2. Create post: http://localhost:8080/admin/posts?action=add
3. View profile: click avatar → My Profile
4. Notifications: bell icon in navbar
5. Bookmarks: user dropdown → Bookmarks

**Try:** like a post, leave a comment, follow an author.

### 3. Admin panel (login as admin)

| Module | URL |
|--------|-----|
| Dashboard | http://localhost:8080/admin |
| Posts | http://localhost:8080/admin/posts |
| Comments | http://localhost:8080/admin/comments |
| Users | http://localhost:8080/admin/users |
| Reports | http://localhost:8080/admin/reports |
| Incidents | http://localhost:8080/admin/incidents |
| Challenges | http://localhost:8080/admin/challenges |
| Messages | http://localhost:8080/admin/messages |
| Announcements | http://localhost:8080/admin/announcements |
| Settings | http://localhost:8080/admin/settings |
| Analytics | http://localhost:8080/admin/analytics |

**Try:** approve a pending post, reply to a contact message, create a challenge, triage an incident.

---

## Sample content (after seed)

`database/seed_demo.php` creates **5 published posts** across categories:

- Agriculture — rice harvest
- Culture — village festival
- Tourism — waterfall trail
- Community — charity food drive
- Events — football tournament

Plus **5 default categories** and **1 active community challenge**.

---

## What to tell buyers

**Included:**

- Full PHP source code
- Docker local dev stack
- PostgreSQL schema + migrations
- Admin panel with moderation tools
- Unique community features (incidents, challenges, mood/nearby feeds)

**Not included:**

- Hosted server or domain
- Real-time chat between users
- Ongoing maintenance (unless agreed separately)
- Pre-existing user base or revenue

---

## Reset demo data

To refresh demo content without wiping migrations:

```bash
docker compose exec -T app php database/seed_demo.php
```

Full reset (destructive):

```bash
docker compose exec -T app php database/setup.php
docker compose exec -T app php database/migrate.php
docker compose exec -T app php database/migrate_user_preferences.php
docker compose exec -T app php database/migrate_incident_reports.php
docker compose exec -T app php database/migrate_phase25.php
```
