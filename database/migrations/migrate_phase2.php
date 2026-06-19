<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Phase 2 migration (i18n prep, account likes, email verify) ===\n\n";

$queries = array(
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP",

    "CREATE TABLE IF NOT EXISTS email_verification_tokens (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        token_hash VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE INDEX IF NOT EXISTS idx_email_verification_user ON email_verification_tokens(user_id, created_at DESC)",

    "ALTER TABLE post_likes ADD COLUMN IF NOT EXISTS user_id INT REFERENCES users(id) ON DELETE CASCADE",

    "UPDATE users SET email_verified_at = COALESCE(created_at, CURRENT_TIMESTAMP) WHERE email_verified_at IS NULL",

    "CREATE INDEX IF NOT EXISTS idx_post_likes_post_user ON post_likes(post_id, user_id) WHERE user_id IS NOT NULL",
);

try {
    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "OK: " . substr(preg_replace('/\s+/', ' ', $sql), 0, 80) . "...\n";
    }

    $pdo->exec('ALTER TABLE post_likes DROP CONSTRAINT IF EXISTS post_likes_post_id_visitor_key_key');

    echo "\nMigration complete.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
