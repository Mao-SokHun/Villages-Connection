<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== Phase 4 migration (push subscriptions, analytics support) ===\n\n";

$queries = array(
    "CREATE TABLE IF NOT EXISTS push_subscriptions (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        endpoint TEXT NOT NULL UNIQUE,
        p256dh VARCHAR(255) NOT NULL,
        auth_key VARCHAR(255) NOT NULL,
        user_agent VARCHAR(255) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE INDEX IF NOT EXISTS idx_push_subscriptions_user ON push_subscriptions(user_id)",
);

try {
    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "OK: " . substr(preg_replace('/\s+/', ' ', $sql), 0, 80) . "...\n";
    }
    echo "\nMigration complete.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
