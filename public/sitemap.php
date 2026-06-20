<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$base = site_base_url();
$pages = array(
    'index.php',
    'about.php',
    'faq.php',
    'help-us.php',
    'contact.php',
    'report.php',
    'challenges.php',
    'terms.php',
    'privacy.php',
    'login.php',
    'register.php'
);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $page) {
    echo '  <url>';
    echo '<loc>' . htmlspecialchars($base . '/' . $page) . '</loc>';
    echo '<changefreq>weekly</changefreq>';
    echo '</url>' . "\n";
}

$cats = $pdo->query("SELECT slug FROM categories ORDER BY name ASC")->fetchAll();
foreach ($cats as $cat) {
    echo '  <url>';
    echo '<loc>' . htmlspecialchars($base . feed_url(array('cat' => $cat['slug']))) . '</loc>';
    echo '<changefreq>weekly</changefreq>';
    echo '</url>' . "\n";
}

$posts = $pdo->query("SELECT slug, created_at, updated_at FROM posts WHERE status = 'Published' AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP) ORDER BY COALESCE(updated_at, created_at) DESC")->fetchAll();
foreach ($posts as $post) {
    echo '  <url>';
    echo '<loc>' . htmlspecialchars($base . '/post/' . rawurlencode($post['slug'])) . '</loc>';
    $lastmod = $post['created_at'];
    if (isset($post['updated_at']) && $post['updated_at'] != '') {
        $lastmod = $post['updated_at'];
    }
    if ($lastmod != '') {
        echo '<lastmod>' . date('c', strtotime($lastmod)) . '</lastmod>';
    }
    echo '<changefreq>monthly</changefreq>';
    echo '</url>' . "\n";
}

$authors = $pdo->query("SELECT id FROM users WHERE role IN ('author', 'admin') ORDER BY id ASC")->fetchAll();
foreach ($authors as $author) {
    echo '  <url>';
    echo '<loc>' . htmlspecialchars($base . '/profile.php?id=' . (int) $author['id']) . '</loc>';
    echo '<changefreq>weekly</changefreq>';
    echo '</url>' . "\n";
}

echo '</urlset>';
