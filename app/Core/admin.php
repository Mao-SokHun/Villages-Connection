<?php

function &admin_setting_cache()
{
    static $cache = null;
    return $cache;
}

function load_admin_settings($pdo)
{
    $cache = array();
    try {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        // Table may not exist before migration.
    }

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
}

function setting_is_enabled($key, $default = false)
{
    $val = get_setting($key, $default ? '1' : '0');
    return ($val == '1' || $val == 'true' || $val === true);
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
    return setting_is_enabled('comments_require_approval', true);
}

function resolve_post_status_for_author($requested_status)
{
    if ($requested_status != 'Published' && $requested_status != 'Draft') {
        $requested_status = 'Draft';
    }

    if (isAdmin()) {
        return $requested_status;
    }

    if (posts_require_approval() && $requested_status == 'Published') {
        return 'Pending';
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
    return 'bg-secondary';
}

function save_contact_message($pdo, $name, $email, $subject, $message)
{
    $sql = 'INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message) RETURNING id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message
    ));
    $id = (int) $stmt->fetchColumn();
    log_activity($pdo, 'contact.received', 'Message #' . $id . ' from ' . $email);
    return $id;
}

function save_content_report($pdo, $name, $email, $reason, $post_url, $details)
{
    $sql = 'INSERT INTO content_reports (reporter_name, reporter_email, reason, post_url, details) VALUES (:name, :email, :reason, :post_url, :details) RETURNING id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'name' => $name,
        'email' => $email,
        'reason' => $reason,
        'post_url' => $post_url,
        'details' => $details
    ));
    $id = (int) $stmt->fetchColumn();
    log_activity($pdo, 'report.received', 'Report #' . $id . ' — ' . $reason);
    return $id;
}

function admin_unread_counts($pdo)
{
    $counts = array('messages' => 0, 'reports' => 0, 'pending_posts' => 0, 'pending_comments' => 0, 'notifications' => 0);

    try {
        $counts['messages'] = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
        $counts['reports'] = (int) $pdo->query("SELECT COUNT(*) FROM content_reports WHERE status = 'open'")->fetchColumn();
        $counts['pending_posts'] = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'Pending'")->fetchColumn();
        $counts['pending_comments'] = (int) $pdo->query("SELECT COUNT(*) FROM post_comments WHERE status = 'pending'")->fetchColumn();
        if (isLoggedIn()) {
            $counts['notifications'] = unread_notification_count($pdo, (int) $_SESSION['user_id']);
        }
    } catch (PDOException $e) {
        // Tables may not exist yet.
    }

    return $counts;
}

function get_active_announcement($pdo)
{
    try {
        $sql = "SELECT * FROM announcements
                WHERE is_active = TRUE
                AND (starts_at IS NULL OR starts_at <= CURRENT_TIMESTAMP)
                AND (ends_at IS NULL OR ends_at >= CURRENT_TIMESTAMP)
                ORDER BY id DESC LIMIT 1";
        return $pdo->query($sql)->fetch();
    } catch (PDOException $e) {
        return null;
    }
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

    $onclick = '';
    if ($confirm != '') {
        $onclick = ' onclick="return confirm(' . json_encode($confirm) . ')"';
    }

    echo '<form method="POST" action="' . htmlspecialchars($page) . '" class="d-inline admin-action-form">';
    echo csrf_field();
    echo '<input type="hidden" name="admin_action" value="' . htmlspecialchars($action) . '">';
    echo '<input type="hidden" name="admin_id" value="' . (int) $id . '">';
    if ($value != '') {
        echo '<input type="hidden" name="admin_value" value="' . htmlspecialchars($value) . '">';
    }
    echo '<button type="submit" class="' . htmlspecialchars($class) . '" title="' . htmlspecialchars($title) . '"' . $onclick . '>';
    if ($icon != '') {
        echo '<i class="' . htmlspecialchars($icon) . '"></i>';
    }
    if ($label != '') {
        echo ' ' . htmlspecialchars($label);
    }
    echo '</button></form>';
}
