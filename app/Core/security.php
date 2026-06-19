<?php

function request_is_https()
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    return false;
}

function normalize_https_request()
{
    if (request_is_https()) {
        $_SERVER['HTTPS'] = 'on';
    }
}

function hsts_header_value()
{
    $enabled = getenv('HSTS_ENABLED');
    if ($enabled === 'false' || $enabled === '0') {
        return '';
    }

    if (!request_is_https()) {
        return '';
    }

    $max_age = (int) getenv('HSTS_MAX_AGE');
    if ($max_age <= 0) {
        $max_age = 31536000;
    }

    $value = 'max-age=' . $max_age;
    $include = getenv('HSTS_INCLUDE_SUBDOMAINS');
    if ($include === 'true' || $include === '1') {
        $value .= '; includeSubDomains';
    }

    return $value;
}

function send_security_headers()
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-site');
    header('X-Permitted-Cross-Domain-Policies: none');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:; frame-src https://www.youtube.com; connect-src 'self'");

    $hsts = hsts_header_value();
    if ($hsts != '') {
        header('Strict-Transport-Security: ' . $hsts);
    }
}

function csrf_token()
{
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] == '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function is_safe_redirect_path($path)
{
    $path = trim((string) $path);
    if ($path == '' || $path == '/') {
        return false;
    }
    if (preg_match('/[\r\n\x00]/', $path)) {
        return false;
    }
    if (strpos($path, ':') !== false) {
        return false;
    }
    if (strpos($path, '//') === 0) {
        return false;
    }
    if (strpos($path, '\\') !== false) {
        return false;
    }
    if (strpos($path, '..') !== false) {
        return false;
    }

    return true;
}

function safe_redirect_path($path, $default = 'index.php')
{
    if (is_safe_redirect_path($path)) {
        return $path;
    }

    return $default;
}

function safe_http_href($url)
{
    $url = trim((string) $url);
    if ($url == '') {
        return '';
    }

    if (is_safe_redirect_path($url)) {
        return app_url($url);
    }

    if (preg_match('#^https://#i', $url)) {
        return $url;
    }

    return '';
}

function sanitize_plain_text_field($value, $max_length = 120)
{
    $value = trim((string) $value);
    $value = strip_tags($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value) > (int) $max_length) {
            $value = mb_substr($value, 0, (int) $max_length);
        }
    } elseif (strlen($value) > (int) $max_length) {
        $value = substr($value, 0, (int) $max_length);
    }

    return $value;
}

function app_log_error($message)
{
    error_log('[VillageConnect] ' . $message);
}

function app_public_error_message($fallback = 'A server error occurred. Please try again later.')
{
    if (defined('APP_DEBUG') && APP_DEBUG) {
        return $fallback;
    }

    return 'A server error occurred. Please try again later.';
}

