<?php
require_once __DIR__ . '/../../bootstrap.php';

$queries = array(
    "CREATE TABLE IF NOT EXISTS incident_reports (
        id SERIAL PRIMARY KEY,
        user_id INT REFERENCES users(id) ON DELETE SET NULL,
        reporter_name VARCHAR(100) NOT NULL,
        reporter_email VARCHAR(120) NOT NULL,
        incident_type VARCHAR(40) NOT NULL,
        priority VARCHAR(20) NOT NULL DEFAULT 'medium',
        title VARCHAR(180) NOT NULL,
        details TEXT NOT NULL,
        village_name VARCHAR(150) DEFAULT '',
        location_text VARCHAR(255) DEFAULT '',
        latitude NUMERIC(10, 7),
        longitude NUMERIC(10, 7),
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        admin_notes TEXT DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP
    )",
    "CREATE INDEX IF NOT EXISTS idx_incident_reports_status ON incident_reports(status)",
    "CREATE INDEX IF NOT EXISTS idx_incident_reports_created ON incident_reports(created_at DESC)",
);

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 80) . "...\n";
    } catch (PDOException $e) {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}

echo "Incident reports migration complete.\n";
