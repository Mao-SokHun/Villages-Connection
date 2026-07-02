<?php
require_once APP_PATH . '/Models/oauth.php';

if (isLoggedIn()) {
    redirect_to('index.php');
}

if (!oauth_is_configured('google')) {
    setFlashMessage('warning', 'Google login is not configured yet.');
    redirect_to('login.php');
}

header('Location: ' . google_auth_url());
exit;
