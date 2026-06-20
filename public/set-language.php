<?php

require_once dirname(__DIR__) . '/bootstrap.php';

$locale = 'en';
if (isset($_GET['lang'])) {
    $locale = trim((string) $_GET['lang']);
}

set_user_locale($locale);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$redirect = safe_redirect_path(isset($_GET['redirect']) ? $_GET['redirect'] : '', 'index.php');

header('Location: ' . app_url($redirect));
exit;
