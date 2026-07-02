<?php

function &admin_setting_cache()
{
    static $cache = null;
    return $cache;
}

function load_admin_settings($pdo)
{
    $cached = app_cache_get('site_settings', 300);
    if (is_array($cached)) {
        $ref = &admin_setting_cache();
        $ref = $cached;
        return $cached;
    }

    $cache = array();
    try {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        // Table may not exist before migration.
    }

    app_cache_put('site_settings', $cache);

    $ref = &admin_setting_cache();
    $ref = $cache;
    return $cache;
}

function get_setting($key, $default = '')
{
    global $pdo;

    $cache = admin_setting_cache();
    if ($cache === null && isset($pdo)) {
        load_admin_settings($pdo);
        $cache = admin_setting_cache();
    }

    if (is_array($cache) && array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    return $default;
}

function set_setting($pdo, $key, $value)
{
    $sql = "INSERT INTO site_settings (setting_key, setting_value, updated_at)
            VALUES (:k, :v, CURRENT_TIMESTAMP)
            ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = CURRENT_TIMESTAMP";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('k' => $key, 'v' => $value));

    $cache = &admin_setting_cache();
    if (!is_array($cache)) {
        $cache = array();
    }
    $cache[$key] = $value;
    app_cache_put('site_settings', $cache);
}

function setting_is_enabled($key, $default = false)
{
    $val = get_setting($key, $default ? '1' : '0');
    return ($val == '1' || $val == 'true' || $val === true);
}

function media_file_in_use($pdo, $filename, $subdir)
{
    if ($filename == '') {
        return false;
    }

    if ($subdir == 'avatars') {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE avatar = :file');
        $stmt->execute(array('file' => $filename));
        return (int) $stmt->fetchColumn() > 0;
    }

    if ($subdir == 'videos') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE video_url = :file AND video_type = 'upload'");
        $stmt->execute(array('file' => $filename));
        return (int) $stmt->fetchColumn() > 0;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE image_url = :file');
    $stmt->execute(array('file' => $filename));
    return (int) $stmt->fetchColumn() > 0;
}

function admin_request_ip()
{
    if (isset($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return '';
}

function log_activity($pdo, $action, $details = '')
{
    $user_id = null;
    if (isset($_SESSION['user_id'])) {
        $user_id = (int) $_SESSION['user_id'];
    }

    try {
        $sql = 'INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (:uid, :action, :details, :ip)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            'uid' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip' => admin_request_ip()
        ));
    } catch (PDOException $e) {
        // Ignore if table missing.
    }
}

function user_is_banned($user)
{
    if (!is_array($user)) {
        return false;
    }
    if (!isset($user['is_banned'])) {
        return false;
    }
    return ($user['is_banned'] === true || $user['is_banned'] == 1 || $user['is_banned'] === 't');
}

function user_is_publicly_visible($user)
{
    if (!is_array($user)) {
        return false;
    }
    if (user_is_deleted($user) || user_is_banned($user)) {
        return false;
    }
    return true;
}

function sql_hide_inactive_authors($user_alias = 'u')
{
    return " AND ($user_alias.id IS NULL OR (COALESCE($user_alias.account_status, 'active') != 'deleted' AND COALESCE($user_alias.is_banned, FALSE) = FALSE))";
}

function user_account_status($user)
{
    if (!is_array($user) || !isset($user['account_status']) || $user['account_status'] === '') {
        return 'active';
    }
    return $user['account_status'];
}

function user_is_deleted($user)
{
    return user_account_status($user) === 'deleted';
}

function soft_delete_post($pdo, $post_id)
{
    $stmt = $pdo->prepare("UPDATE posts SET status = 'Deleted', is_featured = FALSE, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute(array('id' => (int) $post_id));
    return $stmt->rowCount() > 0;
}

function ban_user($pdo, $user_id, $reason = '')
{
    $sql = 'UPDATE users SET is_banned = TRUE, banned_reason = :reason, banned_at = CURRENT_TIMESTAMP WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => $user_id, 'reason' => $reason));
    log_activity($pdo, 'user.banned', 'User #' . $user_id . ': ' . $reason);
}

function unban_user($pdo, $user_id)
{
    $sql = 'UPDATE users SET is_banned = FALSE, banned_reason = \'\', banned_at = NULL WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => $user_id));
    log_activity($pdo, 'user.unbanned', 'User #' . $user_id);
}

function activate_user_account($pdo, $user_id)
{
    $sql = "UPDATE users SET account_status = 'active', deleted_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND account_status = 'deleted'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => (int) $user_id));
    if ($stmt->rowCount() > 0) {
        log_activity($pdo, 'user.activated', 'User #' . (int) $user_id);
        return true;
    }
    return false;
}

function posts_require_approval()
{
    return setting_is_enabled('require_post_approval', false);
}

function registration_is_enabled()
{
    return setting_is_enabled('registration_enabled', true);
}

function maintenance_mode_active()
{
    return setting_is_enabled('maintenance_mode', false);
}

function comments_are_enabled()
{
    return setting_is_enabled('comments_enabled', true);
}

function comments_require_approval()
{
    return setting_is_enabled('comments_require_approval', false);
}

function email_verification_required()
{
    return setting_is_enabled('require_email_verification', false);
}

function resolve_post_status_for_author($requested_status)
{
    if ($requested_status != 'Published' && $requested_status != 'Draft') {
        $requested_status = 'Draft';
    }

    return $requested_status;
}

function post_status_label($status)
{
    if ($status == 'Pending') {
        return 'Pending';
    }
    if ($status == 'Rejected') {
        return 'Rejected';
    }
    if ($status == 'Published') {
        return 'Published';
    }
    if ($status == 'Archived') {
        return 'Archived';
    }
    return 'Draft';
}

function post_status_badge_class($status)
{
    if ($status == 'Published') {
        return 'bg-success';
    }
    if ($status == 'Pending') {
        return 'bg-warning text-dark';
    }
    if ($status == 'Rejected') {
        return 'bg-danger';
    }
    if ($status == 'Archived') {
        return 'bg-dark';
    }
    return 'bg-secondary';
}

function save_contact_message($pdo, $name, $email, $subject, $message, $user_id = 0)
{
    $sql = 'INSERT INTO contact_messages (name, email, subject, message, user_id) VALUES (:name, :email, :subject, :message, :user_id) RETURNING id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'user_id' => $user_id > 0 ? (int) $user_id : null
    ));
    $id = (int) $stmt->fetchColumn();
    log_activity($pdo, 'contact.received', 'Message #' . $id . ' from ' . $email);
    notify_admins_contact_message($pdo, $id, $name, $email, $subject);
    return $id;
}

