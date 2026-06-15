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

$redirect = safe_redirect_path(isset($_GET['redirect']) ? $_GET['redirect'] : '', 'index.php');

header('Location: ' . app_url($redirect));
exit;
