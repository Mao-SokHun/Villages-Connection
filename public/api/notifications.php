<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (!isLoggedIn()) {
    echo json_encode(array('success' => false, 'count' => 0));
    exit;
}

$action = 'count';
if (isset($_GET['action'])) {
    $action = trim($_GET['action']);
}

$user_id = (int) $_SESSION['user_id'];

if ($action == 'mark_read' && isset($_POST['id'])) {
    require_valid_csrf();
    mark_notification_read($pdo, (int) $_POST['id'], $user_id);
    echo json_encode(array('success' => true, 'count' => unread_notification_count($pdo, $user_id)));
    exit;
}

if ($action == 'mark_all' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    require_valid_csrf();
    mark_all_notifications_read($pdo, $user_id);
    echo json_encode(array('success' => true, 'count' => 0));
    exit;
}

$items = array();
$rows = get_recent_notifications($pdo, $user_id, 6);
foreach ($rows as $row) {
    $items[] = array(
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'message' => excerpt($row['message'], 80),
        'link' => $row['link_url'],
        'is_read' => ($row['is_read'] === true || $row['is_read'] == 1 || $row['is_read'] === 't'),
        'icon' => notification_icon($row['type']),
        'time' => date('M j, H:i', strtotime($row['created_at']))
    );
}

echo json_encode(array(
    'success' => true,
    'count' => unread_notification_count($pdo, $user_id),
    'items' => $items
));
