<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$errors = array();
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$admin_post = admin_post_action();
if ($admin_post && $admin_post['id'] > 0) {
    if ($admin_post['action'] === 'delete') {
        $pdo->prepare('DELETE FROM community_challenges WHERE id = :id')->execute(array('id' => (int) $admin_post['id']));
        log_activity($pdo, 'challenge.deleted', 'Challenge #' . (int) $admin_post['id']);
        setFlashMessage('success', 'Challenge deleted.');
        header('Location: challenges.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_action'])) {
    require_valid_csrf();
    $db_action = isset($_POST['db_action']) ? trim($_POST['db_action']) : '';
    $title = isset($_POST['title']) ? sanitize_plain_text_field($_POST['title'], 180) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $goal_type = isset($_POST['goal_type']) ? trim($_POST['goal_type']) : 'posts';
    $goal_target = isset($_POST['goal_target']) ? (int) $_POST['goal_target'] : 10;
    $reward_text = isset($_POST['reward_text']) ? sanitize_plain_text_field($_POST['reward_text'], 255) : '';
    $start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
    $end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

    if ($title === '') $errors[] = 'Title is required';
    if ($description === '') $errors[] = 'Description is required';
    if (!in_array($goal_type, array('posts', 'knowledge_posts', 'helpers'), true)) $goal_type = 'posts';
    if ($goal_target < 1) $goal_target = 1;
    if (!in_array($status, array('draft', 'active', 'completed', 'closed'), true)) $status = 'draft';
    if ($start_date === '' || $end_date === '') $errors[] = 'Start and end date are required';
    if ($start_date !== '' && $end_date !== '' && strtotime($start_date) > strtotime($end_date)) $errors[] = 'End date must be after start date';

    if (count($errors) === 0) {
        $slug = slugify($title);
        if ($slug === '') $slug = 'challenge-' . time();
        $check = $pdo->prepare('SELECT COUNT(*) FROM community_challenges WHERE slug = :slug' . ($db_action === 'edit' ? ' AND id != :id' : ''));
        $params = array('slug' => $slug);
        if ($db_action === 'edit') $params['id'] = $id;
        $check->execute($params);
        if ((int) $check->fetchColumn() > 0) $slug .= '-' . time();

        if ($db_action === 'edit' && $id > 0) {
            $sql = "UPDATE community_challenges
                    SET title=:title, slug=:slug, description=:description, goal_type=:goal_type, goal_target=:goal_target,
                        reward_text=:reward_text, start_date=:start_date, end_date=:end_date, status=:status, updated_at=CURRENT_TIMESTAMP
                    WHERE id=:id";
            $pdo->prepare($sql)->execute(array(
                'title' => $title, 'slug' => $slug, 'description' => $description, 'goal_type' => $goal_type,
                'goal_target' => $goal_target, 'reward_text' => $reward_text, 'start_date' => $start_date,
                'end_date' => $end_date, 'status' => $status, 'id' => $id
            ));
            log_activity($pdo, 'challenge.updated', 'Challenge #' . $id . ' ' . $title);
            setFlashMessage('success', 'Challenge updated.');
        } else {
            $sql = "INSERT INTO community_challenges (title, slug, description, goal_type, goal_target, reward_text, start_date, end_date, status, created_by)
                    VALUES (:title, :slug, :description, :goal_type, :goal_target, :reward_text, :start_date, :end_date, :status, :created_by)";
            $pdo->prepare($sql)->execute(array(
                'title' => $title, 'slug' => $slug, 'description' => $description, 'goal_type' => $goal_type,
                'goal_target' => $goal_target, 'reward_text' => $reward_text, 'start_date' => $start_date,
                'end_date' => $end_date, 'status' => $status, 'created_by' => (int) $_SESSION['user_id']
            ));
            log_activity($pdo, 'challenge.created', $title);
            setFlashMessage('success', 'Challenge created.');
        }
        header('Location: challenges.php');
        exit;
    }
}

$challenge = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM community_challenges WHERE id = :id');
    $stmt->execute(array('id' => $id));
    $challenge = $stmt->fetch();
}

$all = array();
try {
    $all = $pdo->query("SELECT * FROM community_challenges ORDER BY end_date DESC, id DESC")->fetchAll();
} catch (Exception $e) {
    $all = array();
}

