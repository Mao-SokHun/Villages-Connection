<?php

require_once dirname(__DIR__) . '/bootstrap.php';

$lang = 'en';
if (isset($_GET['lang'])) {
    $lang = trim($_GET['lang']);
}
if (!in_array($lang, supported_locales(), true)) {
    $lang = 'en';
}

$_SESSION['locale'] = $lang;
$secure = request_is_https();
setcookie('vc_locale', $lang, array(
    'expires' => time() + (86400 * 365),
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
));

$redirect = safe_redirect_path(isset($_GET['redirect']) ? $_GET['redirect'] : '', 'index.php');

header('Location: ' . app_url($redirect));
exit;
