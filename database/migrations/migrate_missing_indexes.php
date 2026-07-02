<?php

require_once __DIR__ . '/../bootstrap_schema.php';

$queries = [
    'CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);',
    'CREATE INDEX IF NOT EXISTS idx_posts_category_id ON posts(category_id);'
];

foreach ($queries as $query) {
    try {
        $pdo->exec($query);
        echo "[SUCCESS] " . substr($query, 0, 80) . "...\n";
    } catch (PDOException $e) {
        echo "[ERROR] Failed to execute query. " . $e->getMessage() . "\n";
    }
}