function verify_csrf_token()
{
    $token = '';
    if (isset($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }

    if ($token == '' || !isset($_SESSION['csrf_token'])) {
        return false;
    }

    if (hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }

    return false;
}

function require_valid_csrf()
{
    if (!verify_csrf_token()) {
        setFlashMessage('danger', 'Security check failed. Please try again.');
        $back = 'index.php';
        if (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF'] != '') {
            $back = basename($_SERVER['PHP_SELF']);
        }
        header('Location: ' . $back);
        exit;
    }
}

function require_valid_csrf_json($message = 'Security check failed. Please refresh the page.')
{
    if (!verify_csrf_token()) {
        echo json_encode(array('success' => false, 'message' => $message));
        exit;
    }
}

function client_rate_limit_id()
{
    $ip = client_ip_address();

    if (isLoggedIn()) {
        return 'user:' . (int) $_SESSION['user_id'] . '|ip:' . $ip;
    }

    return 'ip:' . $ip;
}

function rate_limit_blocked_response($action, $id, $window_seconds, $as_json = false)
{
    $wait = rate_limit_remaining_seconds($action, $id, $window_seconds);
    $mins = (int) ceil($wait / 60);
    if ($mins < 1) {
        $mins = 1;
    }
    $message = 'Too many requests. Please wait about ' . $mins . ' minute(s) and try again.';

    if ($as_json) {
        echo json_encode(array('success' => false, 'message' => $message));
        exit;
    }

    return $message;
}

function enforce_rate_limit_or_exit($action, $id, $max_attempts, $window_seconds, $as_json = false)
{
    if (!rate_limit_hit($action, $id, $max_attempts, $window_seconds)) {
        rate_limit_blocked_response($action, $id, $window_seconds, $as_json);
    }
}

function rate_limit_key($action, $id)
{
    return rate_limit_storage_key($action, $id);
}

function rate_limit_hit($action, $id, $max_attempts, $window_seconds)
{
    if (rate_limit_driver() === 'database') {
        global $pdo;
        return rate_limit_hit_db($pdo, $action, $id, $max_attempts, $window_seconds);
    }

    return rate_limit_hit_session($action, $id, $max_attempts, $window_seconds);
}

function rate_limit_remaining_seconds($action, $id, $window_seconds)
{
    if (rate_limit_driver() === 'database') {
        global $pdo;
        return rate_limit_remaining_seconds_db($pdo, $action, $id, $window_seconds);
    }

    return rate_limit_remaining_seconds_session($action, $id, $window_seconds);
}

function login_is_locked($email)
{
    $email = strtolower(trim($email));
    if ($email == '') {
        return false;
    }

    if (rate_limit_driver() === 'database') {
        global $pdo;
        return rate_limit_is_exceeded_db($pdo, 'login_fail', 'email:' . $email, 5, 900);
    }

    if (!isset($_SESSION['login_locks'])) {
        return false;
    }

    if (!isset($_SESSION['login_locks'][$email])) {
        return false;
    }

    if ($_SESSION['login_locks'][$email] > time()) {
        return true;
    }

    return false;
}

function login_lock_remaining($email)
{
    $email = strtolower(trim($email));
    if ($email == '') {
        return 0;
    }

    if (rate_limit_driver() === 'database') {
        global $pdo;
        return rate_limit_remaining_seconds_db($pdo, 'login_fail', 'email:' . $email, 900);
    }

    if (!isset($_SESSION['login_locks'][$email])) {
        return 0;
    }

    $left = $_SESSION['login_locks'][$email] - time();
    if ($left < 0) {
        return 0;
    }

    return $left;
}

function register_login_fail($email)
{
    $email = strtolower(trim($email));
    if ($email == '') {
        return;
    }

    if (rate_limit_driver() === 'database') {
        global $pdo;
        rate_limit_hit_db($pdo, 'login_fail', 'email:' . $email, 5, 900);
        return;
    }

    if (!isset($_SESSION['login_fails'])) {
        $_SESSION['login_fails'] = array();
    }

    if (!isset($_SESSION['login_fails'][$email])) {
        $_SESSION['login_fails'][$email] = 0;
    }

    $_SESSION['login_fails'][$email] = $_SESSION['login_fails'][$email] + 1;

    if ($_SESSION['login_fails'][$email] >= 5) {
        if (!isset($_SESSION['login_locks'])) {
            $_SESSION['login_locks'] = array();
        }
        $_SESSION['login_locks'][$email] = time() + 900;
        $_SESSION['login_fails'][$email] = 0;
    }
}

function clear_login_fails($email)
{
    $email = strtolower(trim($email));
    if (rate_limit_driver() === 'database') {
        global $pdo;
        rate_limit_clear_db($pdo, 'login_fail', 'email:' . $email);
        return;
    }

    if (isset($_SESSION['login_fails'][$email])) {
        unset($_SESSION['login_fails'][$email]);
    }
    if (isset($_SESSION['login_locks'][$email])) {
        unset($_SESSION['login_locks'][$email]);
    }
}

function regenerate_session_on_login()
{
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
