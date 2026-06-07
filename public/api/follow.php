<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (!isLoggedIn()) {
    echo json_encode(array('success' => false, 'message' => 'Please sign in.'));
    exit;
}

require_valid_csrf();

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
