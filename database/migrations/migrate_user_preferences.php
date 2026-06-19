<?php
require_once __DIR__ . '/../../bootstrap.php';

$queries = array(
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS ui_theme VARCHAR(10) NOT NULL DEFAULT 'system'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS ui_density VARCHAR(20) NOT NULL DEFAULT 'comfortable'",
);

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: $sql\n";
    } catch (PDOException $e) {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}

echo "User preferences migration complete.\n";
