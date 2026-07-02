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

function env_var($key, $default = '')
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    $value = (string) $value;
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

    return trim($value);
}

function apply_database_url_from_env()
{
    $url = getenv('DATABASE_URL');
    if ($url == false || $url == '') {
        return;
    }

    $parts = parse_url($url);
    if (!$parts || !isset($parts['host'])) {
        return;
    }

    if (!getenv('DB_HOST')) {
        putenv('DB_HOST=' . $parts['host']);
        $_ENV['DB_HOST'] = $parts['host'];
        $_SERVER['DB_HOST'] = $parts['host'];
    }
    if (!getenv('DB_PORT') && isset($parts['port'])) {
        putenv('DB_PORT=' . $parts['port']);
        $_ENV['DB_PORT'] = (string) $parts['port'];
        $_SERVER['DB_PORT'] = (string) $parts['port'];
    }
    if (!getenv('DB_DATABASE') && isset($parts['path'])) {
        $db = ltrim($parts['path'], '/');
        putenv('DB_DATABASE=' . $db);
        $_ENV['DB_DATABASE'] = $db;
        $_SERVER['DB_DATABASE'] = $db;
    }
    if (!getenv('DB_USERNAME') && isset($parts['user'])) {
        putenv('DB_USERNAME=' . $parts['user']);
        $_ENV['DB_USERNAME'] = $parts['user'];
        $_SERVER['DB_USERNAME'] = $parts['user'];
    }
    if (!getenv('DB_PASSWORD') && isset($parts['pass'])) {
        putenv('DB_PASSWORD=' . $parts['pass']);
        $_ENV['DB_PASSWORD'] = $parts['pass'];
        $_SERVER['DB_PASSWORD'] = $parts['pass'];
    }

    if (!getenv('DB_SSLMODE') && isset($parts['query'])) {
        parse_str($parts['query'], $query);
        if (isset($query['sslmode']) && $query['sslmode'] !== '') {
            putenv('DB_SSLMODE=' . $query['sslmode']);
            $_ENV['DB_SSLMODE'] = $query['sslmode'];
            $_SERVER['DB_SSLMODE'] = $query['sslmode'];
        }
    }
}

loadEnv(__DIR__ . '/../.env');

apply_database_url_from_env();

function app_resolve_timezone()
{
    $tz = getenv('APP_TIMEZONE');
    if ($tz == false || $tz == '') {
        $tz = 'Asia/Phnom_Penh';
    }
    try {
        new DateTimeZone($tz);
    } catch (Exception $e) {
        $tz = 'Asia/Phnom_Penh';
    }
    return $tz;
}

define('APP_TIMEZONE', app_resolve_timezone());
date_default_timezone_set(APP_TIMEZONE);

require_once __DIR__ . '/../app/Models/rate_limit.php';
require_once __DIR__ . '/../app/Models/security.php';
require_once __DIR__ . '/../app/Models/session.php';
normalize_https_request();

$db_host = env_var('DB_HOST', '127.0.0.1');
define('DB_HOST', $db_host);

$db_port = env_var('DB_PORT', '5432');
define('DB_PORT', $db_port);

$db_name = env_var('DB_DATABASE', 'project_cms');
define('DB_NAME', $db_name);

$db_user = env_var('DB_USERNAME', 'postgres');
define('DB_USER', $db_user);

$db_pass = env_var('DB_PASSWORD');
if ($db_pass == '') {
    http_response_code(500);
    exit('Database configuration error: DB_PASSWORD is not set. Configure it in your .env file.');
}
define('DB_PASS', $db_pass);

define('DB_SSLMODE', env_var('DB_SSLMODE'));

define('SITE_NAME', 'Villages Connection');
define('SITE_TAGLINE', 'Building Stronger Communities Together');
define('SITE_DESC', 'Share everyday moments, stories, and creative content — like Facebook, Instagram, and Twitter, built for your village');

$site_contact = getenv('SITE_CONTACT_EMAIL');
if ($site_contact == false || $site_contact == '') {
    $site_contact = getenv('MAIL_FROM');
}
if ($site_contact == false || $site_contact == '') {
    $site_contact = 'villagesconnection@gmail.com';
}
define('SITE_CONTACT_EMAIL', $site_contact);

$app_url = env_var('APP_URL');
define('APP_URL', $app_url);

$app_env = env_var('APP_ENV', 'local');
define('APP_ENV', $app_env);

$app_debug = getenv('APP_DEBUG');
$debug_on = ($app_debug === 'true' || $app_debug === '1');
if (APP_ENV === 'production') {
    $debug_on = false;
}
define('APP_DEBUG', $debug_on);

$i18n_switcher = getenv('I18N_USER_SWITCHER_ENABLED');
define('I18N_USER_SWITCHER_ENABLED', $i18n_switcher === 'true' || $i18n_switcher === '1');

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
        return __('flash.success');
    }
    if ($type == 'danger') {
        return __('flash.danger');
    }
    if ($type == 'warning') {
        return __('flash.warning');
    }
    return __('flash.info');
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
        echo '<button type="button" class="btn btn-gradient flash-modal-btn px-4" data-bs-dismiss="modal">' . htmlspecialchars(__('common.got_it')) . '</button>';
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

    global $pdo;
    app_start_session($pdo);

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

require_once __DIR__ . '/../app/Models/routes.php';
send_security_headers();
