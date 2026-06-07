<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once APP_PATH . '/Core/oauth.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

if (!oauth_is_configured('google')) {
    setFlashMessage('warning', 'Google login is not configured yet.');
    header('Location: ../login.php');
    exit;
}

$code = '';
if (isset($_GET['code'])) {
    $code = trim($_GET['code']);
}

$state = '';
if (isset($_GET['state'])) {
    $state = trim($_GET['state']);
}

if ($code == '') {
    setFlashMessage('danger', 'Google login was cancelled.');
    header('Location: ../login.php');
    exit;
}

oauth_handle_callback('google', $code, $state);
