<?php
requireLogin();

$message_id = 0;
if (isset($_GET['message'])) {
    $message_id = (int) $_GET['message'];
}

$user_id = (int) $_SESSION['user_id'];
$messages = array();
$active_message = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages
        WHERE user_id = :uid OR LOWER(email) = LOWER(:email)
        ORDER BY created_at DESC
        LIMIT 50");
    $stmt->execute(array(
        'uid' => $user_id,
        'email' => $_SESSION['user_email']
    ));
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $messages = array();
}

if ($message_id > 0) {
    foreach ($messages as $msg) {
        if ((int) $msg['id'] == $message_id) {
            $active_message = $msg;
            break;
        }
    }
    if ($active_message) {
        mark_support_notifications_read($pdo, $user_id, $message_id);
    }
} elseif (count($messages) > 0) {
    $active_message = $messages[0];
}

$page_title = 'Support Messages';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
?>

<div class="row justify-content-center g-4">
    <div class="col-lg-10">
        <div class="glass-panel p-4 reveal">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="text-white mb-1"><i class="fa-solid fa-headset text-warning me-2"></i>Support Messages</h3>
                    <p class="text-secondary small mb-0">Read admin replies to your contact messages here.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-envelope"></i> Contact Us</a>
                    <a href="<?php echo app_url('notifications.php'); ?>" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-bell"></i> Notifications</a>
                </div>
            </div>

            <?php if (count($messages) == 0): ?>
            <div class="empty-state py-4">
                <i class="fa-regular fa-envelope-open fa-2x text-secondary mb-3"></i>
                <p class="text-secondary mb-3">You have not sent any contact messages yet.</p>
                <a href="<?php echo app_url('contact.php'); ?>" class="btn btn-gradient btn-sm">Send a Message</a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="support-thread-list">
                        <?php foreach ($messages as $msg): ?>
                        <a href="<?php echo app_url('support.php?message=' . (int)$msg['id']); ?>" class="support-thread-item <?php if ($active_message && (int) $active_message['id'] == (int) $msg['id']) echo 'active'; ?>">
                            <strong><?php echo htmlspecialchars(excerpt($msg['subject'], 42)); ?></strong>
                            <span class="small text-secondary d-block"><?php echo date('M j, Y', strtotime($msg['created_at'])); ?></span>
                            <?php if (!empty($msg['admin_reply'])): ?>
                            <span class="badge bg-success mt-1">Replied</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark mt-1">Waiting</span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <?php if ($active_message): ?>
                    <div class="glass-panel-sm p-4">
                        <h5 class="text-white mb-2"><?php echo htmlspecialchars($active_message['subject']); ?></h5>
                        <p class="text-secondary small mb-3">Sent <?php echo date('M j, Y H:i', strtotime($active_message['created_at'])); ?></p>
                        <div class="mb-4">
                            <h6 class="text-white small mb-2">Your message</h6>
                            <p class="text-secondary mb-0"><?php echo nl2br(htmlspecialchars($active_message['message'])); ?></p>
                        </div>
                        <?php if (!empty($active_message['admin_reply'])): ?>
                        <div class="p-3 rounded" style="border-left:3px solid var(--success); background:rgba(52,211,153,0.08);">
                            <h6 class="text-white small mb-2"><i class="fa-solid fa-reply text-success me-1"></i>Admin reply</h6>
                            <?php if (!empty($active_message['replied_at'])): ?>
                            <p class="text-secondary small mb-2"><?php echo date('M j, Y H:i', strtotime($active_message['replied_at'])); ?></p>
                            <?php endif; ?>
                            <p class="text-secondary mb-0"><?php echo nl2br(htmlspecialchars($active_message['admin_reply'])); ?></p>
                        </div>
                        <?php else: ?>
                        <p class="text-secondary small mb-0"><i class="fa-solid fa-hourglass-half me-1"></i>Waiting for an admin reply. You will also get a notification when we respond.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
