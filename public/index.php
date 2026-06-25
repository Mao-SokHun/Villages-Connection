<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$cat_slug = '';
if (isset($_GET['cat'])) {
    $cat_slug = trim($_GET['cat']);
}

$page_title = trim(get_setting('seo_home_title', 'Home'));
if ($page_title == '') {
    $page_title = 'Home';
}

// Update page title for category pages
if ($cat_slug != '') {
    $stmt = $pdo->prepare('SELECT name FROM categories WHERE slug = :slug LIMIT 1');
    $stmt->execute(array('slug' => $cat_slug));
    $category = $stmt->fetch();
    if ($category) {
        $page_title = $category['name'];
    }
}

$page_description = trim(get_setting('seo_home_description', ''));
if ($page_description == '') {
    $page_description = site_default_meta_description();
}

require_once ROOT_PATH . '/app/Views/layouts/header.php';

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

$where = " WHERE p.status = 'Published' AND (p.expires_at IS NULL OR p.expires_at > CURRENT_TIMESTAMP)";
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
        setFlashMessage('info', __('home.follow_signin'));
        redirect_to('login.php');
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

$use_home_cache = ($cat_slug == '' && $search == '' && $author_id == 0 && $sort == 'latest' && $page == 1);
$home_cache = $use_home_cache ? app_cache_get('home_feed_meta', 120) : null;

if (is_array($home_cache) && isset($home_cache['stats'], $home_cache['all_cats'])) {
    $stats = $home_cache['stats'];
    $all_cats = $home_cache['all_cats'];
} else {
    $stats = $pdo->query("SELECT
        (SELECT COUNT(*) FROM posts WHERE status = 'Published' AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)) as total_posts,
        (SELECT COALESCE(SUM(views),0) FROM posts WHERE status = 'Published' AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)) as total_views,
        (SELECT COALESCE(SUM(likes),0) FROM posts WHERE status = 'Published' AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)) as total_likes")->fetch();

    $all_cats = $pdo->query("SELECT c.*, COUNT(p.id) as post_count
        FROM categories c
        LEFT JOIN posts p ON p.category_id = c.id AND p.status = 'Published' AND (p.expires_at IS NULL OR p.expires_at > CURRENT_TIMESTAMP)
        GROUP BY c.id ORDER BY c.name")->fetchAll();

    if ($use_home_cache) {
        app_cache_put('home_feed_meta', array(
            'stats' => $stats,
            'all_cats' => $all_cats,
        ));
    }
}

$hero_views = (int) $stats['total_views'];
$hero_likes = (int) $stats['total_likes'];

$current_category = null;
if ($cat_slug != '') {
    $sql = 'SELECT * FROM categories WHERE slug = :slug';
    $s = $pdo->prepare($sql);
    $s->execute(array('slug' => $cat_slug));
    $current_category = $s->fetch();
}

$show_hero = false;
if ($cat_slug == '' && $search == '' && $page == 1 && $sort != 'following' && $author_id == 0) {
    $show_hero = true;
}

