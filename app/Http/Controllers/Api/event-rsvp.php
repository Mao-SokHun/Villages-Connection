<?php

secure_json_api(array(
    'methods'    => array('POST'),
    'login'      => true,
    'csrf'       => true,
    'rate_limit' => array('action' => 'event_rsvp', 'id' => client_rate_limit_id(), 'max' => 20, 'window' => 60),
));

$action   = isset($_POST['action']) ? trim($_POST['action']) : 'rsvp';
$event_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
$user_id  = (int) $_SESSION['user_id'];

if ($event_id <= 0) {
    echo json_encode(array('ok' => false, 'error' => 'Invalid event.'));
    exit;
}

if ($action === 'rsvp') {
    $result = toggle_event_rsvp($pdo, $event_id, $user_id);
    echo json_encode($result);
    exit;
}

echo json_encode(array('ok' => false, 'error' => 'Invalid action.'));
