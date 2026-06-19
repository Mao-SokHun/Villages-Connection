<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Admin features migration ===\n\n";

$queries = array(
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned BOOLEAN NOT NULL DEFAULT FALSE",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS banned_reason TEXT DEFAULT ''",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS banned_at TIMESTAMP",

    "CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS contact_messages (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        admin_notes TEXT DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS content_reports (
        id SERIAL PRIMARY KEY,
        reporter_name VARCHAR(100) NOT NULL,
        reporter_email VARCHAR(100) NOT NULL,
        reason VARCHAR(150) NOT NULL,
        post_url VARCHAR(500) DEFAULT '',
        details TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        admin_notes TEXT DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS activity_logs (
        id SERIAL PRIMARY KEY,
        user_id INT REFERENCES users(id) ON DELETE SET NULL,
        action VARCHAR(80) NOT NULL,
        details TEXT DEFAULT '',
        ip_address VARCHAR(45) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS announcements (
        id SERIAL PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        link_url VARCHAR(255) DEFAULT '',
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        starts_at TIMESTAMP,
        ends_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS post_comments (
        id SERIAL PRIMARY KEY,
        post_id INT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
        user_id INT REFERENCES users(id) ON DELETE SET NULL,
        author_name VARCHAR(100) NOT NULL DEFAULT '',
        content TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE INDEX IF NOT EXISTS idx_contact_messages_status ON contact_messages(status)",
    "CREATE INDEX IF NOT EXISTS idx_content_reports_status ON content_reports(status)",
    "CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON activity_logs(created_at DESC)",
    "CREATE INDEX IF NOT EXISTS idx_post_comments_status ON post_comments(status)",
    "CREATE INDEX IF NOT EXISTS idx_post_comments_post ON post_comments(post_id)"
);

$defaults = array(
    'registration_enabled' => '1',
    'require_post_approval' => '0',
    'maintenance_mode' => '0',
    'maintenance_message' => 'We are performing scheduled maintenance. Please check back soon.',
    'site_contact_email' => 'villagesconnection@gmail.com',
    'default_meta_description' => SITE_DESC,
    'comments_enabled' => '1',
    'comments_require_approval' => '0',
    'email_template_reset_subject' => SITE_NAME . ' — Password Reset Code',
    'email_template_reset_body' => 'Hello {name}, your OTP code is {otp}. It expires in 15 minutes.',
    'email_template_welcome_subject' => 'Welcome to ' . SITE_NAME,
    'email_template_welcome_body' => 'Hello {name}, welcome to ' . SITE_NAME . '! Start sharing with your community today.'
);

try {
    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "OK: " . substr($sql, 0, 70) . "...\n";
    }

    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON CONFLICT (setting_key) DO NOTHING");
    foreach ($defaults as $key => $value) {
        $stmt->execute(array('k' => $key, 'v' => $value));
    }
    echo "\nDefault settings inserted.\n";
    echo "Migration complete.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
