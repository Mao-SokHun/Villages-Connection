<?php

if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/database.php';
}

echo "=== Cloudinary column migration ===\n\n";

$queries = array(
    'ALTER TABLE posts ALTER COLUMN image_url TYPE VARCHAR(500)',
    'ALTER TABLE posts ALTER COLUMN video_url TYPE VARCHAR(500)',
);

foreach ($queries as $sql) {
    $pdo->exec($sql);
    echo "OK: $sql\n";
}

echo "\nCloudinary migration complete.\n";
