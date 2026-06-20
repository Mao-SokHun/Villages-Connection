<?php

/**
 * Front controller for PHP built-in server and Vercel (api/index.php).
 *
 * Local dev:
 *   php -S localhost:8080 -t public public/router.php
 *
 * Set PRETTY_URLS=true in .env for clean URLs.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === false || $uri === null) {
    $uri = '/';
}

$path = rtrim($uri, '/');
if ($path === '') {
    $path = '/';
}

$publicRoot = __DIR__;
$staticExtensions = array('css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'map', 'webmanifest', 'json', 'txt', 'html');

if ($path !== '/') {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext !== '' && in_array($ext, $staticExtensions, true)) {
        return false;
    }

    $candidate = $publicRoot . $path;
    if (is_file($candidate)) {
        if (preg_match('/\.php$/i', $path) && (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false)) {
            require $candidate;
            return true;
        }
        return false;
    }
}

require_once dirname(__DIR__) . '/app/bootstrap/paths.php';
require_once APP_PATH . '/Core/route_registry.php';

$publicRoutes = route_registry_public();
$adminRoutes = route_registry_admin();

foreach ($publicRoutes as $script => $prettyPath) {
    if ($path === $prettyPath) {
        require $publicRoot . '/' . $script;
        return true;
    }
}

foreach ($adminRoutes as $script => $prettyPath) {
    if ($path === $prettyPath) {
        require $publicRoot . '/admin/' . $script;
        return true;
    }
}

$feedParams = route_registry_feed_match($path);
if ($feedParams !== null) {
    foreach ($feedParams as $feedKey => $feedValue) {
        $_GET[$feedKey] = $feedValue;
    }
    require $publicRoot . '/index.php';
    return true;
}

if (preg_match('#^/post/([^/]+)/?$#', $path, $matches)) {
    $_GET['slug'] = rawurldecode($matches[1]);
    require $publicRoot . '/post.php';
    return true;
}

if (preg_match('#^/profile/([0-9]+)/?$#', $path, $matches)) {
    $_GET['id'] = (int) $matches[1];
    require $publicRoot . '/profile.php';
    return true;
}

if ($path === '/post') {
    require $publicRoot . '/post.php';
    return true;
}

if ($path === '/profile') {
    require $publicRoot . '/profile.php';
    return true;
}

if (preg_match('#^/api/([a-z0-9_-]+\.php)$#', $path, $matches)) {
    $apiFile = $publicRoot . '/api/' . $matches[1];
    if (is_file($apiFile)) {
        require $apiFile;
        return true;
    }
}

if (preg_match('#^/auth/([a-z0-9_-]+\.php)$#', $path, $matches)) {
    $authFile = $publicRoot . '/auth/' . $matches[1];
    if (is_file($authFile)) {
        require $authFile;
        return true;
    }
}

if (preg_match('#^/admin/([a-z0-9_-]+\.php)$#', $path, $matches)) {
    $adminFile = $publicRoot . '/admin/' . $matches[1];
    if (is_file($adminFile)) {
        require $adminFile;
        return true;
    }
}

if (preg_match('#^/([a-z0-9_-]+\.php)$#i', $path, $matches)) {
    $scriptFile = $publicRoot . '/' . $matches[1];
    if (is_file($scriptFile)) {
        require $scriptFile;
        return true;
    }
}

if ($path === '/') {
    require $publicRoot . '/index.php';
    return true;
}

http_response_code(404);
require $publicRoot . '/404.php';
return true;
