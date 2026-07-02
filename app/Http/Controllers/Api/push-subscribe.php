<?php

secure_json_api(array(
    'methods' => array('POST'),
    'login' => true,
    'csrf' => true,
    'rate_limit' => array('action' => 'push_api', 'id' => client_rate_limit_id(), 'max' => 40, 'window' => 300),
));

$action = '';
if (isset($_POST['action'])) {
    $action = trim($_POST['action']);
}

if (!push_is_configured()) {
    echo json_encode(array('ok' => false, 'error' => 'push_not_configured'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

if ($action == 'subscribe') {
    $endpoint = '';
    $p256dh = '';
    $auth_key = '';
    if (isset($_POST['endpoint'])) {
        $endpoint = trim($_POST['endpoint']);
    }
    if (isset($_POST['p256dh'])) {
        $p256dh = trim($_POST['p256dh']);
    }
    if (isset($_POST['auth'])) {
        $auth_key = trim($_POST['auth']);
    }

    if ($endpoint == '' || $p256dh == '' || $auth_key == '') {
        echo json_encode(array('ok' => false, 'error' => 'invalid_subscription'));
        exit;
    }

    if (strlen($endpoint) > 2000) {
        echo json_encode(array('ok' => false, 'error' => 'invalid_subscription'));
        exit;
    }

    $ua = '';
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $ua = $_SERVER['HTTP_USER_AGENT'];
    }

    try {
        push_save_subscription($pdo, $user_id, $endpoint, $p256dh, $auth_key, $ua);
        echo json_encode(array('ok' => true, 'subscribed' => true));
    } catch (Exception $e) {
        echo json_encode(array('ok' => false, 'error' => 'save_failed'));
    }
    exit;
}

if ($action == 'unsubscribe') {
    $endpoint = '';
    if (isset($_POST['endpoint'])) {
        $endpoint = trim($_POST['endpoint']);
    }
    if ($endpoint != '') {
        push_remove_subscription($pdo, $user_id, $endpoint);
    }
    echo json_encode(array('ok' => true, 'subscribed' => false));
    exit;
}

echo json_encode(array('ok' => false, 'error' => 'invalid_action'));
