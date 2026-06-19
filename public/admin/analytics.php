<?php
require_once __DIR__ . '/auth.php';
require_once APP_PATH . '/Core/analytics.php';
require_once APP_PATH . '/Core/backup.php';
requireAdmin();

$days = 30;
if (isset($_GET['days'])) {
    $days = analytics_days_allowed($_GET['days']);
}

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

if ($export == 'reports') {
    $status = 'all';
    if (isset($_GET['status'])) {
        $status = trim($_GET['status']);
    }
    analytics_export_reports($pdo, $status);
}

if ($export == 'messages') {
    analytics_export_messages($pdo);
}

if ($export == 'comments') {
    analytics_export_comments($pdo);
}

if ($export == 'activity') {
    analytics_export_activity($pdo);
}

$stats = analytics_overview($pdo, $days);
$daily_series = analytics_daily_series($pdo, $days);
$top_posts = $pdo->query("SELECT title, views, likes FROM posts WHERE status = 'Published' ORDER BY views DESC LIMIT 8")->fetchAll();
$top_authors = analytics_top_authors($pdo, 8);
$top_categories = analytics_top_categories($pdo, 8);

$chart_labels = array();
$chart_users = array();
$chart_posts = array();
$chart_comments = array();
$chart_likes = array();
foreach ($daily_series as $row) {
    $chart_labels[] = date('M j', strtotime($row['day']));
    $chart_users[] = (int) $row['users'];
    $chart_posts[] = (int) $row['posts'];
    $chart_comments[] = (int) $row['comments'];
    $chart_likes[] = (int) $row['likes'];
}

$author_labels = array();
$author_views = array();
foreach ($top_authors as $a) {
    $author_labels[] = excerpt($a['name'], 18);
    $author_views[] = (int) $a['views'];
}

$cat_labels = array();
$cat_posts = array();
foreach ($top_categories as $c) {
    $cat_labels[] = excerpt($c['name'], 16);
    $cat_posts[] = (int) $c['posts'];
}

$page_title = 'Analytics';
$admin_active = 'analytics';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h3 class="text-white mb-0"><i class="fa-solid fa-chart-line text-warning me-2"></i>Advanced Analytics</h3>
    <div class="d-flex gap-2">
        <?php foreach (array(7, 14, 30, 90) as $d): ?>
        <a href="analytics.php?days=<?php echo $d; ?>" class="btn btn-sm <?php echo $days == $d ? 'btn-gradient' : 'btn-outline-custom'; ?>"><?php echo $d; ?>d</a>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = array(
        array('Total Users', $stats['users'], '+' . $stats['users_new'] . ' new', 'fa-users', 'text-info'),
        array('Total Posts', $stats['posts'], '+' . $stats['posts_new'] . ' new', 'fa-images', 'text-warning'),
        array('Published', $stats['published'], $stats['pending'] . ' pending', 'fa-check', 'text-success'),
        array('Engagement', $stats['engagement_rate'] . '%', number_format($stats['likes']) . ' likes', 'fa-heart', 'text-danger'),
        array('Comments', $stats['comments'], '+' . $stats['comments_new'] . ' new', 'fa-comments', 'text-info'),
        array('Bookmarks', $stats['bookmarks'], number_format($stats['views']) . ' views', 'fa-bookmark', 'text-warning'),
        array('Follows', $stats['followers'], $stats['reports_open'] . ' open reports', 'fa-user-plus', 'text-success'),
        array('New Messages', $stats['messages_new'], 'in last ' . $days . ' days', 'fa-envelope', 'text-danger'),
    );
    foreach ($cards as $card):
    ?>
    <div class="col-6 col-md-3">
        <div class="glass-panel-sm p-3 text-center h-100">
            <i class="fa-solid <?php echo $card[3]; ?> <?php echo $card[4]; ?> mb-2"></i>
            <div class="stat-num"><?php echo $card[1]; ?></div>
            <div class="stat-label"><?php echo $card[0]; ?></div>
            <div class="small text-secondary mt-1"><?php echo $card[2]; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="glass-panel dash-chart-panel mb-4 reveal">
    <h5 class="dash-chart-title"><i class="fa-solid fa-chart-area text-warning me-2"></i>Activity (Last <?php echo (int) $days; ?> Days)</h5>
    <div class="chart-wrap chart-wrap-wide"><canvas id="chartActivity"></canvas></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="glass-panel dash-chart-panel h-100 reveal">
            <h5 class="dash-chart-title"><i class="fa-solid fa-user-pen text-warning me-2"></i>Top Authors by Views</h5>
            <div class="chart-wrap chart-wrap-wide"><canvas id="chartAuthors"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-panel dash-chart-panel h-100 reveal">
            <h5 class="dash-chart-title"><i class="fa-solid fa-tags text-warning me-2"></i>Posts by Category</h5>
            <div class="chart-wrap chart-wrap-wide"><canvas id="chartCategories"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="glass-panel p-4">
            <h4 class="text-white mb-3">Top Posts</h4>
            <?php if (count($top_posts) == 0): ?>
            <p class="text-secondary mb-0">No published posts yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead><tr><th>Title</th><th>Views</th><th>Likes</th></tr></thead>
                    <tbody>
                    <?php foreach ($top_posts as $tp): ?>
                    <tr>
                        <td class="table-cell-title"><?php echo htmlspecialchars(excerpt($tp['title'], 50)); ?></td>
                        <td><?php echo (int) $tp['views']; ?></td>
                        <td><?php echo (int) $tp['likes']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="glass-panel p-4">
            <h4 class="text-white mb-3">Export Data</h4>
            <div class="d-grid gap-2">
                <a href="analytics.php?export=posts" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-csv"></i> Posts (CSV)</a>
                <a href="analytics.php?export=users" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-csv"></i> Users (CSV)</a>
                <a href="analytics.php?export=reports" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-csv"></i> Content Reports (CSV)</a>
                <a href="analytics.php?export=messages" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-csv"></i> Contact Messages (CSV)</a>
                <a href="analytics.php?export=comments" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-csv"></i> Comments (CSV)</a>
                <a href="analytics.php?export=activity" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-file-csv"></i> Activity Logs (CSV)</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var textColor = 'rgba(255,255,255,0.85)';
    var gridColor = 'rgba(255,255,255,0.08)';
    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    new Chart(document.getElementById('chartActivity'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                { label: 'Users', data: <?php echo json_encode($chart_users); ?>, borderColor: '#60a5fa', tension: 0.3, fill: false },
                { label: 'Posts', data: <?php echo json_encode($chart_posts); ?>, borderColor: '#fbbf24', tension: 0.3, fill: false },
                { label: 'Comments', data: <?php echo json_encode($chart_comments); ?>, borderColor: '#34d399', tension: 0.3, fill: false },
                { label: 'Likes', data: <?php echo json_encode($chart_likes); ?>, borderColor: '#f87171', tension: 0.3, fill: false }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('chartAuthors'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($author_labels); ?>,
            datasets: [{ label: 'Views', data: <?php echo json_encode($author_views); ?>, backgroundColor: 'rgba(251,191,36,0.7)' }]
        },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('chartCategories'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{ data: <?php echo json_encode($cat_posts); ?>, backgroundColor: ['#fbbf24','#60a5fa','#34d399','#f87171','#a78bfa','#fb923c','#2dd4bf','#e879f9'] }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
})();
</script>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
