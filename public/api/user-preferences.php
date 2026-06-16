<?php
require_once dirname(__DIR__, 2) . '/bootstrap-api.php';

secure_json_api(array(
    'methods' => array('POST'),
    'login' => true,
    'csrf' => true,
    'rate_limit' => array('action' => 'user_preferences_api', 'id' => client_rate_limit_id(), 'max' => 120, 'window' => 300),
));

$key = '';
if (isset($_POST['key'])) {
    $key = trim($_POST['key']);
}
$value = '';
if (isset($_POST['value'])) {
    $value = trim($_POST['value']);
}

$allowed = array(
    'theme' => array('light', 'dark', 'system'),
    'density' => array('comfortable', 'compact'),
);
if (!isset($allowed[$key]) || !in_array($value, $allowed[$key], true)) {
    json_error('Invalid preference.', 422);
}

$field = $key === 'theme' ? 'ui_theme' : 'ui_density';
$sql = 'UPDATE users SET ' . $field . ' = :value, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'value' => $value,
        'id' => (int) $_SESSION['user_id'],
    ));
} catch (PDOException $e) {
    json_error('Preferences storage is not ready. Run database migration first.', 500);
}

if ($key === 'theme') {
    $_SESSION['ui_theme'] = $value;
} else {
    $_SESSION['ui_density'] = $value;
}

echo json_encode(array(
    'success' => true,
    'key' => $key,
    'value' => $value,
));
