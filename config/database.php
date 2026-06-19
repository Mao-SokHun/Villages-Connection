<?php

require_once __DIR__ . '/config.php';

try {
    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
    if (DB_SSLMODE !== '') {
        $dsn .= ';sslmode=' . DB_SSLMODE;
    }
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    );
    $persistent = getenv('DB_PERSISTENT');
    $is_local_db = in_array(DB_HOST, array('db', '127.0.0.1', 'localhost'), true);
    if ($persistent === 'true' || $persistent === '1' || ($persistent !== 'false' && !$is_local_db)) {
        $options[PDO::ATTR_PERSISTENT] = true;
    }
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    if (defined('APP_TIMEZONE') && APP_TIMEZONE !== '') {
        $pdo->exec('SET TIME ZONE ' . $pdo->quote(APP_TIMEZONE));
    }
} catch (PDOException $e) {
    app_log_error('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit(app_public_error_message('Database connection failed.'));
}
