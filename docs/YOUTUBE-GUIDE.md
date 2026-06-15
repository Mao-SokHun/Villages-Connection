# YouTube Video Guide — Code Reference

How YouTube links are saved and displayed in Village Connect.  
All paths are relative to the project root.

---

## Flow Overview

```
Create Post form (admin/posts.php)
        │
        ▼
parse_video_input()          ← validates URL, returns type + url
        │
        ▼
INSERT/UPDATE posts          ← video_type = 'youtube', video_url = full URL
        │
        ├─► Feed / Profile cards
        │       post_card_media() → youtube_thumbnail_url() → <img>
        │
        └─► Post page (post.php)
                youtube_embed_url() → <iframe>
```

---

## 1. Database

YouTube data is stored on the `posts` table:

```44:45:database/schema.sql
    video_url VARCHAR(500),
    video_type VARCHAR(20) NOT NULL DEFAULT 'none',
```

| Column | Values for YouTube |
|--------|-------------------|
| `video_type` | `'youtube'` |
| `video_url` | Full URL, e.g. `https://youtu.be/YBJR4F_rhjI` |
| `image_url` | Optional cover image; if empty, card uses YouTube thumbnail |

Example row:

```sql
video_type = 'youtube'
video_url  = 'https://youtu.be/YBJR4F_rhjI'
image_url  = ''   -- empty → thumbnail from YouTube
```

---

## 2. Create / Edit Post Form

**File:** `public/admin/posts.php`

### Form fields (HTML)

Dropdown + YouTube URL input:

```477:502:public/admin/posts.php
                        <select name="video_type" id="video_type" class="form-select form-control-custom mb-2">
                            <option value="none" <?php if ($post_vtype == 'none') echo 'selected'; ?>>None</option>
                            <option value="upload" <?php if ($post_vtype == 'upload') echo 'selected'; ?>>Upload file (MP4)</option>
                            <option value="youtube" <?php if ($post_vtype == 'youtube') echo 'selected'; ?>>YouTube URL</option>
                        </select>
                        ...
                        <input type="url" name="youtube_url" id="youtube_url" class="form-control form-control-custom" placeholder="https://youtube.com/watch?v=..."
                            value="<?php if ($post_vtype == 'youtube' && $post && isset($post['video_url'])) echo htmlspecialchars($post['video_url']); ?>">
```

### Show/hide YouTube input (JavaScript)

```592:607:public/admin/posts.php
document.getElementById('video_type').addEventListener('change', function() {
    var v = this.value;
    var videoWrap = document.getElementById('video_file_wrap');
    var youtubeInput = document.getElementById('youtube_url');
    if (v == 'upload') {
        videoWrap.style.display = 'flex';
    } else {
        videoWrap.style.display = 'none';
    }
    if (v == 'youtube') {
        youtubeInput.style.display = 'block';
    } else {
        youtubeInput.style.display = 'none';
    }
});
document.getElementById('video_type').dispatchEvent(new Event('change'));
```

### Save handler (PHP)

Reads POST data and calls `parse_video_input()`:

```167:175:public/admin/posts.php
    $video_type = 'none';
    if (isset($_POST['video_type'])) {
        $video_type = $_POST['video_type'];
    }

    $youtube_url = '';
    if (isset($_POST['youtube_url'])) {
        $youtube_url = trim($_POST['youtube_url']);
    }
```

```235:272:public/admin/posts.php
    if (!isset($_FILES['video_file'])) {
        $vid = parse_video_input($video_type, $youtube_url, $no_file, $existing_video, $existing_vtype);
    } else {
        $vid = parse_video_input($video_type, $youtube_url, $_FILES['video_file'], $existing_video, $existing_vtype);
    }
    ...
        $fields = array(
            ...
            'video_url' => $video_url,
            'video_type' => $vid_type,
```

---

## 3. Validate & Parse YouTube URL

**File:** `app/Core/helpers.php`

### `parse_video_input()` — save-time validation

When `video_type === 'youtube'`:

- Trims URL from `youtube_url` POST field
- Requires non-empty URL
- Must contain `youtube.com` or `youtu.be`
- Returns `['ok' => true, 'type' => 'youtube', 'url' => $url]`

