<?php
require_once __DIR__ . '/auth.php';
require_once APP_PATH . '/Models/mail.php';
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

$reply_errors = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contact_reply'])) {
    require_valid_csrf();

    $reply_id = 0;
    if (isset($_POST['message_id'])) {
        $reply_id = (int) $_POST['message_id'];
    }

    $reply_body = '';
    if (isset($_POST['reply_body'])) {
        $reply_body = trim($_POST['reply_body']);
    }

    if ($reply_id <= 0) {
        $reply_errors[] = 'Invalid message.';
    } elseif ($reply_body == '') {
        $reply_errors[] = 'Please enter your reply.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM contact_messages WHERE id = :id');
        $stmt->execute(array('id' => $reply_id));
        $reply_message = $stmt->fetch();

        if (!$reply_message) {
            $reply_errors[] = 'Message not found.';
        } else {
            $pdo->prepare("UPDATE contact_messages SET admin_reply = :reply, replied_at = CURRENT_TIMESTAMP WHERE id = :id")
                ->execute(array(
                    'reply' => $reply_body,
                    'id' => $reply_id
                ));
            $reply_message['admin_reply'] = $reply_body;

            $email_sent = false;
            $reply_email = trim($reply_message['email']);
            if ($reply_email != '' && !is_placeholder_oauth_email($reply_email) && filter_var($reply_email, FILTER_VALIDATE_EMAIL)) {
                $email_sent = send_contact_reply_email(
                    $reply_email,
                    $reply_message['name'],
                    $reply_message['subject'],
                    $reply_body,
                    $reply_message['message'],
                    date('M j, Y H:i', strtotime($reply_message['created_at']))
                );
            }

            notify_user_contact_reply($pdo, $reply_message);

            if ($reply_message['status'] == 'new') {
                $pdo->prepare("UPDATE contact_messages SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE id = :id")
                    ->execute(array('id' => $reply_id));
            }

            log_activity($pdo, 'message.replied', 'Message #' . $reply_id . ' to ' . $reply_message['email']);
            if ($email_sent) {
                setFlashMessage('success', 'Reply saved and emailed to ' . $reply_message['email'] . '. The user can also read it in Support / Notifications.');
            } else {
                setFlashMessage('success', 'Reply saved. The user can read it in Support and Notifications.');
            }
            header('Location: messages.php?action=view&id=' . $reply_id);
            exit;
        }
    }

    if (count($reply_errors) > 0) {
        $action = 'view';
        $id = $reply_id;
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
    mark_admin_contact_notifications_read($pdo, (int) $_SESSION['user_id'], $id);
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
        <?php if (!empty($view_message['admin_reply'])): ?>
        <div class="mt-3 p-3 rounded glass-panel-sm">
            <h6 class="text-white mb-2"><i class="fa-solid fa-reply text-success me-2"></i>Your Reply</h6>
            <p class="text-secondary mb-1 small">Sent <?php echo !empty($view_message['replied_at']) ? date('M j, Y H:i', strtotime($view_message['replied_at'])) : ''; ?></p>
            <p class="text-secondary mb-0"><?php echo nl2br(htmlspecialchars($view_message['admin_reply'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
            <h6 class="text-white mb-3"><i class="fa-solid fa-reply text-warning me-2"></i>Reply</h6>
            <?php if (count($reply_errors) > 0): ?>
            <?php render_user_alerts($reply_errors, 'danger'); ?>
            <?php endif; ?>
            <form method="POST" action="messages.php?action=view&id=<?php echo (int) $view_message['id']; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="contact_reply" value="1">
                <input type="hidden" name="message_id" value="<?php echo (int) $view_message['id']; ?>">
                <div class="mb-3">
                    <label for="reply_body" class="form-label form-label-custom small">Your reply to <?php echo htmlspecialchars($view_message['email']); ?></label>
                    <textarea name="reply_body" id="reply_body" class="form-control form-control-custom" rows="5" required placeholder="Type your reply here..."><?php
                    if (isset($_POST['reply_body']) && count($reply_errors) > 0) {
                        echo htmlspecialchars($_POST['reply_body']);
                    }
                    ?></textarea>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-paper-plane"></i> Send Reply</button>
                    <a href="mailto:<?php echo htmlspecialchars($view_message['email']); ?>?subject=<?php echo rawurlencode('Re: ' . $view_message['subject']); ?>" class="btn btn-outline-custom btn-sm"><i class="fa-solid fa-envelope"></i> Open in Email App</a>
                    <?php render_admin_action_button('messages.php', 'archive', $view_message['id'], array('class' => 'btn btn-outline-custom btn-sm', 'label' => 'Archive', 'title' => 'Archive')); ?>
                    <a href="messages.php" class="btn btn-outline-custom btn-sm">Back</a>
                </div>
            </form>
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
            <tr class="<?php echo $m['status'] == 'new' ? 'table-row-unread' : ''; ?>">
                <td><code>#<?php echo (int) $m['id']; ?></code></td>
                <td class="table-cell-title">
                    <?php if ($m['status'] == 'new'): ?><span class="unread-dot" title="Unread"></span><?php endif; ?>
                    <?php echo htmlspecialchars($m['subject']); ?>
                </td>
                <td class="small table-cell-muted"><?php echo htmlspecialchars($m['email']); ?></td>
                <td><span class="badge <?php echo $m['status'] == 'new' ? 'bg-warning text-dark' : ($m['status'] == 'read' ? 'bg-info' : 'bg-secondary'); ?>"><?php echo $m['status'] == 'new' ? 'unread' : htmlspecialchars($m['status']); ?></span></td>
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
