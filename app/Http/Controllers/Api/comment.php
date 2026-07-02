<?php

secure_json_api(array(
    'methods' => array('POST'),
    'login' => true,
    'csrf' => true,
    'rate_limit' => array('action' => 'comment_api', 'id' => client_rate_limit_id(), 'max' => 40, 'window' => 300),
));

$action = '';
if (isset($_POST['action'])) {
    $action = trim($_POST['action']);
}

if ($action == 'edit') {
    $comment_id = 0;
    if (isset($_POST['comment_id'])) {
        $comment_id = (int) $_POST['comment_id'];
    }
    $content = '';
    if (isset($_POST['content'])) {
        $content = trim($_POST['content']);
    }

    $result = update_own_comment($pdo, $comment_id, $content);
    if ($result['ok'] == false) {
        echo json_encode(array('success' => false, 'message' => $result['error']));
        exit;
    }

    $msg = 'Comment updated.';
    if (isset($result['status']) && $result['status'] == 'pending') {
        $msg = 'Comment updated and sent for approval again.';
    }
    echo json_encode(array('success' => true, 'message' => $msg, 'content' => htmlspecialchars($content), 'status' => $result['status']));
    exit;
}

if ($action == 'delete') {
    $comment_id = 0;
    if (isset($_POST['comment_id'])) {
        $comment_id = (int) $_POST['comment_id'];
    }

    $result = delete_own_comment($pdo, $comment_id);
    if ($result['ok'] == false) {
        echo json_encode(array('success' => false, 'message' => $result['error']));
        exit;
    }
    echo json_encode(array('success' => true, 'message' => 'Comment deleted.'));
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid action.'));
