<?php

function rate_limit_driver()
{
    static $driver = null;
    if ($driver !== null) {
        return $driver;
    }

    $env = getenv('RATE_LIMIT_DRIVER');
    if ($env === 'session') {
        $driver = 'session';
        return $driver;
    }

    if (defined('APP_ENV') && APP_ENV === 'production') {
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) {
            $driver = 'database';
            return $driver;
        }
    }

    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO) {
        $driver = 'database';
        return $driver;
    }

    $driver = 'session';
    return $driver;
}

function client_ip_address()
{
    $trust_proxy = getenv('TRUST_PROXY');
    if ($trust_proxy === 'true' || $trust_proxy === '1') {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '') {
        return $_SERVER['REMOTE_ADDR'];
    }

    return 'unknown';
}

function rate_limit_storage_key($action, $id)
{
    return substr($action . ':' . strtolower(trim($id)), 0, 160);
}

function rate_limit_hit_ip_from_id($id)
{
    $id = strtolower(trim($id));
    if (preg_match('/ip:([0-9a-fA-F:\\.]+)/', $id, $matches)) {
        return $matches[1];
    }

    return client_ip_address();
}

function rate_limit_purge_old($pdo, $key, $window_seconds)
{
    $sql = 'DELETE FROM rate_limit_hits
        WHERE action_key = :key
        AND hit_at < (CURRENT_TIMESTAMP - (:window || \' seconds\')::interval)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'key' => $key,
        'window' => (int) $window_seconds,
    ));
}

function rate_limit_count_recent($pdo, $key, $window_seconds)
{
    rate_limit_purge_old($pdo, $key, $window_seconds);

    $sql = 'SELECT COUNT(*)::int FROM rate_limit_hits
        WHERE action_key = :key
        AND hit_at >= (CURRENT_TIMESTAMP - (:window || \' seconds\')::interval)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'key' => $key,
        'window' => (int) $window_seconds,
    ));

    return (int) $stmt->fetchColumn();
}

function rate_limit_record_hit_db($pdo, $key, $id)
{
    $sql = 'INSERT INTO rate_limit_hits (action_key, ip_address, hit_at)
        VALUES (:key, :ip, CURRENT_TIMESTAMP)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'key' => $key,
        'ip' => substr(rate_limit_hit_ip_from_id($id), 0, 45),
    ));
}

function rate_limit_oldest_hit_age_db($pdo, $key, $window_seconds)
{
    $sql = 'SELECT EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - MIN(hit_at)))::int
        FROM rate_limit_hits
        WHERE action_key = :key
        AND hit_at >= (CURRENT_TIMESTAMP - (:window || \' seconds\')::interval)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'key' => $key,
        'window' => (int) $window_seconds,
    ));
    $age = $stmt->fetchColumn();
    if ($age === false || $age === null) {
        return 0;
    }

    $left = (int) $window_seconds - (int) $age;
    if ($left < 0) {
        return 0;
    }

    return $left;
}

function rate_limit_clear_db($pdo, $action, $id)
{
    $key = rate_limit_storage_key($action, $id);
    $stmt = $pdo->prepare('DELETE FROM rate_limit_hits WHERE action_key = :key');
    $stmt->execute(array('key' => $key));
}

function rate_limit_hit_db($pdo, $action, $id, $max_attempts, $window_seconds)
{
    $key = rate_limit_storage_key($action, $id);
    $count = rate_limit_count_recent($pdo, $key, $window_seconds);

    if ($count >= (int) $max_attempts) {
        return false;
    }

    rate_limit_record_hit_db($pdo, $key, $id);
    return true;
}

function rate_limit_is_exceeded_db($pdo, $action, $id, $max_attempts, $window_seconds)
{
    $key = rate_limit_storage_key($action, $id);
    $count = rate_limit_count_recent($pdo, $key, $window_seconds);
    return $count >= (int) $max_attempts;
}

function rate_limit_remaining_seconds_db($pdo, $action, $id, $window_seconds)
{
    $key = rate_limit_storage_key($action, $id);
    return rate_limit_oldest_hit_age_db($pdo, $key, $window_seconds);
}

function rate_limit_hit_session($action, $id, $max_attempts, $window_seconds)
{
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = array();
    }

    $key = rate_limit_storage_key($action, $id);
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

    if (count($hits) >= $max_attempts) {
        $_SESSION['rate_limits'][$key] = $hits;
        return false;
    }

    $hits[] = $now;
    $_SESSION['rate_limits'][$key] = $hits;

    return true;
}

function rate_limit_remaining_seconds_session($action, $id, $window_seconds)
{
    $key = rate_limit_storage_key($action, $id);
    if (!isset($_SESSION['rate_limits'][$key])) {
        return 0;
    }

    $hits = $_SESSION['rate_limits'][$key];
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

function rate_limit_clear_session($action, $id)
{
    $key = rate_limit_storage_key($action, $id);
    if (isset($_SESSION['rate_limits'][$key])) {
        unset($_SESSION['rate_limits'][$key]);
    }
}

function rate_limit_is_exceeded_session($action, $id, $max_attempts, $window_seconds)
{
    if (!isset($_SESSION['rate_limits'])) {
        return false;
    }

    $key = rate_limit_storage_key($action, $id);
    if (!isset($_SESSION['rate_limits'][$key])) {
        return false;
    }

    $now = time();
    $hits = 0;
    foreach ($_SESSION['rate_limits'][$key] as $hit_time) {
        if (($now - $hit_time) < $window_seconds) {
            $hits++;
        }
    }

    return $hits >= (int) $max_attempts;
}
