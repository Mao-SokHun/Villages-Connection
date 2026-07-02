<?php
require_once APP_PATH . '/Models/oauth.php';

if (isLoggedIn()) {
    redirect_to('index.php');
}

if (!oauth_is_configured('google')) {
    setFlashMessage('warning', 'Google login is not configured yet.');
    redirect_to('login.php');
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
    redirect_to('login.php');
}

oauth_handle_callback('google', $code, $state);
