<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$slug = '';
if (isset($_GET['slug'])) {
    $slug = trim($_GET['slug']);
}
if ($slug == '') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon,
    u.id as author_id, u.name as author_name, u.avatar as author_avatar, u.bio as author_bio, u.location as author_location, u.role as author_role
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.slug = :slug AND p.status = 'Published'");
$stmt->execute(array('slug' => $slug));
$post = $stmt->fetch();

if (!$post) {
    $page_title = 'Not Found';
    require_once ROOT_PATH . '/app/Views/layouts/header.php';
    echo '<div class="empty-state glass-panel my-5"><i class="fa-solid fa-file-circle-xmark"></i><h3>Post not found</h3><p>This post may have been removed or is not published yet.</p><a href="index.php" class="btn btn-gradient mt-3">Go Back Home</a></div>';
    require_once ROOT_PATH . '/app/Views/layouts/footer.php';
    exit;
}

if (record_post_view($pdo, (int) $post['id'])) {
    $post['views']++;
}

$liked = false;
$lk = $pdo->prepare('SELECT id FROM post_likes WHERE post_id = :pid AND visitor_key = :vk');
$lk->execute(array('pid' => $post['id'], 'vk' => visitor_key()));
if ($lk->fetch()) {
    $liked = true;
}

$comment_errors = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_content']) && comments_are_enabled()) {
    require_valid_csrf();
    if (!isLoggedIn()) {
        $comment_errors[] = 'Please sign in to comment.';
    } else {
        $comment_content = trim($_POST['comment_content']);
        if (strlen($comment_content) < 2) {
            $comment_errors[] = 'Comment must be at least 2 characters.';
        } elseif (strlen($comment_content) > 1000) {
            $comment_errors[] = 'Comment is too long (max 1000 characters).';
        } else {
            $comment_status = comments_require_approval() ? 'pending' : 'approved';
            $sql = 'INSERT INTO post_comments (post_id, user_id, author_name, content, status) VALUES (:pid, :uid, :name, :content, :status)';
            $pdo->prepare($sql)->execute(array(
                'pid' => $post['id'],
                'uid' => (int) $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'content' => $comment_content,
                'status' => $comment_status
            ));
            log_activity($pdo, 'comment.created', 'Post #' . $post['id']);
            notify_post_author_on_comment($pdo, $post['id'], $_SESSION['user_name'], $slug);
            if ($comment_status == 'pending') {
                setFlashMessage('info', 'Your comment was submitted and is awaiting approval.');
            } else {
                setFlashMessage('success', 'Comment posted.');
            }
            header('Location: post.php?slug=' . urlencode($slug) . '#comments');
            exit;
        }
    }
}

$post_comments = array();
if (comments_are_enabled()) {
    try {
        if (isAdmin()) {
            $cstmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = :pid AND status != 'rejected' ORDER BY created_at ASC");
            $cstmt->execute(array('pid' => $post['id']));
        } elseif (isLoggedIn()) {
            $cstmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = :pid AND (status = 'approved' OR (user_id = :uid AND status = 'pending')) ORDER BY created_at ASC");
            $cstmt->execute(array('pid' => $post['id'], 'uid' => (int) $_SESSION['user_id']));
        } else {
            $cstmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = :pid AND status = 'approved' ORDER BY created_at ASC");
            $cstmt->execute(array('pid' => $post['id']));
        }
        $post_comments = $cstmt->fetchAll();
    } catch (PDOException $e) {
        $post_comments = array();
    }
}

$prev_post = null;
$next_post = null;
$nav_sql = "SELECT slug, title FROM posts WHERE status = 'Published' AND created_at > :created ORDER BY created_at ASC LIMIT 1";
$nav_stmt = $pdo->prepare($nav_sql);
$nav_stmt->execute(array('created' => $post['created_at']));
$next_post = $nav_stmt->fetch();

$nav_sql = "SELECT slug, title FROM posts WHERE status = 'Published' AND created_at < :created ORDER BY created_at DESC LIMIT 1";
$nav_stmt = $pdo->prepare($nav_sql);
$nav_stmt->execute(array('created' => $post['created_at']));
$prev_post = $nav_stmt->fetch();

