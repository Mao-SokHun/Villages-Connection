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

$errors = array();

$admin_post = admin_post_action();
if ($admin_post) {
    if ($admin_post['action'] == 'delete' && $admin_post['id'] > 0) {
        $pdo->prepare('DELETE FROM announcements WHERE id = :id')->execute(array('id' => $admin_post['id']));
        log_activity($pdo, 'announcement.deleted', 'Announcement #' . $admin_post['id']);
        setFlashMessage('success', 'Announcement deleted.');
        header('Location: announcements.php');
        exit;
    }
    if ($admin_post['action'] == 'toggle' && $admin_post['id'] > 0) {
        $pdo->prepare('UPDATE announcements SET is_active = NOT is_active WHERE id = :id')->execute(array('id' => $admin_post['id']));
        header('Location: announcements.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['admin_action'])) {
    require_valid_csrf();

    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $link_url = isset($_POST['link_url']) ? trim($_POST['link_url']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $starts_at = isset($_POST['starts_at']) ? trim($_POST['starts_at']) : '';
    $ends_at = isset($_POST['ends_at']) ? trim($_POST['ends_at']) : '';

    if ($title == '') {
        $errors[] = 'Title is required.';
    }
    if ($message == '') {
        $errors[] = 'Message is required.';
    }

    if (count($errors) == 0) {
        $fields = array(
            'title' => $title,
            'message' => $message,
            'link_url' => $link_url,
            'is_active' => $is_active,
            'starts_at' => $starts_at != '' ? $starts_at : null,
            'ends_at' => $ends_at != '' ? $ends_at : null
        );

        $db_action = isset($_POST['db_action']) ? $_POST['db_action'] : 'add';

        if ($db_action == 'add') {
            $sql = 'INSERT INTO announcements (title, message, link_url, is_active, starts_at, ends_at) VALUES (:title, :message, :link_url, :is_active, :starts_at, :ends_at)';
            $pdo->prepare($sql)->execute($fields);
            log_activity($pdo, 'announcement.created', $title);
            setFlashMessage('success', 'Announcement created.');
        } elseif ($db_action == 'edit' && $id > 0) {
            $fields['id'] = $id;
            $sql = 'UPDATE announcements SET title=:title, message=:message, link_url=:link_url, is_active=:is_active, starts_at=:starts_at, ends_at=:ends_at WHERE id=:id';
            $pdo->prepare($sql)->execute($fields);
            log_activity($pdo, 'announcement.updated', $title);
            setFlashMessage('success', 'Announcement updated.');
        }
        header('Location: announcements.php');
        exit;
    }
}

$edit = null;
if ($action == 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM announcements WHERE id = :id');
    $stmt->execute(array('id' => $id));
    $edit = $stmt->fetch();
}

$announcements = $pdo->query('SELECT * FROM announcements ORDER BY id DESC')->fetchAll();

$page_title = 'Announcements';
$admin_active = 'announcements';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="glass-panel p-4">
            <h4 class="text-white mb-3"><?php echo $edit ? 'Edit Announcement' : 'New Announcement'; ?></h4>
            <?php if (count($errors) > 0): ?>
            <?php render_user_alerts($errors, 'danger'); ?>
            <?php endif; ?>
            <form method="POST" action="announcements.php<?php if ($edit) echo '?action=edit&id=' . (int) $edit['id']; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="db_action" value="<?php echo $edit ? 'edit' : 'add'; ?>">
                <div class="mb-3">
                    <label class="form-label form-label-custom">Title</label>
                    <input type="text" name="title" class="form-control form-control-custom" required value="<?php echo $edit ? htmlspecialchars($edit['title']) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-custom">Message</label>
                    <textarea name="message" class="form-control form-control-custom" rows="3" required><?php echo $edit ? htmlspecialchars($edit['message']) : ''; ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-custom">Link URL (optional)</label>
                    <input type="url" name="link_url" class="form-control form-control-custom" value="<?php echo $edit ? htmlspecialchars($edit['link_url']) : ''; ?>">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label form-label-custom">Starts</label>
                        <input type="datetime-local" name="starts_at" class="form-control form-control-custom" value="<?php if ($edit && $edit['starts_at']) echo date('Y-m-d\TH:i', strtotime($edit['starts_at'])); ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label form-label-custom">Ends</label>
                        <input type="datetime-local" name="ends_at" class="form-control form-control-custom" value="<?php if ($edit && $edit['ends_at']) echo date('Y-m-d\TH:i', strtotime($edit['ends_at'])); ?>">
                    </div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?php if (!$edit || $edit['is_active']) echo 'checked'; ?>>
                    <label class="form-check-label text-secondary" for="is_active">Active</label>
                </div>
                <button type="submit" class="btn btn-gradient btn-sm"><i class="fa-solid fa-save"></i> Save</button>
                <?php if ($edit): ?><a href="announcements.php" class="btn btn-outline-custom btn-sm">Cancel</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="glass-panel p-4">
            <h4 class="text-white mb-3">All Announcements</h4>
            <?php if (count($announcements) == 0): ?>
            <p class="text-secondary mb-0">No announcements yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom table-hover mb-0">
                    <thead><tr><th>Title</th><th>Status</th><th>Schedule</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($announcements as $a): ?>
                    <tr>
                        <td class="table-cell-title"><?php echo htmlspecialchars($a['title']); ?></td>
                        <td><span class="badge <?php echo $a['is_active'] ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $a['is_active'] ? 'Active' : 'Off'; ?></span></td>
                        <td class="small table-cell-muted"><?php
                            if ($a['starts_at']) echo date('M j', strtotime($a['starts_at']));
                            if ($a['ends_at']) echo ' – ' . date('M j', strtotime($a['ends_at']));
                        ?></td>
                        <td class="text-end">
                            <?php render_admin_action_button('announcements.php', 'toggle', $a['id'], array('class' => 'btn btn-sm btn-outline-custom', 'icon' => 'fa-solid fa-power-off', 'title' => 'Toggle')); ?>
                            <a href="announcements.php?action=edit&id=<?php echo (int) $a['id']; ?>" class="btn btn-sm btn-outline-custom"><i class="fa-solid fa-edit"></i></a>
                            <?php render_admin_action_button('announcements.php', 'delete', $a['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Delete', 'confirm' => 'Delete?')); ?>
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

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
