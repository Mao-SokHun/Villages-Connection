<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== Recommendations migration ===\n\n";

$queries = array(
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS image_alt VARCHAR(255) DEFAULT ''",
    "UPDATE posts SET updated_at = created_at WHERE updated_at IS NULL",
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        id SERIAL PRIMARY KEY,
        migration VARCHAR(120) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS rate_limit_hits (
        id SERIAL PRIMARY KEY,
        action_key VARCHAR(120) NOT NULL,
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        hit_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE INDEX IF NOT EXISTS idx_rate_limit_hits_key ON rate_limit_hits(action_key, hit_at DESC)"
);

try {
    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 72) . "...\n";
    }
    echo "\nRecommendations migration complete.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