$page_title = $post['title'];
$author_name = 'Author';
if (isset($post['author_name']) && $post['author_name'] != '') {
    $author_name = $post['author_name'];
}
$page_description = excerpt($post['summary'], 160);
$page_og_image = '';
if (!empty($post['image_url']) && file_exists(PUBLIC_PATH . '/uploads/' . $post['image_url'])) {
    $page_og_image = site_base_url() . '/' . media_url($post['image_url'], '');
}
$canonical_url = site_base_url() . '/post/' . rawurlencode($post['slug']);
$share_url = $canonical_url;
$extra_head = render_json_ld_article($post, $author_name, $canonical_url);

require_once ROOT_PATH . '/app/Views/layouts/header.php';
$read_time = max(1, (int) ceil(str_word_count(strip_tags($post['content'])) / 180));

$author_avatar = '';
if (isset($post['author_avatar'])) {
    $author_avatar = $post['author_avatar'];
}

$author_bio = '';
if (isset($post['author_bio'])) {
    $author_bio = trim($post['author_bio']);
}

$author_id = 0;
if (isset($post['author_id'])) {
    $author_id = (int) $post['author_id'];
}

$author_subtitle = 'Community Member';
if (isset($post['author_role'])) {
    if ($post['author_role'] == 'admin') {
        $author_subtitle = 'Administrator';
    } elseif ($post['author_role'] == 'author') {
        $author_subtitle = 'Author';
    }
}
if ($author_bio != '') {
    $author_subtitle = $author_bio;
}
?>

<nav aria-label="breadcrumb" class="mb-4 reveal">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-secondary text-decoration-none">Home</a></li>
        <li class="breadcrumb-item"><a href="index.php?cat=<?php echo urlencode($post['category_slug']); ?>" class="text-secondary text-decoration-none"><?php
            if (isset($post['category_name'])) echo htmlspecialchars($post['category_name']);
        ?></a></li>
        <li class="breadcrumb-item active text-white"><?php echo htmlspecialchars(excerpt($post['title'], 40)); ?></li>
    </ol>
</nav>

