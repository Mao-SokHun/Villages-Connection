<?php

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

require_once __DIR__ . '/../app/Core/rate_limit.php';
require_once __DIR__ . '/../app/Core/security.php';
normalize_https_request();

if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => request_is_https(),
        'httponly' => true,
        'samesite' => 'Lax'
    ));

    ini_set('session.use_strict_mode', '1');
    session_start();
}

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
    http_response_code(500);
    exit('Database configuration error: DB_PASSWORD is not set. Configure it in your .env file.');
}
define('DB_PASS', $db_pass);

define('SITE_NAME', 'Village Connect');
define('SITE_TAGLINE', 'Post photos, videos, and updates from your community');
define('SITE_DESC', 'Share everyday moments, stories, and creative content — like Facebook, Instagram, and Twitter, built for your village');

$site_contact = getenv('SITE_CONTACT_EMAIL');
if ($site_contact == false || $site_contact == '') {
    $site_contact = getenv('MAIL_FROM');
}
if ($site_contact == false || $site_contact == '') {
    $site_contact = 'villagesconnection@gmail.com';
}
define('SITE_CONTACT_EMAIL', $site_contact);

$app_url = getenv('APP_URL');
if ($app_url == false || $app_url == '') {
    $app_url = '';
}
define('APP_URL', $app_url);

$app_env = getenv('APP_ENV');
if ($app_env == false || $app_env == '') {
    $app_env = 'local';
}
define('APP_ENV', $app_env);

$app_debug = getenv('APP_DEBUG');
$debug_on = ($app_debug === 'true' || $app_debug === '1');
if (APP_ENV === 'production') {
    $debug_on = false;
}
define('APP_DEBUG', $debug_on);

if (!APP_DEBUG) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

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
    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO) {
        return is_admin_user($pdo);
    }

    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
        return true;
    }

    return false;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Please login to continue.');
        redirect_to('login.php');
    }

    global $pdo;
    validate_active_session($pdo);
}

function perform_logout($flash_type = '', $flash_message = '')
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    if ($flash_type != '' && $flash_message != '') {
        setFlashMessage($flash_type, $flash_message);
    }
}

function logout_closed_account()
{
    perform_logout('warning', 'This account has been closed.');
    redirect_to('login.php');
}

function logout_banned_account()
{
    perform_logout('warning', 'This account has been suspended.');
    redirect_to('login.php');
}

function requireStaff()
{
    requireLogin();

    global $pdo;
    if (!is_staff_user($pdo)) {
        setFlashMessage('danger', 'Access denied.');
        redirect_to('index.php');
    }
}

function requireAdmin()
{
    requireLogin();

    global $pdo;
    if (!is_admin_user($pdo)) {
        setFlashMessage('danger', 'Access denied. Administrator privileges required.');
        redirect_to('index.php');
    }
}

require_once __DIR__ . '/../app/Core/routes.php';
send_security_headers();
