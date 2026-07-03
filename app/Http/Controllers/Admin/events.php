<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$errors = array();
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Handle delete
$admin_post = admin_post_action();
if ($admin_post && $admin_post['id'] > 0 && $admin_post['action'] === 'delete') {
    delete_event($pdo, (int) $admin_post['id']);
    log_activity($pdo, 'event.deleted', 'Event #' . (int) $admin_post['id']);
    setFlashMessage('success', 'Event deleted.');
    header('Location: events.php');
    exit;
}

// Handle create / update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['admin_action'])) {
    require_valid_csrf();
    $db_action   = isset($_POST['db_action']) ? trim($_POST['db_action']) : '';
    $title       = isset($_POST['title']) ? sanitize_plain_text_field($_POST['title'], 255) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $location    = isset($_POST['location']) ? sanitize_plain_text_field($_POST['location'], 255) : '';
    $event_date  = isset($_POST['event_date']) ? trim($_POST['event_date']) : '';
    $event_time  = isset($_POST['event_time']) ? trim($_POST['event_time']) : '';
    $end_date    = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
    $status      = isset($_POST['status']) ? trim($_POST['status']) : 'upcoming';
    $max_att     = isset($_POST['max_attendees']) ? (int) $_POST['max_attendees'] : 0;

    if ($title === '') $errors[] = 'Title is required.';
    if ($event_date === '') $errors[] = 'Event date is required.';
    if (!in_array($status, array('upcoming', 'ongoing', 'completed', 'cancelled'), true)) $status = 'upcoming';

    if (count($errors) === 0) {
        $data = array(
            'title'        => $title,
            'description'  => $description,
            'location'     => $location,
            'event_date'   => $event_date,
            'event_time'   => $event_time,
            'end_date'     => $end_date,
            'status'       => $status,
            'max_attendees'=> $max_att > 0 ? $max_att : null,
            'created_by'   => (int) $_SESSION['user_id'],
        );

        if ($db_action === 'edit' && $id > 0) {
            $result = update_event($pdo, $id, $data);
            log_activity($pdo, 'event.updated', 'Event #' . $id . ' ' . $title);
            setFlashMessage('success', 'Event updated.');
        } else {
            $result = create_event($pdo, $data);
            log_activity($pdo, 'event.created', $title);
            setFlashMessage('success', 'Event created.');
        }

        header('Location: events.php');
        exit;
    }
}

$event = null;
if ($action === 'edit' && $id > 0) {
    $event = get_event_by_id($pdo, $id);
}

$list = list_events_admin($pdo);

$page_title = 'Manage Events';
require_once ROOT_PATH . '/app/Views/layouts/admin-nav.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="text-white mb-0"><i class="fa-solid fa-calendar-star text-warning me-2"></i> Events</h2>
    <a href="?action=add" class="btn btn-gradient btn-sm"><i class="fa-solid fa-plus"></i> Add Event</a>
</div>

<?php if (count($errors) > 0): ?>
<?php render_user_alerts($errors, 'danger'); ?>
<?php endif; ?>
<?php echo flash_html(); ?>

<?php if ($action === 'add' || ($action === 'edit' && $id > 0)): ?>
<div class="glass-panel p-4 mb-4">
    <h5 class="text-white mb-3"><?php echo $action === 'edit' ? 'Edit Event' : 'Add Event'; ?></h5>
    <form method="POST" action="events.php<?php if ($action === 'edit') echo '?action=edit&id=' . $id; ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="db_action" value="<?php echo $action === 'edit' ? 'edit' : 'add'; ?>">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label form-label-custom">Title *</label>
                <input type="text" name="title" class="form-control form-control-custom" required maxlength="255"
                    value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label form-label-custom">Status</label>
                <select name="status" class="form-select form-select-custom">
                    <?php foreach (array('upcoming', 'ongoing', 'completed', 'cancelled') as $s): ?>
                    <option value="<?php echo $s; ?>" <?php if (($event['status'] ?? 'upcoming') === $s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label form-label-custom">Event Date *</label>
                <input type="date" name="event_date" class="form-control form-control-custom" required
                    value="<?php echo htmlspecialchars($event['event_date'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label form-label-custom">Start Time</label>
                <input type="time" name="event_time" class="form-control form-control-custom"
                    value="<?php echo htmlspecialchars($event['event_time'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label form-label-custom">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-custom"
                    value="<?php echo htmlspecialchars($event['end_date'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label form-label-custom">Max Attendees (0 = unlimited)</label>
                <input type="number" name="max_attendees" class="form-control form-control-custom" min="0"
                    value="<?php echo (int) ($event['max_attendees'] ?? 0); ?>">
            </div>
            <div class="col-12">
                <label class="form-label form-label-custom">Location</label>
                <input type="text" name="location" class="form-control form-control-custom" maxlength="255"
                    value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>">
            </div>
            <div class="col-12">
                <label class="form-label form-label-custom">Description</label>
                <textarea name="description" class="form-control form-control-custom" rows="4"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-gradient"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Event'; ?></button>
                <a href="events.php" class="btn btn-outline-custom">Cancel</a>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="glass-panel p-0">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Attendees</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($list['items']) === 0): ?>
                <tr><td colspan="6" class="text-secondary text-center py-4">No events yet.</td></tr>
                <?php else: ?>
                <?php foreach ($list['items'] as $ev): ?>
                <tr>
                    <td class="text-white"><?php echo htmlspecialchars($ev['title']); ?></td>
                    <td class="text-secondary small"><?php echo date('M j, Y', strtotime($ev['event_date'])); ?></td>
                    <td class="text-secondary small"><?php echo htmlspecialchars($ev['location'] ?? '—'); ?></td>
                    <td><span class="badge bg-<?php echo $ev['status'] === 'upcoming' ? 'success' : ($ev['status'] === 'cancelled' ? 'danger' : 'secondary'); ?>"><?php echo ucfirst($ev['status']); ?></span></td>
                    <td class="text-secondary"><?php echo (int) $ev['attendee_count']; ?></td>
                    <td>
                        <a href="?action=edit&id=<?php echo (int) $ev['id']; ?>" class="btn btn-outline-custom btn-sm me-1">Edit</a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this event?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="admin_action" value="delete">
                            <input type="hidden" name="admin_id" value="<?php echo (int) $ev['id']; ?>">
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
