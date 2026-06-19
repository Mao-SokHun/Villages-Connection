<?php

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Demo data seed ===\n\n";

$user_count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$post_count = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();

if ($user_count > 0 && $post_count >= 5) {
    echo "Database already seeded ($user_count users, $post_count posts). Skipping.\n";
    exit(0);
}

if ($user_count == 0) {
    $users = array(
        array('name' => 'Site Admin', 'email' => 'admin@admin.com', 'password' => password_hash('admin123', PASSWORD_BCRYPT), 'role' => 'admin'),
        array('name' => 'Village Reporter', 'email' => 'author@author.com', 'password' => password_hash('author123', PASSWORD_BCRYPT), 'role' => 'author'),
    );

    $st = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
    foreach ($users as $u) {
        $st->execute($u);
        echo "User: {$u['email']}\n";
    }
}

$uids = array();
foreach ($pdo->query('SELECT id, email FROM users')->fetchAll() as $u) {
    $uids[$u['email']] = (int) $u['id'];
}

$cids = array();
foreach ($pdo->query('SELECT id, slug FROM categories')->fetchAll() as $c) {
    $cids[$c['slug']] = (int) $c['id'];
}

$posts = array(
    array(
        'category_id' => $cids['agriculture'],
        'user_id' => $uids['author@author.com'],
        'title' => 'Village Farmers Celebrate First Rice Harvest of the Season',
        'slug' => 'village-rice-harvest-first-season',
        'summary' => 'A local village completed a successful rice harvest using modern farming techniques and strong community teamwork.',
        'content' => "Many villagers joined the harvest over three days. Farmers used quality seeds and efficient irrigation, which improved yields compared to last year.\n\nThe harvest festival also included youth performances and shared meals, showing the spirit of community cooperation.",
        'image_url' => '',
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'video_type' => 'youtube',
        'location' => 'Riverside Village',
        'is_featured' => true,
        'status' => 'Published',
        'views' => 245,
        'likes' => 18,
    ),
    array(
        'category_id' => $cids['culture'],
        'user_id' => $uids['admin@admin.com'],
        'title' => 'Village Festival — Tradition and Folk Dance',
        'slug' => 'village-pchum-ben-festival',
        'summary' => 'Photos and stories from the annual village festival organized by local families.',
        'content' => "The festival included food offerings, celebration dances, and family gatherings from nearby areas.\n\nLocal youth recorded videos and shared them on this platform to keep traditions alive for future generations.",
        'image_url' => '',
        'video_url' => '',
        'video_type' => 'none',
        'location' => 'Angk Snuol District',
        'is_featured' => true,
        'status' => 'Published',
        'views' => 412,
        'likes' => 32,
    ),
    array(
        'category_id' => $cids['tourism'],
        'user_id' => $uids['author@author.com'],
        'title' => 'New Local Tourism Spot — Waterfall and Nature Trail',
        'slug' => 'new-village-tourism-waterfall',
        'summary' => 'A guide to a new community tourism destination for nature lovers.',
        'content' => "This spot features a small waterfall, forest garden, and a scenic walking path. It is about a two-hour drive from the capital.\n\nVisitors are asked to protect the environment and take all trash with them.",
        'image_url' => '',
        'video_url' => '',
        'video_type' => 'none',
        'location' => 'Kampong Speu Province',
        'is_featured' => false,
        'status' => 'Published',
        'views' => 156,
        'likes' => 11,
    ),
    array(
        'category_id' => $cids['community'],
        'user_id' => $uids['admin@admin.com'],
        'title' => 'Youth Group Organizes Charity Food Drive',
        'slug' => 'youth-charity-food-village',
        'summary' => 'A youth-led charity event supporting families in need across the village.',
        'content' => 'Young volunteers organized a free meal program and donated dry food supplies. Many residents joined, and village leaders praised the initiative.',
        'image_url' => '',
        'video_url' => '',
        'video_type' => 'none',
        'location' => 'Ta Kev Village',
        'is_featured' => false,
        'status' => 'Published',
        'views' => 98,
        'likes' => 24,
    ),
    array(
        'category_id' => $cids['events'],
        'user_id' => $uids['author@author.com'],
        'title' => 'Village Football Tournament — 2026',
        'slug' => 'village-football-2026',
        'summary' => 'A community sports event with teams competing across the district.',
        'content' => 'Eight teams joined the tournament. Matches started in the morning and ended with an awards ceremony. A live video recap will be published soon.',
        'image_url' => '',
        'video_url' => '',
        'video_type' => 'none',
        'location' => 'Village Stadium',
        'is_featured' => true,
        'status' => 'Published',
        'views' => 301,
        'likes' => 45,
    ),
);

$ins = $pdo->prepare('INSERT INTO posts (category_id, user_id, title, slug, summary, content, image_url, video_url, video_type, location, is_featured, status, views, likes)
    VALUES (:category_id, :user_id, :title, :slug, :summary, :content, :image_url, :video_url, :video_type, :location, :is_featured, :status, :views, :likes)');

foreach ($posts as $p) {
    $check = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE slug = :slug');
    $check->execute(array('slug' => $p['slug']));
    if ((int) $check->fetchColumn() > 0) {
        continue;
    }
    $featured = !empty($p['is_featured']);
    $ins->bindValue(':category_id', $p['category_id'], PDO::PARAM_INT);
    $ins->bindValue(':user_id', $p['user_id'], PDO::PARAM_INT);
    $ins->bindValue(':title', $p['title']);
    $ins->bindValue(':slug', $p['slug']);
    $ins->bindValue(':summary', $p['summary']);
    $ins->bindValue(':content', $p['content']);
    $ins->bindValue(':image_url', $p['image_url']);
    $ins->bindValue(':video_url', $p['video_url']);
    $ins->bindValue(':video_type', $p['video_type']);
    $ins->bindValue(':location', $p['location']);
    $ins->bindValue(':is_featured', $featured, PDO::PARAM_BOOL);
    $ins->bindValue(':status', $p['status']);
    $ins->bindValue(':views', $p['views'], PDO::PARAM_INT);
    $ins->bindValue(':likes', $p['likes'], PDO::PARAM_INT);
    $ins->execute();
    echo "Post: {$p['title']}\n";
}

echo "\nSeed complete.\n";
echo "Login: admin@admin.com / admin123\n";
echo "Login: author@author.com / author123\n";
