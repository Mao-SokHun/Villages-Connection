<?php
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__, 3) . '/bootstrap.php';
}

$is_admin_dir = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
$base_path = $is_admin_dir ? '../' : '';

$current_page = basename($_SERVER['SCRIPT_NAME']);
$nav_search = '';
if (isset($_GET['q'])) {
    $nav_search = trim($_GET['q']);
} elseif (isset($_GET['search'])) {
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
    $page_description = site_default_meta_description();
}
if (!isset($page_og_image)) {
    $page_og_image = '';
}
if (!isset($canonical_url) || $canonical_url == '') {
    $canonical_url = current_page_url();
}

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_locale()); ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <?php if (!$is_admin_dir && pretty_urls_enabled()):
        $document_base_href = '/';
        if (defined('APP_URL') && APP_URL != '') {
            $document_base_href = rtrim(APP_URL, '/') . '/';
        }
    ?>
    <base href="<?php echo htmlspecialchars($document_base_href); ?>">
    <?php endif; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="dark light">
    <title><?php echo htmlspecialchars(isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME . ' | ' . SITE_TAGLINE, ENT_QUOTES, 'UTF-8'); ?></title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Estonia&family=Fredoka:wght@600;700&family=Inter:opsz,wght@14..32,400..700&family=Kantumruy+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="<?php echo public_asset_url('css/style.css'); ?>?v=<?php echo asset_version('css/style.css'); ?>" rel="stylesheet">
    <link rel="manifest" href="<?php echo public_asset_url('manifest.webmanifest'); ?>">
    <link rel="icon" type="image/png" href="<?php echo public_asset_url('icons/logo.png'); ?>?v=<?php echo asset_version('icons/logo.png'); ?>">
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="<?php echo public_asset_url('icons/logo.png'); ?>">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token()); ?>">
    <script>
    window.APP_BASE = <?php echo json_encode($base_path); ?>;
    window.APP_PRETTY = <?php echo pretty_urls_enabled() ? 'true' : 'false'; ?>;
    window.APP_ROUTE_MAP = <?php echo json_encode(public_pretty_route_map()); ?>;
    window.APP_I18N = <?php echo json_encode(array(
        'like_login' => __('post.like_login'),
        'like_thanks' => __('post.like_thanks'),
        'like_removed' => __('post.like_removed'),
        'like_failed' => __('post.like_failed'),
        'bookmark_login' => __('bookmarks.login'),
        'bookmark_saved' => __('bookmarks.saved'),
        'bookmark_removed' => __('bookmarks.removed'),
        'bookmark_fail' => __('js.bookmark_fail'),
        'notify_open' => __('nav.notify_open'),
        'notify_empty' => __('nav.notify_empty'),
        'notify_loading' => __('nav.notify_loading'),
        'notify_error' => __('nav.notify_error'),
        'confirm' => __('common.confirm'),
        'confirm_message' => __('common.confirm_message'),
        'cancel' => __('common.cancel'),
        'delete' => __('common.delete'),
        'error' => __('common.error'),
        'failed' => __('common.failed'),
        'copied' => __('common.copied'),
        'copied_link' => __('common.copied_link'),
        'copy_link_prompt' => __('common.copy_link_prompt'),
        'network_error' => __('common.network_error'),
        'server_error' => __('common.server_error'),
        'sign_in' => __('common.sign_in'),
        'show_password' => __('common.show_password'),
        'hide_password' => __('common.hide_password'),
        'choose_category' => __('js.choose_category'),
        'comment_min' => __('js.comment_min'),
        'comment_max' => __('js.comment_max'),
        'comment_update_fail' => __('js.comment_update_fail'),
        'comment_delete_fail' => __('js.comment_delete_fail'),
        'comment_delete_confirm' => __('comments.delete_confirm'),
        'follow_fail' => __('js.follow_fail'),
        'try_again' => __('js.try_again'),
        'follow' => __('profile.follow'),
        'unfollow' => __('profile.unfollow'),
        'push_not_configured' => __('push.not_configured'),
        'push_enable_failed' => __('push.enable_failed'),
    )); ?>;
    </script>
