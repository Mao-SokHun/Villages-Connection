<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== Phase 3 migration (comment threads, bookmarks) ===\n\n";

$queries = array(
    "ALTER TABLE post_comments ADD COLUMN IF NOT EXISTS parent_id INT REFERENCES post_comments(id) ON DELETE CASCADE",

    "CREATE INDEX IF NOT EXISTS idx_post_comments_parent ON post_comments(parent_id)",
    "CREATE INDEX IF NOT EXISTS idx_post_comments_post_parent ON post_comments(post_id, parent_id, created_at)",

    "CREATE TABLE IF NOT EXISTS post_bookmarks (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        post_id INT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, post_id)
    )",

    "CREATE INDEX IF NOT EXISTS idx_post_bookmarks_user ON post_bookmarks(user_id, created_at DESC)",
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
