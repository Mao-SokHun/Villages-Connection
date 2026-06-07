<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once APP_PATH . '/Core/oauth.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

if (!oauth_is_configured('facebook')) {
    setFlashMessage('warning', 'Facebook login is not configured yet.');
    header('Location: ../login.php');
    exit;
}

header('Location: ' . facebook_auth_url());
exit;
