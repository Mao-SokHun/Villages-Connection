<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

$id = 0;
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
}

$admin_post = admin_post_action();
if ($admin_post) {
    if ($admin_post['action'] == 'resolve' && $admin_post['id'] > 0) {
        $notes = '';
        if (isset($_POST['admin_notes'])) {
            $notes = trim($_POST['admin_notes']);
        }
        $sql = "UPDATE content_reports SET status = 'resolved', admin_notes = :notes, resolved_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('id' => $admin_post['id'], 'notes' => $notes));
        if ($stmt->rowCount() > 0) {
            $log_details = 'Report #' . $admin_post['id'];
            if ($notes !== '') {
                $log_details .= ' | Notes: ' . excerpt($notes, 100);
            }
            log_activity($pdo, 'report.resolved', $log_details);
            notify_reporter_status_update($pdo, (int) $admin_post['id'], 'resolved', $notes);
        }
        setFlashMessage('success', 'Report marked as resolved.');
        header('Location: reports.php');
        exit;
    }
    if ($admin_post['action'] == 'open' && $admin_post['id'] > 0) {
        $sql = "UPDATE content_reports SET status = 'open', resolved_at = NULL WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('id' => $admin_post['id']));
        if ($stmt->rowCount() > 0) {
            log_activity($pdo, 'report.reopened', 'Report #' . $admin_post['id']);
            notify_reporter_status_update($pdo, (int) $admin_post['id'], 'open', '');
        }
        setFlashMessage('info', 'Report reopened.');
        header('Location: reports.php');
        exit;
    }
    if ($admin_post['action'] == 'delete' && $admin_post['id'] > 0) {
        $pdo->prepare('DELETE FROM content_reports WHERE id = :id')->execute(array('id' => $admin_post['id']));
        log_activity($pdo, 'report.deleted', 'Report #' . $admin_post['id']);
        setFlashMessage('success', 'Report deleted.');
        header('Location: reports.php');
        exit;
    }
}

$filter = 'open';
if (isset($_GET['status']) && $_GET['status'] != '') {
    $filter = trim($_GET['status']);
}

if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $export_status = $filter;
    if ($export_status != 'open' && $export_status != 'resolved') {
        $export_status = 'all';
    }
    analytics_export_reports($pdo, $export_status);
}

$list_where = ' WHERE 1=1';
$list_params = array();
if ($filter == 'open' || $filter == 'resolved') {
    $list_where .= ' AND status = :status';
    $list_params['status'] = $filter;
}

$stmt = $pdo->prepare('SELECT * FROM content_reports' . $list_where . ' ORDER BY created_at DESC');
$stmt->execute($list_params);
$reports = $stmt->fetchAll();

$view_report = null;
if ($action == 'view' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM content_reports WHERE id = :id');
    $stmt->execute(array('id' => $id));
    $view_report = $stmt->fetch();
}

$page_title = 'Content Reports';
$admin_active = 'reports';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="text-white mb-0"><i class="fa-solid fa-flag text-danger me-2"></i>Content Reports</h3>
        <div class="d-flex gap-2">
            <a href="reports.php?export=csv&amp;status=<?php echo htmlspecialchars($filter); ?>" class="btn btn-sm btn-outline-custom"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
            <a href="reports.php?status=open" class="btn btn-sm <?php echo $filter == 'open' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Open</a>
            <a href="reports.php?status=resolved" class="btn btn-sm <?php echo $filter == 'resolved' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Resolved</a>
            <a href="reports.php?status=all" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-gradient' : 'btn-outline-custom'; ?>">All</a>
        </div>
    </div>

    <?php if ($view_report): ?>
    <div class="glass-panel-sm p-4 mb-4">
        <h5 class="text-white mb-3">Report #<?php echo (int) $view_report['id']; ?></h5>
        <p class="text-secondary mb-1"><strong>From:</strong> <?php echo htmlspecialchars($view_report['reporter_name']); ?> &lt;<?php echo htmlspecialchars($view_report['reporter_email']); ?>&gt;</p>
        <p class="text-secondary mb-1"><strong>Reason:</strong> <?php echo htmlspecialchars($view_report['reason']); ?></p>
        <?php if ($view_report['post_url'] != ''): ?>
        <p class="text-secondary mb-1"><strong>Post URL:</strong> <a href="<?php echo htmlspecialchars($view_report['post_url']); ?>" target="_blank" rel="noopener" class="footer-link"><?php echo htmlspecialchars($view_report['post_url']); ?></a></p>
        <?php endif; ?>
        <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($view_report['details'])); ?></p>
        <form method="POST" action="reports.php" class="mb-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="admin_action" value="resolve">
            <input type="hidden" name="admin_id" value="<?php echo (int) $view_report['id']; ?>">
            <label class="form-label form-label-custom">Admin notes</label>
            <textarea name="admin_notes" class="form-control form-control-custom mb-2" rows="2"><?php echo htmlspecialchars($view_report['admin_notes']); ?></textarea>
            <div class="d-flex gap-2 flex-wrap">
                <?php if ($view_report['status'] == 'open'): ?>
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-check"></i> Mark Resolved</button>
                <?php endif; ?>
                <a href="reports.php" class="btn btn-outline-custom btn-sm">Back</a>
            </div>
        </form>
        <?php if ($view_report['status'] != 'open'): ?>
        <div class="d-flex gap-2 flex-wrap">
            <?php render_admin_action_button('reports.php', 'open', $view_report['id'], array('class' => 'btn btn-outline-custom btn-sm', 'label' => 'Reopen', 'title' => 'Reopen')); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (count($reports) == 0): ?>
    <p class="text-secondary mb-0">No reports found.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead><tr><th>ID</th><th>Reason</th><th>Reporter</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td><code>#<?php echo (int) $r['id']; ?></code></td>
                <td class="table-cell-title"><?php echo htmlspecialchars($r['reason']); ?></td>
                <td class="small table-cell-muted"><?php echo htmlspecialchars($r['reporter_email']); ?></td>
                <td><span class="badge <?php echo $r['status'] == 'open' ? 'bg-warning text-dark' : 'bg-success'; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                <td class="small table-cell-muted"><?php echo date('M j, Y H:i', strtotime($r['created_at'])); ?></td>
                <td class="text-end">
                    <a href="reports.php?action=view&id=<?php echo (int) $r['id']; ?>" class="btn btn-sm btn-outline-custom"><i class="fa-solid fa-eye"></i></a>
                    <?php render_admin_action_button('reports.php', 'delete', $r['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Delete', 'confirm' => 'Delete this report?')); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
