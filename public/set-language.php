<?php

require_once dirname(__DIR__) . '/bootstrap.php';

$lang = 'km';
if (isset($_GET['lang'])) {
    $lang = trim($_GET['lang']);
}
if (!in_array($lang, supported_locales(), true)) {
    $lang = 'km';
}

$_SESSION['locale'] = $lang;
setcookie('vc_locale', $lang, time() + (86400 * 365), '/', '', false, true);

$redirect = 'index.php';
if (isset($_GET['redirect'])) {
    $redirect = trim($_GET['redirect']);
}
if ($redirect == '' || strpos($redirect, '://') !== false || strpos($redirect, '..') !== false) {
    $redirect = 'index.php';
}

header('Location: ' . app_url($redirect));
exit;
