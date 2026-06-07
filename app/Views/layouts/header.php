<?php
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__, 3) . '/bootstrap.php';
}

$is_admin_dir = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
$base_path = $is_admin_dir ? '../' : '';

$current_page = basename($_SERVER['SCRIPT_NAME']);
$nav_search = '';
if (isset($_GET['search'])) {
    $nav_search = trim($_GET['search']);
}

$support_pages = array('about.php', 'faq.php', 'help-us.php', 'contact.php', 'report.php');
$is_support_page = in_array($current_page, $support_pages);

$show_user_menu_create_post = true;
$show_user_menu_dashboard = true;
$show_user_menu_my_posts = true;
$show_user_menu_categories = true;
$show_user_menu_my_profile = true;
$show_user_menu_notifications = true;
$show_user_menu_edit_profile = true;

// Navbar bell covers notifications everywhere.
$show_user_menu_notifications = false;
// Footer CTA also links to create/register flow for guests.
$show_user_menu_create_post = isLoggedIn() && !isAdmin();

if ($current_page == 'profile.php') {
    $show_user_menu_my_profile = false;
}

if ($current_page == 'edit-profile.php') {
    $show_user_menu_edit_profile = false;
}

if ($is_admin_dir && isset($_GET['action']) && $_GET['action'] == 'add') {
    $show_user_menu_create_post = false;
}

