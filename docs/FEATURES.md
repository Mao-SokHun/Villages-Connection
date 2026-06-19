# Features — Village Connect

Honest feature list for buyers and evaluators. This document reflects what is **implemented and working** in the current codebase.

---

## Public member features

| Feature | Status | Notes |
|---------|--------|-------|
| Community feed | ✅ | Latest, popular, following filters |
| Categories | ✅ | Browse and filter by topic |
| Search | ✅ | Dedicated `/search` page + feed search; guests allowed |
| Post detail page | ✅ | Views counter, Markdown content |
| Post likes | ✅ | Toggle like per user |
| Comments | ✅ | Threaded replies, moderation states |
| Follow authors | ✅ | Following feed |
| Bookmarks | ✅ | Save posts for later |
| Notifications | ✅ | In-app bell + notification page |
| User profiles | ✅ | Public profile, stats, post list |
| Edit profile | ✅ | Avatar (upload or URL), bio, location |
| Switch account | ✅ | Quick re-login from recent accounts |
| Announcements | ✅ | Site-wide + full announcement page |
| Incident quick report | ✅ | Public form with admin triage |
| Community challenges | ✅ | Public listing + basic leaderboard |
| Reputation display | ✅ | Points, badge, trust rank on profile |
| Khmer translations (i18n) | ✅ | `?lang=km` / cookie; navbar switcher not in UI |
| Dark / light theme | ✅ | Navbar toggle (system-aware) |
| PWA basics | ✅ | Manifest + service worker |
| Pretty URLs | ✅ | `/post/{slug}`, `/admin/posts`, etc. |
| Responsive design | ✅ | Mobile-friendly layout |

---

## Content & posts

| Feature | Status | Notes |
|---------|--------|-------|
| Image upload | ✅ | Local storage + optional Cloudinary |
| Video upload (MP4) | ✅ | |
| YouTube embed | ✅ | See [YOUTUBE-GUIDE.md](YOUTUBE-GUIDE.md) |
| Markdown content | ✅ | Bold, italic, links, headings, lists |
| Post location text | ✅ | |
| Geo coordinates | ⚠️ | DB columns exist; not in admin post form |
| Post expiry | ⚠️ | DB + archive logic; not in admin post form |
| Auto-archive on expiry | ✅ | When `expires_at` set on post |
| Knowledge posts | ⚠️ | DB labels; not in admin post form |
| Challenge-linked posts | ⚠️ | DB link; not in admin post form |
| Featured posts | ⚠️ | Admin can flag; no dedicated homepage section |
| Post status workflow | ✅ | Draft, Pending, Published, Rejected, Archived |

---

## Authentication & accounts

| Feature | Status | Notes |
|---------|--------|-------|
| Email/password registration | ✅ | |
| Email verification | ✅ | Configurable |
| Password reset (OTP email) | ✅ | |
| Google OAuth | ✅ | Optional via `.env` |
| Facebook OAuth | ✅ | Optional via `.env` |
| Role-based access | ✅ | Member, author, admin |
| Account ban / soft delete | ✅ | Admin controlled |

---

## Support & communication

| Feature | Status | Notes |
|---------|--------|-------|
| Contact form | ✅ | Public contact page |
| Support inbox (user) | ✅ | View admin replies |
| Contact inbox (admin) | ✅ | Reply to users |
| Content reports | ✅ | Report posts + admin triage |
| User notifications on replies | ✅ | Report + contact updates |

> **Not included:** real-time user-to-user chat or direct messaging between members.

---

## Admin panel

| Module | Status | Notes |
|--------|--------|-------|
| Dashboard | ✅ | Stats overview |
| Posts management | ✅ | CRUD, approve, bulk actions |
| Comments moderation | ✅ | Approve / reject |
| Users management | ✅ | Roles, ban, profiles |
| Categories | ✅ | Icons, CRUD |
| Content reports | ✅ | Open / resolved workflow |
| Incident triage | ✅ | Open / in progress / resolved |
| Community challenges | ✅ | CRUD + status |
| Contact messages | ✅ | Inbox + reply |
| Announcements | ✅ | Broadcast to users |
| Site settings | ✅ | SEO, moderation toggles |
| Analytics | ✅ | Basic stats |
| Activity log | ✅ | Admin audit trail |
| Media library | ✅ | Uploaded files overview |

---

## Security

| Feature | Status |
|---------|--------|
| CSRF protection | ✅ |
| Password hashing (bcrypt) | ✅ |
| Session hardening | ✅ |
| Rate limiting | ✅ |
| Secure file upload validation | ✅ |
| SQL injection protection (PDO prepared statements) | ✅ |
| Admin POST action tokens | ✅ |
| HTTPS-ready nginx snippets | ✅ |

---

## SEO & technical

| Feature | Status |
|---------|--------|
| XML sitemap | ✅ |
| Meta tags + Open Graph | ✅ |
| JSON-LD structured data | ✅ |
| Clean URL routing (nginx) | ✅ |
| Docker development stack | ✅ |
| Docker local dev stack | ✅ |
| PHPUnit test suite | ✅ | 33 tests (run `vendor/bin/phpunit`) |

---

## Removed or not in current UI

| Feature | Status | Notes |
|---------|--------|-------|
| Mood feed filter | ❌ | Removed from homepage |
| Nearby feed filter | ❌ | Removed from homepage |
| Language switcher in navbar | ❌ | i18n code remains (`?lang=km`) |
| User preferences API | ❌ | `api/user-preferences.php` removed |

---

## Roadmap (not in current sale package)

These were discussed as future phases but are **not** included unless explicitly added:

- Real-time chat between users
- Full emoji reaction set (beyond likes)
- AI summary / voice post
- Native mobile apps
- Payment / subscription system

---

## Tech stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3 |
| Database | PostgreSQL 16 |
| Web server | Nginx |
| Containers | Docker Compose |
| Frontend | Bootstrap 5, vanilla JS |
| Email | SMTP (PHPMailer) |
| Media CDN | Cloudinary (optional) |
| OAuth | Google + Facebook |
