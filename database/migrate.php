<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== Village Connect migration runner ===\n\n";

require_once __DIR__ . '/bootstrap_schema.php';

try {
    if (migrate_bootstrap_schema_if_needed($pdo)) {
        echo "BOOTSTRAP: loaded schema.sql (fresh database)\n\n";
    }
} catch (Exception $e) {
    echo 'ERROR bootstrapping schema: ' . $e->getMessage() . "\n";
    exit(1);
}

$migrations = array(
    'migrate_profile.php',
    'migrate_security.php',
    'migrate_oauth.php',
    'migrate_categories.php',
    'migrate_admin_features.php',
    'migrate_member_features.php',
    'migrate_recommendations.php',
    'migrate_soft_delete.php',
    'migrate_contact_support.php',
    'migrate_cloudinary.php',
    'migrate_phase2.php',
    'migrate_phase3.php',
    'migrate_phase4.php',
    'migrate_phase5.php',
    'migrate_php_sessions.php',
    'migrate_user_preferences.php',
    'migrate_incident_reports.php',
    'migrate_phase25.php',
);

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id SERIAL PRIMARY KEY,
        migration VARCHAR(120) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    echo "ERROR creating schema_migrations: " . $e->getMessage() . "\n";
    exit(1);
}

$applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$applied_map = array();
foreach ($applied as $name) {
    $applied_map[$name] = true;
}

foreach ($migrations as $file) {
    if (isset($applied_map[$file])) {
        echo "SKIP: $file (already applied)\n";
        continue;
    }

    $path = __DIR__ . '/migrations/' . $file;
    if (!file_exists($path)) {
        echo "MISSING: $file\n";
        continue;
    }

    echo "RUN: $file\n";
    ob_start();
    include $path;
    $output = ob_get_clean();
    echo $output;

    $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m) ON CONFLICT (migration) DO NOTHING');
    $stmt->execute(array('m' => $file));
    echo "RECORDED: $file\n\n";
}

echo "All migrations up to date.\n";
