<?php

secure_json_api(array(
    'methods'    => array('POST'),
    'login'      => true,
    'csrf'       => true,
    'rate_limit' => array('action' => 'dm_api', 'id' => client_rate_limit_id(), 'max' => 60, 'window' => 60),
));

$action  = isset($_POST['action']) ? trim($_POST['action']) : '';
$user_id = (int) $_SESSION['user_id'];

// ---- SEND message ----
if ($action === 'send') {
    $to_user_id = isset($_POST['to_user_id']) ? (int) $_POST['to_user_id'] : 0;
    $body       = isset($_POST['body']) ? trim($_POST['body']) : '';

    if ($to_user_id <= 0 || $to_user_id === $user_id) {
        echo json_encode(array('ok' => false, 'error' => 'Invalid recipient.'));
        exit;
    }

    // Verify target user exists and is not banned
    $target_stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = :id AND COALESCE(is_banned, FALSE) = FALSE AND COALESCE(account_status,'active') != 'deleted'");
    $target_stmt->execute(array('id' => $to_user_id));
    $target = $target_stmt->fetch();
    if (!$target) {
        echo json_encode(array('ok' => false, 'error' => 'User not found.'));
        exit;
    }

    $conv = dm_get_or_create_conversation($pdo, $user_id, $to_user_id);
    if (!$conv) {
        echo json_encode(array('ok' => false, 'error' => 'Could not open conversation.'));
        exit;
    }

    $result = dm_send_message($pdo, (int) $conv['id'], $user_id, $body);
    if (!$result['ok']) {
        echo json_encode($result);
        exit;
    }

    // Notify recipient
    create_notification(
        $pdo,
        $to_user_id,
        'dm',
        'New message from ' . $_SESSION['user_name'],
        $_SESSION['user_name'] . ' sent you a message.',
        'messages.php?conv=' . (int) $conv['id'],
        false
    );

    echo json_encode(array(
        'ok'           => true,
        'message_id'   => $result['id'],
        'conv_id'      => (int) $conv['id'],
        'created_at'   => $result['created_at'],
        'sender_name'  => $_SESSION['user_name'],
    ));
    exit;
}

// ---- FETCH messages for a conversation ----
if ($action === 'fetch') {
    $conv_id = isset($_POST['conv_id']) ? (int) $_POST['conv_id'] : 0;
    if ($conv_id <= 0 || !dm_user_can_access_conversation($pdo, $conv_id, $user_id)) {
        echo json_encode(array('ok' => false, 'error' => 'Access denied.'));
        exit;
    }
    dm_mark_read($pdo, $conv_id, $user_id);
    $messages = dm_get_messages($pdo, $conv_id);
    echo json_encode(array('ok' => true, 'messages' => $messages));
    exit;
}

// ---- DELETE a message ----
if ($action === 'delete') {
    $msg_id = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
    $ok     = dm_delete_message($pdo, $msg_id, $user_id);
    echo json_encode(array('ok' => $ok));
    exit;
}

echo json_encode(array('ok' => false, 'error' => 'Invalid action.'));
