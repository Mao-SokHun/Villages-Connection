<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$errors = array();
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Delete poll
$admin_post = admin_post_action();
if ($admin_post && $admin_post['id'] > 0 && $admin_post['action'] === 'delete') {
    try {
        $pdo->prepare('DELETE FROM polls WHERE id = :id')->execute(array('id' => (int) $admin_post['id']));
        log_activity($pdo, 'poll.deleted', 'Poll #' . (int) $admin_post['id']);
        setFlashMessage('success', 'Poll deleted.');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Could not delete poll.');
    }
    header('Location: polls.php');
    exit;
}

// Create/Update poll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_action'])) {
    require_valid_csrf();
    $db_action   = isset($_POST['db_action']) ? trim($_POST['db_action']) : '';
    $post_id     = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $question    = isset($_POST['question']) ? sanitize_plain_text_field($_POST['question'], 500) : '';
    $is_multiple = isset($_POST['is_multiple']) ? true : false;
    $ends_at     = isset($_POST['ends_at']) ? trim($_POST['ends_at']) : '';
    $options     = isset($_POST['options']) ? (array) $_POST['options'] : array();
    $options     = array_filter(array_map('trim', $options), function($o) { return $o !== ''; });
    $options     = array_values($options);

    if ($question === '') $errors[] = 'Question is required.';
    if ($post_id <= 0) $errors[] = 'Post ID is required.';
    if (count($options) < 2) $errors[] = 'At least 2 options are required.';

    if (count($errors) === 0) {
        try {
            if ($db_action === 'edit' && $id > 0) {
                $pdo->prepare('UPDATE polls SET question = :q, is_multiple = :m, ends_at = :e WHERE id = :id')
                    ->execute(array('q' => $question, 'm' => $is_multiple ? 't' : 'f', 'e' => $ends_at ?: null, 'id' => $id));
                $pdo->prepare('DELETE FROM poll_options WHERE poll_id = :pid')->execute(array('pid' => $id));
                $poll_id = $id;
            } else {
                $ins = $pdo->prepare('INSERT INTO polls (post_id, question, is_multiple, ends_at) VALUES (:pid, :q, :m, :e) RETURNING id');
                $ins->execute(array('pid' => $post_id, 'q' => $question, 'm' => $is_multiple ? 't' : 'f', 'e' => $ends_at ?: null));
                $poll_id = (int) $ins->fetchColumn();
            }
            foreach ($options as $i => $label) {
                $pdo->prepare('INSERT INTO poll_options (poll_id, label, sort_order) VALUES (:pid, :label, :sort)')
                    ->execute(array('pid' => $poll_id, 'label' => $label, 'sort' => $i));
            }
            log_activity($pdo, $db_action === 'edit' ? 'poll.updated' : 'poll.created', 'Poll #' . $poll_id);
            setFlashMessage('success', 'Poll saved.');
            header('Location: polls.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$poll = null;
$poll_options_list = array();
if ($action === 'edit' && $id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM polls WHERE id = :id');
        $stmt->execute(array('id' => $id));
        $poll = $stmt->fetch();
        if ($poll) {
            $opt_stmt = $pdo->prepare('SELECT * FROM poll_options WHERE poll_id = :pid ORDER BY sort_order ASC');
            $opt_stmt->execute(array('pid' => $id));
            $poll_options_list = $opt_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $poll = null;
    }
}

// List polls
try {
    $poll_list = $pdo->query("SELECT p.*, po.title AS post_title,
        (SELECT COUNT(*) FROM poll_votes v WHERE v.poll_id = p.id) AS total_votes
        FROM polls p LEFT JOIN posts po ON po.id = p.post_id
        ORDER BY p.id DESC LIMIT 50")->fetchAll();
} catch (PDOException $e) {
    $poll_list = array();
}

// Fetch published posts for dropdown
try {
    $posts_for_poll = $pdo->query("SELECT id, title FROM posts WHERE status = 'Published' ORDER BY created_at DESC LIMIT 200")->fetchAll();
} catch (PDOException $e) {
    $posts_for_poll = array();
}

$page_title = 'Manage Polls';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="text-white mb-0"><i class="fa-solid fa-square-poll-vertical text-warning me-2"></i> Polls</h2>
    <a href="?action=add" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> Add Poll</a>
</div>

<?php if (count($errors) > 0): ?>
<?php render_user_alerts($errors, 'danger'); ?>
<?php endif; ?>
<?php echo flash_html(); ?>

<?php if ($action === 'add' || ($action === 'edit' && $id > 0)): ?>
<div class="glass-panel p-4 mb-4">
    <h5 class="text-white mb-3"><?php echo $action === 'edit' ? 'Edit Poll' : 'Create Poll'; ?></h5>
    <form method="POST" action="polls.php<?php if ($action === 'edit') echo '?action=edit&id=' . $id; ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="db_action" value="<?php echo $action === 'edit' ? 'edit' : 'add'; ?>">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label form-label-custom">Poll Question *</label>
                <input type="text" name="question" class="form-control form-control-custom" required maxlength="500"
                    value="<?php echo htmlspecialchars($poll['question'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label form-label-custom">Attach to Post *</label>
                <select name="post_id" class="form-select form-select-custom" required>
                    <option value="">— Select post —</option>
                    <?php foreach ($posts_for_poll as $p): ?>
                    <option value="<?php echo (int) $p['id']; ?>" <?php if ($poll && (int) $poll['post_id'] === (int) $p['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars(excerpt($p['title'], 60)); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label form-label-custom">Poll Ends At (optional)</label>
                <input type="datetime-local" name="ends_at" class="form-control form-control-custom"
                    value="<?php echo !empty($poll['ends_at']) ? date('Y-m-d\TH:i', strtotime($poll['ends_at'])) : ''; ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check mt-2">
                    <input type="checkbox" name="is_multiple" id="is_multiple" class="form-check-input" value="1"
                        <?php if (!empty($poll['is_multiple'])) echo 'checked'; ?>>
                    <label class="form-check-label text-secondary" for="is_multiple">Allow multiple choice</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label form-label-custom">Options (one per line, min 2)</label>
                <div id="poll-options-list">
                    <?php $existing_opts = count($poll_options_list) > 0 ? $poll_options_list : array(array('label' => ''), array('label' => '')); ?>
                    <?php foreach ($existing_opts as $i => $opt): ?>
                    <div class="d-flex gap-2 mb-2 poll-opt-row">
                        <input type="text" name="options[]" class="form-control form-control-custom" placeholder="Option <?php echo $i + 1; ?>" maxlength="255"
                            value="<?php echo htmlspecialchars($opt['label']); ?>">
                        <?php if ($i >= 2): ?><button type="button" class="btn btn-outline-danger btn-sm poll-remove-opt"><i class="fa-solid fa-xmark"></i></button><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-outline-custom btn-sm mt-1" id="poll-add-opt"><i class="fa-solid fa-plus"></i> Add Option</button>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-gradient"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Poll'; ?></button>
                <a href="polls.php" class="btn btn-outline-custom">Cancel</a>
            </div>
        </div>
    </form>
</div>
<script>
document.getElementById('poll-add-opt').addEventListener('click', function() {
    var list = document.getElementById('poll-options-list');
    var n = list.children.length + 1;
    var row = document.createElement('div');
    row.className = 'd-flex gap-2 mb-2 poll-opt-row';
    row.innerHTML = '<input type="text" name="options[]" class="form-control form-control-custom" placeholder="Option ' + n + '" maxlength="255"><button type="button" class="btn btn-outline-danger btn-sm poll-remove-opt"><i class="fa-solid fa-xmark"></i></button>';
    list.appendChild(row);
    row.querySelector('.poll-remove-opt').addEventListener('click', function() { row.remove(); });
});
document.querySelectorAll('.poll-remove-opt').forEach(function(btn) {
    btn.addEventListener('click', function() { btn.closest('.poll-opt-row').remove(); });
});
</script>
<?php endif; ?>

<div class="glass-panel p-0">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Question</th>
                    <th>Total Votes</th>
                    <th>Ends</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($poll_list) === 0): ?>
                <tr><td colspan="5" class="text-secondary text-center py-4">No polls yet.</td></tr>
                <?php else: ?>
                <?php foreach ($poll_list as $p): ?>
                <tr>
                    <td class="text-secondary small"><?php echo htmlspecialchars(excerpt($p['post_title'] ?? '—', 40)); ?></td>
                    <td class="text-white"><?php echo htmlspecialchars(excerpt($p['question'], 60)); ?></td>
                    <td class="text-secondary"><?php echo (int) $p['total_votes']; ?></td>
                    <td class="text-secondary small"><?php echo !empty($p['ends_at']) ? date('M j, Y', strtotime($p['ends_at'])) : '—'; ?></td>
                    <td>
                        <a href="?action=edit&id=<?php echo (int) $p['id']; ?>" class="btn btn-outline-custom btn-sm me-1">Edit</a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this poll?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="admin_action" value="delete">
                            <input type="hidden" name="admin_id" value="<?php echo (int) $p['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