</head>
<body<?php echo $is_admin_dir ? '' : ' class="site-public"'; ?>>
<div class="liquid-bg" aria-hidden="true">
    <div class="liquid-blob liquid-blob-1"></div>
    <div class="liquid-blob liquid-blob-2"></div>
    <div class="liquid-blob liquid-blob-3"></div>
</div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top" id="main-navbar">
    <div class="container navbar-container">
        <a class="navbar-brand navbar-brand-custom" href="<?php echo app_url('index.php'); ?>">
            <?php echo render_code_logo('nav'); ?>
        </a>

        <button class="navbar-toggler navbar-toggler--desktop" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="<?php echo htmlspecialchars(__('common.nav_toggle')); ?>">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav nav-menu-pills">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php if ($current_page == 'index.php' && $nav_search == '' && !isset($_GET['sort']) && !isset($_GET['cat']) && !isset($_GET['author'])) echo 'active'; ?>" href="<?php echo app_url('index.php'); ?>">
                        <i class="fa-solid fa-house"></i><span><?php echo __('nav.feed'); ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php if (isset($_GET['sort']) && $_GET['sort'] == 'popular') echo 'active'; ?>" href="<?php echo app_url('index.php?sort=popular'); ?>">
                        <i class="fa-solid fa-fire"></i><span><?php echo __('nav.popular'); ?></span>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php if (isset($_GET['sort']) && $_GET['sort'] == 'following') echo 'active'; ?>" href="<?php echo app_url('index.php?sort=following'); ?>">
                        <i class="fa-solid fa-user-group"></i><span><?php echo __('nav.following'); ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown nav-support-dropdown">
                    <a class="nav-link nav-link-custom dropdown-toggle <?php if ($is_support_page) echo 'active'; ?>" href="#" id="navbarSupport" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">
                        <i class="fa-solid fa-life-ring"></i><span><?php echo __('nav.support'); ?></span>
                    </a>
                    <ul class="dropdown-menu glass-dropdown" aria-labelledby="navbarSupport">
                        <li class="dropdown-header-custom"><?php echo __('nav.help_contact'); ?></li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'about.php') echo 'active'; ?>" href="<?php echo app_url('about.php'); ?>">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-circle-info"></i></span>
                                <span class="dropdown-item-text"><?php echo __('nav.about'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'faq.php') echo 'active'; ?>" href="<?php echo app_url('faq.php'); ?>">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-circle-question"></i></span>
                                <span class="dropdown-item-text"><?php echo __('nav.faq'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'help-us.php') echo 'active'; ?>" href="<?php echo app_url('help-us.php'); ?>">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-hand-holding-heart"></i></span>
                                <span class="dropdown-item-text"><?php echo __('nav.help_us'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'contact.php') echo 'active'; ?>" href="<?php echo app_url('contact.php'); ?>">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-envelope"></i></span>
                                <span class="dropdown-item-text"><?php echo __('nav.contact'); ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item-custom <?php if ($current_page == 'report.php') echo 'active'; ?>" href="<?php echo app_url('report.php'); ?>">
                                <span class="dropdown-item-icon"><i class="fa-solid fa-flag"></i></span>
                                <span class="dropdown-item-text"><?php echo __('nav.report'); ?></span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown nav-cat-dropdown">
                    <a class="nav-link nav-link-custom dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" data-bs-display="static" data-bs-auto-close="true" aria-expanded="false">
                        <i class="fa-solid fa-tags"></i><span><?php echo __('nav.categories'); ?></span>
                    </a>
                    <ul class="dropdown-menu glass-dropdown" aria-labelledby="navbarDropdown">
                        <li class="dropdown-header-custom"><?php echo __('nav.browse_topics'); ?></li>
                        <li>
                            <a class="dropdown-item-custom" href="<?php echo app_url('index.php'); ?>">
                                <span class="dropdown-item-icon all"><i class="fa-solid fa-border-all"></i></span>
                                <span class="dropdown-item-text"><?php echo __('nav.all_categories'); ?></span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider-custom"></li>
                        <?php
                        foreach (nav_category_list($pdo) as $nc):
                        ?>
                        <li>
                            <a class="dropdown-item-custom" href="<?php echo app_url('index.php?cat=' . rawurlencode($nc['slug'])); ?>">
                                <span class="dropdown-item-icon"><?php echo render_category_icon($nc['icon'], ''); ?></span>
                                <span class="dropdown-item-text"><?php echo htmlspecialchars($nc['name']); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>

            <div class="navbar-actions">
                <form action="<?php echo app_url('search.php'); ?>" method="GET" class="nav-search-form">
                    <div class="nav-search-wrap">
                        <i class="fa-solid fa-magnifying-glass nav-search-icon"></i>
                        <input type="text" name="q" class="nav-search-input" placeholder="<?php echo htmlspecialchars(__('nav.search')); ?>" value="<?php echo htmlspecialchars($nav_search); ?>">
                    </div>
                </form>

                <div class="navbar-tools">
                    <a href="<?php echo htmlspecialchars(language_toggle_url()); ?>" class="lang-switch-btn lang-switch-btn--toggle <?php echo current_locale() === 'km' ? 'lang-switch-btn-km' : ''; ?> is-active" title="<?php echo htmlspecialchars(current_locale() === 'km' ? __('lang.km') : __('lang.en')); ?>" aria-label="<?php echo htmlspecialchars(__('common.language')); ?>">
                        <?php echo htmlspecialchars(current_locale() === 'km' ? __('lang.km_short') : __('lang.en_short')); ?>
                    </a>
                    <button type="button" id="theme-toggle" class="nav-tool-btn theme-toggle-btn" aria-label="<?php echo htmlspecialchars(__('common.theme')); ?>" title="<?php echo htmlspecialchars(__('common.theme')); ?>">
                        <i class="fa-solid fa-moon theme-icon-dark"></i>
                        <i class="fa-solid fa-sun theme-icon-light"></i>
                    </button>

                    <?php if (isLoggedIn()):
                        $nav_avatar = '';
                        if (isset($_SESSION['user_avatar'])) {
                            $nav_avatar = $_SESSION['user_avatar'];
                        }
                        $nav_session_user = array(
                            'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '',
                            'oauth_provider' => isset($_SESSION['oauth_provider']) ? $_SESSION['oauth_provider'] : 'local',
                        );
                        $nav_account_subtitle = user_account_subtitle($nav_session_user);
                    ?>
                    <div class="dropdown nav-notify-dropdown">
                        <button class="nav-tool-btn nav-notify-btn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="<?php echo htmlspecialchars(__('nav.notifications')); ?>" title="<?php echo htmlspecialchars(__('nav.notifications')); ?>">
                            <i class="fa-solid fa-bell"></i>
                            <span class="nav-notify-badge" id="nav-notify-badge" hidden></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end glass-dropdown nav-notify-menu">
                            <div class="nav-notify-head">
                                <div>
                                    <strong><?php echo __('nav.notifications'); ?></strong>
                                    <span class="nav-notify-unread-label text-secondary" id="nav-notify-unread-label" hidden></span>
                                </div>
                                <div class="nav-notify-head-actions">
                                    <button type="button" class="btn btn-link btn-sm p-0 nav-notify-mark-all" id="nav-notify-mark-all" hidden><?php echo __('nav.mark_all_read'); ?></button>
                                    <a href="<?php echo app_url('notifications.php'); ?>" class="small"><?php echo __('nav.view_all'); ?></a>
                                </div>
                            </div>
                            <div id="nav-notify-list" class="nav-notify-list">
                                <div class="nav-notify-empty text-secondary small"><?php echo __('nav.notify_open'); ?></div>
                            </div>
                            <?php if (!isAdmin()): ?>
                            <div class="nav-notify-foot">
                                <a href="<?php echo app_url('support.php'); ?>"><i class="fa-solid fa-headset"></i> <?php echo __('nav.support_messages'); ?></a>
                                <a href="<?php echo app_url('contact.php'); ?>"><i class="fa-solid fa-envelope"></i> <?php echo __('nav.contact'); ?></a>
                            </div>
                            <?php else: ?>
                            <div class="nav-notify-foot">
                                <a href="<?php echo admin_area_url('messages.php'); ?>"><i class="fa-solid fa-inbox"></i> <?php echo __('nav.contact_inbox'); ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="dropdown nav-user-dropdown">
                        <button class="nav-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                            <?php echo render_user_avatar($_SESSION['user_name'], $nav_avatar, '', $_SESSION['user_email']); ?>
                            <span class="nav-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        </button>
                        <ul class="dropdown-menu glass-dropdown dropdown-menu-end user-dropdown-menu">
                            <li class="dropdown-user-header">
                                <?php echo render_user_avatar($_SESSION['user_name'], $nav_avatar, 'user-avatar-md', $_SESSION['user_email']); ?>
                                <div class="dropdown-user-info">
                                    <div class="dropdown-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                                    <?php if ($nav_account_subtitle != ''): ?>
                                    <div class="dropdown-user-email"><?php echo htmlspecialchars($nav_account_subtitle); ?></div>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                                    <span class="dropdown-user-badge admin"><?php echo __('common.admin'); ?></span>
                                    <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'author'): ?>
                                    <span class="dropdown-user-badge author"><?php echo __('common.author'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider-custom"></li>
                            <?php if (isAdmin()): ?>
                            <?php if ($show_user_menu_dashboard): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo admin_area_url('dashboard.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-gauge"></i></span><span class="dropdown-item-text"><?php echo __('nav.dashboard'); ?></span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_my_posts): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo admin_area_url('posts.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-square-pen"></i></span><span class="dropdown-item-text"><?php echo __('nav.manage_posts'); ?></span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_categories): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo admin_area_url('categories.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-tags"></i></span><span class="dropdown-item-text"><?php echo __('nav.categories'); ?></span></a></li>
                            <?php endif; ?>
                            <?php else: ?>
                            <?php if ($show_user_menu_dashboard): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo admin_area_url('dashboard.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-gauge"></i></span><span class="dropdown-item-text"><?php echo __('nav.dashboard'); ?></span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_my_posts): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo admin_area_url('posts.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-pen-nib"></i></span><span class="dropdown-item-text"><?php echo __('nav.my_posts'); ?></span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_create_post): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo create_post_url($base_path); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-plus"></i></span><span class="dropdown-item-text"><?php echo __('nav.create_post'); ?></span></a></li>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($show_user_menu_my_profile): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo isLoggedIn() ? profile_url((int) $_SESSION['user_id']) : app_url('profile.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-user"></i></span><span class="dropdown-item-text"><?php echo __('nav.my_profile'); ?></span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_edit_profile): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo app_url('edit-profile.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-user-pen"></i></span><span class="dropdown-item-text"><?php echo __('nav.edit_profile'); ?></span></a></li>
                            <?php endif; ?>
                            <?php if ($show_user_menu_notifications): ?>
                            <li><a class="dropdown-item-custom" href="<?php echo app_url('bookmarks.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-bookmark"></i></span><span class="dropdown-item-text"><?php echo __('nav.bookmarks'); ?></span></a></li>
                            <li><a class="dropdown-item-custom" href="<?php echo app_url('notifications.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-bell"></i></span><span class="dropdown-item-text"><?php echo __('nav.notifications'); ?></span></a></li>
                            <li><a class="dropdown-item-custom" href="<?php echo app_url('support.php'); ?>"><span class="dropdown-item-icon"><i class="fa-solid fa-headset"></i></span><span class="dropdown-item-text"><?php echo __('nav.support_messages'); ?></span></a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider-custom"></li>
                            <li>
                                <form method="POST" action="<?php echo app_url('logout.php'); ?>" class="dropdown-logout-form">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="dropdown-item-custom danger w-100 text-start border-0 bg-transparent">
                                        <span class="dropdown-item-icon"><i class="fa-solid fa-sign-out-alt"></i></span>
                                        <span class="dropdown-item-text"><?php echo __('nav.logout'); ?></span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="navbar-auth-group">
                        <a href="<?php echo app_url('login.php'); ?>" class="nav-auth-link"><?php echo __('nav.sign_in'); ?></a>
                        <a href="<?php echo app_url('register.php'); ?>" class="btn btn-gradient btn-sm nav-register-btn"><?php echo __('nav.register'); ?></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>
<?php if (!$is_admin_dir): ?>
<div class="mobile-nav-backdrop" id="mobile-nav-backdrop" aria-hidden="true"></div>
<?php endif; ?>

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
            <?php $announcement_href = safe_http_href($site_announcement['link_url']); ?>
            <?php if ($announcement_href != ''): ?>
            <a href="<?php echo htmlspecialchars($announcement_href); ?>" class="site-announcement-link"><?php echo __('common.learn_more'); ?></a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container py-5">
