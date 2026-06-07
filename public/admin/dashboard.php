<?php
require_once __DIR__ . '/auth.php';
$page_title = 'Dashboard';
$admin_active = 'dashboard';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';

$is_author_view = false;
if (!isAdmin()) {
    $is_author_view = true;
}

try {
    if ($is_author_view) {
        $author_id = (int) $_SESSION['user_id'];

        $sql = 'SELECT COUNT(*) FROM posts WHERE user_id = :uid';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $total_posts = (int) $stmt->fetchColumn();

        $sql = "SELECT COUNT(*) FROM posts WHERE user_id = :uid AND status = 'Published'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $published_posts = (int) $stmt->fetchColumn();

        $sql = "SELECT COUNT(*) FROM posts WHERE user_id = :uid AND status = 'Draft'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $draft_posts = (int) $stmt->fetchColumn();

        $sql = 'SELECT COALESCE(SUM(views), 0) FROM posts WHERE user_id = :uid';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $total_views = (int) $stmt->fetchColumn();

        $sql = 'SELECT COALESCE(SUM(likes), 0) FROM posts WHERE user_id = :uid';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $total_likes = (int) $stmt->fetchColumn();

        $sql = 'SELECT COUNT(*) FROM posts WHERE user_id = :uid AND is_featured = TRUE';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $featured_posts = (int) $stmt->fetchColumn();

        $sql = "SELECT p.*, c.name as category_name
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.user_id = :uid
                ORDER BY p.created_at DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $recent_articles = $stmt->fetchAll();

        $sql = "SELECT p.id, c.name as category_name, p.title, p.views, p.likes, p.slug
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.user_id = :uid AND p.status = 'Published'
                ORDER BY p.views DESC
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $popular_articles = $stmt->fetchAll();

        $sql = "SELECT title, views, likes FROM posts
                WHERE user_id = :uid AND status = 'Published'
                ORDER BY views DESC LIMIT 8";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $chart_posts = $stmt->fetchAll();

        $sql = "SELECT DATE_TRUNC('month', created_at) as month_key, COUNT(*)::int as total
                FROM posts WHERE user_id = :uid
                AND created_at >= (CURRENT_DATE - INTERVAL '5 months')
                GROUP BY month_key ORDER BY month_key ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $month_rows = $stmt->fetchAll();

        $sql = "SELECT COALESCE(c.name, 'Uncategorized') as cat_name, COUNT(*)::int as total
                FROM posts p LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.user_id = :uid
                GROUP BY COALESCE(c.name, 'Uncategorized') ORDER BY total DESC LIMIT 6";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $author_id));
        $category_rows = $stmt->fetchAll();

        $author_followers = follower_count($pdo, $author_id);
        $author_following = following_count($pdo, $author_id);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :uid AND status = 'Pending'");
        $stmt->execute(array('uid' => $author_id));
        $author_pending_posts = (int) $stmt->fetchColumn();
    } else {
        $total_posts = (int) $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        $published_posts = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'Published'")->fetchColumn();
        $draft_posts = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'Draft'")->fetchColumn();
        $total_views = (int) $pdo->query("SELECT COALESCE(SUM(views), 0) FROM posts")->fetchColumn();
        $authors_count = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_likes = (int) $pdo->query('SELECT COALESCE(SUM(likes), 0) FROM posts')->fetchColumn();
        $featured_posts = (int) $pdo->query('SELECT COUNT(*) FROM posts WHERE is_featured = TRUE')->fetchColumn();
        $admin_queue = admin_unread_counts($pdo);

        $sql = "SELECT p.*, c.name as category_name, u.name as author_name
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC
                LIMIT 5";
        $recent_articles = $pdo->query($sql)->fetchAll();

        $sql = "SELECT p.id, c.name as category_name, p.title, p.views, p.likes, p.slug, u.name as author_name
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON u.id = p.user_id
                WHERE p.status = 'Published'
                ORDER BY p.views DESC
                LIMIT 5";
        $popular_articles = $pdo->query($sql)->fetchAll();

        $chart_posts = $pdo->query("SELECT title, views, likes FROM posts WHERE status = 'Published' ORDER BY views DESC LIMIT 8")->fetchAll();

        $month_rows = $pdo->query("SELECT DATE_TRUNC('month', created_at) as month_key, COUNT(*)::int as total
            FROM posts WHERE created_at >= (CURRENT_DATE - INTERVAL '5 months')
            GROUP BY month_key ORDER BY month_key ASC")->fetchAll();

        $category_rows = $pdo->query("SELECT COALESCE(c.name, 'Uncategorized') as cat_name, COUNT(*)::int as total
            FROM posts p LEFT JOIN categories c ON p.category_id = c.id
            GROUP BY COALESCE(c.name, 'Uncategorized') ORDER BY total DESC LIMIT 6")->fetchAll();
    }
} catch (PDOException $e) {
    die('Database query error: ' . $e->getMessage());
}

$avg_views = 0;
if ($total_posts > 0) {
    $avg_views = number_format($total_views / $total_posts, 1);
}

$chart_month_labels = array();
$chart_month_values = array();
for ($i = 5; $i >= 0; $i--) {
    $chart_month_labels[] = date('M', strtotime('-' . $i . ' months'));
    $chart_month_values[] = 0;
}
if (isset($month_rows)) {
    foreach ($month_rows as $mr) {
        $key = date('M', strtotime($mr['month_key']));
        for ($j = 0; $j < count($chart_month_labels); $j++) {
            if ($chart_month_labels[$j] == $key) {
                $chart_month_values[$j] = (int) $mr['total'];
            }
        }
    }
}

$chart_post_labels = array();
$chart_post_views = array();
$chart_post_likes = array();
if (isset($chart_posts)) {
    foreach ($chart_posts as $cp) {
        $chart_post_labels[] = excerpt($cp['title'], 22);
        $chart_post_views[] = (int) $cp['views'];
        $chart_post_likes[] = (int) $cp['likes'];
    }
}

$chart_cat_labels = array();
$chart_cat_values = array();
if (isset($category_rows)) {
    foreach ($category_rows as $cr) {
        $chart_cat_labels[] = $cr['cat_name'];
        $chart_cat_values[] = (int) $cr['total'];
    }
}
?>

<?php if ($is_author_view): ?>

<div class="glass-panel dash-stats-bar mb-4 reveal">
    <div class="dash-stat-item">
        <span class="dash-stat-label"><i class="fa-solid fa-images"></i> Posts</span>
        <span class="dash-stat-value"><?php echo $total_posts; ?></span>
        <span class="dash-stat-sub"><?php echo $published_posts; ?> published · <?php echo $draft_posts; ?> draft<?php if ($author_pending_posts > 0): ?> · <?php echo $author_pending_posts; ?> pending<?php endif; ?></span>
    </div>
    <div class="dash-stat-item">
        <span class="dash-stat-label"><i class="fa-solid fa-eye"></i> Total Views</span>
        <span class="dash-stat-value"><?php echo number_format($total_views); ?></span>
        <span class="dash-stat-sub">Across all your posts</span>
    </div>
    <div class="dash-stat-item">
        <span class="dash-stat-label"><i class="fa-solid fa-heart"></i> Total Likes</span>
        <span class="dash-stat-value"><?php echo number_format($total_likes); ?></span>
        <span class="dash-stat-sub">Reader reactions</span>
    </div>
    <div class="dash-stat-item">
        <span class="dash-stat-label"><i class="fa-solid fa-user-group"></i> Followers</span>
        <span class="dash-stat-value"><?php echo number_format($author_followers); ?></span>
        <span class="dash-stat-sub"><?php echo $author_following; ?> following · <?php echo $featured_posts; ?> featured</span>
    </div>
</div>
<?php else: ?>
<div class="glass-panel dash-stats-bar mb-4 reveal">
    <div class="dash-stat-item">
        <span class="dash-stat-label"><i class="fa-solid fa-images"></i> Posts</span>
        <span class="dash-stat-value"><?php echo $total_posts; ?></span>
        <span class="dash-stat-sub"><?php echo $published_posts; ?> published · <?php echo $draft_posts; ?> draft<?php if (isset($admin_queue) && $admin_queue['pending_posts'] > 0): ?> · <?php echo $admin_queue['pending_posts']; ?> pending<?php endif; ?></span>
    </div>
    <div class="dash-stat-item">
        <span class="dash-stat-label"><i class="fa-solid fa-eye"></i> Site Views</span>
        <span class="dash-stat-value"><?php echo number_format($total_views); ?></span>
        <span class="dash-stat-sub">All published content</span>
    </div>
    <div class="dash-stat-item">
        <span class="dash-stat-label"><i class="fa-solid fa-heart"></i> Total Likes</span>
        <span class="dash-stat-value"><?php echo number_format($total_likes); ?></span>
        <span class="dash-stat-sub">Across all posts</span>
    </div>
    <div class="dash-stat-item">
        <span class="dash-stat-label"><i class="fa-solid fa-users"></i> Members</span>
        <span class="dash-stat-value"><?php echo $authors_count; ?></span>
        <span class="dash-stat-sub"><?php echo $featured_posts; ?> featured posts</span>
    </div>
</div>

<?php if (isset($admin_queue)): ?>
<div class="glass-panel p-4 mb-4 reveal">
    <h4 class="text-white mb-3"><i class="fa-solid fa-list-check text-warning me-2"></i>Moderation Queue</h4>
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <a href="posts.php?status=Pending" class="dash-action-card <?php echo $admin_queue['pending_posts'] > 0 ? 'dash-action-primary' : ''; ?>">
                <span class="dash-action-icon"><i class="fa-solid fa-hourglass-half"></i></span>
                <span class="dash-action-label">Pending Posts</span>
                <span class="dash-action-meta"><?php echo $admin_queue['pending_posts']; ?> waiting</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="comments.php?status=pending" class="dash-action-card <?php echo $admin_queue['pending_comments'] > 0 ? 'dash-action-primary' : ''; ?>">
                <span class="dash-action-icon"><i class="fa-solid fa-comments"></i></span>
                <span class="dash-action-label">Pending Comments</span>
                <span class="dash-action-meta"><?php echo $admin_queue['pending_comments']; ?> waiting</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="reports.php" class="dash-action-card <?php echo $admin_queue['reports'] > 0 ? 'dash-action-primary' : ''; ?>">
                <span class="dash-action-icon"><i class="fa-solid fa-flag"></i></span>
                <span class="dash-action-label">Open Reports</span>
                <span class="dash-action-meta"><?php echo $admin_queue['reports']; ?> open</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="messages.php" class="dash-action-card <?php echo $admin_queue['messages'] > 0 ? 'dash-action-primary' : ''; ?>">
                <span class="dash-action-icon"><i class="fa-solid fa-envelope"></i></span>
                <span class="dash-action-label">New Messages</span>
                <span class="dash-action-meta"><?php echo $admin_queue['messages']; ?> unread</span>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="glass-panel dash-chart-panel mb-4 reveal">
    <h5 class="dash-chart-title"><i class="fa-solid fa-chart-line text-warning me-2"></i>Posts Over Time</h5>
    <p class="text-secondary small mb-3"><?php if ($is_author_view): ?>How many posts you created each month<?php else: ?>Site-wide post creation trend<?php endif; ?></p>
    <div class="chart-wrap chart-wrap-wide"><canvas id="chartPostsMonth"></canvas></div>
</div>

<div class="glass-panel dash-chart-panel mb-4 reveal">
    <h5 class="dash-chart-title"><i class="fa-solid fa-chart-column text-warning me-2"></i>Views &amp; Likes by Post</h5>
    <p class="text-secondary small mb-3"><?php if ($is_author_view): ?>Engagement on your top published posts<?php else: ?>Top published posts by engagement<?php endif; ?></p>
    <div class="chart-wrap chart-wrap-wide chart-wrap-tall"><canvas id="chartEngagement"></canvas></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="glass-panel dash-chart-panel h-100 reveal">
            <h5 class="dash-chart-title"><i class="fa-solid fa-chart-pie text-warning me-2"></i>Post Status</h5>
            <p class="text-secondary small mb-3">Published vs draft breakdown</p>
            <div class="chart-wrap chart-wrap-donut position-relative">
                <canvas id="chartPostStatus"></canvas>
                <?php if ($published_posts == 0 && $draft_posts == 0): ?>
                <div class="chart-empty-hint">No posts yet — create your first post to see this chart.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="glass-panel dash-chart-panel h-100 reveal">
            <h5 class="dash-chart-title"><i class="fa-solid fa-tags text-warning me-2"></i>Posts by Category</h5>
            <p class="text-secondary small mb-3"><?php if ($is_author_view): ?>Topics you write about most<?php else: ?>Content distribution across categories<?php endif; ?></p>
            <div class="chart-wrap chart-wrap-donut position-relative">
                <canvas id="chartCategories"></canvas>
                <?php if (count($chart_cat_labels) == 0): ?>
                <div class="chart-empty-hint">No category data yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (isset($popular_articles) && count($popular_articles) > 0): ?>
<div class="glass-panel p-4 mb-4 reveal">
    <h4 class="text-white mb-4"><i class="fa-solid fa-fire text-warning me-2"></i>Top Performing Posts</h4>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Post</th>
                    <?php if (!$is_author_view): ?><th>Author</th><?php endif; ?>
                    <th>Category</th>
                    <th class="text-end">Views</th>
                    <th class="text-end">Likes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($popular_articles as $pop): ?>
                <tr>
                    <td class="table-cell-title"><?php echo htmlspecialchars($pop['title']); ?></td>
                    <?php if (!$is_author_view): ?>
                    <td class="table-cell-muted small"><?php
                        if (isset($pop['author_name']) && $pop['author_name'] != '') {
                            echo htmlspecialchars($pop['author_name']);
                        } else {
                            echo '—';
                        }
                    ?></td>
                    <?php endif; ?>
                    <td class="table-cell-muted small"><?php
                        if (isset($pop['category_name']) && $pop['category_name'] != '') {
                            echo htmlspecialchars($pop['category_name']);
                        } else {
                            echo 'General';
                        }
                    ?></td>
                    <td class="text-end table-cell-strong"><?php echo (int) $pop['views']; ?></td>
                    <td class="text-end table-cell-strong"><?php echo (int) $pop['likes']; ?></td>
                    <td class="text-end text-nowrap">
                        <a href="../post.php?slug=<?php echo urlencode($pop['slug']); ?>" class="btn btn-sm btn-outline-custom" target="_blank" title="View"><i class="fa-solid fa-eye"></i></a>
                        <a href="posts.php?action=edit&id=<?php echo (int) $pop['id']; ?>" class="btn btn-sm btn-outline-custom text-info" title="Edit"><i class="fa-solid fa-edit"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <div class="col-lg-12">
        <div class="glass-panel p-4 h-100">
            <h4 class="text-white mb-4">
                <i class="fa-solid fa-list-check text-indigo me-2"></i>
                <?php if ($is_author_view): ?>My Recent Posts<?php else: ?>Recently Edited Posts<?php endif; ?>
            </h4>

            <?php if (count($recent_articles) == 0): ?>
            <div class="text-center py-5 text-secondary">
                <i class="fa-solid fa-folder-open fs-2 mb-3 text-muted"></i>
                <p class="mb-3">No posts yet.</p>
                <?php if ($is_author_view): ?>
                <a href="posts.php?action=add" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> Write Your First Post</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Post Title</th>
                            <?php if (!$is_author_view): ?><th>Author</th><?php endif; ?>
                            <th class="text-center">Status</th>
                            <th class="text-end">Views</th>
                            <?php if ($is_author_view): ?><th class="text-end">Likes</th><?php endif; ?>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_articles as $art): ?>
                        <tr>
                            <td>
                                <div class="table-cell-title"><?php echo htmlspecialchars($art['title']); ?></div>
                                <span class="table-cell-meta"><?php
                                    if (isset($art['category_name']) && $art['category_name'] != '') {
                                        echo htmlspecialchars($art['category_name']);
                                    } else {
                                        echo 'General';
                                    }
                                ?></span>
                            </td>
                            <?php if (!$is_author_view): ?>
                            <td class="table-cell-muted small">
                                <?php
                                if (isset($art['author_name'])) {
                                    echo htmlspecialchars($art['author_name']);
                                } else {
                                    echo 'System';
                                }
                                ?>
                            </td>
                            <?php endif; ?>
                            <td class="text-center">
                                <?php
                                $badge_class = 'bg-secondary';
                                if ($art['status'] == 'Published') {
                                    $badge_class = 'bg-success';
                                } elseif ($art['status'] == 'Draft') {
                                    $badge_class = 'bg-warning text-dark';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $art['status']; ?></span>
                            </td>
                            <td class="text-end table-cell-strong"><?php echo (int) $art['views']; ?></td>
                            <?php if ($is_author_view): ?>
                            <td class="text-end table-cell-strong"><?php echo (int) $art['likes']; ?></td>
                            <?php endif; ?>
                            <td class="text-end">
                                <a href="posts.php?action=edit&id=<?php echo $art['id']; ?>" class="text-indigo p-2" title="Edit"><i class="fa-solid fa-edit"></i></a>
                                <?php if ($art['status'] == 'Published'): ?>
                                <a href="../post.php?slug=<?php echo urlencode($art['slug']); ?>" class="text-indigo p-2" title="View" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var isDark = document.documentElement.getAttribute('data-theme') != 'light';
    var gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(15,23,42,0.08)';
    var textColor = isDark ? '#94a3b8' : '#64748b';
    var fontFamily = "'Inter', sans-serif";

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;
    Chart.defaults.font.family = fontFamily;

    function chartHover(event, elements, chart) {
        if (event.native && event.native.target) {
            event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
        }
        var wrap = chart.canvas.closest('.chart-wrap');
        if (!wrap) {
            return;
        }
        if (elements.length > 0) {
            wrap.classList.add('chart-hovering');
        } else {
            wrap.classList.remove('chart-hovering');
        }
    }

    function chartTooltip() {
        return {
            enabled: true,
            backgroundColor: isDark ? 'rgba(22, 28, 45, 0.96)' : 'rgba(255, 255, 255, 0.98)',
            titleColor: isDark ? '#f8fafc' : '#0f172a',
            bodyColor: isDark ? '#cbd5e1' : '#475569',
            borderColor: 'rgba(129, 140, 248, 0.45)',
            borderWidth: 1,
            padding: 12,
            cornerRadius: 12,
            boxPadding: 6,
            usePointStyle: true,
            animation: {
                duration: 180
            }
        };
    }

    var monthLabels = <?php echo json_encode($chart_month_labels); ?>;
    var monthValues = <?php echo json_encode($chart_month_values); ?>;
    var postLabels = <?php echo json_encode($chart_post_labels); ?>;
    var postViews = <?php echo json_encode($chart_post_views); ?>;
    var postLikes = <?php echo json_encode($chart_post_likes); ?>;
    var catLabels = <?php echo json_encode($chart_cat_labels); ?>;
    var catValues = <?php echo json_encode($chart_cat_values); ?>;
    var publishedCount = <?php echo (int) $published_posts; ?>;
    var draftCount = <?php echo (int) $draft_posts; ?>;

    new Chart(document.getElementById('chartPostsMonth'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Posts created',
                data: monthValues,
                borderColor: '#818cf8',
                backgroundColor: 'rgba(129, 140, 248, 0.15)',
                fill: true,
                tension: 0.35,
                borderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 9,
                pointBackgroundColor: '#818cf8',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#818cf8',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            onHover: chartHover,
            plugins: {
                legend: { display: false },
                tooltip: Object.assign(chartTooltip(), {
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + ' post(s)';
                        }
                    }
                })
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    var statusData = [publishedCount, draftCount];
    var statusEmpty = publishedCount == 0 && draftCount == 0;
    if (statusEmpty) {
        statusData = [1];
    }
    new Chart(document.getElementById('chartPostStatus'), {
        type: 'doughnut',
        data: {
            labels: statusEmpty ? ['Empty'] : ['Published', 'Draft'],
            datasets: [{
                data: statusData,
                backgroundColor: statusEmpty
                    ? ['rgba(148, 163, 184, 0.25)']
                    : ['#34d399', '#fbbf24'],
                hoverBackgroundColor: statusEmpty
                    ? ['rgba(148, 163, 184, 0.35)']
                    : ['#10b981', '#f59e0b'],
                borderWidth: 0,
                hoverBorderWidth: 2,
                hoverBorderColor: '#ffffff',
                hoverOffset: 14
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onHover: chartHover,
            plugins: {
                legend: {
                    display: !statusEmpty,
                    position: 'bottom'
                },
                tooltip: Object.assign(chartTooltip(), {
                    callbacks: {
                        label: function(ctx) {
                            if (statusEmpty) {
                                return ' No posts yet';
                            }
                            var total = publishedCount + draftCount;
                            var pct = 0;
                            if (total > 0) {
                                pct = Math.round((ctx.parsed / total) * 100);
                            }
                            return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                        }
                    }
                })
            }
        }
    });

    new Chart(document.getElementById('chartEngagement'), {
        type: 'bar',
        data: {
            labels: postLabels.length > 0 ? postLabels : ['No posts yet'],
            datasets: [
                {
                    label: 'Views',
                    data: postViews.length > 0 ? postViews : [0],
                    backgroundColor: 'rgba(56, 189, 248, 0.7)',
                    hoverBackgroundColor: 'rgba(56, 189, 248, 1)',
                    borderRadius: 6,
                    borderSkipped: false
                },
                {
                    label: 'Likes',
                    data: postLikes.length > 0 ? postLikes : [0],
                    backgroundColor: 'rgba(248, 113, 113, 0.7)',
                    hoverBackgroundColor: 'rgba(248, 113, 113, 1)',
                    borderRadius: 6,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            onHover: chartHover,
            plugins: {
                legend: { position: 'top' },
                tooltip: Object.assign(chartTooltip(), {
                    callbacks: {
                        title: function(items) {
                            if (items.length > 0) {
                                return items[0].label;
                            }
                            return '';
                        },
                        label: function(ctx) {
                            return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y;
                        }
                    }
                })
            },
            scales: { y: { beginAtZero: true } }
        }
    });

    new Chart(document.getElementById('chartCategories'), {
        type: 'polarArea',
        data: {
            labels: catLabels.length > 0 ? catLabels : ['No data'],
            datasets: [{
                data: catValues.length > 0 ? catValues : [1],
                backgroundColor: [
                    'rgba(129, 140, 248, 0.65)',
                    'rgba(251, 191, 36, 0.65)',
                    'rgba(52, 211, 153, 0.65)',
                    'rgba(248, 113, 113, 0.65)',
                    'rgba(56, 189, 248, 0.65)',
                    'rgba(167, 139, 250, 0.65)'
                ],
                hoverBackgroundColor: [
                    'rgba(129, 140, 248, 0.9)',
                    'rgba(251, 191, 36, 0.9)',
                    'rgba(52, 211, 153, 0.9)',
                    'rgba(248, 113, 113, 0.9)',
                    'rgba(56, 189, 248, 0.9)',
                    'rgba(167, 139, 250, 0.9)'
                ],
                borderWidth: 1,
                borderColor: 'rgba(255, 255, 255, 0.15)',
                hoverBorderWidth: 2,
                hoverBorderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onHover: chartHover,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: Object.assign(chartTooltip(), {
                    callbacks: {
                        label: function(ctx) {
                            return ' ' + ctx.label + ': ' + ctx.parsed.r + ' post(s)';
                        }
                    }
                })
            },
            scales: {
                r: {
                    ticks: { display: false },
                    grid: { color: gridColor }
                }
            }
        }
    });
})();
</script>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>

