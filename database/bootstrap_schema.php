<?php

/**
 * Load base schema when the database is empty (fresh local Docker volume, CI Postgres, etc.).
 */

function migrate_bootstrap_schema_if_needed(PDO $pdo)
{
    try {
        $stmt = $pdo->query("SELECT to_regclass('public.users')");
        if ($stmt && $stmt->fetchColumn() !== null) {
            return false;
        }
    } catch (PDOException $e) {
        // Fall through and attempt bootstrap.
    }

    $schema_path = __DIR__ . '/schema.sql';
    if (!is_file($schema_path)) {
        throw new RuntimeException('Missing database/schema.sql');
    }

    $pdo->exec(file_get_contents($schema_path));

    return true;
}