function save_content_report($pdo, $name, $email, $reason, $post_url, $details, $user_id = 0)
{
    $sql = 'INSERT INTO content_reports (reporter_name, reporter_email, reason, post_url, details, user_id) VALUES (:name, :email, :reason, :post_url, :details, :user_id) RETURNING id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'name' => $name,
        'email' => $email,
        'reason' => $reason,
        'post_url' => $post_url,
        'details' => $details,
        'user_id' => $user_id > 0 ? (int) $user_id : null
    ));
    $id = (int) $stmt->fetchColumn();
    log_activity($pdo, 'report.received', 'Report #' . $id . ' — ' . $reason);
    notify_admins_content_report($pdo, $id, $reason);
    return $id;
}

function save_incident_report($pdo, $payload)
{
    $sql = "INSERT INTO incident_reports (
                user_id, reporter_name, reporter_email, incident_type, priority, title, details,
                village_name, location_text, latitude, longitude
            ) VALUES (
                :user_id, :reporter_name, :reporter_email, :incident_type, :priority, :title, :details,
                :village_name, :location_text, :latitude, :longitude
            ) RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'user_id' => isset($payload['user_id']) && (int) $payload['user_id'] > 0 ? (int) $payload['user_id'] : null,
        'reporter_name' => $payload['reporter_name'],
        'reporter_email' => $payload['reporter_email'],
        'incident_type' => $payload['incident_type'],
        'priority' => $payload['priority'],
        'title' => $payload['title'],
        'details' => $payload['details'],
        'village_name' => isset($payload['village_name']) ? $payload['village_name'] : '',
        'location_text' => isset($payload['location_text']) ? $payload['location_text'] : '',
        'latitude' => isset($payload['latitude']) && $payload['latitude'] !== '' ? $payload['latitude'] : null,
        'longitude' => isset($payload['longitude']) && $payload['longitude'] !== '' ? $payload['longitude'] : null,
    ));
    $id = (int) $stmt->fetchColumn();
    log_activity($pdo, 'incident.reported', 'Incident #' . $id . ' — ' . excerpt($payload['title'], 80));
    notify_admins_incident_report($pdo, $id, $payload['incident_type'], $payload['priority'], $payload['title']);
    return $id;
}

