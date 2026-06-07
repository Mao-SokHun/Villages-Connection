<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$export = '';
if (isset($_GET['export'])) {
    $export = trim($_GET['export']);
}

if ($export == 'posts') {
    $rows = $pdo->query("SELECT p.id, p.title, p.status, p.views, p.likes, c.name as category, u.name as author, p.created_at FROM posts p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN users u ON u.id = p.user_id ORDER BY p.id DESC")->fetchAll();
    $data = array();
    foreach ($rows as $r) {
        $data[] = array($r['id'], $r['title'], $r['status'], $r['category'], $r['author'], $r['views'], $r['likes'], $r['created_at']);
    }
    admin_export_csv('posts-export.csv', array('ID', 'Title', 'Status', 'Category', 'Author', 'Views', 'Likes', 'Created'), $data);
}

if ($export == 'users') {
    $rows = $pdo->query("SELECT u.id, u.name, u.email, u.role, u.is_banned, COUNT(p.id) as posts, u.created_at FROM users u LEFT JOIN posts p ON p.user_id = u.id GROUP BY u.id ORDER BY u.id DESC")->fetchAll();
    $data = array();
    foreach ($rows as $r) {
        $data[] = array($r['id'], $r['name'], $r['email'], $r['role'], $r['is_banned'] ? 'yes' : 'no', $r['posts'], $r['created_at']);
    }
    admin_export_csv('users-export.csv', array('ID', 'Name', 'Email', 'Role', 'Banned', 'Posts', 'Joined'), $data);
}

$stats = array(
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'posts' => (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'published' => (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'Published'")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'Pending'")->fetchColumn(),
    'views' => (int) $pdo->query('SELECT COALESCE(SUM(views),0) FROM posts')->fetchColumn(),
    'likes' => (int) $pdo->query('SELECT COALESCE(SUM(likes),0) FROM posts')->fetchColumn(),
    'reports' => (int) $pdo->query("SELECT COUNT(*) FROM content_reports WHERE status = 'open'")->fetchColumn(),
    'messages' => (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
    'comments' => (int) $pdo->query("SELECT COUNT(*) FROM post_comments WHERE status = 'pending'")->fetchColumn()
);

$daily_posts = $pdo->query("SELECT DATE(created_at) as day, COUNT(*)::int as total FROM posts WHERE created_at >= (CURRENT_DATE - INTERVAL '14 days') GROUP BY day ORDER BY day ASC")->fetchAll();
$top_posts = $pdo->query("SELECT title, views, likes FROM posts WHERE status = 'Published' ORDER BY views DESC LIMIT 8")->fetchAll();

$page_title = 'Analytics';
$admin_active = 'analytics';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="row g-3 mb-4">
    <?php
    $cards = array(
        array('Users', $stats['users'], 'fa-users', 'text-info'),
        array('Posts', $stats['posts'], 'fa-images', 'text-warning'),
        array('Published', $stats['published'], 'fa-check', 'text-success'),
        array('Pending', $stats['pending'], 'fa-hourglass', 'text-warning'),
        array('Views', number_format($stats['views']), 'fa-eye', 'text-info'),
        array('Likes', number_format($stats['likes']), 'fa-heart', 'text-danger'),
        array('Open Reports', $stats['reports'], 'fa-flag', 'text-danger'),
        array('New Messages', $stats['messages'], 'fa-envelope', 'text-warning')
    );
    foreach ($cards as $card):
    ?>
    <div class="col-6 col-md-3">
        <div class="glass-panel-sm p-3 text-center">
            <i class="fa-solid <?php echo $card[2]; ?> <?php echo $card[3]; ?> mb-2"></i>
            <div class="stat-num"><?php echo $card[1]; ?></div>
            <div class="stat-label"><?php echo $card[0]; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="glass-panel p-4">
            <h4 class="text-white mb-3">Posts Created (Last 14 Days)</h4>
            <?php if (count($daily_posts) == 0): ?>
            <p class="text-secondary mb-0">No recent data.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead><tr><th>Date</th><th>Posts</th></tr></thead>
                    <tbody>
                    <?php foreach ($daily_posts as $d): ?>
                    <tr><td><?php echo date('M j, Y', strtotime($d['day'])); ?></td><td><?php echo (int) $d['total']; ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="glass-panel p-4 mb-4">
            <h4 class="text-white mb-3">Top Posts</h4>
            <?php foreach ($top_posts as $tp): ?>
            <div class="d-flex justify-content-between gap-2 mb-2 small">
                <span class="text-secondary text-truncate"><?php echo htmlspecialchars(excerpt($tp['title'], 40)); ?></span>
                <span class="text-white"><?php echo (int) $tp['views']; ?> views</span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="glass-panel p-4">
            <h4 class="text-white mb-3">Export Data</h4>
            <div class="d-grid gap-2">
                <a href="analytics.php?export=posts" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-csv"></i> Export Posts (CSV)</a>
                <a href="analytics.php?export=users" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-csv"></i> Export Users (CSV)</a>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
