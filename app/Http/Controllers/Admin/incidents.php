<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$filter = isset($_GET['status']) ? trim($_GET['status']) : 'open';
if (!in_array($filter, array('open', 'in_progress', 'resolved', 'all'), true)) {
    $filter = 'open';
}

$admin_post = admin_post_action();
if ($admin_post && $admin_post['id'] > 0) {
    $incident_id = (int) $admin_post['id'];
    $notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';

    if ($admin_post['action'] === 'set_status') {
        $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : '';
        if (in_array($new_status, array('open', 'in_progress', 'resolved'), true)) {
            $resolved_at_sql = $new_status === 'resolved' ? 'CURRENT_TIMESTAMP' : 'NULL';
            $sql = "UPDATE incident_reports
                    SET status = :status,
                        admin_notes = :notes,
                        updated_at = CURRENT_TIMESTAMP,
                        resolved_at = " . $resolved_at_sql . "
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array('status' => $new_status, 'notes' => $notes, 'id' => $incident_id));
            if ($stmt->rowCount() > 0) {
                log_activity($pdo, 'incident.' . $new_status, 'Incident #' . $incident_id);
                notify_reporter_incident_update($pdo, $incident_id, $new_status, $notes);
            }
            setFlashMessage('success', 'Incident status updated.');
        }
        header('Location: incidents.php');
        exit;
    }

    if ($admin_post['action'] === 'delete') {
        $pdo->prepare('DELETE FROM incident_reports WHERE id = :id')->execute(array('id' => $incident_id));
        log_activity($pdo, 'incident.deleted', 'Incident #' . $incident_id);
        setFlashMessage('success', 'Incident deleted.');
        header('Location: incidents.php');
        exit;
    }
}

$where = ' WHERE 1=1';
$params = array();
if ($filter !== 'all') {
    $where .= ' AND i.status = :status';
    $params['status'] = $filter;
}

$stmt = $pdo->prepare("SELECT i.*, u.name AS reporter_user_name
    FROM incident_reports i
    LEFT JOIN users u ON u.id = i.user_id
    $where
    ORDER BY i.created_at DESC");
$stmt->execute($params);
$incidents = $stmt->fetchAll();

$view = null;
if ($action === 'view' && $id > 0) {
    $stmt = $pdo->prepare("SELECT i.*, u.name AS reporter_user_name
        FROM incident_reports i
        LEFT JOIN users u ON u.id = i.user_id
        WHERE i.id = :id");
    $stmt->execute(array('id' => $id));
    $view = $stmt->fetch();
}

$page_title = 'Incidents';
$admin_active = 'incidents';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="text-white mb-0"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Incident Triage</h3>
        <div class="d-flex gap-2 flex-wrap">
            <a href="incidents.php?status=open" class="btn btn-sm <?php if ($filter == 'open') echo 'btn-gradient'; else echo 'btn-outline-custom'; ?>">Open</a>
            <a href="incidents.php?status=in_progress" class="btn btn-sm <?php if ($filter == 'in_progress') echo 'btn-gradient'; else echo 'btn-outline-custom'; ?>">In Progress</a>
            <a href="incidents.php?status=resolved" class="btn btn-sm <?php if ($filter == 'resolved') echo 'btn-gradient'; else echo 'btn-outline-custom'; ?>">Resolved</a>
            <a href="incidents.php?status=all" class="btn btn-sm <?php if ($filter == 'all') echo 'btn-gradient'; else echo 'btn-outline-custom'; ?>">All</a>
        </div>
    </div>

    <?php if ($view): ?>
    <div class="glass-panel-sm p-4 mb-4">
        <h5 class="text-white mb-2">Incident #<?php echo (int) $view['id']; ?> — <?php echo htmlspecialchars($view['title']); ?></h5>
        <p class="text-secondary small mb-2"><?php echo date('M j, Y H:i', strtotime($view['created_at'])); ?> · <?php echo htmlspecialchars($view['incident_type']); ?> · <?php echo strtoupper(htmlspecialchars($view['priority'])); ?></p>
        <p class="text-secondary mb-1"><strong>Reporter:</strong> <?php echo htmlspecialchars($view['reporter_name']); ?> (<?php echo htmlspecialchars($view['reporter_email']); ?>)</p>
        <p class="text-secondary mb-1"><strong>Village:</strong> <?php echo htmlspecialchars($view['village_name']); ?></p>
        <p class="text-secondary mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($view['location_text']); ?></p>
        <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($view['details'])); ?></p>

        <form method="POST" action="incidents.php" class="mb-3">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="admin_action" value="set_status">
            <input type="hidden" name="admin_id" value="<?php echo (int) $view['id']; ?>">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label form-label-custom">Status</label>
                    <select name="new_status" class="form-select form-control-custom">
                        <option value="open" <?php if ($view['status'] === 'open') echo 'selected'; ?>>Open</option>
                        <option value="in_progress" <?php if ($view['status'] === 'in_progress') echo 'selected'; ?>>In Progress</option>
                        <option value="resolved" <?php if ($view['status'] === 'resolved') echo 'selected'; ?>>Resolved</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label form-label-custom">Admin Notes</label>
                    <input type="text" name="admin_notes" class="form-control form-control-custom" value="<?php echo htmlspecialchars($view['admin_notes']); ?>">
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-save"></i> Update</button>
                <a href="incidents.php" class="btn btn-outline-custom btn-sm">Back</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if (count($incidents) == 0): ?>
    <p class="text-secondary mb-0">No incidents found.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead>
                <tr><th>ID</th><th>Type</th><th>Title</th><th>Priority</th><th>Status</th><th>Village</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($incidents as $inc): ?>
                <tr>
                    <td><code>#<?php echo (int) $inc['id']; ?></code></td>
                    <td class="small table-cell-muted"><?php echo htmlspecialchars($inc['incident_type']); ?></td>
                    <td class="table-cell-title"><?php echo htmlspecialchars($inc['title']); ?></td>
                    <td class="small table-cell-muted"><?php echo strtoupper(htmlspecialchars($inc['priority'])); ?></td>
                    <td><span class="badge <?php echo $inc['status'] === 'resolved' ? 'bg-success' : ($inc['status'] === 'in_progress' ? 'bg-info' : 'bg-warning text-dark'); ?>"><?php echo htmlspecialchars($inc['status']); ?></span></td>
                    <td class="small table-cell-muted"><?php echo htmlspecialchars($inc['village_name']); ?></td>
                    <td class="small table-cell-muted"><?php echo date('M j, H:i', strtotime($inc['created_at'])); ?></td>
                    <td class="text-end">
                        <a href="incidents.php?action=view&id=<?php echo (int) $inc['id']; ?>" class="btn btn-sm btn-outline-custom"><i class="fa-solid fa-eye"></i></a>
                        <?php render_admin_action_button('incidents.php', 'delete', $inc['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Delete', 'confirm' => 'Delete this incident?')); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