function admin_unread_counts($pdo)
{
    $counts = array('messages' => 0, 'reports' => 0, 'incidents' => 0, 'pending_posts' => 0, 'pending_comments' => 0, 'notifications' => 0);

    if (!isLoggedIn()) {
        return $counts;
    }

    $user_id = (int) $_SESSION['user_id'];
    $cache_key = 'admin_unread_counts_' . $user_id;
    $cached = app_cache_get($cache_key, 30);
    if (is_array($cached)) {
        return $cached;
    }

    try {
        $sql = "SELECT
            (SELECT COUNT(*) FROM contact_messages WHERE status = 'new') AS messages,
            (SELECT COUNT(*) FROM content_reports WHERE status = 'open') AS reports,
            (SELECT COUNT(*) FROM incident_reports WHERE status = 'open') AS incidents,
            (SELECT COUNT(*) FROM posts WHERE status = 'Pending') AS pending_posts,
            (SELECT COUNT(*) FROM post_comments WHERE status = 'pending') AS pending_comments,
            (SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = FALSE) AS notifications";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $user_id));
        $row = $stmt->fetch();
        if ($row) {
            $counts['messages'] = (int) $row['messages'];
            $counts['reports'] = (int) $row['reports'];
            $counts['incidents'] = (int) $row['incidents'];
            $counts['pending_posts'] = (int) $row['pending_posts'];
            $counts['pending_comments'] = (int) $row['pending_comments'];
            $counts['notifications'] = (int) $row['notifications'];
        }
    } catch (PDOException $e) {
        // Tables may not exist yet.
    }

    app_cache_put($cache_key, $counts);

    return $counts;
}

function invalidate_admin_unread_counts_cache()
{
    if (!isLoggedIn()) {
        return;
    }
    app_cache_forget('admin_unread_counts_' . (int) $_SESSION['user_id']);
}

function get_active_announcement($pdo)
{
    static $request_cache = null;
    static $loaded = false;
    if ($loaded) {
        return $request_cache;
    }
    $loaded = true;

    $cached = app_cache_get('active_announcement', 120);
    if (is_array($cached) && array_key_exists('row', $cached)) {
        $request_cache = $cached['row'];
        return $request_cache;
    }

    try {
        $sql = "SELECT * FROM announcements
                WHERE is_active = TRUE
                AND (starts_at IS NULL OR starts_at <= CURRENT_TIMESTAMP)
                AND (ends_at IS NULL OR ends_at >= CURRENT_TIMESTAMP)
                ORDER BY id DESC LIMIT 1";
        $request_cache = $pdo->query($sql)->fetch();
    } catch (PDOException $e) {
        $request_cache = null;
    }

    app_cache_put('active_announcement', array('row' => $request_cache));

    return $request_cache;
}

function admin_can_manage_post($post)
{
    if (isAdmin()) {
        return true;
    }
    if (!is_array($post) || !isset($post['user_id'])) {
        return false;
    }
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    return (int) $post['user_id'] == (int) $_SESSION['user_id'];
}

function scan_upload_directory($subdir = '')
{
    $path = upload_path($subdir);
    $files = array();
    $items = scandir($path);
    if (!is_array($items)) {
        return $files;
    }

    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        $full = $path . $item;
        if (!is_file($full)) {
            continue;
        }
        $files[] = array(
            'name' => $item,
            'subdir' => $subdir,
            'size' => filesize($full),
            'modified' => filemtime($full),
            'url' => media_url($item, $subdir)
        );
    }

    usort($files, function ($a, $b) {
        return $b['modified'] - $a['modified'];
    });

    return $files;
}