$page_title = 'Challenges';
$admin_active = 'challenges';
require_once ROOT_PATH . '/app/Views/layouts/header.php';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="glass-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-white mb-0"><i class="fa-solid fa-trophy text-warning me-2"></i>Community Challenges</h3>
        <a href="challenges.php?action=add" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> New Challenge</a>
    </div>

    <?php if ($action === 'add' || ($action === 'edit' && $challenge)): ?>
    <?php if (count($errors) > 0): ?>
    <?php render_user_alerts($errors, 'danger'); ?>
    <?php endif; ?>
    <form method="POST" class="row g-3 mb-4">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="db_action" value="<?php echo $action; ?>">
        <div class="col-md-6"><label class="form-label form-label-custom">Title</label><input name="title" class="form-control form-control-custom" required value="<?php echo htmlspecialchars($challenge['title'] ?? ''); ?>"></div>
        <div class="col-md-3"><label class="form-label form-label-custom">Goal Type</label>
            <select name="goal_type" class="form-select form-control-custom">
                <option value="posts" <?php if (($challenge['goal_type'] ?? '') === 'posts') echo 'selected'; ?>>Posts</option>
                <option value="knowledge_posts" <?php if (($challenge['goal_type'] ?? '') === 'knowledge_posts') echo 'selected'; ?>>Knowledge Posts</option>
                <option value="helpers" <?php if (($challenge['goal_type'] ?? '') === 'helpers') echo 'selected'; ?>>Helpers</option>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label form-label-custom">Goal Target</label><input type="number" min="1" name="goal_target" class="form-control form-control-custom" value="<?php echo (int) ($challenge['goal_target'] ?? 10); ?>"></div>
        <div class="col-12"><label class="form-label form-label-custom">Description</label><textarea name="description" rows="3" class="form-control form-control-custom" required><?php echo htmlspecialchars($challenge['description'] ?? ''); ?></textarea></div>
        <div class="col-md-6"><label class="form-label form-label-custom">Reward</label><input name="reward_text" class="form-control form-control-custom" value="<?php echo htmlspecialchars($challenge['reward_text'] ?? ''); ?>"></div>
        <div class="col-md-2"><label class="form-label form-label-custom">Start</label><input type="date" name="start_date" class="form-control form-control-custom" value="<?php echo htmlspecialchars($challenge['start_date'] ?? ''); ?>"></div>
        <div class="col-md-2"><label class="form-label form-label-custom">End</label><input type="date" name="end_date" class="form-control form-control-custom" value="<?php echo htmlspecialchars($challenge['end_date'] ?? ''); ?>"></div>
        <div class="col-md-2"><label class="form-label form-label-custom">Status</label>
            <select name="status" class="form-select form-control-custom">
                <option value="draft" <?php if (($challenge['status'] ?? 'active') === 'draft') echo 'selected'; ?>>Draft</option>
                <option value="active" <?php if (($challenge['status'] ?? 'active') === 'active') echo 'selected'; ?>>Active</option>
                <option value="completed" <?php if (($challenge['status'] ?? '') === 'completed') echo 'selected'; ?>>Completed</option>
                <option value="closed" <?php if (($challenge['status'] ?? '') === 'closed') echo 'selected'; ?>>Closed</option>
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-gradient btn-sm" type="submit"><i class="fa-solid fa-save"></i> Save</button>
            <a href="challenges.php" class="btn btn-outline-custom btn-sm">Cancel</a>
        </div>
    </form>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-custom table-hover mb-0">
            <thead><tr><th>Title</th><th>Goal</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($all as $row): ?>
                <tr>
                    <td class="table-cell-title"><?php echo htmlspecialchars($row['title']); ?></td>
                    <td class="small table-cell-muted"><?php echo (int) $row['goal_target']; ?> <?php echo htmlspecialchars($row['goal_type']); ?></td>
                    <td class="small table-cell-muted"><?php echo htmlspecialchars($row['start_date']); ?> → <?php echo htmlspecialchars($row['end_date']); ?></td>
                    <td><span class="badge <?php echo $row['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                    <td class="text-end">
                        <a href="challenges.php?action=edit&id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-outline-custom"><i class="fa-solid fa-pen"></i></a>
                        <?php render_admin_action_button('challenges.php', 'delete', $row['id'], array('class' => 'btn btn-sm btn-outline-custom text-danger', 'icon' => 'fa-solid fa-trash', 'title' => 'Delete', 'confirm' => 'Delete this challenge?')); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($all) === 0): ?>
                <tr><td colspan="5" class="text-secondary small">No challenges yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/Views/layouts/footer.php'; ?>