$featured_posts = array();
if ($show_hero) {
    $featured_posts = get_featured_posts($pdo, 3);
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
<section class="hero-welcome text-center text-md-start reveal" aria-label="Welcome">
    <div class="hero-welcome__glow" aria-hidden="true"></div>
    <div class="hero-welcome__inner">
        <div class="row align-items-center g-4 g-xl-5">
            <div class="col-lg-7 hero-welcome__content">
                <?php if (isLoggedIn()):
                    $hero_avatar = '';
                    if (isset($_SESSION['user_avatar'])) {
                        $hero_avatar = $_SESSION['user_avatar'];
                    }
                    $hero_email = '';
                    if (isset($_SESSION['user_email'])) {
                        $hero_email = $_SESSION['user_email'];
                    }
                ?>
                <div class="hero-welcome__profile">
                    <?php echo render_user_avatar($_SESSION['user_name'], $hero_avatar, 'hero-welcome__avatar', $hero_email); ?>
                    <span class="hero-welcome__badge"><span class="hero-welcome__badge-emoji" aria-hidden="true">👋</span> <?php echo __('home.welcome_back'); ?></span>
                </div>
                <div class="hero-welcome__headline">
                    <h1 class="hero-welcome__greeting"><?php echo __('home.hi', array('name' => htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'))); ?></h1>
                    <p class="hero-welcome__tagline">
                        <span class="hero-welcome__tagline-a"><?php echo __('home.your_feed_a'); ?></span>
                        <span class="hero-welcome__tagline-b"><?php echo __('home.your_feed_b'); ?></span>
                    </p>
                </div>
                <p class="hero-welcome__desc"><?php echo __('home.logged_desc'); ?></p>
                <div class="hero-welcome__actions">
                    <a href="#posts-feed" class="hero-action hero-action--primary"><i class="fa-solid fa-images" aria-hidden="true"></i><span><?php echo __('home.browse_feed'); ?></span></a>
                    <a href="<?php echo feed_url(array('sort' => 'following')); ?>" class="hero-action"><i class="fa-solid fa-user-group" aria-hidden="true"></i><span><?php echo __('nav.following'); ?></span></a>
                    <a href="<?php echo create_post_url(); ?>" class="hero-action"><i class="fa-solid fa-plus" aria-hidden="true"></i><span><?php echo __('home.create_post'); ?></span></a>
                    <?php if (isAdmin() || (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'author')): ?>
                    <a href="<?php echo admin_area_url('dashboard.php'); ?>" class="hero-action"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span><?php echo __('nav.dashboard'); ?></span></a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <span class="hero-welcome__badge hero-welcome__badge--guest"><i class="fa-solid fa-seedling" aria-hidden="true"></i> <?php echo htmlspecialchars(__('site.tagline')); ?></span>
                <div class="hero-welcome__headline">
                    <h1 class="hero-welcome__greeting"><?php echo __('home.your_community'); ?></h1>
                    <p class="hero-welcome__tagline">
                        <span class="hero-welcome__tagline-a"><?php echo __('home.social_a'); ?></span>
                        <span class="hero-welcome__tagline-b"><?php echo __('home.social_b'); ?></span>
                    </p>
                </div>
                <p class="hero-welcome__desc"><?php echo __('site.desc'); ?></p>
                <div class="hero-welcome__actions">
                    <a href="#posts-feed" class="hero-action hero-action--primary"><i class="fa-solid fa-images" aria-hidden="true"></i><span><?php echo __('home.browse_feed'); ?></span></a>
                    <a href="<?php echo app_url('register.php'); ?>" class="hero-action"><i class="fa-solid fa-user-plus" aria-hidden="true"></i><span><?php echo __('home.join_free'); ?></span></a>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-5">
                <div class="hero-welcome__stats" role="group" aria-label="<?php echo htmlspecialchars(__('home.engagement_community')); ?>">
                    <div class="hero-stat">
                        <span class="hero-stat__icon hero-stat__icon--views"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
                        <span class="hero-stat__value"><?php echo number_format($hero_views); ?></span>
                        <span class="hero-stat__label"><?php echo __('home.views'); ?></span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat__icon hero-stat__icon--likes"><i class="fa-solid fa-heart" aria-hidden="true"></i></span>
                        <span class="hero-stat__value"><?php echo number_format($hero_likes); ?></span>
                        <span class="hero-stat__label"><?php echo __('home.likes'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($show_hero && count($featured_posts) > 0): ?>
<section class="featured-posts-section mb-4 reveal" aria-label="Featured posts">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 text-white mb-0"><i class="fa-solid fa-star text-warning me-2"></i><?php echo __('home.featured_title'); ?></h2>
        <span class="text-secondary small"><?php echo __('home.featured_sub'); ?></span>
    </div>
    <div class="row g-4">
        <?php foreach ($featured_posts as $art):
            $p = $art;
        ?>
        <div class="col-md-4">
            <?php include ROOT_PATH . '/app/Views/partials/news-card.php'; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($sort == 'following'): ?>
<div class="feed-header glass-panel p-3 mb-4 reveal">
    <h2 class="h5 text-white mb-1"><i class="fa-solid fa-user-group text-info me-2"></i><?php echo __('home.following_title'); ?></h2>
    <p class="text-secondary small mb-0"><?php echo __('home.following_sub'); ?></p>
</div>
<?php elseif ($author_id > 0):
    $author_user = get_user_by_id($pdo, $author_id);
?>
<div class="feed-header glass-panel p-3 mb-4 reveal">
    <h2 class="h5 text-white mb-1"><i class="fa-solid fa-user text-warning me-2"></i><?php echo $author_user ? htmlspecialchars(__('home.member_posts', array('name' => $author_user['name']))) : htmlspecialchars(__('home.member_posts_fallback')); ?></h2>
</div>
<?php endif; ?>

<div class="category-scroll-wrap mb-4 reveal d-lg-none">
    <div class="category-scroll">
        <a href="<?php echo build_page_url(array_merge($page_params, array('page' => ''))); ?>" class="cat-pill <?php if ($cat_slug == '') echo 'active'; ?>"><?php echo __('home.all'); ?></a>
        <?php foreach ($all_cats as $sc): ?>
        <a href="<?php echo build_page_url(array_merge($page_params, array('cat' => $sc['slug'], 'page' => ''))); ?>" class="cat-pill <?php if ($cat_slug == $sc['slug']) echo 'active'; ?>">
            <?php echo render_category_icon($sc['icon'], 'me-1'); ?> <?php echo htmlspecialchars($sc['name']); ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<section id="posts-feed" class="row g-4 mb-5">
    <aside class="col-lg-3 d-none d-lg-block">
        <div class="glass-panel p-4 sticky-sidebar reveal">
            <h5 class="text-white mb-3"><i class="fa-solid fa-filter text-warning me-2"></i><?php echo __('home.browse'); ?></h5>
            <?php if ($search != ''): ?>
            <div class="active-search-tag mb-3">
                <span class="text-secondary small d-block mb-1"><?php echo __('home.searching'); ?></span>
                <span class="search-tag" title="<?php echo htmlspecialchars($search); ?>">
                    <?php echo htmlspecialchars(excerpt($search, 28)); ?>
                    <a href="<?php echo build_page_url(array('sort' => $sort, 'cat' => $cat_slug)); ?>" class="search-tag-clear" title="<?php echo htmlspecialchars(__('home.clear_search')); ?>">&times;</a>
                </span>
            </div>
            <?php endif; ?>
            <div class="d-flex flex-column gap-1">
                <a href="<?php echo build_page_url(array_merge($page_params, array('cat' => '', 'page' => ''))); ?>" class="filter-chip <?php if ($cat_slug == '') echo 'active'; ?>"><?php echo __('home.all'); ?></a>
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
                <a href="<?php echo build_page_url(array('sort' => 'latest', 'cat' => $cat_slug, 'search' => $search)); ?>" class="filter-chip flex-fill text-center <?php if ($sort != 'popular') echo 'active'; ?>"><?php echo __('nav.latest'); ?></a>
                <a href="<?php echo build_page_url(array('sort' => 'popular', 'cat' => $cat_slug, 'search' => $search)); ?>" class="filter-chip flex-fill text-center <?php if ($sort == 'popular') echo 'active'; ?>"><?php echo __('nav.popular'); ?></a>
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
                            echo __('home.search_results');
                        } elseif ($sort == 'popular') {
                            echo __('home.popular_posts');
                        } else {
                            echo __('home.latest_posts');
                        }
                        ?>
                    </h2>
                    <span class="text-secondary small"><?php echo __('home.posts_found', array('count' => $total_posts)); ?></span>
                </div>
                <div class="d-flex gap-2 d-lg-none">
                    <a href="<?php echo build_page_url(array('sort' => 'latest', 'cat' => $cat_slug, 'search' => $search)); ?>" class="filter-chip <?php if ($sort != 'popular') echo 'active'; ?>"><?php echo __('nav.latest'); ?></a>
                    <a href="<?php echo build_page_url(array('sort' => 'popular', 'cat' => $cat_slug, 'search' => $search)); ?>" class="filter-chip <?php if ($sort == 'popular') echo 'active'; ?>"><?php echo __('nav.popular'); ?></a>
                </div>
            </div>
        </div>

        <?php if (count($articles) == 0): ?>
            <div class="empty-state glass-panel reveal">
                <i class="fa-solid fa-magnifying-glass"></i>
                <h4><?php echo __('home.empty_title'); ?></h4>
                <?php if ($search != ''): ?>
                <p><?php echo htmlspecialchars(__('home.empty_search', array('query' => excerpt($search, 50)))); ?></p>
                <p class="text-secondary small"><?php echo __('home.empty_search_hint'); ?></p>
                <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                    <a href="<?php echo build_page_url(array('sort' => $sort, 'cat' => $cat_slug)); ?>" class="btn btn-outline-custom"><?php echo __('home.clear_search'); ?></a>
                    <a href="<?php echo app_url('index.php'); ?>" class="btn btn-gradient"><?php echo __('home.back_home'); ?></a>
                </div>
                <?php else: ?>
                <p><?php echo __('home.empty_browse_hint'); ?></p>
                <a href="<?php echo app_url('index.php'); ?>" class="btn btn-gradient mt-2"><?php echo __('home.back_home'); ?></a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($articles as $art): ?>
                <div class="col-md-6 col-xl-4 reveal">
                    <article class="news-card glass-panel h-100">
                        <a href="<?php echo post_url($art['slug']); ?>" class="news-card-media news-card-media-sm">
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
                            <h3 class="news-card-title h6"><a href="<?php echo post_url($art['slug']); ?>"><?php echo htmlspecialchars($art['title']); ?></a></h3>
                            <p class="news-card-summary small"><?php echo htmlspecialchars(excerpt($art['summary'], 80)); ?></p>
                            <div class="news-card-meta small">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo khmer_date($art['created_at']); ?></span>
                                <span><i class="fa-solid fa-eye"></i> <?php echo (int)$art['views']; ?></span>
                                <span><i class="fa-solid fa-heart"></i> <?php echo (int)$art['likes']; ?></span>
                            </div>
                            <a href="<?php echo post_url($art['slug']); ?>" class="btn btn-outline-custom btn-sm w-100 mt-2"><?php echo __('common.read_more'); ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav class="pagination-wrap mt-5 reveal" aria-label="<?php echo htmlspecialchars(__('common.pagination')); ?>">
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
                <p class="text-secondary small text-center mt-2 mb-0"><?php echo __('common.page_of', array('page' => $page, 'total' => $total_pages)); ?></p>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

