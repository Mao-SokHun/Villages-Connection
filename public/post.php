<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$slug = '';
if (isset($_GET['slug'])) {
    $slug = trim(rawurldecode((string) $_GET['slug']));
}
if ($slug == '') {
    redirect_to('index.php');
}

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon,
    u.id as author_id, u.name as author_name, u.email as author_email, u.avatar as author_avatar, u.bio as author_bio, u.location as author_location, u.role as author_role
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.slug = :slug AND p.status = 'Published' AND (p.expires_at IS NULL OR p.expires_at > CURRENT_TIMESTAMP)" . sql_hide_inactive_authors('u'));
$stmt->execute(array('slug' => $slug));
$post = $stmt->fetch();

if (!$post) {
    $page_title = __('post.not_found_title');
    require_once ROOT_PATH . '/app/Views/layouts/header.php';
    echo '<div class="empty-state glass-panel my-5"><i class="fa-solid fa-file-circle-xmark"></i><h3>' . htmlspecialchars(__('post.not_found_title')) . '</h3><p>' . htmlspecialchars(__('post.not_found_desc')) . '</p><a href="' . htmlspecialchars(app_url('index.php')) . '" class="btn btn-gradient mt-3">' . htmlspecialchars(__('post.go_home')) . '</a></div>';
    require_once ROOT_PATH . '/app/Views/layouts/footer.php';
    exit;
}

if (record_post_view($pdo, (int) $post['id'])) {
    $post['views']++;
}

$liked = false;
if (isLoggedIn()) {
    $liked = user_liked_post($pdo, (int) $post['id'], (int) $_SESSION['user_id']);
}

$is_post_owner = isLoggedIn() && (int) $_SESSION['user_id'] == (int) $post['user_id'];

$comment_errors = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['moderate_comment']) && comments_are_enabled()) {
    require_valid_csrf();
    if (!$is_post_owner && !isAdmin()) {
        setFlashMessage('danger', __('post.cannot_moderate'));
    } else {
        $moderate_id = 0;
        if (isset($_POST['comment_id'])) {
            $moderate_id = (int) $_POST['comment_id'];
        }
        $moderate_action = trim($_POST['moderate_comment']);
        if ($moderate_id > 0 && ($moderate_action == 'approve' || $moderate_action == 'reject')) {
            $result = moderate_post_owner_comment($pdo, $moderate_id, $moderate_action == 'approve' ? 'approved' : 'rejected');
            if ($result['ok']) {
                setFlashMessage('success', $moderate_action == 'approve' ? __('post.comment_approved') : __('post.comment_rejected'));
            } else {
                setFlashMessage('danger', $result['error']);
            }
        }
    }
    header('Location: ' . post_url($slug, '/') . '#comments');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_content']) && comments_are_enabled()) {
    require_valid_csrf();
    if (!isLoggedIn()) {
        $comment_errors[] = __('post.sign_in_comment');
    } else {
        $comment_user_id = (int) $_SESSION['user_id'];
        if (!rate_limit_hit('post_comment', 'user:' . $comment_user_id, 15, 300)) {
            $comment_errors[] = rate_limit_blocked_response('post_comment', 'user:' . $comment_user_id, 300, false);
        } else {
        $parent_id = 0;
        if (isset($_POST['parent_id'])) {
            $parent_id = (int) $_POST['parent_id'];
        }
        $comment_content = '';
        if (isset($_POST['comment_content'])) {
            $comment_content = trim($_POST['comment_content']);
        }
        $result = create_post_comment($pdo, (int) $post['id'], $comment_user_id, $_SESSION['user_name'], $comment_content, $parent_id);
        if ($result['ok'] == false) {
            $comment_errors[] = $result['error'];
        } else {
            log_activity($pdo, 'comment.created', 'Post #' . $post['id']);
            $comment_status = $result['status'];
            notify_post_author_on_comment($pdo, $post['id'], $_SESSION['user_name'], $slug, $comment_status == 'pending', (int) $result['id']);
            if ($parent_id > 0 && $comment_status == 'approved') {
                notify_comment_reply($pdo, $parent_id, $_SESSION['user_name'], $slug, $post['title'], (int) $result['id']);
            }
            if ($comment_status == 'pending') {
                notify_admins_pending_comment($pdo, $post['id'], $_SESSION['user_name']);
            }
            if ($comment_status == 'pending') {
                setFlashMessage('info', __('comments.pending_notice'));
            } else {
                setFlashMessage('success', $parent_id > 0 ? __('comments.reply_posted') : __('comments.posted'));
            }
            $anchor = $parent_id > 0 ? '#comment-' . $parent_id : '#comments';
            header('Location: ' . post_url($slug, '/') . $anchor);
            exit;
        }
        }
    }
}

