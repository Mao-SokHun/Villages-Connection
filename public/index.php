<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$page_title = trim(get_setting('seo_home_title', 'Home'));
if ($page_title == '') {
    $page_title = 'Home';
}

$page_description = trim(get_setting('seo_home_description', ''));
if ($page_description == '') {
    $page_description = site_default_meta_description();
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';

$cat_slug = '';
if (isset($_GET['cat'])) {
    $cat_slug = trim($_GET['cat']);
}

$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

$sort = 'latest';
if (isset($_GET['sort'])) {
    $sort = trim($_GET['sort']);
}

$author_id = 0;
if (isset($_GET['author'])) {
    $author_id = (int) $_GET['author'];
}

$page = 1;
if (isset($_GET['page'])) {
    $page = (int) $_GET['page'];
}
if ($page < 1) {
    $page = 1;
}

$per_page = 9;

$where = " WHERE p.status = 'Published'";
$where .= sql_hide_inactive_authors('u');
$params = array();

if ($cat_slug != '') {
    $where .= ' AND c.slug = :cat_slug';
    $params['cat_slug'] = $cat_slug;
}
if ($search != '') {
    $where .= ' AND (p.title ILIKE :search OR p.summary ILIKE :search OR p.content ILIKE :search OR p.location ILIKE :search OR u.name ILIKE :search OR c.name ILIKE :search)';
    $params['search'] = '%' . $search . '%';
}
if ($author_id > 0) {
    $where .= ' AND p.user_id = :author_id';
    $params['author_id'] = $author_id;
}
if ($sort == 'following') {
    if (isLoggedIn()) {
        $where .= ' AND p.user_id IN (SELECT following_id FROM user_follows WHERE follower_id = :follower_id)';
        $params['follower_id'] = (int) $_SESSION['user_id'];
    } else {
        setFlashMessage('info', 'Sign in to see posts from people you follow.');
        header('Location: login.php');
        exit;
    }
}

$count_sql = 'SELECT COUNT(*) FROM posts p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.user_id = u.id' . $where;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_posts = (int) $count_stmt->fetchColumn();

$total_pages = 1;
if ($total_posts > 0) {
    $total_pages = (int) ceil($total_posts / $per_page);
}
if ($page > $total_pages) {
    $page = $total_pages;
}
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $per_page;

$order = ' ORDER BY p.id DESC';
if ($sort == 'popular') {
    $order = ' ORDER BY p.views DESC, p.likes DESC, p.id DESC';
}

$query = "SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon,
          u.name as author_name
          FROM posts p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN users u ON p.user_id = u.id" . $where . $order . ' LIMIT ' . $per_page . ' OFFSET ' . $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$articles = $stmt->fetchAll();

$featured = $pdo->query("SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon, u.name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.status = 'Published' AND p.is_featured = TRUE" . sql_hide_inactive_authors('u') . "
    ORDER BY p.id DESC LIMIT 3")->fetchAll();

if (count($featured) == 0 && $total_posts > 0 && $page == 1 && $search == '') {
    $feat_sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon, u.name as author_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.user_id = u.id" . $where . $order . ' LIMIT 3';
    $feat_stmt = $pdo->prepare($feat_sql);
    $feat_stmt->execute($params);
    $featured = $feat_stmt->fetchAll();
}

$stats = $pdo->query("SELECT
    (SELECT COUNT(*) FROM posts WHERE status = 'Published') as total_posts,
    (SELECT COALESCE(SUM(views),0) FROM posts WHERE status = 'Published') as total_views,
    (SELECT COALESCE(SUM(likes),0) FROM posts WHERE status = 'Published') as total_likes")->fetch();

$current_category = null;
if ($cat_slug != '') {
    $sql = 'SELECT * FROM categories WHERE slug = :slug';
    $s = $pdo->prepare($sql);
    $s->execute(array('slug' => $cat_slug));
    $current_category = $s->fetch();
}

$all_cats = $pdo->query("SELECT c.*, COUNT(p.id) as post_count
    FROM categories c
    LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'Published'
    GROUP BY c.id ORDER BY c.name")->fetchAll();

$show_hero = false;
if ($cat_slug == '' && $search == '' && $page == 1 && $sort != 'following' && $author_id == 0) {
    $show_hero = true;
}

$page_params = array();
if ($cat_slug != '') {
    $page_params['cat'] = $cat_slug;
}
if ($search != '') {
    $page_params['search'] = $search;
}
if ($sort != 'latest') {
    $page_params['sort'] = $sort;
}
if ($author_id > 0) {
    $page_params['author'] = $author_id;
}
?>

<?php if ($show_hero): ?>
<section class="hero-section hero-village text-center text-md-start mb-5 reveal">
    <div class="hero-glow"></div>
    <div class="row align-items-center g-4 px-md-3">
        <div class="col-lg-7">
            <?php if (isLoggedIn()): ?>
            <span class="hero-badge"><i class="fa-solid fa-hand-sparkles me-2"></i>Welcome back</span>
            <h1 class="display-5 text-white mb-3 lh-sm">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?><br><span class="text-gradient-gold">Your Feed</span></h1>
            <p class="text-secondary mb-4 lead-sm">Catch up on community posts, follow authors, and share your own updates.</p>
            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-start">
                <a href="#posts-feed" class="btn btn-gradient px-4 py-3"><i class="fa-solid fa-images"></i> Browse Feed</a>
                <a href="index.php?sort=following" class="btn btn-outline-custom px-4 py-3"><i class="fa-solid fa-user-group"></i> Following</a>
                <a href="<?php echo create_post_url(); ?>" class="btn btn-outline-custom px-4 py-3"><i class="fa-solid fa-plus"></i> Create Post</a>
                <?php if (isAdmin() || (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'author')): ?>
                <a href="admin/dashboard.php" class="btn btn-outline-custom px-4 py-3"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <span class="hero-badge"><i class="fa-solid fa-seedling me-2"></i><?php echo SITE_TAGLINE; ?></span>
            <h1 class="display-5 text-white mb-3 lh-sm">Your Community<br><span class="text-gradient-gold">Social Feed</span></h1>
            <p class="text-secondary mb-4 lead-sm"><?php echo SITE_DESC; ?></p>
            <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-start">
                <a href="#posts-feed" class="btn btn-gradient px-4 py-3"><i class="fa-solid fa-images"></i> Browse Feed</a>
                <a href="register.php" class="btn btn-outline-custom px-4 py-3"><i class="fa-solid fa-user-plus"></i> Join Free</a>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-5">
            <div class="stats-glass row g-3 text-center">
                <div class="col-4">
                    <div class="stat-item"><span class="stat-num"><?php echo (int)$stats['total_posts']; ?></span><span class="stat-label">Posts</span></div>
                </div>
                <div class="col-4">
                    <div class="stat-item"><span class="stat-num"><?php echo number_format((int)$stats['total_views']); ?></span><span class="stat-label">Views</span></div>
                </div>
                <div class="col-4">
                    <div class="stat-item"><span class="stat-num"><?php echo number_format((int)$stats['total_likes']); ?></span><span class="stat-label">Likes</span></div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($sort == 'following'): ?>
<div class="feed-header glass-panel p-3 mb-4 reveal">
    <h2 class="h5 text-white mb-1"><i class="fa-solid fa-user-group text-info me-2"></i>Following Feed</h2>
    <p class="text-secondary small mb-0">Posts from members you follow.</p>
</div>
<?php elseif ($author_id > 0):
    $author_user = get_user_by_id($pdo, $author_id);
?>
<div class="feed-header glass-panel p-3 mb-4 reveal">
    <h2 class="h5 text-white mb-1"><i class="fa-solid fa-user text-warning me-2"></i><?php echo $author_user ? htmlspecialchars($author_user['name']) . "'s Posts" : 'Member Posts'; ?></h2>
</div>
<?php endif; ?>

<div class="category-scroll-wrap mb-4 reveal d-lg-none">
    <div class="category-scroll">
        <a href="<?php echo build_page_url(array_merge($page_params, array('page' => ''))); ?>" class="cat-pill <?php if ($cat_slug == '') echo 'active'; ?>">All</a>
        <?php foreach ($all_cats as $sc): ?>
        <a href="<?php echo build_page_url(array_merge($page_params, array('cat' => $sc['slug'], 'page' => ''))); ?>" class="cat-pill <?php if ($cat_slug == $sc['slug']) echo 'active'; ?>">
            <?php echo render_category_icon($sc['icon'], 'me-1'); ?> <?php echo htmlspecialchars($sc['name']); ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($show_hero && count($featured) > 0): ?>
<section class="mb-5 reveal" id="featured-posts">
    <div class="section-header mb-3">
        <h2 class="h4 text-white mb-0"><i class="fa-solid fa-star text-warning me-2"></i>Featured Posts</h2>
        <p class="text-secondary small mb-0">Popular posts picked for the community feed</p>
    </div>
    <div class="row g-4">
        <?php foreach ($featured as $fp): ?>
        <div class="col-md-4">
            <article class="news-card glass-panel h-100 news-card-featured">
                <a href="post.php?slug=<?php echo urlencode($fp['slug']); ?>" class="news-card-media">
                    <?php $fp_media = post_card_media($fp); ?>
                    <?php if ($fp_media['url'] != ''): ?>
                        <img src="<?php echo htmlspecialchars($fp_media['url']); ?>" alt="<?php echo htmlspecialchars($fp_media['alt']); ?>">
                    <?php else: ?>
                        <div class="news-card-placeholder"><?php
                            $fp_icon = 'fa-image';
                            if (isset($fp['category_icon']) && $fp['category_icon'] != '') {
                                $fp_icon = $fp['category_icon'];
                            }
                            echo render_category_icon($fp_icon, 'news-card-ph-icon');
                        ?></div>
                    <?php endif; ?>
                    <?php if (post_has_video($fp)): ?><span class="media-badge video"><i class="fa-solid fa-play"></i> Video</span><?php endif; ?>
                    <?php if (!empty($fp['is_featured'])): ?><span class="media-badge featured"><i class="fa-solid fa-star"></i></span><?php endif; ?>
                </a>
                <div class="news-card-body p-4">
                    <span class="cat-chip"><?php
                        $fp_chip_icon = 'fa-tag';
                        if (isset($fp['category_icon']) && $fp['category_icon'] != '') {
                            $fp_chip_icon = $fp['category_icon'];
                        }
                        echo render_category_icon($fp_chip_icon, 'me-1');
                    ?> <?php
                        if (isset($fp['category_name'])) echo htmlspecialchars($fp['category_name']);
                    ?></span>
                    <h3 class="news-card-title"><a href="post.php?slug=<?php echo urlencode($fp['slug']); ?>"><?php echo htmlspecialchars($fp['title']); ?></a></h3>
                    <p class="news-card-summary"><?php echo htmlspecialchars(excerpt($fp['summary'], 100)); ?></p>
                    <div class="news-card-meta">
                        <?php if (!empty($fp['location'])): ?><span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($fp['location']); ?></span><?php endif; ?>
                        <span><i class="fa-solid fa-eye"></i> <?php echo (int)$fp['views']; ?></span>
                        <span><i class="fa-solid fa-heart"></i> <?php echo (int)$fp['likes']; ?></span>
                    </div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section id="posts-feed" class="row g-4 mb-5">
    <aside class="col-lg-3 d-none d-lg-block">
        <div class="glass-panel p-4 sticky-sidebar reveal">
            <h5 class="text-white mb-3"><i class="fa-solid fa-filter text-warning me-2"></i>Browse</h5>
            <?php if ($search != ''): ?>
            <div class="active-search-tag mb-3">
                <span class="text-secondary small d-block mb-1">Searching:</span>
                <span class="search-tag" title="<?php echo htmlspecialchars($search); ?>">
                    <?php echo htmlspecialchars(excerpt($search, 28)); ?>
                    <a href="<?php echo build_page_url(array('sort' => $sort, 'cat' => $cat_slug)); ?>" class="search-tag-clear" title="Clear search">&times;</a>
                </span>
            </div>
            <?php endif; ?>
            <div class="d-flex flex-column gap-1">
                <a href="<?php echo build_page_url(array_merge($page_params, array('cat' => '', 'page' => ''))); ?>" class="filter-chip <?php if ($cat_slug == '') echo 'active'; ?>">All</a>
                <?php foreach ($all_cats as $sc): ?>
                <a href="<?php echo build_page_url(array_merge($page_params, array('cat' => $sc['slug'], 'page' => ''))); ?>" class="filter-chip <?php if ($cat_slug == $sc['slug']) echo 'active'; ?>">
                    <?php echo render_category_icon($sc['icon'], 'me-1'); ?>
                    <?php echo htmlspecialchars($sc['name']); ?>
                    <span class="ms-auto badge rounded-pill"><?php echo (int)$sc['post_count']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <hr class="border-secondary my-3">
            <div class="d-flex gap-2">
                <a href="<?php echo build_page_url(array('sort' => 'latest', 'cat' => $cat_slug, 'search' => $search)); ?>" class="filter-chip flex-fill text-center <?php if ($sort != 'popular') echo 'active'; ?>">Latest</a>
                <a href="<?php echo build_page_url(array('sort' => 'popular', 'cat' => $cat_slug, 'search' => $search)); ?>" class="filter-chip flex-fill text-center <?php if ($sort == 'popular') echo 'active'; ?>">Popular</a>
            </div>
        </div>
    </aside>

    <div class="col-lg-9">
        <div class="feed-header glass-panel p-3 mb-4 reveal">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="h4 text-white mb-0">
                        <?php
                        if ($current_category) {
                            echo htmlspecialchars($current_category['name']);
                        } elseif ($search != '') {
                            echo 'Search Results';
                        } elseif ($sort == 'popular') {
                            echo 'Popular Posts';
                        } else {
                            echo 'Latest Posts';
                        }
                        ?>
                    </h2>
                    <span class="text-secondary small"><?php echo $total_posts; ?> posts found</span>
                </div>
                <div class="d-flex gap-2 d-lg-none">
                    <a href="<?php echo build_page_url(array('sort' => 'latest', 'cat' => $cat_slug, 'search' => $search)); ?>" class="filter-chip <?php if ($sort != 'popular') echo 'active'; ?>">Latest</a>
                    <a href="<?php echo build_page_url(array('sort' => 'popular', 'cat' => $cat_slug, 'search' => $search)); ?>" class="filter-chip <?php if ($sort == 'popular') echo 'active'; ?>">Popular</a>
                </div>
            </div>
        </div>

        <?php if (count($articles) == 0): ?>
            <div class="empty-state glass-panel reveal">
                <i class="fa-solid fa-magnifying-glass"></i>
                <h4>No posts found</h4>
                <?php if ($search != ''): ?>
                <p>No results for <strong>"<?php echo htmlspecialchars(excerpt($search, 50)); ?>"</strong></p>
                <p class="text-secondary small">Try shorter keywords or browse by category</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                    <a href="<?php echo build_page_url(array('sort' => $sort, 'cat' => $cat_slug)); ?>" class="btn btn-outline-custom">Clear Search</a>
                    <a href="index.php" class="btn btn-gradient">Back to Home</a>
                </div>
                <?php else: ?>
                <p>Try a different category or check back later</p>
                <a href="index.php" class="btn btn-gradient mt-2">Back to Home</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($articles as $art): ?>
                <div class="col-md-6 col-xl-4 reveal">
                    <article class="news-card glass-panel h-100">
                        <a href="post.php?slug=<?php echo urlencode($art['slug']); ?>" class="news-card-media news-card-media-sm">
                            <?php $art_media = post_card_media($art); ?>
                            <?php if ($art_media['url'] != ''): ?>
                                <img src="<?php echo htmlspecialchars($art_media['url']); ?>" alt="<?php echo htmlspecialchars($art_media['alt']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="news-card-placeholder"><?php
                                    $art_icon = 'fa-image';
                                    if (isset($art['category_icon']) && $art['category_icon'] != '') {
                                        $art_icon = $art['category_icon'];
                                    }
                                    echo render_category_icon($art_icon, 'news-card-ph-icon');
                                ?></div>
                            <?php endif; ?>
                            <?php if (post_has_video($art)): ?><span class="media-badge video"><i class="fa-solid fa-play"></i></span><?php endif; ?>
                        </a>
                        <div class="news-card-body p-3">
                            <span class="cat-chip small"><?php
                                if (isset($art['category_name'])) echo htmlspecialchars($art['category_name']);
                            ?></span>
                            <h3 class="news-card-title h6"><a href="post.php?slug=<?php echo urlencode($art['slug']); ?>"><?php echo htmlspecialchars($art['title']); ?></a></h3>
                            <p class="news-card-summary small"><?php echo htmlspecialchars(excerpt($art['summary'], 80)); ?></p>
                            <div class="news-card-meta small">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo khmer_date($art['created_at']); ?></span>
                                <span><i class="fa-solid fa-eye"></i> <?php echo (int)$art['views']; ?></span>
                                <span><i class="fa-solid fa-heart"></i> <?php echo (int)$art['likes']; ?></span>
                            </div>
                            <a href="post.php?slug=<?php echo urlencode($art['slug']); ?>" class="btn btn-outline-custom btn-sm w-100 mt-2">Read More <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav class="pagination-wrap mt-5 reveal" aria-label="Page navigation">
                <ul class="pagination-custom">
                    <?php if ($page > 1): ?>
                    <li><a href="<?php echo build_page_url(array_merge($page_params, array('page' => $page - 1))); ?>" class="page-link-custom"><i class="fa-solid fa-chevron-left"></i></a></li>
                    <?php endif; ?>

                    <?php
                    $start = $page - 2;
                    if ($start < 1) $start = 1;
                    $end = $start + 4;
                    if ($end > $total_pages) $end = $total_pages;
                    if ($end - $start < 4) {
                        $start = $end - 4;
                        if ($start < 1) $start = 1;
                    }
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <li><a href="<?php echo build_page_url(array_merge($page_params, array('page' => $i))); ?>" class="page-link-custom <?php if ($i == $page) echo 'active'; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                    <li><a href="<?php echo build_page_url(array_merge($page_params, array('page' => $page + 1))); ?>" class="page-link-custom"><i class="fa-solid fa-chevron-right"></i></a></li>
                    <?php endif; ?>
                </ul>
                <p class="text-secondary small text-center mt-2 mb-0">Page <?php echo $page; ?> of <?php echo $total_pages; ?></p>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

