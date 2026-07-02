<?php

secure_json_api(array(
    'methods' => array('POST'),
    'login' => true,
    'csrf' => true,
    'rate_limit' => array('action' => 'post_like', 'id' => client_rate_limit_id(), 'max' => 60, 'window' => 60),
));

$post_id = 0;
if (isset($_POST['post_id'])) {
    $post_id = (int) $_POST['post_id'];
}

$toggle = isset($_POST['toggle']) && $_POST['toggle'] == '1';
$user_id = (int) $_SESSION['user_id'];

$result = toggle_post_like($pdo, $post_id, $user_id, $toggle);
echo json_encode($result);
