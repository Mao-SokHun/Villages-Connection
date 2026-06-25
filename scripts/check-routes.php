<?php

/**
 * Smoke-test public and admin pretty URLs.
 *
 * Usage:
 *   php scripts/check-routes.php
 *   php scripts/check-routes.php http://127.0.0.1:8080
 */

$base = isset($argv[1]) ? rtrim($argv[1], '/') : 'http://127.0.0.1:8080';

require_once __DIR__ . '/../app/bootstrap/paths.php';
require_once APP_PATH . '/Core/route_registry.php';

$paths = array(
    '/health.php',
    '/',
    '/popular',
    '/login',
    '/register',
    '/about',
    '/faq',
    '/help-us',
    '/contact',
    '/report',
    '/incident-report',
    '/privacy',
    '/terms',
    '/search',
    '/bookmarks',
    '/challenges',
    '/announcements',
    '/notifications',
    '/profile',
    '/support',
    '/forgot-password',
    '/resend-verification',
    '/admin',
    '/admin/posts',
    '/admin/categories',
    '/admin/comments',
    '/admin/users',
    '/admin/messages',
    '/admin/reports',
    '/admin/incidents',
    '/admin/challenges',
    '/admin/analytics',
    '/admin/settings',
    '/admin/announcements',
    '/admin/activity',
    '/admin/media',
);

foreach (route_registry_public() as $script => $pretty) {
    if (!in_array($pretty, $paths, true)) {
        $paths[] = $pretty;
    }
}
foreach (route_registry_admin() as $script => $pretty) {
    if (!in_array($pretty, $paths, true)) {
        $paths[] = $pretty;
    }
}

$paths = array_values(array_unique($paths));
sort($paths);

$failures = array();
$passed = 0;

echo "Route check: {$base}\n";
echo str_repeat('-', 60) . "\n";

foreach ($paths as $path) {
    $url = $base . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
    ));
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $ok = ($code >= 200 && $code < 400) || $code === 401 || $code === 403;
    if ($path === '/logout') {
        $ok = ($code === 302 || $code === 303 || $code === 405);
    }
    if (strpos($path, '/admin') === 0 || in_array($path, array('/bookmarks', '/notifications', '/support', '/settings/profile'), true)) {
        $ok = ($code === 302 || $code === 303 || $code === 200);
    }

    if ($err !== '') {
        $failures[] = array($path, 0, $err);
        echo "FAIL {$path} curl: {$err}\n";
        continue;
    }

    if ($ok) {
        $passed++;
        echo "OK   {$path} ({$code})\n";
    } else {
        $failures[] = array($path, $code, '');
        echo "FAIL {$path} ({$code})\n";
    }
}

echo str_repeat('-', 60) . "\n";
echo "Passed: {$passed}/" . count($paths) . "\n";

if (count($failures) > 0) {
    echo "\nFailures:\n";
    foreach ($failures as $row) {
        echo "  {$row[0]} => HTTP {$row[1]} {$row[2]}\n";
    }
    exit(1);
}

echo "All routes OK.\n";
