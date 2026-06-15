<?php
require_once dirname(__DIR__, 2) . '/bootstrap-api.php';

$action = 'list';
if (isset($_GET['action'])) {
    $action = trim($_GET['action']);
}

if ($action == 'mark_read' || $action == 'mark_all') {
    secure_json_api(array(
        'methods' => array('POST'),
        'login' => true,
        'csrf' => true,
        'rate_limit' => array('action' => 'notifications_api', 'id' => client_rate_limit_id(), 'max' => 60, 'window' => 300),
    ));
} else {
    secure_json_api(array(
        'methods' => array('GET'),
        'login' => true,
        'csrf' => false,
        'rate_limit' => array('action' => 'notifications_api', 'id' => client_rate_limit_id(), 'max' => 120, 'window' => 300),
    ));
}

$user_id = (int) $_SESSION['user_id'];

if ($action == 'mark_read' && isset($_POST['id'])) {
    mark_notification_read($pdo, (int) $_POST['id'], $user_id);
    echo json_encode(array('success' => true, 'count' => unread_notification_count($pdo, $user_id)));
    exit;
}

if ($action == 'mark_all') {
    mark_all_notifications_read($pdo, $user_id);
    echo json_encode(array('success' => true, 'count' => 0));
    exit;
}

$items = array();
$rows = get_recent_notifications($pdo, $user_id, 8);
foreach ($rows as $row) {
    $type = isset($row['type']) ? $row['type'] : '';
    $items[] = array(
        'id' => (int) $row['id'],
        'type' => $type,
        'type_label' => notification_type_label($type),
        'is_support' => notification_is_support_type($type) || $type == 'contact_message',
        'title' => $row['title'],
        'message' => excerpt($row['message'], 80),
        'link' => $row['link_url'],
        'is_read' => ($row['is_read'] === true || $row['is_read'] == 1 || $row['is_read'] === 't'),
        'icon' => notification_icon($type),
        'time' => date('M j, H:i', strtotime($row['created_at']))
    );
}

$count = unread_notification_count($pdo, $user_id);

echo json_encode(array(
    'success' => true,
    'count' => $count,
    'items' => $items
));