```138:157:app/Core/helpers.php
    if ($video_type == 'youtube') {
        $url = trim($youtube_url);

        if ($url == '' && $existing_type == 'youtube') {
            $url = $existing_url;
        }

        if ($url == '') {
            return array('ok' => false, 'error' => 'Enter a YouTube URL');
        }

        if (strpos($url, 'youtube.com') === false && strpos($url, 'youtu.be') === false) {
            return array('ok' => false, 'error' => 'Invalid YouTube URL');
        }

        if ($existing_type == 'upload' && $existing_url != '') {
            delete_upload($existing_url, 'videos');
        }

        return array('ok' => true, 'type' => 'youtube', 'url' => $url);
    }
```

### `youtube_video_id()` — extract ID from any supported format

Supports: `youtu.be/ID`, `?v=ID`, `/embed/ID`

```186:203:app/Core/helpers.php
function youtube_video_id($url)
{
    if ($url == '') {
        return '';
    }

    if (preg_match('/youtu\.be\/([^\?&]+)/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/[?&]v=([^&]+)/', $url, $m)) {
        return $m[1];
    }
    if (preg_match('/embed\/([^?&]+)/', $url, $m)) {
        return $m[1];
    }

    return '';
}
```

### `youtube_embed_url()` — for iframe on post page

```205:214:app/Core/helpers.php
function youtube_embed_url($url)
{
    $id = youtube_video_id($url);

    if ($id == '') {
        return '';
    }

    return 'https://www.youtube.com/embed/' . $id;
}
```

### `youtube_thumbnail_url()` — for card preview image

```216:225:app/Core/helpers.php
function youtube_thumbnail_url($url)
{
    $id = youtube_video_id($url);

    if ($id == '') {
        return '';
    }

    return 'https://img.youtube.com/vi/' . $id . '/hqdefault.jpg';
}
```

---

## 4. Card Thumbnail Display (Feed, Profile)

### `post_card_media()` — picks image for cards

Priority:

1. Uploaded image (`uploads/{image_url}`)
2. YouTube thumbnail (`img.youtube.com`)
3. Empty → placeholder icon

```236:257:app/Core/helpers.php
function post_card_media($post)
{
    $result = array('url' => '', 'external' => false, 'alt' => '');

    if (!empty($post['image_url']) && post_uploaded_image_exists($post['image_url'])) {
        $result['url'] = media_url($post['image_url'], '');
        $result['alt'] = post_image_alt($post, isset($post['title']) ? $post['title'] : '');
        return $result;
    }

    if (isset($post['video_type']) && $post['video_type'] == 'youtube' && !empty($post['video_url'])) {
        $thumb = youtube_thumbnail_url($post['video_url']);
        if ($thumb != '') {
            $result['url'] = $thumb;
            $result['external'] = true;
            $result['alt'] = isset($post['title']) ? $post['title'] : 'Video thumbnail';
            return $result;
        }
    }

    return $result;
}
```

### Card partial — `app/Views/partials/news-card.php`

```12:44:app/Views/partials/news-card.php
$media = post_card_media($p);
$has_img = ($media['url'] != '');
$img = $media['url'];
$has_video = post_has_video($p);
...
        <?php if ($has_img): ?>
            <img src="<?php echo htmlspecialchars($img); ?>" alt="..." loading="lazy">
        <?php else: ?>
            <div class="news-card-placeholder">
                <?php echo render_category_icon($cat_icon, 'news-card-ph-icon'); ?>
            </div>
        <?php endif; ?>
        <?php if ($has_video): ?>
            <span class="media-badge video-badge"><i class="fa-solid fa-play me-1"></i>Video</span>
        <?php endif; ?>
```

### Where cards are rendered

| Page | File | Code |
|------|------|------|
| Home feed | `public/index.php` | `post_card_media($art)` ~line 334 |
| Featured posts | `public/index.php` | `post_card_media($fp)` ~line 190 |
| Profile posts | `public/profile.php` | includes `news-card.php` ~line 258 |

**Important:** Profile query must include `video_type` and `video_url`:

```69:76:public/profile.php
$posts_sql = "SELECT p.title, p.slug, p.summary, p.image_url, p.image_alt, p.views, p.likes, p.created_at,
    p.video_type, p.video_url, p.location, p.is_featured,
    c.name AS category_name, c.icon AS category_icon
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = :uid AND p.status = 'Published'
```

Home feed uses `SELECT p.*` so video fields are already included.

---

## 5. Post Page — Embed Player

**File:** `public/post.php`

When `video_type == 'youtube'`, renders a 16:9 iframe:

