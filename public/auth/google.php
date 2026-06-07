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

header('Location: ' . google_auth_url());
exit;
