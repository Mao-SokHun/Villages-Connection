<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Phase 2.5 migration (expiry + knowledge + challenges) ===\n\n";

$queries = array(
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS post_kind VARCHAR(20) NOT NULL DEFAULT 'general'",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS knowledge_label VARCHAR(20) DEFAULT ''",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS mood_tag VARCHAR(20) DEFAULT ''",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS latitude NUMERIC(10, 7)",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS longitude NUMERIC(10, 7)",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS archive_on_expiry BOOLEAN NOT NULL DEFAULT TRUE",
    "CREATE TABLE IF NOT EXISTS community_challenges (
        id SERIAL PRIMARY KEY,
        title VARCHAR(180) NOT NULL,
        slug VARCHAR(180) UNIQUE NOT NULL,
        description TEXT NOT NULL,
        goal_type VARCHAR(20) NOT NULL DEFAULT 'posts',
        goal_target INT NOT NULL DEFAULT 10,
        reward_text VARCHAR(255) DEFAULT '',
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by INT REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS challenge_id INT NULL",
    "CREATE INDEX IF NOT EXISTS idx_posts_post_kind ON posts(post_kind)",
    "CREATE INDEX IF NOT EXISTS idx_posts_knowledge_label ON posts(knowledge_label)",
    "CREATE INDEX IF NOT EXISTS idx_posts_mood_tag ON posts(mood_tag)",
    "CREATE INDEX IF NOT EXISTS idx_posts_expires_at ON posts(expires_at)",
    "CREATE INDEX IF NOT EXISTS idx_challenges_status_dates ON community_challenges(status, end_date DESC)",
);

try {
    foreach ($queries as $sql) {
        $pdo->exec($sql);
        echo "OK: " . substr(preg_replace('/\s+/', ' ', $sql), 0, 90) . "...\n";
    }

    $pdo->exec("INSERT INTO community_challenges (title, slug, description, goal_type, goal_target, reward_text, start_date, end_date, status)
                SELECT 'Weekly Helpful Knowledge', 'weekly-helpful-knowledge',
                       'Share practical local tips this week. Top contributors will be highlighted on the homepage.',
                       'knowledge_posts', 5, 'Featured badge + homepage highlight',
                       CURRENT_DATE, CURRENT_DATE + INTERVAL '7 days', 'active'
                WHERE NOT EXISTS (SELECT 1 FROM community_challenges WHERE slug = 'weekly-helpful-knowledge')");
    echo "OK: seeded default challenge (if missing)\n";
    echo "\nMigration complete.\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
