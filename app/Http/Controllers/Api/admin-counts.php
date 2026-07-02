<?php

secure_json_api(array(
    'methods' => array('GET'),
    'login' => true,
    'csrf' => false,
    'rate_limit' => array('action' => 'admin_counts_api', 'id' => client_rate_limit_id(), 'max' => 120, 'window' => 300),
));

$user_id = (int) $_SESSION['user_id'];
$response = array(
    'success' => true,
    'notifications' => unread_notification_count($pdo, $user_id),
    'messages' => 0,
    'reports' => 0,
    'pending_posts' => 0,
    'pending_comments' => 0,
);

if (isAdmin()) {
    $counts = admin_unread_counts($pdo);
    $response['messages'] = $counts['messages'];
    $response['reports'] = $counts['reports'];
    $response['pending_posts'] = $counts['pending_posts'];
    $response['pending_comments'] = $counts['pending_comments'];
    $response['notifications'] = $counts['notifications'];
} else {
    $counts = author_unread_counts($pdo, $user_id);
    $response['pending_posts'] = $counts['pending_posts'];
    $response['pending_comments'] = $counts['pending_comments'];
    $response['notifications'] = $counts['notifications'];
}

echo json_encode($response);