<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <article class="glass-panel p-4 p-md-5 reveal" id="post-article" data-post-id="<?php echo (int)$post['id']; ?>">
            <div class="mb-4">
                <span class="cat-chip mb-2"><?php
                    $post_cat_icon = 'fa-tag';
                    if (isset($post['category_icon']) && $post['category_icon'] != '') {
                        $post_cat_icon = $post['category_icon'];
                    }
                    echo render_category_icon($post_cat_icon, 'me-1');
                ?> <?php
                    if (isset($post['category_name'])) echo htmlspecialchars($post['category_name']);
                ?></span>
                <h1 class="text-white mb-3 post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="post-meta-bar">
                    <?php if (!empty($post['location'])): ?>
                        <span><i class="fa-solid fa-location-dot text-warning"></i> <?php echo htmlspecialchars($post['location']); ?></span>
                    <?php endif; ?>
                    <span><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($author_name); ?></span>
                    <span><i class="fa-regular fa-calendar"></i> <?php echo khmer_date($post['created_at']); ?></span>
                    <span><i class="fa-solid fa-clock"></i> <?php echo $read_time; ?> min read</span>
                    <span><i class="fa-solid fa-eye"></i> <span id="view-count"><?php echo (int)$post['views']; ?></span></span>
                </div>
            </div>

            <?php if (!empty($post['image_url']) && file_exists(PUBLIC_PATH . '/uploads/' . $post['image_url'])): ?>
            <figure class="post-hero-image mb-4 rounded overflow-hidden">
                <img src="<?php echo media_url($post['image_url'], ''); ?>" alt="<?php echo htmlspecialchars(post_image_alt($post, $post['title'])); ?>" class="w-100">
            </figure>
            <?php endif; ?>

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
                    <video class="w-100" controls playsinline poster="<?php
                        if (!empty($post['image_url'])) echo media_url($post['image_url'], '');
                    ?>">
                        <source src="<?php echo media_url($post['video_url'], 'videos'); ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <p class="lead-summary text-secondary mb-4"><?php echo htmlspecialchars($post['summary']); ?></p>

            <div class="article-body text-secondary post-content-markdown"><?php echo render_post_content($post['content']); ?></div>

            <div class="author-card mt-5">
                <?php echo render_user_avatar($author_name, $author_avatar, 'user-avatar-lg'); ?>
                <div class="author-card-info">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($author_name); ?></div>
                    <div class="text-secondary small author-card-bio"><?php echo htmlspecialchars(excerpt($author_subtitle, 120)); ?></div>
                    <?php if ($author_id > 0): ?>
                    <a href="profile.php?id=<?php echo $author_id; ?>" class="author-profile-link small">
                        <i class="fa-solid fa-user"></i> View Profile
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="post-actions-bar mt-4 pt-4 border-top border-secondary">
                <button type="button" class="btn btn-like <?php if ($liked) echo 'liked'; ?>" id="btn-like" data-liked="<?php if ($liked) echo '1'; else echo '0'; ?>">
                    <i class="fa-<?php if ($liked) echo 'solid'; else echo 'regular'; ?> fa-heart"></i>
                    <span id="like-count"><?php echo (int)$post['likes']; ?></span> Likes
                </button>
                <button type="button" class="btn btn-outline-custom btn-share" data-share="copy" data-url="<?php echo htmlspecialchars($share_url); ?>">
                    <i class="fa-solid fa-link"></i> Copy Link
                </button>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($share_url); ?>" target="_blank" rel="noopener" class="btn btn-outline-custom">
                    <i class="fa-brands fa-facebook"></i> Share
                </a>
                <a href="https://t.me/share/url?url=<?php echo urlencode($share_url); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" rel="noopener" class="btn btn-outline-custom">
                    <i class="fa-brands fa-telegram"></i> Telegram
                </a>
                <a href="report.php?url=<?php echo urlencode($share_url); ?>" class="btn btn-outline-custom">
                    <i class="fa-solid fa-flag"></i> Report
                </a>
            </div>
        </article>

        <?php if (comments_are_enabled()): ?>
        <section id="comments" class="glass-panel p-4 mt-4 reveal">
            <h4 class="text-white mb-3"><i class="fa-solid fa-comments text-info me-2"></i>Comments (<?php echo count($post_comments); ?>)</h4>

            <?php if (count($comment_errors) > 0): ?>
            <div class="alert alert-danger"><ul class="mb-0 small"><?php foreach ($comment_errors as $ce): ?><li><?php echo htmlspecialchars($ce); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
            <form method="POST" action="post.php?slug=<?php echo urlencode($slug); ?>#comments" class="mb-4">
                <?php echo csrf_field(); ?>
                <label class="form-label form-label-custom">Add a comment</label>
                <textarea name="comment_content" class="form-control form-control-custom mb-2" rows="3" placeholder="Share your thoughts..." required></textarea>
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-paper-plane"></i> Post Comment</button>
            </form>
            <?php else: ?>
            <p class="text-secondary small mb-4"><a href="login.php" class="footer-link">Sign in</a> to join the conversation.</p>
            <?php endif; ?>

            <?php if (count($post_comments) == 0): ?>
            <p class="text-secondary small mb-0">No comments yet. Be the first to comment.</p>
            <?php else: ?>
            <div class="comment-list">
                <?php foreach ($post_comments as $comment): ?>
                <?php $can_manage_comment = can_manage_comment($comment); ?>
                <div class="comment-item" id="comment-<?php echo (int) $comment['id']; ?>" data-comment-id="<?php echo (int) $comment['id']; ?>">
                    <div class="comment-meta">
                        <strong class="text-white"><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                        <span class="text-secondary small"><?php echo date('M j, Y H:i', strtotime($comment['created_at'])); ?></span>
                        <?php if ($comment['status'] == 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                        <?php if ($can_manage_comment): ?>
                        <span class="comment-actions ms-auto">
                            <button type="button" class="btn btn-sm btn-outline-custom py-0 px-2 comment-edit-btn" data-id="<?php echo (int) $comment['id']; ?>" data-content="<?php echo htmlspecialchars($comment['content']); ?>"><i class="fa-solid fa-pen"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-custom text-danger py-0 px-2 comment-delete-btn" data-id="<?php echo (int) $comment['id']; ?>"><i class="fa-solid fa-trash"></i></button>
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-secondary mb-0 comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($prev_post || $next_post): ?>
        <div class="post-nav-row mt-4 reveal">
            <?php if ($prev_post): ?>
            <a href="post.php?slug=<?php echo urlencode($prev_post['slug']); ?>" class="post-nav-card glass-panel">
                <span class="post-nav-label"><i class="fa-solid fa-arrow-left"></i> Previous</span>
                <span class="post-nav-title"><?php echo htmlspecialchars(excerpt($prev_post['title'], 50)); ?></span>
            </a>
            <?php else: ?>
            <div></div>
            <?php endif; ?>

            <?php if ($next_post): ?>
            <a href="post.php?slug=<?php echo urlencode($next_post['slug']); ?>" class="post-nav-card glass-panel text-end">
                <span class="post-nav-label">Next <i class="fa-solid fa-arrow-right"></i></span>
                <span class="post-nav-title"><?php echo htmlspecialchars(excerpt($next_post['title'], 50)); ?></span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <aside class="col-lg-4">
        <div class="glass-panel p-4 mb-4 reveal sticky-sidebar">
            <h5 class="text-white mb-3"><i class="fa-solid fa-fire text-warning me-2"></i>Popular Posts</h5>
            <?php
            $pop = $pdo->prepare('SELECT title, slug, views, likes, image_url FROM posts WHERE id != :id AND status = \'Published\' ORDER BY views DESC LIMIT 4');
            $pop->execute(array('id' => $post['id']));
            $popular_list = $pop->fetchAll();
            if (count($popular_list) == 0):
            ?>
            <p class="text-secondary small mb-0">No other posts yet.</p>
            <?php else: foreach ($popular_list as $p): ?>
            <a href="post.php?slug=<?php echo urlencode($p['slug']); ?>" class="sidebar-post-item">
                <?php if ($p['image_url'] != '' && file_exists(PUBLIC_PATH . '/uploads/' . $p['image_url'])): ?>
                <img src="<?php echo media_url($p['image_url'], ''); ?>" alt="" class="sidebar-post-thumb">
                <?php else: ?>
                <div class="sidebar-post-thumb sidebar-post-thumb-placeholder"><i class="fa-solid fa-image"></i></div>
                <?php endif; ?>
                <div>
                    <div class="sidebar-post-title"><?php echo htmlspecialchars(excerpt($p['title'], 45)); ?></div>
                    <div class="text-secondary small"><i class="fa-solid fa-eye"></i> <?php echo (int)$p['views']; ?> · <i class="fa-solid fa-heart"></i> <?php echo (int)$p['likes']; ?></div>
                </div>
            </a>
            <?php endforeach; endif; ?>
        </div>

        <div class="glass-panel p-4 reveal">
            <h5 class="text-white mb-3"><i class="fa-solid fa-tags text-warning me-2"></i>Related Posts</h5>
            <?php
            $rel = $pdo->prepare('SELECT title, slug, image_url FROM posts WHERE category_id = :cid AND id != :id AND status = \'Published\' ORDER BY created_at DESC LIMIT 5');
            $rel->execute(array('cid' => $post['category_id'], 'id' => $post['id']));
            $related_list = $rel->fetchAll();
            if (count($related_list) == 0):
            ?>
            <p class="text-secondary small mb-0">No related posts in this category.</p>
            <?php else: foreach ($related_list as $r): ?>
            <a href="post.php?slug=<?php echo urlencode($r['slug']); ?>" class="sidebar-post-item">
                <?php if ($r['image_url'] != '' && file_exists(PUBLIC_PATH . '/uploads/' . $r['image_url'])): ?>
                <img src="<?php echo media_url($r['image_url'], ''); ?>" alt="" class="sidebar-post-thumb">
                <?php else: ?>
                <div class="sidebar-post-thumb sidebar-post-thumb-placeholder"><i class="fa-solid fa-image"></i></div>
                <?php endif; ?>
                <div class="sidebar-post-title"><?php echo htmlspecialchars(excerpt($r['title'], 50)); ?></div>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </aside>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