function format_file_size($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 1) . ' MB';
}

function admin_export_csv($filename, $headers, $rows)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

function apply_email_template($template, $vars)
{
    $output = $template;
    foreach ($vars as $key => $value) {
        $output = str_replace('{' . $key . '}', $value, $output);
    }
    return $output;
}

function check_maintenance_mode()
{
    if (!maintenance_mode_active()) {
        return;
    }

    $script = basename($_SERVER['SCRIPT_NAME']);
    $allowed = array('login.php', 'logout.php', '404.php');
    if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
        return;
    }
    if (in_array($script, $allowed)) {
        return;
    }
    if (isLoggedIn() && isAdmin()) {
        return;
    }

    http_response_code(503);
    $message = get_setting('maintenance_message', 'Site is under maintenance.');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Maintenance - ' . htmlspecialchars(SITE_NAME) . '</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '</head><body class="bg-dark text-white d-flex align-items-center" style="min-height:100vh">';
    echo '<div class="container text-center py-5"><h1 class="mb-3">' . htmlspecialchars(SITE_NAME) . '</h1>';
    echo '<p class="lead text-secondary">' . htmlspecialchars($message) . '</p>';
    echo '<a href="login.php" class="btn btn-primary mt-3">Admin Sign In</a></div></body></html>';
    exit;
}

function ensure_admin_tables_loaded($pdo)
{
    load_admin_settings($pdo);
    check_maintenance_mode();
}

function admin_post_action()
{
    if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['admin_action'])) {
        return null;
    }
    require_valid_csrf();
    return array(
        'action' => trim($_POST['admin_action']),
        'id' => isset($_POST['admin_id']) ? (int) $_POST['admin_id'] : 0,
        'value' => isset($_POST['admin_value']) ? trim($_POST['admin_value']) : ''
    );
}

function render_admin_action_button($page, $action, $id, $options = array())
{
    $class = 'btn btn-sm btn-outline-custom';
    if (isset($options['class'])) {
        $class = $options['class'];
    }
    $icon = '';
    if (isset($options['icon'])) {
        $icon = $options['icon'];
    }
    $label = '';
    if (isset($options['label'])) {
        $label = $options['label'];
    }
    $title = $action;
    if (isset($options['title'])) {
        $title = $options['title'];
    }
    $confirm = '';
    if (isset($options['confirm'])) {
        $confirm = $options['confirm'];
    }
    $value = '';
    if (isset($options['value'])) {
        $value = $options['value'];
    }

    $confirm_title = 'Confirm';
    if (isset($options['confirm_title'])) {
        $confirm_title = $options['confirm_title'];
    } elseif ($action == 'delete') {
        $confirm_title = 'Delete?';
    }

    $form_attrs = 'class="d-inline admin-action-form"';
    if ($confirm != '') {
        $form_attrs = $form_attrs . ' data-confirm="' . htmlspecialchars($confirm, ENT_QUOTES) . '"';
        $form_attrs = $form_attrs . ' data-confirm-title="' . htmlspecialchars($confirm_title, ENT_QUOTES) . '"';
        if ($action == 'delete') {
            $form_attrs = $form_attrs . ' data-confirm-danger="1"';
        }
    }

    echo '<form method="POST" action="' . htmlspecialchars($page) . '" ' . $form_attrs . '>';
    echo csrf_field();
    echo '<input type="hidden" name="admin_action" value="' . htmlspecialchars($action) . '">';
    echo '<input type="hidden" name="admin_id" value="' . (int) $id . '">';
    if ($value != '') {
        echo '<input type="hidden" name="admin_value" value="' . htmlspecialchars($value) . '">';
    }
    echo '<button type="submit" class="' . htmlspecialchars($class) . '" title="' . htmlspecialchars($title) . '">';
    if ($icon != '') {
        echo '<i class="' . htmlspecialchars($icon) . '"></i>';
    }
    if ($label != '') {
        echo ' ' . htmlspecialchars($label);
    }
    echo '</button></form>';
}
