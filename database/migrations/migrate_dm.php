<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Migration: Direct Messaging ===\n\n";

$queries = array(
    "CREATE TABLE IF NOT EXISTS dm_conversations (
        id SERIAL PRIMARY KEY,
        user_a INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        user_b INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_a, user_b)
    )",

    "CREATE INDEX IF NOT EXISTS idx_dm_conv_user_a ON dm_conversations(user_a, last_message_at DESC)",
    "CREATE INDEX IF NOT EXISTS idx_dm_conv_user_b ON dm_conversations(user_b, last_message_at DESC)",

    "CREATE TABLE IF NOT EXISTS dm_messages (
        id SERIAL PRIMARY KEY,
        conversation_id INT NOT NULL REFERENCES dm_conversations(id) ON DELETE CASCADE,
        sender_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        body TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE INDEX IF NOT EXISTS idx_dm_messages_conv ON dm_messages(conversation_id, created_at ASC)",
    "CREATE INDEX IF NOT EXISTS idx_dm_messages_sender ON dm_messages(sender_id)",
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
