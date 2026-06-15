<?php
require_once dirname(__DIR__, 2) . '/bootstrap-api.php';

secure_json_api(array(
    'methods' => array('GET'),
    'login' => true,
    'csrf' => false,
    'rate_limit' => array('action' => 'push_vapid', 'id' => client_rate_limit_id(), 'max' => 60, 'window' => 300),
));

echo json_encode(array(
    'ok' => push_is_configured(),
    'publicKey' => push_vapid_public_key(),
));
