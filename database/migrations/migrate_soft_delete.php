<?php

if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/database.php';
}

echo "=== Soft delete migration ===\n\n";

$queries = array(
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS account_status VARCHAR(20) NOT NULL DEFAULT 'active'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP",
    "CREATE INDEX IF NOT EXISTS idx_users_account_status ON users(account_status)",
);

foreach ($queries as $sql) {
    $pdo->exec($sql);
    echo "OK: " . $sql . "\n";
}

echo "\nMigration complete.\n";
