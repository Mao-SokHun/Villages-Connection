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

header('Location: ' . facebook_auth_url());
exit;
