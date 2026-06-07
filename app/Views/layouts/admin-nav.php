<?php
$admin_page = '';
if (isset($admin_active)) {
    $admin_page = $admin_active;
}

$admin_counts = array('messages' => 0, 'reports' => 0, 'pending_posts' => 0, 'pending_comments' => 0, 'notifications' => 0);
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
            ?>
            <?php echo render_user_avatar($_SESSION['user_name'], $toolbar_avatar, 'dash-toolbar-avatar'); ?>
            <div>
                <h4 class="dash-toolbar-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
                <p class="dash-toolbar-sub">Manage your posts and profile</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="dash-toolbar-actions">
            <?php if (isAdmin()): ?>
            <a href="posts.php?action=add" class="btn btn-gradient btn-sm dash-toolbar-quick"><i class="fa-solid fa-plus"></i> New Post</a>
            <?php elseif (!isAdmin()): ?>
            <a href="posts.php?action=add" class="btn btn-gradient btn-sm dash-toolbar-quick"><i class="fa-solid fa-plus"></i> New Post</a>
            <?php endif; ?>
            <a href="../index.php" class="dash-toolbar-site">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>View Site</span>
            </a>
        </div>
    </div>
    <div class="dash-toolbar-nav-wrap" id="dashToolbarNavWrap">
        <nav class="dash-toolbar-nav" id="dashToolbarNav">
        <a href="dashboard.php" class="dash-toolbar-tab <?php if ($admin_page == 'dashboard') echo 'active'; ?>">
            <i class="fa-solid fa-chart-pie"></i><span>Dashboard</span>
        </a>
        <a href="posts.php" class="dash-toolbar-tab <?php if ($admin_page == 'posts' && (!isset($_GET['action']) || $_GET['action'] != 'add')) echo 'active'; ?>">
            <i class="fa-solid fa-square-pen"></i><span><?php echo isAdmin() ? 'Posts' : 'My Posts'; ?></span>
            <?php
            $posts_badge = isAdmin() ? $admin_counts['pending_posts'] : $author_counts['pending_posts'];
            if ($posts_badge > 0):
            ?><span class="dash-badge"><?php echo $posts_badge; ?></span><?php endif; ?>
        </a>
        <?php if (isAdmin()): ?>
        <a href="posts.php?action=add" class="dash-toolbar-tab <?php if ($admin_page == 'posts' && isset($_GET['action']) && $_GET['action'] == 'add') echo 'active'; ?>">
            <i class="fa-solid fa-plus"></i><span>New Post</span>
        </a>
        <?php endif; ?>
        <?php if (!isAdmin()): ?>
        <a href="posts.php?action=add" class="dash-toolbar-tab <?php if ($admin_page == 'posts' && isset($_GET['action']) && $_GET['action'] == 'add') echo 'active'; ?>">
            <i class="fa-solid fa-plus"></i><span>New Post</span>
        </a>
        <a href="my-comments.php" class="dash-toolbar-tab <?php if ($admin_page == 'my-comments') echo 'active'; ?>">
            <i class="fa-solid fa-comments"></i><span>Comments</span>
            <?php if ($author_counts['pending_comments'] > 0): ?><span class="dash-badge"><?php echo $author_counts['pending_comments']; ?></span><?php endif; ?>
        </a>
        <a href="my-media.php" class="dash-toolbar-tab <?php if ($admin_page == 'my-media') echo 'active'; ?>">
            <i class="fa-solid fa-photo-film"></i><span>My Media</span>
        </a>
        <a href="../profile.php" class="dash-toolbar-tab">
            <i class="fa-solid fa-user"></i><span>Profile</span>
        </a>
        <a href="../edit-profile.php" class="dash-toolbar-tab">
            <i class="fa-solid fa-user-pen"></i><span>Edit Profile</span>
        </a>
        <a href="../notifications.php" class="dash-toolbar-tab">
            <i class="fa-solid fa-bell"></i><span>Notifications</span>
            <?php if ($author_counts['notifications'] > 0): ?><span class="dash-badge"><?php echo $author_counts['notifications']; ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
        <a href="comments.php" class="dash-toolbar-tab <?php if ($admin_page == 'comments') echo 'active'; ?>">
            <i class="fa-solid fa-comments"></i><span>Comments</span>
            <?php if ($admin_counts['pending_comments'] > 0): ?><span class="dash-badge"><?php echo $admin_counts['pending_comments']; ?></span><?php endif; ?>
        </a>
        <a href="reports.php" class="dash-toolbar-tab <?php if ($admin_page == 'reports') echo 'active'; ?>">
            <i class="fa-solid fa-flag"></i><span>Reports</span>
            <?php if ($admin_counts['reports'] > 0): ?><span class="dash-badge"><?php echo $admin_counts['reports']; ?></span><?php endif; ?>
        </a>
        <a href="messages.php" class="dash-toolbar-tab <?php if ($admin_page == 'messages') echo 'active'; ?>">
            <i class="fa-solid fa-envelope"></i><span>Messages</span>
            <?php if ($admin_counts['messages'] > 0): ?><span class="dash-badge"><?php echo $admin_counts['messages']; ?></span><?php endif; ?>
        </a>
        <a href="users.php" class="dash-toolbar-tab <?php if ($admin_page == 'users') echo 'active'; ?>">
            <i class="fa-solid fa-users"></i><span>Users</span>
        </a>
        <a href="categories.php" class="dash-toolbar-tab <?php if ($admin_page == 'categories') echo 'active'; ?>">
            <i class="fa-solid fa-tags"></i><span>Categories</span>
        </a>
        <a href="media.php" class="dash-toolbar-tab <?php if ($admin_page == 'media') echo 'active'; ?>">
            <i class="fa-solid fa-photo-film"></i><span>Media</span>
        </a>
        <a href="announcements.php" class="dash-toolbar-tab <?php if ($admin_page == 'announcements') echo 'active'; ?>">
            <i class="fa-solid fa-bullhorn"></i><span>Announce</span>
        </a>
        <a href="analytics.php" class="dash-toolbar-tab <?php if ($admin_page == 'analytics') echo 'active'; ?>">
            <i class="fa-solid fa-chart-line"></i><span>Analytics</span>
        </a>
        <a href="settings.php" class="dash-toolbar-tab <?php if ($admin_page == 'settings') echo 'active'; ?>">
            <i class="fa-solid fa-gear"></i><span>Settings</span>
        </a>
        <a href="activity.php" class="dash-toolbar-tab <?php if ($admin_page == 'activity') echo 'active'; ?>">
            <i class="fa-solid fa-clock-rotate-left"></i><span>Activity</span>
        </a>
        <a href="../edit-profile.php" class="dash-toolbar-tab">
            <i class="fa-solid fa-user-pen"></i><span>Edit Profile</span>
        </a>
        <a href="../notifications.php" class="dash-toolbar-tab">
            <i class="fa-solid fa-bell"></i><span>Notifications</span>
            <?php if ($admin_counts['notifications'] > 0): ?><span class="dash-badge"><?php echo $admin_counts['notifications']; ?></span><?php endif; ?>
        </a>
        <?php endif; ?>
        </nav>
    </div>
</div>
