<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$profile_id = 0;
if (isset($_GET['id'])) {
    $profile_id = (int) $_GET['id'];
}

$user = null;
$is_own_profile = false;

if ($profile_id > 0) {
    $user = get_user_by_id($pdo, $profile_id);
} elseif (isLoggedIn()) {
    $profile_id = (int) $_SESSION['user_id'];
    $user = get_user_by_id($pdo, $profile_id);
    $is_own_profile = true;
} else {
    setFlashMessage('warning', __('profile.login_required'));
    redirect_to('login.php');
}

if (!$user || !user_is_publicly_visible($user)) {
    $page_title = __('profile.not_found_title');
    require_once ROOT_PATH . '/app/Views/layouts/header.php';
    echo '<div class="empty-state glass-panel my-5"><i class="fa-solid fa-user-slash"></i><h3>' . htmlspecialchars(__('profile.not_found')) . '</h3><p>' . htmlspecialchars(__('profile.not_found_desc')) . '</p><a href="' . htmlspecialchars(app_url('index.php')) . '" class="btn btn-gradient mt-3">' . htmlspecialchars(__('profile.go_home')) . '</a></div>';
    require_once ROOT_PATH . '/app/Views/layouts/footer.php';
    exit;
}

if (is_oauth_user($user)) {
    require_once APP_PATH . '/Core/oauth.php';
    $user = oauth_ensure_user_avatar($pdo, $user);
}

if (isLoggedIn() && (int) $_SESSION['user_id'] == (int) $user['id']) {
    $is_own_profile = true;
}

if ($is_own_profile && is_oauth_user($user)) {
    refresh_user_session($pdo, (int) $user['id']);
}

$page_title = $user['name'] . ' — ' . __('profile.title_suffix');
$post_count = user_post_count($pdo, $user['id']);

$bio = '';
if (isset($user['bio'])) {
    $bio = trim($user['bio']);
}

$location = '';
if (isset($user['location'])) {
    $location = trim($user['location']);
}

$website = '';
if (isset($user['website'])) {
    $website = trim($user['website']);
}

$avatar = '';
if (isset($user['avatar'])) {
    $avatar = $user['avatar'];
}

$posts_per_page = 12;
$posts_page = 1;
if (isset($_GET['page'])) {
    $posts_page = max(1, (int) $_GET['page']);
}
$posts_offset = ($posts_page - 1) * $posts_per_page;
$posts_total_pages = 1;
if ($post_count > 0) {
    $posts_total_pages = (int) ceil($post_count / $posts_per_page);
}

$posts_sql = "SELECT p.title, p.slug, p.summary, p.image_url, p.image_alt, p.views, p.likes, p.created_at,
    p.video_type, p.video_url, p.location, p.is_featured,
    c.name AS category_name, c.icon AS category_icon
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = :uid AND p.status = 'Published'
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset";
$posts_stmt = $pdo->prepare($posts_sql);
$posts_stmt->bindValue(':uid', (int) $user['id'], PDO::PARAM_INT);
$posts_stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
$posts_stmt->bindValue(':offset', $posts_offset, PDO::PARAM_INT);
$posts_stmt->execute();
$author_posts = $posts_stmt->fetchAll();

$total_views = 0;
$total_likes = 0;
$stats_sql = "SELECT COALESCE(SUM(views), 0) as total_views, COALESCE(SUM(likes), 0) as total_likes
    FROM posts WHERE user_id = :uid AND status = 'Published'";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute(array('uid' => $user['id']));
$stats_row = $stats_stmt->fetch();
if ($stats_row) {
    $total_views = (int) $stats_row['total_views'];
    $total_likes = (int) $stats_row['total_likes'];
}

$profile_followers = follower_count($pdo, $user['id']);
$profile_following = following_count($pdo, $user['id']);
$is_following_profile = false;
if (isLoggedIn() && !$is_own_profile) {
    $is_following_profile = is_following_user($pdo, (int) $_SESSION['user_id'], (int) $user['id']);
}