if (!isset($page_description) || $page_description == '') {
    $page_description = SITE_DESC;
}
if (!isset($page_og_image)) {
    $page_og_image = '';
}
if (!isset($canonical_url) || $canonical_url == '') {
    $canonical_url = current_page_url();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME . ' | ' . SITE_TAGLINE; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars(SITE_NAME); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars(isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); ?>">
    <?php if ($page_og_image != ''): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($page_og_image); ?>">
    <?php endif; ?>
    <?php if (isset($extra_head) && $extra_head != ''): ?>
    <?php echo $extra_head; ?>
    <?php endif; ?>
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo htmlspecialchars(isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <script>
    (function() {
        var saved = localStorage.getItem('cms-theme');
        var theme = 'dark';
        if (saved == 'light' || saved == 'dark') {
            theme = saved;
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
            theme = 'light';
        }
        document.documentElement.setAttribute('data-theme', theme);
    })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="<?php echo $base_path; ?>css/style.css" rel="stylesheet">
    <?php if (isLoggedIn()): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
    <?php endif; ?>
</head>
<body>
<div class="liquid-bg" aria-hidden="true">
    <div class="liquid-blob liquid-blob-1"></div>
    <div class="liquid-blob liquid-blob-2"></div>
    <div class="liquid-blob liquid-blob-3"></div>
</div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top" id="main-navbar">
    <div class="container-fluid navbar-container">
        <a class="navbar-brand navbar-brand-custom" href="<?php echo $base_path; ?>index.php">
            <span class="brand-icon"><i class="fa-solid fa-house-chimney"></i></span>
            <span class="brand-text">
                <span class="brand-title"><?php echo SITE_NAME; ?></span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav nav-menu-pills">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php if ($current_page == 'index.php' && $nav_search == '' && !isset($_GET['sort']) && !isset($_GET['cat']) && !isset($_GET['author'])) echo 'active'; ?>" href="<?php echo $base_path; ?>index.php">
                        <i class="fa-solid fa-house"></i><span>Feed</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php if (isset($_GET['sort']) && $_GET['sort'] == 'popular') echo 'active'; ?>" href="<?php echo $base_path; ?>index.php?sort=popular">
                        <i class="fa-solid fa-fire"></i><span>Popular</span>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php if (isset($_GET['sort']) && $_GET['sort'] == 'following') echo 'active'; ?>" href="<?php echo $base_path; ?>index.php?sort=following">
                        <i class="fa-solid fa-user-group"></i><span>Following</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown nav-support-dropdown">
                    <a class="nav-link nav-link-custom dropdown-toggle <?php if ($is_support_page) echo 'active'; ?>" href="#" id="navbarSupport" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">
                        <i class="fa-solid fa-life-ring"></i><span>Support</span>
                    </a>
                    <ul class="dropdown-menu glass-dropdown" aria-labelledby="navbarSupport">
                        <li class="dropdown-header-custom">Help &amp; Contact</li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'about.php') echo 'active'; ?>" href="<?php echo $base_path; ?>about.php">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-circle-info"></i></span>
                                <span class="dropdown-item-text">About</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'faq.php') echo 'active'; ?>" href="<?php echo $base_path; ?>faq.php">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-circle-question"></i></span>
                                <span class="dropdown-item-text">FAQ</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'help-us.php') echo 'active'; ?>" href="<?php echo $base_path; ?>help-us.php">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-hand-holding-heart"></i></span>
                                <span class="dropdown-item-text">Help Us</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'contact.php') echo 'active'; ?>" href="<?php echo $base_path; ?>contact.php">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-envelope"></i></span>
                                <span class="dropdown-item-text">Contact Us</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'report.php') echo 'active'; ?>" href="<?php echo $base_path; ?>report.php">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-flag"></i></span>
                                <span class="dropdown-item-text">Report Content</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown nav-cat-dropdown">
                    <a class="nav-link nav-link-custom dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">
                        <i class="fa-solid fa-tags"></i><span>Categories</span>
                    </a>
                    <ul class="dropdown-menu glass-dropdown" aria-labelledby="navbarDropdown">
                        <li class="dropdown-header-custom">Browse by Topic</li>
                        <li>
                            <a class="dropdown-item-custom" href="<?php echo $base_path; ?>index.php">
                                <span class="dropdown-item-icon all"><i class="fa-solid fa-border-all"></i></span>
                                <span class="dropdown-item-text">All Categories</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider-custom"></li>
                        <?php
                        $nav_cats = $pdo->query('SELECT name, slug, icon FROM categories ORDER BY name ASC')->fetchAll();
                        foreach ($nav_cats as $nc):
                        ?>
                        <li>
                            <a class="dropdown-item-custom" href="<?php echo $base_path; ?>index.php?cat=<?php echo $nc['slug']; ?>">
                                <span class="dropdown-item-icon"><?php echo render_category_icon($nc['icon'], ''); ?></span>
                                <span class="dropdown-item-text"><?php echo htmlspecialchars($nc['name']); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>

            <div class="navbar-actions">
                <form action="<?php echo $base_path; ?>index.php" method="GET" class="nav-search-form">
                    <div class="nav-search-wrap">
                        <i class="fa-solid fa-search nav-search-icon"></i>
                        <input type="text" name="search" class="nav-search-input" placeholder="Search..." value="<?php echo htmlspecialchars($nav_search); ?>">
                    </div>
                </form>

                <div class="navbar-tools">
                    <button type="button" id="theme-toggle" class="nav-tool-btn theme-toggle-btn" aria-label="Toggle theme" title="Toggle theme">
                        <i class="fa-solid fa-moon theme-icon-dark"></i>
                        <i class="fa-solid fa-sun theme-icon-light"></i>
                    </button>

                    <?php if (isLoggedIn()):
                        $nav_avatar = '';
                        if (isset($_SESSION['user_avatar'])) {
                            $nav_avatar = $_SESSION['user_avatar'];
                        }
                        $nav_notify_count = unread_notification_count($pdo, (int) $_SESSION['user_id']);
                    ?>
                    <div class="dropdown nav-notify-dropdown">
                        <button class="nav-tool-btn nav-notify-btn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications" title="Notifications">
                            <i class="fa-solid fa-bell"></i>
                            <?php if ($nav_notify_count > 0): ?><span class="nav-notify-badge" id="nav-notify-badge"><?php echo $nav_notify_count; ?></span><?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end glass-dropdown nav-notify-menu">
                            <div class="nav-notify-head">
                                <strong>Notifications</strong>
                                <a href="<?php echo $base_path; ?>notifications.php" class="small">View all</a>
                            </div>
                            <div id="nav-notify-list" class="nav-notify-list">
                                <div class="nav-notify-empty text-secondary small">Loading...</div>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown nav-user-dropdown">
                        <button class="nav-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                            <?php echo render_user_avatar($_SESSION['user_name'], $nav_avatar, ''); ?>
                            <span class="nav-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </button>
                        <ul class="dropdown-menu glass-dropdown dropdown-menu-end user-dropdown-menu">
                            <li class="dropdown-user-header">
                                <?php echo render_user_avatar($_SESSION['user_name'], $nav_avatar, 'user-avatar-md'); ?>
                                <div>
                                    <div class="dropdown-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                                    <div class="dropdown-user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                                    <span class="dropdown-user-badge admin">Admin</span>
                                    <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'author'): ?>
                                    <span class="dropdown-user-badge author">Author</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider-custom"></li>
                            <?php if (isAdmin()): ?>
                            <?php if ($show_user_menu_dashboard): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo $base_path; ?>admin/dashboard.php"><span class="dropdown-item-icon"><i class="fa-solid fa-gauge"></i></span><span class="dropdown-item-text">Dashboard</span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_my_posts): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo $base_path; ?>admin/posts.php"><span class="dropdown-item-icon"><i class="fa-solid fa-square-pen"></i></span><span class="dropdown-item-text">Manage Posts</span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_categories): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo $base_path; ?>admin/categories.php"><span class="dropdown-item-icon"><i class="fa-solid fa-tags"></i></span><span class="dropdown-item-text">Categories</span></a></li>
                            <?php endif; ?>
                            <?php else: ?>
                            <?php if ($show_user_menu_dashboard): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo $base_path; ?>admin/dashboard.php"><span class="dropdown-item-icon"><i class="fa-solid fa-gauge"></i></span><span class="dropdown-item-text">Dashboard</span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_my_posts): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo $base_path; ?>admin/posts.php"><span class="dropdown-item-icon"><i class="fa-solid fa-pen-nib"></i></span><span class="dropdown-item-text">My Posts</span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_create_post): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo create_post_url($base_path); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-plus"></i></span><span class="dropdown-item-text">Create Post</span></a></li>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($show_user_menu_my_profile): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo $base_path; ?>profile.php"><span class="dropdown-item-icon"><i class="fa-solid fa-user"></i></span><span class="dropdown-item-text">My Profile</span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_edit_profile): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo $base_path; ?>edit-profile.php"><span class="dropdown-item-icon"><i class="fa-solid fa-user-pen"></i></span><span class="dropdown-item-text">Edit Profile</span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_notifications): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo $base_path; ?>notifications.php"><span class="dropdown-item-icon"><i class="fa-solid fa-bell"></i></span><span class="dropdown-item-text">Notifications</span></a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider-custom"></li>
                            <li><a class="dropdown-item-custom danger" href="<?php echo $base_path; ?>logout.php"><span class="dropdown-item-icon"><i class="fa-solid fa-sign-out-alt"></i></span><span class="dropdown-item-text">Logout</span></a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <a href="<?php echo $base_path; ?>login.php" class="nav-auth-link">Sign In</a>
                    <a href="<?php echo $base_path; ?>register.php" class="btn btn-gradient btn-sm nav-register-btn">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>

<?php
$site_announcement = null;
if (isset($pdo)) {
    $site_announcement = get_active_announcement($pdo);
}
if ($site_announcement):
?>
<div class="site-announcement reveal visible">
    <div class="container">
        <div class="site-announcement-inner">
            <i class="fa-solid fa-bullhorn"></i>
            <div>
                <strong><?php echo htmlspecialchars($site_announcement['title']); ?></strong>
                <span><?php echo htmlspecialchars($site_announcement['message']); ?></span>
            </div>
            <?php if ($site_announcement['link_url'] != ''): ?>
            <a href="<?php echo htmlspecialchars($site_announcement['link_url']); ?>" class="site-announcement-link">Learn more</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container py-5">
