<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$admin_post = admin_post_action();
if ($admin_post) {
    if ($admin_post['action'] == 'approve' && $admin_post['id'] > 0) {
        $pdo->prepare("UPDATE post_comments SET status = 'approved' WHERE id = :id")->execute(array('id' => $admin_post['id']));
        notify_comment_approved($pdo, $admin_post['id']);
        log_activity($pdo, 'comment.approved', 'Comment #' . $admin_post['id']);
        setFlashMessage('success', 'Comment approved.');
        header('Location: comments.php');
        exit;
    }
    if ($admin_post['action'] == 'reject' && $admin_post['id'] > 0) {
        $pdo->prepare("UPDATE post_comments SET status = 'rejected' WHERE id = :id")->execute(array('id' => $admin_post['id']));
        log_activity($pdo, 'comment.rejected', 'Comment #' . $admin_post['id']);
        setFlashMessage('info', 'Comment rejected.');
        header('Location: comments.php');
        exit;
    }
    if ($admin_post['action'] == 'delete' && $admin_post['id'] > 0) {
        $pdo->prepare('DELETE FROM post_comments WHERE id = :id')->execute(array('id' => $admin_post['id']));
        setFlashMessage('success', 'Comment deleted.');
        header('Location: comments.php');
        exit;
    }
}

$filter = 'pending';
if (isset($_GET['status']) && $_GET['status'] != '') {
    $filter = trim($_GET['status']);
}

$list_search = '';
if (isset($_GET['search'])) {
    $list_search = trim($_GET['search']);
}

$list_where = ' WHERE 1=1';
$list_params = array();
if ($filter == 'pending' || $filter == 'approved' || $filter == 'rejected') {
    $list_where .= ' AND c.status = :status';
    $list_params['status'] = $filter;
}
if ($list_search != '') {
    $list_where .= ' AND (c.author_name ILIKE :search OR c.content ILIKE :search OR u.email ILIKE :search)';
    $list_params['search'] = '%' . $list_search . '%';
}

$sql = "SELECT c.*, p.title as post_title, p.slug as post_slug
        FROM post_comments c
        LEFT JOIN posts p ON p.id = c.post_id
        LEFT JOIN users u ON u.id = c.user_id" . $list_where . "
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($list_params);
$comments = $stmt->fetchAll();

$list_has_filters = ($list_search != '' || $filter != 'pending');

$page_title = 'Comment Moderation';
$admin_active = 'comments';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="text-white mb-0"><i class="fa-solid fa-comments text-info me-2"></i>Comments</h3>
        <div class="d-flex gap-2">
            <a href="comments.php?status=pending" class="btn btn-sm <?php echo $filter == 'pending' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Pending</a>
            <a href="comments.php?status=approved" class="btn btn-sm <?php echo $filter == 'approved' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Approved</a>
            <a href="comments.php?status=rejected" class="btn btn-sm <?php echo $filter == 'rejected' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Rejected</a>
            <a href="comments.php?status=all" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-gradient' : 'btn-outline-custom'; ?>">All</a>
        </div>
    </div>

    <form method="GET" action="comments.php" class="admin-list-toolbar mb-4">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
        <div class="row g-2 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label class="form-label form-label-custom small mb-1">Search</label>
                <input type="search" name="search" class="form-control form-control-custom" placeholder="Name, email, or content..." value="<?php echo htmlspecialchars($list_search); ?>">
            </div>
            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
                <?php if ($list_has_filters): ?>
                <a href="comments.php" class="btn btn-outline-custom btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <p class="admin-list-results mb-0 mt-2"><i class="fa-solid fa-list-ul me-1"></i><?php echo count($comments); ?> comment<?php if (count($comments) != 1) echo 's'; ?> found</p>
    </form>

    <?php if (count($comments) == 0): ?>
    <p class="text-secondary mb-0">No comments found.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead><tr><th>Author</th><th>Comment</th><th>Post</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($comments as $c): ?>
            <tr>
                <td class="small"><?php echo htmlspecialchars($c['author_name']); ?></td>
                <td class="small table-cell-title" style="max-width:240px"><?php echo htmlspecialchars(excerpt($c['content'], 80)); ?></td>
                <td class="small"><a href="../post.php?slug=<?php echo urlencode($c['post_slug']); ?>" class="footer-link" target="_blank"><?php echo htmlspecialchars(excerpt($c['post_title'], 30)); ?></a></td>
                <td><span class="badge <?php echo $c['status'] == 'approved' ? 'bg-success' : ($c['status'] == 'pending' ? 'bg-warning text-dark' : 'bg-danger'); ?>"><?php echo htmlspecialchars($c['status']); ?></span></td>
                <td class="small table-cell-muted"><?php echo date('M j, Y', strtotime($c['created_at'])); ?></td>
                <td class="text-end text-nowrap">
                    <?php if ($c['status'] != 'approved'): ?>
                    <?php render_admin_action_button('comments.php', 'approve', $c['id'], array('class' => 'btn btn-sm btn-outline-custom text-success', 'icon' => 'fa-solid fa-check', 'title' => 'Approve')); ?>
                    <?php endif; ?>
                    <?php if ($c['status'] != 'rejected'): ?>
                    <?php render_admin_action_button('comments.php', 'reject', $c['id'], array('class' => 'btn btn-sm btn-outline-custom text-warning', 'icon' => 'fa-solid fa-ban', 'title' => 'Reject')); ?>
                    <?php endif; ?>
                    <?php render_admin_action_button('comments.php', 'delete', $c['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Delete', 'confirm' => 'Delete comment?')); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
