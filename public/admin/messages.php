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
    if ($admin_post['action'] == 'read' && $admin_post['id'] > 0) {
        $sql = "UPDATE contact_messages SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE id = :id";
        $pdo->prepare($sql)->execute(array('id' => $admin_post['id']));
        setFlashMessage('success', 'Message marked as read.');
        header('Location: messages.php');
        exit;
    }
    if ($admin_post['action'] == 'archive' && $admin_post['id'] > 0) {
        $sql = "UPDATE contact_messages SET status = 'archived' WHERE id = :id";
        $pdo->prepare($sql)->execute(array('id' => $admin_post['id']));
        setFlashMessage('info', 'Message archived.');
        header('Location: messages.php');
        exit;
    }
    if ($admin_post['action'] == 'delete' && $admin_post['id'] > 0) {
        $pdo->prepare('DELETE FROM contact_messages WHERE id = :id')->execute(array('id' => $admin_post['id']));
        log_activity($pdo, 'message.deleted', 'Message #' . $admin_post['id']);
        setFlashMessage('success', 'Message deleted.');
        header('Location: messages.php');
        exit;
    }
}

$filter = 'new';
if (isset($_GET['status']) && $_GET['status'] != '') {
    $filter = trim($_GET['status']);
}

$list_where = ' WHERE 1=1';
$list_params = array();
if ($filter == 'new' || $filter == 'read' || $filter == 'archived') {
    $list_where .= ' AND status = :status';
    $list_params['status'] = $filter;
}

$stmt = $pdo->prepare('SELECT * FROM contact_messages' . $list_where . ' ORDER BY created_at DESC');
$stmt->execute($list_params);
$messages = $stmt->fetchAll();

$view_message = null;
if ($action == 'view' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM contact_messages WHERE id = :id');
    $stmt->execute(array('id' => $id));
    $view_message = $stmt->fetch();
    if ($view_message && $view_message['status'] == 'new') {
        $pdo->prepare("UPDATE contact_messages SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE id = :id")->execute(array('id' => $id));
        $view_message['status'] = 'read';
    }
}

$page_title = 'Contact Messages';
$admin_active = 'messages';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="text-white mb-0"><i class="fa-solid fa-envelope text-warning me-2"></i>Contact Messages</h3>
        <div class="d-flex gap-2">
            <a href="messages.php?status=new" class="btn btn-sm <?php echo $filter == 'new' ? 'btn-gradient' : 'btn-outline-custom'; ?>">New</a>
            <a href="messages.php?status=read" class="btn btn-sm <?php echo $filter == 'read' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Read</a>
            <a href="messages.php?status=archived" class="btn btn-sm <?php echo $filter == 'archived' ? 'btn-gradient' : 'btn-outline-custom'; ?>">Archived</a>
            <a href="messages.php?status=all" class="btn btn-sm <?php echo $filter == 'all' ? 'btn-gradient' : 'btn-outline-custom'; ?>">All</a>
        </div>
    </div>

    <?php if ($view_message): ?>
    <div class="glass-panel-sm p-4 mb-4">
        <h5 class="text-white mb-2"><?php echo htmlspecialchars($view_message['subject']); ?></h5>
        <p class="text-secondary small mb-3">From <?php echo htmlspecialchars($view_message['name']); ?> &lt;<?php echo htmlspecialchars($view_message['email']); ?>&gt; · <?php echo date('M j, Y H:i', strtotime($view_message['created_at'])); ?></p>
        <p class="text-secondary"><?php echo nl2br(htmlspecialchars($view_message['message'])); ?></p>
        <div class="d-flex gap-2 flex-wrap mt-3">
            <a href="mailto:<?php echo htmlspecialchars($view_message['email']); ?>" class="btn btn-gradient btn-sm"><i class="fa-solid fa-reply"></i> Reply by Email</a>
            <?php render_admin_action_button('messages.php', 'archive', $view_message['id'], array('class' => 'btn btn-outline-custom btn-sm', 'label' => 'Archive', 'title' => 'Archive')); ?>
            <a href="messages.php" class="btn btn-outline-custom btn-sm">Back</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($messages) == 0): ?>
    <p class="text-secondary mb-0">No messages found.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead><tr><th>ID</th><th>Subject</th><th>From</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($messages as $m): ?>
            <tr>
                <td><code>#<?php echo (int) $m['id']; ?></code></td>
                <td class="table-cell-title"><?php echo htmlspecialchars($m['subject']); ?></td>
                <td class="small table-cell-muted"><?php echo htmlspecialchars($m['email']); ?></td>
                <td><span class="badge <?php echo $m['status'] == 'new' ? 'bg-warning text-dark' : ($m['status'] == 'read' ? 'bg-info' : 'bg-secondary'); ?>"><?php echo htmlspecialchars($m['status']); ?></span></td>
                <td class="small table-cell-muted"><?php echo date('M j, Y H:i', strtotime($m['created_at'])); ?></td>
                <td class="text-end">
                    <a href="messages.php?action=view&id=<?php echo (int) $m['id']; ?>" class="btn btn-sm btn-outline-custom"><i class="fa-solid fa-eye"></i></a>
                    <?php render_admin_action_button('messages.php', 'delete', $m['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Delete', 'confirm' => 'Delete this message?')); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
