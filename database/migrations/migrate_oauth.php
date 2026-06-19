<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== OAuth fields migration ===\n\n";

$queries = array(
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS oauth_provider VARCHAR(20) DEFAULT 'local'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS oauth_id VARCHAR(100) DEFAULT ''",
    "UPDATE users SET oauth_provider = 'local' WHERE oauth_provider IS NULL OR oauth_provider = ''",
    "CREATE UNIQUE INDEX IF NOT EXISTS users_oauth_provider_id_idx ON users (oauth_provider, oauth_id) WHERE oauth_id <> ''"
);

try {
    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "OK: " . $sql . "\n";
    }

    echo "\nMigration complete.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
