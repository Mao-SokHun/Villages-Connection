<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once APP_PATH . '/Core/oauth.php';

if (isLoggedIn()) {
    redirect_to('index.php');
}

if (!oauth_is_configured('facebook')) {
    setFlashMessage('warning', 'Facebook login is not configured yet.');
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
    setFlashMessage('danger', 'Facebook login was cancelled.');
    redirect_to('login.php');
}

oauth_handle_callback('facebook', $code, $state);
