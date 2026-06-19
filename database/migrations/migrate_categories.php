<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Categories author support migration ===\n\n";

$queries = array(
    "ALTER TABLE categories ADD COLUMN IF NOT EXISTS created_by INT REFERENCES users(id) ON DELETE SET NULL",
    "ALTER TABLE categories ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
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
