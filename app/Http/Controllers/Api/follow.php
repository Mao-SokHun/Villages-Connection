<?php

secure_json_api(array(
    'methods' => array('POST'),
    'login' => true,
    'csrf' => true,
    'rate_limit' => array('action' => 'follow_api', 'id' => client_rate_limit_id(), 'max' => 40, 'window' => 300),
));

$action = '';
if (isset($_POST['action'])) {
    $action = trim($_POST['action']);
}

$user_id = 0;
if (isset($_POST['user_id'])) {
    $user_id = (int) $_POST['user_id'];
}

if ($user_id <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid user.'));
    exit;
}

if (!user_can_be_followed($pdo, $user_id)) {
    echo json_encode(array('success' => false, 'message' => 'This member is not available.'));
    exit;
}

if ($action == 'follow') {
    $result = follow_user($pdo, (int) $_SESSION['user_id'], $user_id);
    if ($result['ok'] == false) {
        echo json_encode(array('success' => false, 'message' => $result['error']));
        exit;
    }
    echo json_encode(array(
        'success' => true,
        'message' => 'You are now following this member.',
        'following' => true,
        'followers' => follower_count($pdo, $user_id)
    ));
    exit;
}

if ($action == 'unfollow') {
    unfollow_user($pdo, (int) $_SESSION['user_id'], $user_id);
    echo json_encode(array(
        'success' => true,
        'message' => 'Unfollowed.',
        'following' => false,
        'followers' => follower_count($pdo, $user_id)
    ));
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid action.'));