$own_drafts = 0;
if ($is_own_profile) {
    $own_drafts = author_draft_count($pdo, $user['id']);
    $profile_page_base = 'profile.php?page=';
} else {
    $profile_page_base = 'profile.php?id=' . (int) $user['id'] . '&page=';
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="profile-hero glass-panel reveal mb-4">
            <div class="profile-layout">
                <div class="profile-side">
                    <div class="profile-avatar-wrap">
                        <?php echo render_user_avatar($user['name'], $avatar, 'user-avatar-xl', user_public_email($user)); ?>
                    </div>
                    <?php if ($user['role'] == 'admin'): ?>
                    <span class="dropdown-user-badge admin profile-role-badge"><?php echo __('common.admin'); ?></span>
                    <?php elseif ($user['role'] == 'author'): ?>
                    <span class="dropdown-user-badge author profile-role-badge"><?php echo __('common.author'); ?></span>
                    <?php endif; ?>
                </div>

                <div class="profile-main">
                    <div class="profile-head">
                        <div>
                            <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                            <?php
                            $profile_subtitle = user_account_subtitle($user);
                            if ($is_own_profile && $profile_subtitle != ''):
                                $profile_provider = resolve_oauth_provider($user);
                            ?>
                            <p class="profile-email">
                                <?php if (user_has_managed_email($user) && ($profile_provider == 'facebook' || $profile_provider == 'google')): ?>
                                <i class="fa-brands fa-<?php echo htmlspecialchars($profile_provider); ?>"></i>
                                <?php elseif (user_has_managed_email($user)): ?>
                                <i class="fa-solid fa-user-shield"></i>
                                <?php else: ?>
                                <i class="fa-solid fa-envelope"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($profile_subtitle); ?>
                            </p>
                            <?php elseif (!$is_own_profile): ?>
                            <p class="profile-email"><i class="fa-regular fa-calendar"></i> <?php echo __('profile.member_since', array('date' => format_date($user['created_at']))); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profile-stats">
                        <div class="profile-stat">
                            <span class="profile-stat-value"><?php echo $post_count; ?></span>
                            <span class="profile-stat-label"><?php echo __('profile.published'); ?></span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat-value"><?php echo number_format($total_views); ?></span>
                            <span class="profile-stat-label"><?php echo __('profile.views'); ?></span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat-value"><?php echo number_format($total_likes); ?></span>
                            <span class="profile-stat-label"><?php echo __('profile.likes'); ?></span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat-value profile-stat-date"><?php echo format_date($user['created_at']); ?></span>
                            <span class="profile-stat-label"><?php echo __('profile.joined'); ?></span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat-value"><?php echo $profile_followers; ?></span>
                            <span class="profile-stat-label"><?php echo __('profile.followers'); ?></span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat-value"><?php echo $profile_following; ?></span>
                            <span class="profile-stat-label"><?php echo __('profile.following'); ?></span>
                        </div>
                    </div>

                    <div class="profile-about">
                        <h6 class="profile-about-title"><?php echo __('profile.about'); ?></h6>
                        <?php if ($bio != ''): ?>
                        <p class="profile-bio"><?php echo nl2br(htmlspecialchars($bio)); ?></p>
                        <?php else: ?>
                        <p class="profile-bio profile-bio-empty">
                            <?php if ($is_own_profile): ?>
                            <?php echo __('profile.bio_empty_own'); ?>
                            <?php else: ?>
                            <?php echo __('profile.bio_empty'); ?>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <?php if ($location != '' || $website != ''): ?>
                    <div class="profile-extra">
                        <?php if ($location != ''): ?>
                        <span class="profile-chip"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($location); ?></span>
                        <?php endif; ?>
                        <?php if ($website != ''): ?>
                        <a href="<?php echo htmlspecialchars($website); ?>" target="_blank" rel="noopener" class="profile-chip profile-chip-link">
                            <i class="fa-solid fa-link"></i> <?php echo __('common.website'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($is_own_profile): ?>
                    <div class="profile-actions">
                        <a href="<?php echo app_url('edit-profile.php'); ?>" class="btn btn-gradient btn-sm"><i class="fa-solid fa-pen"></i> <?php echo __('profile.edit_profile'); ?></a>
                        <a href="admin/dashboard.php" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-gauge"></i> Dashboard</a>
                        <a href="admin/posts.php" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-square-pen"></i> My Posts</a>
                        <?php if ($own_drafts > 0): ?>
                        <a href="<?php echo admin_area_url('posts.php?status=Draft'); ?>" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-lines"></i> <?php echo __('profile.my_drafts'); ?> (<?php echo $own_drafts; ?>)</a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="profile-actions">
                        <?php if (isLoggedIn()): ?>
                        <button type="button"
                            id="follow-btn"
                            class="btn btn-sm <?php echo $is_following_profile ? 'btn-outline-custom' : 'btn-gradient'; ?>"
                            data-user-id="<?php echo (int) $user['id']; ?>"
                            data-following="<?php echo $is_following_profile ? '1' : '0'; ?>">
                            <i class="fa-solid <?php echo $is_following_profile ? 'fa-user-minus' : 'fa-user-plus'; ?>"></i>
                            <?php echo $is_following_profile ? __('profile.unfollow') : __('profile.follow'); ?>
                        </button>
                        <?php else: ?>
                        <a href="<?php echo app_url('login.php'); ?>" class="btn btn-gradient btn-sm"><i class="fa-solid fa-user-plus"></i> <?php echo __('profile.follow'); ?></a>
                        <?php endif; ?>
                        <a href="<?php echo app_url('index.php?author=' . (int) $user['id']); ?>" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-images"></i> <?php echo __('profile.view_posts'); ?></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="profile-posts-section glass-panel reveal">
            <div class="profile-section-head">
                <div>
                    <h3 class="text-white mb-1"><i class="fa-solid fa-images text-warning me-2"></i><?php echo __('profile.published_posts'); ?></h3>
                    <p class="text-secondary small mb-0"><?php echo __('bookmarks.count', array('count' => $post_count)); ?> · <?php echo __('site.name'); ?></p>
                </div>
                <?php if ($is_own_profile): ?>
                <a href="<?php echo create_post_url($base_path); ?>" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> <?php echo __('profile.new_post'); ?></a>
                <?php endif; ?>
            </div>

            <?php if (count($author_posts) == 0): ?>
            <div class="profile-empty-box">
                <div class="profile-empty-icon"><i class="fa-solid fa-file-circle-plus"></i></div>
                <h4 class="text-white mb-2"><?php echo __('profile.no_posts'); ?></h4>
                <p class="text-secondary mb-3">
                    <?php if ($is_own_profile): ?>
                    Share your first post with photos, videos, and a caption.
                    <?php else: ?>
                    This member has not published any posts yet.
                    <?php endif; ?>
                </p>
                <?php if ($is_own_profile): ?>
                <a href="<?php echo create_post_url($base_path); ?>" class="btn btn-gradient btn-sm"><i class="fa-solid fa-pen-nib"></i> <?php echo __('profile.create_post'); ?></a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($author_posts as $ap): ?>
                <div class="col-md-6 col-lg-4">
                    <?php
                    $art = $ap;
                    require ROOT_PATH . '/app/Views/partials/news-card.php';
                    ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($posts_total_pages > 1): ?>
            <nav class="profile-pagination mt-4" aria-label="<?php echo htmlspecialchars(__('profile.pagination')); ?>">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <?php if ($posts_page > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $profile_page_base . ($posts_page - 1); ?>">Previous</a></li>
                    <?php endif; ?>
                    <?php for ($pg = 1; $pg <= $posts_total_pages; $pg++): ?>
                    <li class="page-item <?php if ($pg == $posts_page) echo 'active'; ?>">
                        <a class="page-link" href="<?php echo $profile_page_base . $pg; ?>"><?php echo $pg; ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($posts_page < $posts_total_pages): ?>
                    <li class="page-item"><a class="page-link" href="<?php echo $profile_page_base . ($posts_page + 1); ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

