<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Phase 5 migration (database rate limits) ===\n\n";

$queries = array(
    "ALTER TABLE rate_limit_hits ALTER COLUMN action_key TYPE VARCHAR(160)",
    "CREATE INDEX IF NOT EXISTS idx_rate_limit_hits_key ON rate_limit_hits(action_key, hit_at DESC)",
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
