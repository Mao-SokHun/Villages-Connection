<?php
require_once __DIR__ . '/auth.php';

if (isAdmin()) {
    header('Location: comments.php');
    exit;
}

$admin_post = admin_post_action();
if ($admin_post && $admin_post['action'] == 'delete' && $admin_post['id'] > 0) {
    $result = delete_post_owner_comment($pdo, $admin_post['id']);
    if ($result['ok']) {
        setFlashMessage('success', 'Comment removed.');
    } else {
        setFlashMessage('danger', $result['error']);
    }
    header('Location: my-comments.php');
    exit;
}

$filter = '';
if (isset($_GET['status']) && $_GET['status'] != '') {
    $filter = trim($_GET['status']);
}

$list_search = '';
if (isset($_GET['search'])) {
    $list_search = trim($_GET['search']);
}

$author_id = (int) $_SESSION['user_id'];
$list_where = ' WHERE p.user_id = :owner_id';
$list_params = array('owner_id' => $author_id);

if ($filter == 'pending' || $filter == 'approved' || $filter == 'rejected') {
    $list_where .= ' AND c.status = :status';
    $list_params['status'] = $filter;
}
if ($list_search != '') {
    $list_where .= ' AND (c.author_name ILIKE :search OR c.content ILIKE :search OR p.title ILIKE :search)';
    $list_params['search'] = '%' . $list_search . '%';
}

$sql = "SELECT c.*, p.title as post_title, p.slug as post_slug, p.user_id as post_owner_id
        FROM post_comments c
        INNER JOIN posts p ON p.id = c.post_id" . $list_where . "
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($list_params);
$comments = $stmt->fetchAll();

$list_has_filters = ($list_search != '' || $filter != '');

$page_title = 'My Comments';
$admin_active = 'my-comments';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="text-white mb-1"><i class="fa-solid fa-comments text-info me-2"></i>Comments on My Posts</h3>
            <p class="text-secondary small mb-0">Review and manage comments from readers on your content.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="my-comments.php" class="btn btn-sm <?php echo $filter == '' ? 'btn-gradient' : 'btn-outline-custom'; ?>">All</a>
            <a href="my-comments.php?status=pending" class="btn btn-sm <?php echo $filter == 'pending' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Pending</a>
            <a href="my-comments.php?status=approved" class="btn btn-sm <?php echo $filter == 'approved' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Approved</a>
            <a href="my-comments.php?status=rejected" class="btn btn-sm <?php echo $filter == 'rejected' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Rejected</a>
        </div>
    </div>

    <form method="GET" action="my-comments.php" class="admin-list-toolbar mb-4">
        <?php if ($filter != ''): ?>
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
        <?php endif; ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-5 col-lg-4">
                <label class="form-label form-label-custom small mb-1">Search</label>
                <input type="search" name="search" class="form-control form-control-custom" placeholder="Comment, author, or post title..." value="<?php echo htmlspecialchars($list_search); ?>">
            </div>
            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
                <?php if ($list_has_filters): ?>
                <a href="my-comments.php" class="btn btn-outline-custom btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </div>
        <p class="admin-list-results mb-0 mt-2"><i class="fa-solid fa-list-ul me-1"></i><?php echo count($comments); ?> comment<?php if (count($comments) != 1) echo 's'; ?> found</p>
    </form>

    <?php if (count($comments) == 0): ?>
    <div class="text-center py-5 text-secondary">
        <i class="fa-solid fa-comment-slash fs-2 mb-3 text-muted"></i>
        <p class="mb-0">No comments on your posts yet.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr>
                    <th>Reader</th>
                    <th>Comment</th>
                    <th>Post</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($comments as $c): ?>
            <tr>
                <td class="small table-cell-strong"><?php echo htmlspecialchars($c['author_name']); ?></td>
                <td class="small" style="max-width:280px"><?php echo htmlspecialchars($c['content']); ?></td>
                <td class="small">
                    <a href="../post.php?slug=<?php echo urlencode($c['post_slug']); ?>#comments" class="footer-link" target="_blank">
                        <?php echo htmlspecialchars(excerpt($c['post_title'], 36)); ?>
                    </a>
                </td>
                <td>
                    <span class="badge <?php echo $c['status'] == 'approved' ? 'bg-success' : ($c['status'] == 'pending' ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                        <?php echo htmlspecialchars($c['status']); ?>
                    </span>
                </td>
                <td class="small table-cell-muted"><?php echo khmer_date($c['created_at']); ?></td>
                <td class="text-end text-nowrap">
                    <a href="../post.php?slug=<?php echo urlencode($c['post_slug']); ?>#comments" class="btn btn-sm btn-outline-custom" target="_blank" title="View on post"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    <?php render_admin_action_button('my-comments.php', 'delete', $c['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Remove comment', 'confirm' => 'Remove this comment from your post?')); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
