<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Profile fields migration ===\n\n";

$queries = array(
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT ''",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT ''",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS location VARCHAR(150) DEFAULT ''",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS website VARCHAR(255) DEFAULT ''",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
);

try {
    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "OK: " . $sql . "\n";
    }

    $pdo->exec("UPDATE users SET updated_at = created_at WHERE updated_at IS NULL");
    echo "\nMigration complete.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
