<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Member features migration ===\n\n";

$queries = array(
    "CREATE TABLE IF NOT EXISTS user_follows (
        id SERIAL PRIMARY KEY,
        follower_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        following_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(follower_id, following_id),
        CHECK (follower_id != following_id)
    )",

    "CREATE TABLE IF NOT EXISTS notifications (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        type VARCHAR(40) NOT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL DEFAULT '',
        link_url VARCHAR(255) DEFAULT '',
        is_read BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "ALTER TABLE post_comments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",

    "CREATE INDEX IF NOT EXISTS idx_user_follows_follower ON user_follows(follower_id)",
    "CREATE INDEX IF NOT EXISTS idx_user_follows_following ON user_follows(following_id)",
    "CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, is_read, created_at DESC)"
);

try {
    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 70) . "...\n";
    }
    echo "\nMigration complete.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