```191:208:public/post.php
            <?php if (post_has_video($post)): ?>
            <div class="post-video-wrap mb-4 rounded overflow-hidden glass-panel-sm">
                <?php if ($post['video_type'] == 'youtube'): ?>
                    <?php $embed = youtube_embed_url($post['video_url']); ?>
                    <?php if ($embed != ''): ?>
                    <div class="ratio ratio-16x9">
                        <iframe src="<?php echo htmlspecialchars($embed); ?>?rel=0" title="Video" allowfullscreen loading="lazy"></iframe>
                    </div>
                    <?php endif; ?>
                <?php elseif ($post['video_type'] == 'upload' && file_exists(PUBLIC_PATH . '/uploads/videos/' . $post['video_url'])): ?>
                    <video class="w-100" controls playsinline ...>
```

### `post_has_video()` helper

```250:261:app/Core/helpers.php
function post_has_video($post)
{
    if (!isset($post['video_url']) || $post['video_url'] == '') {
        return false;
    }

    if (!isset($post['video_type']) || $post['video_type'] == 'none') {
        return false;
    }

    return true;
}
```

---

## 6. Security (CSP)

YouTube iframe and thumbnails require CSP permissions:

**File:** `app/Core/security.php`

```13:13:app/Core/security.php
    header("Content-Security-Policy: ... img-src 'self' data: https:; frame-src https://www.youtube.com; ...");
```

| Resource | CSP rule |
|----------|----------|
| YouTube iframe | `frame-src https://www.youtube.com` |
| YouTube thumbnail | `img-src https:` (loads from `img.youtube.com`) |

If you change embed/thumbnail domains, update this header.

---

## 7. Adding YouTube Support to a New Page

If you build a new page that lists posts, follow this pattern:

### Step 1 — Include video fields in SQL

```sql
SELECT p.title, p.slug, p.image_url, p.video_type, p.video_url, ...
FROM posts p
```

### Step 2 — Use `post_card_media()` for thumbnail

```php
$media = post_card_media($post);
if ($media['url'] != '') {
    echo '<img src="' . htmlspecialchars($media['url']) . '" alt="' . htmlspecialchars($media['alt']) . '">';
}
```

### Step 3 — Use `post_has_video()` for badge

```php
if (post_has_video($post)) {
    echo '<span class="media-badge video-badge">Video</span>';
}
```

### Step 4 — On detail page, embed with `youtube_embed_url()`

```php
if ($post['video_type'] == 'youtube') {
    $embed = youtube_embed_url($post['video_url']);
    if ($embed != '') {
        echo '<iframe src="' . htmlspecialchars($embed) . '?rel=0" allowfullscreen></iframe>';
    }
}
```

---

## 8. Internet Requirement (by code path)

| Code | External URL | Needs internet |
|------|--------------|----------------|
| `youtube_thumbnail_url()` | `https://img.youtube.com/vi/{id}/hqdefault.jpg` | Yes |
| `youtube_embed_url()` | `https://www.youtube.com/embed/{id}` | Yes |
| `media_url($post['image_url'])` | `uploads/...` (local) | No |
| Uploaded MP4 in `post.php` | `uploads/videos/...` (local) | No |

---

## 9. Troubleshooting (code-level)

| Symptom | Check |
|---------|-------|
| "Invalid YouTube URL" on save | `parse_video_input()` line 149 — URL must contain `youtube.com` or `youtu.be` |
| Card shows icon, not thumbnail | Query missing `video_type` / `video_url`; or `post_card_media()` gets empty fields |
| Post page has no player | `youtube_video_id()` returned empty — URL format not matched |
| iframe blocked | CSP in `security.php` — `frame-src` must include `https://www.youtube.com` |
| Thumbnail blocked | CSP `img-src` must allow `https:` |

---

## File Index

| File | Purpose |
|------|---------|
| `database/schema.sql` | `video_url`, `video_type` columns |
| `public/admin/posts.php` | Form, save, JS toggle |
| `app/Core/helpers.php` | All YouTube helper functions |
| `app/Views/partials/news-card.php` | Card thumbnail + video badge |
| `public/index.php` | Home feed cards |
| `public/profile.php` | Profile posts query + cards |
| `public/post.php` | YouTube iframe embed |
| `app/Core/security.php` | CSP for iframe + external images |

---

## Khmer Summary / សង្ខេប

**កode សំខាន់:**

- **Save:** `public/admin/posts.php` → `parse_video_input()` in `helpers.php`
- **Card thumbnail:** `post_card_media()` → `youtube_thumbnail_url()`
- **Play video:** `public/post.php` → `youtube_embed_url()` → `<iframe>`

**SQL ត្រូវមាន:** `video_type`, `video_url` in SELECT query  
**Internet:** thumbnail + iframe load from YouTube servers
