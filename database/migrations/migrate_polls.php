<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Migration: Polls ===\n\n";

$queries = array(
    "CREATE TABLE IF NOT EXISTS polls (
        id SERIAL PRIMARY KEY,
        post_id INT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
        question VARCHAR(500) NOT NULL,
        is_multiple BOOLEAN DEFAULT FALSE,
        ends_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(post_id)
    )",

    "CREATE TABLE IF NOT EXISTS poll_options (
        id SERIAL PRIMARY KEY,
        poll_id INT NOT NULL REFERENCES polls(id) ON DELETE CASCADE,
        label VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0
    )",

    "CREATE TABLE IF NOT EXISTS poll_votes (
        id SERIAL PRIMARY KEY,
        poll_id INT NOT NULL REFERENCES polls(id) ON DELETE CASCADE,
        option_id INT NOT NULL REFERENCES poll_options(id) ON DELETE CASCADE,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(poll_id, option_id, user_id)
    )",

    "CREATE INDEX IF NOT EXISTS idx_poll_votes_poll_user ON poll_votes(poll_id, user_id)",
    "CREATE INDEX IF NOT EXISTS idx_poll_options_poll ON poll_options(poll_id, sort_order)",
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
