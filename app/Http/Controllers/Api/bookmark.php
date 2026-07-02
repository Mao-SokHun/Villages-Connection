<?php

secure_json_api(array(
    'methods' => array('POST'),
    'login' => true,
    'csrf' => true,
    'rate_limit' => array('action' => 'bookmark_api', 'id' => client_rate_limit_id(), 'max' => 80, 'window' => 300),
));

$post_id = 0;
if (isset($_POST['post_id'])) {
    $post_id = (int) $_POST['post_id'];
}

$result = toggle_post_bookmark($pdo, (int) $_SESSION['user_id'], $post_id);
echo json_encode($result);
