<?php

function send_security_headers()
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:; frame-src https://www.youtube.com; connect-src 'self'");
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

function rate_limit_key($action, $id)
{
    $id = strtolower(trim($id));
    return $action . ':' . $id;
}

function rate_limit_hit($action, $id, $max_attempts, $window_seconds)
{
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = array();
    }

    $key = rate_limit_key($action, $id);
    $now = time();

    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = array();
    }

    $hits = array();
    foreach ($_SESSION['rate_limits'][$key] as $hit_time) {
        if (($now - $hit_time) < $window_seconds) {
            $hits[] = $hit_time;
        }
    }

    $hits[] = $now;
    $_SESSION['rate_limits'][$key] = $hits;

    if (count($hits) > $max_attempts) {
        return false;
    }

    return true;
}

function rate_limit_remaining_seconds($action, $id, $window_seconds)
{
    if (!isset($_SESSION['rate_limits'][rate_limit_key($action, $id)])) {
        return 0;
    }

    $hits = $_SESSION['rate_limits'][rate_limit_key($action, $id)];
    if (count($hits) == 0) {
        return 0;
    }

    $oldest = $hits[0];
    $left = $window_seconds - (time() - $oldest);
    if ($left < 0) {
        return 0;
    }

    return $left;
}

function login_is_locked($email)
{
    $email = strtolower(trim($email));
    if ($email == '') {
        return false;
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
