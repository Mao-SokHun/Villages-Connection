<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$logs = array();
try {
    $logs = $pdo->query('SELECT l.*, u.name as user_name FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.created_at DESC LIMIT 200')->fetchAll();
} catch (PDOException $e) {
    $logs = array();
}

$page_title = 'Activity Log';
$admin_active = 'activity';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-white mb-0"><i class="fa-solid fa-clock-rotate-left text-secondary me-2"></i>Activity Log</h3>
        <span class="text-secondary small">Last 200 events</span>
    </div>

    <?php if (count($logs) == 0): ?>
    <p class="text-secondary mb-0">No activity recorded yet.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td class="small table-cell-muted"><?php echo date('M j, Y H:i', strtotime($log['created_at'])); ?></td>
                <td class="small"><?php echo $log['user_name'] ? htmlspecialchars($log['user_name']) : '—'; ?></td>
                <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                <td class="small table-cell-muted"><?php echo htmlspecialchars($log['details']); ?></td>
                <td class="small table-cell-muted"><?php echo htmlspecialchars($log['ip_address']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
