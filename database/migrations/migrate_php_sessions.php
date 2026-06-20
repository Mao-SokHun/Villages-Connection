<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== PHP sessions table migration ===\n\n";

$sql = "CREATE TABLE IF NOT EXISTS php_sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT NOT NULL DEFAULT '',
    last_activity TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)";

$index = 'CREATE INDEX IF NOT EXISTS idx_php_sessions_last_activity ON php_sessions (last_activity)';

try {
    $pdo->exec($sql);
    $pdo->exec($index);
    echo "OK: php_sessions table ready.\n";
    echo "\nMigration complete.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
