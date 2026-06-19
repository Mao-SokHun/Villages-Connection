<?php
$admin_page = '';
if (isset($admin_active)) {
    $admin_page = $admin_active;
}

$admin_counts = array('messages' => 0, 'reports' => 0, 'incidents' => 0, 'pending_posts' => 0, 'pending_comments' => 0, 'notifications' => 0);
$author_counts = array('pending_posts' => 0, 'pending_comments' => 0, 'notifications' => 0);
if (isAdmin() && isset($pdo)) {
    $admin_counts = admin_unread_counts($pdo);
} elseif (isLoggedIn() && isset($pdo)) {
    $author_counts = author_unread_counts($pdo, (int) $_SESSION['user_id']);
}
?>
<div class="dash-toolbar glass-panel mb-4 reveal">
    <div class="dash-toolbar-top">
        <div class="dash-toolbar-brand">
            <?php if (isAdmin()): ?>
            <span class="dash-toolbar-brand-icon"><i class="fa-solid fa-gauge-high"></i></span>
            <div>
                <h4 class="dash-toolbar-title">Admin Panel</h4>
                <p class="dash-toolbar-sub">Manage content, users, reports, and site settings</p>
            </div>
            <?php else:
                $toolbar_avatar = '';
                if (isset($_SESSION['user_avatar'])) {
                    $toolbar_avatar = $_SESSION['user_avatar'];
                }
                $toolbar_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
            ?>
            <?php echo render_user_avatar($_SESSION['user_name'], $toolbar_avatar, 'dash-toolbar-avatar', $toolbar_email); ?>
            <div>
                <h4 class="dash-toolbar-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
                <p class="dash-toolbar-sub">Manage your posts and profile</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="dash-toolbar-actions">
            <a href="<?php echo admin_area_url('posts.php?action=add'); ?>" class="btn btn-gradient btn-sm dash-toolbar-quick"><i class="fa-solid fa-plus"></i> New Post</a>
            <a href="<?php echo app_url('index.php'); ?>" class="dash-toolbar-site">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>View Site</span>
            </a>
        </div>
    </div>
    <div class="dash-toolbar-nav-wrap" id="dashToolbarNavWrap">
        <nav class="dash-toolbar-nav" id="dashToolbarNav">
        <a href="<?php echo admin_area_url('dashboard.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'dashboard') echo 'active'; ?>">
            <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
        </a>
        <a href="<?php echo admin_area_url('posts.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'posts' && (!isset($_GET['action']) || $_GET['action'] != 'add')) echo 'active'; ?>" data-admin-tab="posts">
            <i class="fa-solid fa-square-pen"></i><span><?php echo isAdmin() ? 'Posts' : 'My Posts'; ?></span>
            <?php $posts_badge = isAdmin() ? $admin_counts['pending_posts'] : $author_counts['pending_posts']; ?>
            <span class="dash-badge" data-admin-badge="pending_posts"<?php if ($posts_badge <= 0) echo ' hidden'; ?>><?php echo (int) $posts_badge; ?></span>
        </a>
        <?php if (!isAdmin()): ?>
        <a href="<?php echo admin_area_url('my-comments.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'my-comments') echo 'active'; ?>" data-admin-tab="comments">
            <i class="fa-solid fa-comments"></i><span>Comments</span>
            <span class="dash-badge" data-admin-badge="pending_comments"<?php if ($author_counts['pending_comments'] <= 0) echo ' hidden'; ?>><?php echo (int) $author_counts['pending_comments']; ?></span>
        </a>
        <a href="<?php echo admin_area_url('my-media.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'my-media') echo 'active'; ?>">
            <i class="fa-solid fa-photo-film"></i><span>My Media</span>
        </a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
        <a href="<?php echo admin_area_url('comments.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'comments') echo 'active'; ?>" data-admin-tab="comments">
            <i class="fa-solid fa-comments"></i><span>Comments</span>
            <span class="dash-badge" data-admin-badge="pending_comments"<?php if ($admin_counts['pending_comments'] <= 0) echo ' hidden'; ?>><?php echo (int) $admin_counts['pending_comments']; ?></span>
        </a>
        <a href="<?php echo admin_area_url('reports.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'reports') echo 'active'; ?>" data-admin-tab="reports">
            <i class="fa-solid fa-flag"></i><span>Reports</span>
            <span class="dash-badge" data-admin-badge="reports"<?php if ($admin_counts['reports'] <= 0) echo ' hidden'; ?>><?php echo (int) $admin_counts['reports']; ?></span>
        </a>
        <a href="<?php echo admin_area_url('incidents.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'incidents') echo 'active'; ?>" data-admin-tab="incidents">
            <i class="fa-solid fa-triangle-exclamation"></i><span>Incidents</span>
            <span class="dash-badge" data-admin-badge="incidents"<?php if ($admin_counts['incidents'] <= 0) echo ' hidden'; ?>><?php echo (int) $admin_counts['incidents']; ?></span>
        </a>
        <a href="<?php echo admin_area_url('challenges.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'challenges') echo 'active'; ?>">
            <i class="fa-solid fa-trophy"></i><span>Challenges</span>
        </a>
        <a href="<?php echo admin_area_url('messages.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'messages') echo 'active'; ?><?php if ($admin_counts['messages'] > 0) echo ' has-unread'; ?>" data-admin-tab="messages">
            <i class="fa-solid fa-envelope"></i><span>Messages</span>
            <span class="dash-badge" data-admin-badge="messages"<?php if ($admin_counts['messages'] <= 0) echo ' hidden'; ?>><?php echo (int) $admin_counts['messages']; ?></span>
        </a>
        <a href="<?php echo admin_area_url('users.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'users') echo 'active'; ?>">
            <i class="fa-solid fa-users"></i><span>Users</span>
        </a>
        <a href="<?php echo admin_area_url('categories.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'categories') echo 'active'; ?>">
            <i class="fa-solid fa-tags"></i><span>Categories</span>
        </a>
        <a href="<?php echo admin_area_url('media.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'media') echo 'active'; ?>">
            <i class="fa-solid fa-photo-film"></i><span>Media</span>
        </a>
        <a href="<?php echo admin_area_url('announcements.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'announcements') echo 'active'; ?>">
            <i class="fa-solid fa-bullhorn"></i><span>Announce</span>
        </a>
        <a href="<?php echo admin_area_url('analytics.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'analytics') echo 'active'; ?>">
            <i class="fa-solid fa-chart-line"></i><span>Analytics</span>
        </a>
        <a href="<?php echo admin_area_url('settings.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'settings') echo 'active'; ?>">
            <i class="fa-solid fa-gear"></i><span>Settings</span>
        </a>
        <a href="<?php echo admin_area_url('activity.php'); ?>" class="dash-toolbar-tab <?php if ($admin_page == 'activity') echo 'active'; ?>">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Activity</span>
        </a>
        <?php endif; ?>
        </nav>
    </div>
</div>
