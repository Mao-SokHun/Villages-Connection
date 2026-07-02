<?php

/**
 * Front controller for PHP built-in server.
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
        return false;
    }
}

require_once dirname(__DIR__) . '/app/bootstrap/paths.php';
require_once APP_PATH . '/Models/route_registry.php';

function route_dispatch_public($script)
{
    require_once ROOT_PATH . '/bootstrap.php';
    require APP_PATH . '/Http/Controllers/Public/' . $script;
}

function route_dispatch_admin($script)
{
    require_once ROOT_PATH . '/bootstrap.php';
    require APP_PATH . '/Http/Controllers/Admin/' . $script;
}

function route_dispatch_api($script)
{
    require_once ROOT_PATH . '/bootstrap-api.php';
    require APP_PATH . '/Http/Controllers/Api/' . $script;
}

function route_dispatch_auth($script)
{
    require_once ROOT_PATH . '/bootstrap.php';
    require APP_PATH . '/Http/Controllers/Auth/' . $script;
}

$publicRoutes = route_registry_public();
$adminRoutes = route_registry_admin();

foreach ($publicRoutes as $script => $prettyPath) {
    if ($path === $prettyPath) {
        route_dispatch_public($script);
        return true;
    }
}

foreach ($adminRoutes as $script => $prettyPath) {
    if ($path === $prettyPath) {
        route_dispatch_admin($script);
        return true;
    }
}

$feedParams = route_registry_feed_match($path);
if ($feedParams !== null) {
    foreach ($feedParams as $feedKey => $feedValue) {
        $_GET[$feedKey] = $feedValue;
    }
    route_dispatch_public('index.php');
    return true;
}

if (preg_match('#^/post/([^/]+)/?$#', $path, $matches)) {
    $_GET['slug'] = rawurldecode($matches[1]);
    route_dispatch_public('post.php');
    return true;
}

if (preg_match('#^/profile/([0-9]+)/?$#', $path, $matches)) {
    $_GET['id'] = (int) $matches[1];
    route_dispatch_public('profile.php');
    return true;
}

if ($path === '/post') {
    route_dispatch_public('post.php');
    return true;
}

if ($path === '/profile') {
    route_dispatch_public('profile.php');
    return true;
}

if (preg_match('#^/api/([a-z0-9_-]+\.php)$#', $path, $matches)) {
    $apiFile = APP_PATH . '/Http/Controllers/Api/' . $matches[1];
    if (is_file($apiFile)) {
        route_dispatch_api($matches[1]);
        return true;
    }
}

if (preg_match('#^/auth/([a-z0-9_-]+\.php)$#', $path, $matches)) {
    $authFile = APP_PATH . '/Http/Controllers/Auth/' . $matches[1];
    if (is_file($authFile)) {
        route_dispatch_auth($matches[1]);
        return true;
    }
}

if (preg_match('#^/admin/([a-z0-9_-]+\.php)$#', $path, $matches)) {
    $adminFile = APP_PATH . '/Http/Controllers/Admin/' . $matches[1];
    if (is_file($adminFile)) {
        route_dispatch_admin($matches[1]);
        return true;
    }
}

if (preg_match('#^/([a-z0-9_-]+\.php)$#i', $path, $matches)) {
    $script = $matches[1];
    $controllerFile = APP_PATH . '/Http/Controllers/Public/' . $script;
    if (is_file($controllerFile)) {
        route_dispatch_public($script);
        return true;
    }
}

if ($path === '/') {
    route_dispatch_public('index.php');
    return true;
}

if (!defined('BOOTSTRAP_LIGHT_REQUEST')) {
    define('BOOTSTRAP_LIGHT_REQUEST', true);
}
http_response_code(404);
route_dispatch_public('404.php');
return true;