$post_comments = array();
if (comments_are_enabled()) {
    try {
        if (isAdmin()) {
            $cstmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = :pid AND status NOT IN ('rejected', 'deleted') ORDER BY created_at ASC");
            $cstmt->execute(array('pid' => $post['id']));
        } elseif (isLoggedIn() && $is_post_owner) {
            $cstmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = :pid AND status NOT IN ('rejected', 'deleted') ORDER BY created_at ASC");
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

$comment_tree = build_comment_tree($post_comments);
$comment_total = count_visible_comments($post_comments);

$bookmarked = false;
if (isLoggedIn()) {
    $bookmarked = user_bookmarked_post($pdo, (int) $_SESSION['user_id'], (int) $post['id']);
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
if (!empty($post['image_url']) && post_media_available($post['image_url'], '')) {
    $page_og_image = resolve_media_src($post['image_url'], '');
    if (!is_remote_media_url($page_og_image)) {
        $page_og_image = site_base_url() . $page_og_image;
    }
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

$author_email = '';
if (isset($post['author_email'])) {
    $author_email = trim($post['author_email']);
}

$author_bio = '';
if (isset($post['author_bio'])) {
    $author_bio = trim($post['author_bio']);
}

$author_id = 0;
if (isset($post['author_id'])) {
    $author_id = (int) $post['author_id'];
}

$author_subtitle = __('post.community_member');
if (isset($post['author_role'])) {
    if ($post['author_role'] == 'admin') {
        $author_subtitle = __('post.administrator');
    } elseif ($post['author_role'] == 'author') {
        $author_subtitle = __('common.author');
    }
}
if ($author_bio != '') {
    $author_subtitle = $author_bio;
}
?>

<nav aria-label="breadcrumb" class="mb-4 reveal">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo app_url('index.php'); ?>" class="text-secondary text-decoration-none"><?php echo __('common.home'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo feed_url(array('cat' => $post['category_slug'])); ?>" class="text-secondary text-decoration-none"><?php
            if (isset($post['category_name'])) echo htmlspecialchars($post['category_name']);
        ?></a></li>
        <li class="breadcrumb-item active text-white"><?php echo htmlspecialchars(excerpt($post['title'], 40)); ?></li>
    </ol>
</nav>

<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <article class="glass-panel p-4 p-md-5 reveal" id="post-article" data-post-id="<?php echo (int)$post['id']; ?>" data-require-login="<?php echo isLoggedIn() ? '0' : '1'; ?>">
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
                    <span><i class="fa-solid fa-clock"></i> <?php echo __('post.min_read', array('mins' => $read_time)); ?></span>
                    <span><i class="fa-solid fa-eye"></i> <span id="view-count"><?php echo (int)$post['views']; ?></span></span>
                </div>
            </div>

            <?php if (!empty($post['image_url']) && post_media_available($post['image_url'], '')): ?>
            <figure class="post-hero-image mb-4 rounded overflow-hidden">
                <img src="<?php echo htmlspecialchars(resolve_media_src($post['image_url'], '')); ?>" alt="<?php echo htmlspecialchars(post_image_alt($post, $post['title'])); ?>" class="w-100">
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
                <?php elseif ($post['video_type'] == 'upload' && post_media_available($post['video_url'], 'videos')): ?>
                    <video class="w-100" controls playsinline poster="<?php
                        if (!empty($post['image_url']) && post_media_available($post['image_url'], '')) {
                            echo htmlspecialchars(resolve_media_src($post['image_url'], ''));
                        }
                    ?>">
                        <source src="<?php echo htmlspecialchars(resolve_media_src($post['video_url'], 'videos')); ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <p class="lead-summary text-secondary mb-4"><?php echo htmlspecialchars($post['summary']); ?></p>

            <div class="article-body text-secondary post-content-markdown"><?php echo render_post_content($post['content']); ?></div>

            <div class="author-card mt-5">
                <?php echo render_user_avatar($author_name, $author_avatar, 'user-avatar-lg', $author_email); ?>
                <div class="author-card-info">
                    <div class="text-white fw-semibold"><?php echo htmlspecialchars($author_name); ?></div>
                    <div class="text-secondary small author-card-bio"><?php echo htmlspecialchars(excerpt($author_subtitle, 120)); ?></div>
                    <?php if ($author_id > 0): ?>
                    <a href="profile.php?id=<?php echo $author_id; ?>" class="author-profile-link small">
                        <i class="fa-solid fa-user"></i> <?php echo __('post.view_profile'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="post-actions-bar mt-4 pt-4 border-top border-secondary">
                <button type="button" class="btn btn-outline-custom btn-bookmark <?php if ($bookmarked) echo 'is-bookmarked'; ?>" id="btn-bookmark" data-post-id="<?php echo (int) $post['id']; ?>" data-bookmarked="<?php echo $bookmarked ? '1' : '0'; ?>" data-require-login="<?php echo isLoggedIn() ? '0' : '1'; ?>">
                    <i class="fa-<?php echo $bookmarked ? 'solid' : 'regular'; ?> fa-bookmark"></i> <?php echo __('bookmarks.save'); ?>
                </button>
                <button type="button" class="btn btn-like <?php if ($liked) echo 'liked'; ?>" id="btn-like" data-liked="<?php if ($liked) echo '1'; else echo '0'; ?>" title="<?php echo htmlspecialchars(__('post.like_login')); ?>">
                    <i class="fa-<?php if ($liked) echo 'solid'; else echo 'regular'; ?> fa-heart"></i>
                    <span id="like-count"><?php echo (int)$post['likes']; ?></span> <?php echo __('post.likes'); ?>
                </button>
                <button type="button" class="btn btn-outline-custom btn-share" data-share="copy" data-url="<?php echo htmlspecialchars($share_url); ?>">
                    <i class="fa-solid fa-link"></i> <?php echo __('post.copy_link'); ?>
                </button>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($share_url); ?>" target="_blank" rel="noopener" class="btn btn-outline-custom">
                    <i class="fa-brands fa-facebook"></i> <?php echo __('post.share'); ?>
                </a>
                <a href="https://t.me/share/url?url=<?php echo urlencode($share_url); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" rel="noopener" class="btn btn-outline-custom">
                    <i class="fa-brands fa-telegram"></i> Telegram
                </a>
                <a href="<?php echo app_url('report.php?url=' . urlencode($share_url)); ?>" class="btn btn-outline-custom">
                    <i class="fa-solid fa-flag"></i> <?php echo __('post.report'); ?>
                </a>
                <?php if (comments_are_enabled()): ?>
                <a href="#comments" class="btn btn-outline-custom">
                    <i class="fa-solid fa-comments"></i> <?php echo __('comments.title'); ?> (<?php echo (int) $comment_total; ?>)
                </a>
                <?php endif; ?>
            </div>
        </article>

        <?php if (comments_are_enabled()): ?>
        <section id="comments" class="glass-panel p-4 mt-4 reveal comments-section">
            <h4 class="text-white mb-3"><i class="fa-solid fa-comments text-info me-2"></i><?php echo __('comments.title'); ?> (<?php echo (int) $comment_total; ?>)</h4>

            <?php if (count($comment_errors) > 0): ?>
            <?php render_user_alerts($comment_errors, 'danger'); ?>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
            <form method="POST" action="<?php echo htmlspecialchars(post_url($slug, '/') . '#comments'); ?>" class="mb-4" id="comment-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="parent_id" id="comment-parent-id" value="0">
                <div id="comment-reply-banner" class="comment-reply-banner" hidden>
                    <span><?php echo __('comments.replying_to'); ?> <strong id="comment-reply-author"></strong></span>
                    <button type="button" class="btn btn-link btn-sm p-0" id="comment-reply-cancel"><?php echo __('comments.cancel_reply'); ?></button>
                </div>
                <label class="form-label form-label-custom" for="comment-content"><?php echo __('comments.add'); ?></label>
                <textarea name="comment_content" id="comment-content" class="form-control form-control-custom mb-2" rows="3" placeholder="<?php echo htmlspecialchars(__('comments.placeholder')); ?>" required></textarea>
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-paper-plane"></i> <?php echo __('comments.submit'); ?></button>
                <?php if (!$is_post_owner && comments_require_approval()): ?>
                <p class="text-secondary small mt-2 mb-0"><i class="fa-solid fa-hourglass-half me-1"></i><?php echo __('comments.approval_note'); ?></p>
                <?php endif; ?>
            </form>
            <?php else: ?>
            <p class="text-secondary small mb-4"><a href="login.php" class="footer-link"><?php echo __('nav.sign_in'); ?></a> <?php echo __('comments.sign_in'); ?></p>
            <?php endif; ?>

            <?php if ($comment_total == 0): ?>
            <p class="text-secondary small mb-0"><?php echo __('comments.empty'); ?></p>
            <?php else: ?>
            <div class="comment-list">
                <?php foreach ($comment_tree as $comment):
                    $comment_depth = 0;
                    $comment_post_slug = $slug;
                    $comment_is_post_owner = $is_post_owner;
                    include ROOT_PATH . '/app/Views/partials/comment-item.php';
                endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($prev_post || $next_post): ?>
        <div class="post-nav-row mt-4 reveal">
            <?php if ($prev_post): ?>
            <a href="<?php echo post_url($prev_post['slug']); ?>" class="post-nav-card glass-panel">
                <span class="post-nav-label"><i class="fa-solid fa-arrow-left"></i> <?php echo __('post.previous'); ?></span>
                <span class="post-nav-title"><?php echo htmlspecialchars(excerpt($prev_post['title'], 50)); ?></span>
            </a>
            <?php else: ?>
            <div></div>
            <?php endif; ?>

            <?php if ($next_post): ?>
            <a href="<?php echo post_url($next_post['slug']); ?>" class="post-nav-card glass-panel text-end">
                <span class="post-nav-label"><?php echo __('post.next'); ?> <i class="fa-solid fa-arrow-right"></i></span>
                <span class="post-nav-title"><?php echo htmlspecialchars(excerpt($next_post['title'], 50)); ?></span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <aside class="col-lg-4">
        <div class="glass-panel p-4 mb-4 reveal sticky-sidebar">
            <h5 class="text-white mb-3"><i class="fa-solid fa-fire text-warning me-2"></i><?php echo __('post.popular_posts'); ?></h5>
            <?php
            $pop = $pdo->prepare("SELECT p.title, p.slug, p.views, p.likes, p.image_url, p.video_type, p.video_url, c.icon AS category_icon
                FROM posts p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.id != :id AND p.status = 'Published'
                ORDER BY p.views DESC LIMIT 4");
            $pop->execute(array('id' => $post['id']));
            $popular_list = $pop->fetchAll();
            if (count($popular_list) == 0):
            ?>
            <p class="text-secondary small mb-0"><?php echo __('post.no_other_posts'); ?></p>
            <?php else: foreach ($popular_list as $p): ?>
            <a href="<?php echo post_url($p['slug']); ?>" class="sidebar-post-item">
                <?php echo render_sidebar_post_thumb($p); ?>
                <div>
                    <div class="sidebar-post-title"><?php echo htmlspecialchars(excerpt($p['title'], 45)); ?></div>
                    <div class="text-secondary small"><i class="fa-solid fa-eye"></i> <?php echo (int)$p['views']; ?> · <i class="fa-solid fa-heart"></i> <?php echo (int)$p['likes']; ?></div>
                </div>
            </a>
            <?php endforeach; endif; ?>
        </div>

        <div class="glass-panel p-4 reveal">
            <h5 class="text-white mb-3"><i class="fa-solid fa-tags text-warning me-2"></i><?php echo __('post.related_posts'); ?></h5>
            <?php
            $rel = $pdo->prepare("SELECT p.title, p.slug, p.image_url, p.video_type, p.video_url, c.icon AS category_icon
                FROM posts p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.category_id = :cid AND p.id != :id AND p.status = 'Published'
                ORDER BY p.id DESC LIMIT 5");
            $rel->execute(array('cid' => $post['category_id'], 'id' => $post['id']));
            $related_list = $rel->fetchAll();
            if (count($related_list) == 0):
            ?>
            <p class="text-secondary small mb-0"><?php echo __('post.no_related'); ?></p>
            <?php else: foreach ($related_list as $r): ?>
            <a href="<?php echo post_url($r['slug']); ?>" class="sidebar-post-item">
                <?php echo render_sidebar_post_thumb($r); ?>
                <div class="sidebar-post-title"><?php echo htmlspecialchars(excerpt($r['title'], 50)); ?></div>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </aside>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

