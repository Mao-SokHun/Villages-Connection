<?php

if (session_status() == PHP_SESSION_NONE) {
    $session_secure = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $session_secure = true;
    }

    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => $session_secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ));

    ini_set('session.use_strict_mode', '1');
    session_start();
}

function loadEnv($path)
{
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        $name = trim($parts[0]);
        $value = trim($parts[1]);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    return true;
}

loadEnv(__DIR__ . '/../.env');

$db_host = getenv('DB_HOST');
if ($db_host == false || $db_host == '') {
    $db_host = '127.0.0.1';
}
define('DB_HOST', $db_host);

$db_port = getenv('DB_PORT');
if ($db_port == false || $db_port == '') {
    $db_port = '5432';
}
define('DB_PORT', $db_port);

$db_name = getenv('DB_DATABASE');
if ($db_name == false || $db_name == '') {
    $db_name = 'project_cms';
}
define('DB_NAME', $db_name);

$db_user = getenv('DB_USERNAME');
if ($db_user == false || $db_user == '') {
    $db_user = 'postgres';
}
define('DB_USER', $db_user);

$db_pass = getenv('DB_PASSWORD');
if ($db_pass == false || $db_pass == '') {
    $db_pass = '4944';
}
define('DB_PASS', $db_pass);

define('SITE_NAME', 'Village Connect');
define('SITE_TAGLINE', 'Post photos, videos, and updates from your community');
define('SITE_DESC', 'Share everyday moments, stories, and creative content — like Facebook, Instagram, and Twitter, built for your village');

$site_contact = getenv('SITE_CONTACT_EMAIL');
if ($site_contact == false || $site_contact == '') {
    $site_contact = 'admin@admin.com';
}
define('SITE_CONTACT_EMAIL', $site_contact);

$app_url = getenv('APP_URL');
if ($app_url == false || $app_url == '') {
    $app_url = '';
}
define('APP_URL', $app_url);

$app_debug = getenv('APP_DEBUG');
define('APP_DEBUG', ($app_debug === 'true' || $app_debug === '1'));

function setFlashMessage($type, $message)
{
    $_SESSION['flash_message'] = array(
        'type' => $type,
        'message' => $message
    );
}

function alert_icon_class($type)
{
    if ($type == 'success') {
        return 'fa-circle-check';
    }
    if ($type == 'danger') {
        return 'fa-circle-xmark';
    }
    if ($type == 'warning') {
        return 'fa-triangle-exclamation';
    }
    return 'fa-circle-info';
}

function flash_modal_title($type)
{
    if ($type == 'success') {
        return 'Success';
    }
    if ($type == 'danger') {
        return 'Something went wrong';
    }
    if ($type == 'warning') {
        return 'Heads up';
    }
    return 'Notice';
}

function displayFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $type = $msg['type'];
        if ($type != 'success' && $type != 'danger' && $type != 'warning' && $type != 'info') {
            $type = 'info';
        }
        $icon = alert_icon_class($type);
        $title = flash_modal_title($type);
        echo '<div class="modal fade flash-modal" id="flashModal" tabindex="-1" aria-hidden="true">';
        echo '<div class="modal-dialog modal-dialog-centered flash-modal-dialog">';
        echo '<div class="modal-content flash-modal-content flash-modal-' . htmlspecialchars($type) . '">';
        echo '<div class="modal-body flash-modal-body text-center">';
        echo '<div class="flash-modal-icon"><i class="fa-solid ' . $icon . '"></i></div>';
        echo '<h4 class="flash-modal-title">' . htmlspecialchars($title) . '</h4>';
        echo '<p class="flash-modal-text">' . htmlspecialchars($msg['message']) . '</p>';
        echo '<button type="button" class="btn btn-gradient flash-modal-btn px-4" data-bs-dismiss="modal">Got it</button>';
        echo '</div></div></div></div>';
    }
}

function isLoggedIn()
{
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    return false;
}

function isAdmin()
{
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
        return true;
    }
    return false;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Please login to continue.');
        header('Location: login.php');
        exit;
    }
}

function requireAdmin()
{
    if (!isAdmin()) {
        setFlashMessage('danger', 'Access denied. Administrator privileges required.');
        header('Location: index.php');
        exit;
    }
}

require_once __DIR__ . '/../app/Core/security.php';
send_security_headers();
