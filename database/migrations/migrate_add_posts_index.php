<?php

require_once __DIR__ . '/../bootstrap_schema.php';

$queries = [
    'CREATE INDEX IF NOT EXISTS idx_posts_archive_lookup ON posts(status, archive_on_expiry, expires_at);'
];

foreach ($queries as $query) {
    try {
        $pdo->exec($query);
        echo "[SUCCESS] " . substr($query, 0, 80) . "...
";
    } catch (PDOException $e) {
        echo "[ERROR] Failed to execute query. " . $e->getMessage() . "
";
    }
}
