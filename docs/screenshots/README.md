# Screenshot Checklist — Village Connect

Capture these screenshots for marketplace listings (CodeCanyon, Gumroad, personal site, etc.).

Save as PNG or JPG in this folder. Recommended size: **1920×1080** or **1440×900**.

---

## Required (minimum 8)

| # | Filename suggestion | What to capture | How |
|---|---------------------|-----------------|-----|
| 1 | `01-home-feed.png` | Homepage with posts | http://localhost:8080 — after seed |
| 2 | `02-post-detail.png` | Single post with comments | Open any seeded post |
| 3 | `03-search.png` | Search results | Search "village" or "festival" |
| 4 | `04-login.png` | Login page | http://localhost:8080/login |
| 5 | `05-profile.png` | User profile with stats | Login as author → profile |
| 6 | `06-admin-dashboard.png` | Admin dashboard | Login as admin → /admin |
| 7 | `07-admin-posts.png` | Posts management table | /admin/posts |
| 8 | `08-mobile-feed.png` | Mobile responsive view | Browser DevTools → iPhone size |

---

## Recommended (stand out from competitors)

| # | Filename suggestion | What to capture |
|---|---------------------|-----------------|
| 9 | `09-notifications.png` | Notification dropdown open |
| 10 | `10-incidents.png` | Admin incident triage board |
| 11 | `11-challenges.png` | Public challenges page |
| 12 | `12-incident-report.png` | Public incident report form |
| 13 | `13-mood-feed.png` | Sidebar mood filter active |
| 14 | `14-dark-mode.png` | Dark theme homepage |
| 15 | `15-create-post.png` | Admin create post form |
| 16 | `16-announcements.png` | Announcements page |

---

## Tips for professional screenshots

1. **Use demo seed data** — run `database/seed_demo.php` first
2. **Use dark mode** — matches the default brand look
3. **Hide personal data** — blur emails if using real accounts
4. **Consistent browser** — Chrome, no extensions visible
5. **No debug banners** — set `APP_DEBUG=false` for screenshots
6. **Add light borders** — optional: 8px padding around captures

---

## Marketplace listing text (copy-paste starter)

**Title:** Village Connect — Community Social CMS (PHP + PostgreSQL + Docker)

**Short description:**
Self-hosted community platform with posts, comments, likes, notifications, admin moderation, incident reporting, community challenges, mood/nearby feeds, OAuth login, and responsive dark UI. Built with PHP 8.3, PostgreSQL, Docker.

**Tags:** php, community, cms, social, postgresql, docker, admin-panel, notifications

---

## After capturing

1. Place images in `docs/screenshots/`
2. Reference them in your README or sales page
3. Optionally create a `docs/screenshots/preview.gif` screen recording (30–60 sec)
