<?php

require_once dirname(__DIR__) . '/bootstrap.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

session_start();
setFlashMessage('info', 'You have been successfully logged out.');

header('Location: login.php');
exit;

