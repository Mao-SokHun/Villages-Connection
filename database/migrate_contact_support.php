<?php

if (!isset($pdo)) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
}

echo "=== Contact support migration ===\n\n";

$queries = array(
    "ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS user_id INT REFERENCES users(id) ON DELETE SET NULL",
    "ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS admin_reply TEXT DEFAULT ''",
    "ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS replied_at TIMESTAMP",
    "ALTER TABLE content_reports ADD COLUMN IF NOT EXISTS user_id INT REFERENCES users(id) ON DELETE SET NULL",
);

foreach ($queries as $sql) {
    $pdo->exec($sql);
    echo "OK: " . $sql . "\n";
}

echo "\nMigration complete.\n";
