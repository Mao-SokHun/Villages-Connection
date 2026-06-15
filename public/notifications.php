<?php
require_once dirname(__DIR__) . '/bootstrap.php';
requireLogin();

if (isset($_GET['read']) && (int) $_GET['read'] > 0) {
    setFlashMessage('warning', 'Please use the Open button to view notifications.');
    header('Location: notifications.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_read'])) {
    require_valid_csrf();
    $note_id = (int) $_POST['mark_read'];
    if ($note_id > 0) {
        mark_notification_read($pdo, $note_id, (int) $_SESSION['user_id']);
    }
    $go = '';
    if (isset($_POST['go'])) {
        $go = trim($_POST['go']);
    }
    if ($go != '') {
        header('Location: ' . app_url(safe_redirect_path($go, 'notifications.php')));
        exit;
    }
    header('Location: notifications.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_all'])) {
    require_valid_csrf();
    mark_all_notifications_read($pdo, (int) $_SESSION['user_id']);
    setFlashMessage('success', 'All notifications marked as read.');
    header('Location: notifications.php');
    exit;
}

$notifications = array();
try {
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 100');
    $stmt->execute(array('uid' => (int) $_SESSION['user_id']));
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $notifications = array();
}

$page_title = 'Notifications';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-panel p-4 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h3 class="text-white mb-0"><i class="fa-solid fa-bell text-warning me-2"></i>Notifications</h3>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (push_is_configured()): ?>
                    <button type="button" id="btn-push-enable" class="btn btn-gradient btn-sm"
                        data-enable-label="<?php echo htmlspecialchars(__('push.enable')); ?>"
                        data-disable-label="<?php echo htmlspecialchars(__('push.disable')); ?>"
                        data-enabled-msg="<?php echo htmlspecialchars(__('push.enabled')); ?>"
                        data-disabled-msg="<?php echo htmlspecialchars(__('push.disabled')); ?>"
                        data-denied-msg="<?php echo htmlspecialchars(__('push.denied')); ?>"
                        data-not-configured="<?php echo htmlspecialchars(__('push.not_configured')); ?>"
                        data-unsupported="<?php echo htmlspecialchars(__('push.unsupported')); ?>">
                        <i class="fa-solid fa-bell"></i> <?php echo htmlspecialchars(__('push.enable')); ?>
                    </button>
                    <?php endif; ?>
                <?php if (count($notifications) > 0): ?>
                <form method="POST" action="notifications.php">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="mark_all" value="1">
                    <button type="submit" class="btn btn-outline-custom btn-sm">Mark all read</button>
                </form>
                <?php endif; ?>
                </div>
            </div>

            <?php if (count($notifications) == 0): ?>
            <div class="empty-state py-4">
                <i class="fa-regular fa-bell-slash fa-2x text-secondary mb-3"></i>
                <p class="text-secondary mb-0">No notifications yet.</p>
            </div>
            <?php else: ?>
            <div class="notification-list">
                <?php foreach ($notifications as $note): ?>
                <div class="notification-item <?php if (!$note['is_read']) echo 'is-unread'; ?><?php if (notification_is_support_type($note['type']) || $note['type'] == 'contact_message') echo ' is-support'; ?>">
                    <div class="notification-item-icon"><i class="fa-solid <?php echo notification_icon($note['type']); ?>"></i></div>
                    <div class="notification-item-body">
                        <span class="notification-type-label"><?php echo htmlspecialchars(notification_type_label($note['type'])); ?></span>
                        <div class="notification-item-title"><?php echo htmlspecialchars($note['title']); ?></div>
                        <div class="notification-item-text"><?php echo htmlspecialchars($note['message']); ?></div>
                        <div class="notification-item-time"><?php echo date('M j, Y H:i', strtotime($note['created_at'])); ?></div>
                    </div>
                    <?php if ($note['link_url'] != ''): ?>
                    <form method="POST" action="notifications.php" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="mark_read" value="<?php echo (int) $note['id']; ?>">
                        <input type="hidden" name="go" value="<?php echo htmlspecialchars($note['link_url']); ?>">
                        <button type="submit" class="btn btn-sm btn-outline-custom">Open</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
