<?php

/**
 * Route and request security helpers.
 * Use at the top of pages/APIs to enforce method, auth, CSRF, and rate limits.
 */

function require_http_method($methods)
{
    if (!is_array($methods)) {
        $methods = array($methods);
    }

    $allowed = array();
    foreach ($methods as $method) {
        $allowed[] = strtoupper(trim((string) $method));
    }

    $current = 'GET';
    if (isset($_SERVER['REQUEST_METHOD'])) {
        $current = strtoupper($_SERVER['REQUEST_METHOD']);
    }

    if (in_array($current, $allowed, true)) {
        return;
    }

    http_response_code(405);
    header('Allow: ' . implode(', ', $allowed));

    if (is_api_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('success' => false, 'message' => 'Method not allowed.'));
        exit;
    }

    echo 'Method not allowed.';
    exit;
}

function is_api_request()
{
    if (!isset($_SERVER['SCRIPT_NAME'])) {
        return false;
    }

    return (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false);
}

function require_guest_only($redirect = 'index.php')
{
    if (isLoggedIn()) {
        redirect_to($redirect);
    }
}

function validate_active_session($pdo)
{
    return ensure_active_authenticated_user($pdo) !== null;
}

function assert_logged_in_json($pdo)
{
    if (!isLoggedIn()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('success' => false, 'message' => 'Please sign in.'));
        exit;
    }

    validate_active_session($pdo);
}

function secure_json_api($options = array())
{
    header('Content-Type: application/json; charset=utf-8');

    $methods = array('POST');
    if (isset($options['methods'])) {
        $methods = $options['methods'];
    }
    require_http_method($methods);

    global $pdo;

    if (!empty($options['login'])) {
        assert_logged_in_json($pdo);
    }

    if (!empty($options['admin'])) {
        if (!is_admin_user($pdo)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('success' => false, 'message' => 'Access denied.'));
            exit;
        }
    }

    if (!empty($options['staff'])) {
        if (!is_staff_user($pdo)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('success' => false, 'message' => 'Access denied.'));
            exit;
        }
    }

    if (!empty($options['csrf'])) {
        require_valid_csrf_json();
    }

    if (!empty($options['rate_limit']) && is_array($options['rate_limit'])) {
        $rl = $options['rate_limit'];
        $action = isset($rl['action']) ? $rl['action'] : 'api';
        $id = isset($rl['id']) ? $rl['id'] : client_rate_limit_id();
        $max = isset($rl['max']) ? (int) $rl['max'] : 60;
        $window = isset($rl['window']) ? (int) $rl['window'] : 60;
        enforce_rate_limit_or_exit($action, $id, $max, $window, true);
    }
}

function secure_form_post($options = array())
{
    require_http_method('POST');
    require_valid_csrf();

    if (!empty($options['rate_limit']) && is_array($options['rate_limit'])) {
        $rl = $options['rate_limit'];
        $action = isset($rl['action']) ? $rl['action'] : 'form';
        $id = isset($rl['id']) ? $rl['id'] : client_rate_limit_id();
        $max = isset($rl['max']) ? (int) $rl['max'] : 10;
        $window = isset($rl['window']) ? (int) $rl['window'] : 3600;
        if (!rate_limit_hit($action, $id, $max, $window)) {
            return rate_limit_blocked_response($action, $id, $window, false);
        }
    }

    return '';
}

function user_can_be_followed($pdo, $user_id)
{
    if ($user_id <= 0) {
        return false;
    }

    $user = get_user_by_id($pdo, (int) $user_id);
    return $user && user_is_publicly_visible($user);
}
