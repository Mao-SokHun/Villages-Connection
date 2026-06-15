<?php

function pretty_urls_enabled()
{
    $value = getenv('PRETTY_URLS');
    if ($value === 'false' || $value === '0') {
        return false;
    }

    return true;
}

function public_pretty_route_map()
{
    return array(
        'index.php' => '/',
        'login.php' => '/login',
        'register.php' => '/register',
        'logout.php' => '/logout',
        'about.php' => '/about',
        'faq.php' => '/faq',
        'help-us.php' => '/help-us',
        'contact.php' => '/contact',
        'report.php' => '/report',
        'privacy.php' => '/privacy',
        'terms.php' => '/terms',
        'search.php' => '/search',
        'bookmarks.php' => '/bookmarks',
        'notifications.php' => '/notifications',
        'profile.php' => '/profile',
        'edit-profile.php' => '/settings/profile',
        'delete-account.php' => '/settings/delete-account',
        'forgot-password.php' => '/forgot-password',
        'reset-password.php' => '/reset-password',
        'verify-email.php' => '/verify-email',
        'resend-verification.php' => '/resend-verification',
        'support.php' => '/support',
        'post.php' => '/post',
    );
}

function admin_pretty_route_map()
{
    return array(
        'dashboard.php' => '/admin',
        'posts.php' => '/admin/posts',
        'categories.php' => '/admin/categories',
        'comments.php' => '/admin/comments',
        'users.php' => '/admin/users',
        'messages.php' => '/admin/messages',
        'reports.php' => '/admin/reports',
        'analytics.php' => '/admin/analytics',
        'settings.php' => '/admin/settings',
        'announcements.php' => '/admin/announcements',
        'activity.php' => '/admin/activity',
        'media.php' => '/admin/media',
        'my-media.php' => '/admin/my-media',
        'my-comments.php' => '/admin/my-comments',
    );
}

function pretty_route_lookup($script_path)
{
    $script_path = ltrim(str_replace('\\', '/', $script_path), '/');
    $query = '';

    if (strpos($script_path, '?') !== false) {
        $parts = explode('?', $script_path, 2);
        $script_path = $parts[0];
        $query = $parts[1];
    }

    if (strpos($script_path, 'admin/') === 0) {
        $admin_file = substr($script_path, strlen('admin/'));
        $map = admin_pretty_route_map();
        if (isset($map[$admin_file])) {
            $path = $map[$admin_file];
            if ($query != '') {
                $path .= '?' . $query;
            }
            return $path;
        }
    }

    $basename = basename($script_path);
    $map = public_pretty_route_map();
    if (isset($map[$basename])) {
        $path = $map[$basename];
        if ($query != '') {
            $path .= '?' . $query;
        }
        return $path;
    }

    return null;
}

function app_url($path, $base_path = '')
{
    if ($path == '' || $path == null) {
        $path = 'index.php';
    }

    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }

    if (strpos($path, '/') === 0 && strpos($path, '//') !== 0) {
        return $path;
    }

    if (pretty_urls_enabled()) {
        $pretty = pretty_route_lookup($path);
        if ($pretty !== null) {
            return $pretty;
        }
    }

    return $base_path . ltrim($path, '/');
}

function admin_area_url($path)
{
    $path = ltrim((string) $path, '/');
    if (strpos($path, 'admin/') !== 0) {
        $path = 'admin/' . $path;
    }

    if (pretty_urls_enabled()) {
        $pretty = pretty_route_lookup($path);
        if ($pretty !== null) {
            return $pretty;
        }
    }

    return '/' . $path;
}

function create_post_url($base_path = '')
{
    if (isLoggedIn()) {
        if (pretty_urls_enabled()) {
            return '/admin/posts?action=add';
        }
        return $base_path . 'admin/posts.php?action=add';
    }

    return app_url('register.php', $base_path);
}

function post_url($slug, $base_path = '')
{
    $slug = rawurlencode($slug);
    if (pretty_urls_enabled()) {
        return '/post/' . $slug;
    }

    return $base_path . 'post/' . $slug;
}

function profile_url($user_id, $base_path = '')
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return app_url('profile.php', $base_path);
    }

    if (pretty_urls_enabled()) {
        return '/profile/' . $user_id;
    }

    return $base_path . 'profile.php?id=' . $user_id;
}

function redirect_to($path, $status = 302)
{
    $url = app_url($path);
    header('Location: ' . $url, true, (int) $status);
    exit;
}

function request_uri_path()
{
    $uri = '/';
    if (isset($_SERVER['REQUEST_URI'])) {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    if ($uri == '' || $uri == false) {
        return '/';
    }

    return rtrim($uri, '/') === '' ? '/' : rtrim($uri, '/');
}

function exposed_php_redirect_url()
{
    if (!pretty_urls_enabled()) {
        return null;
    }

    $script = '';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $script = $_SERVER['SCRIPT_NAME'];
    }

    $script = str_replace('\\', '/', $script);
    if (strpos($script, '/public/') !== false) {
        $script = substr($script, strpos($script, '/public/') + strlen('/public'));
    }

    if (substr($script, -4) !== '.php') {
        return null;
    }

    $skip_prefixes = array('/api/', '/auth/', '/admin/auth');
    foreach ($skip_prefixes as $prefix) {
        if (strpos($script, $prefix) === 0) {
            return null;
        }
    }

    $skip_files = array(
        '/set-language.php',
        '/sitemap.php',
        '/404.php',
    );
    if (in_array($script, $skip_files, true)) {
        return null;
    }

    $relative = ltrim($script, '/');
    if (strpos($relative, 'admin/') === 0) {
        $pretty = pretty_route_lookup($relative);
    } elseif (basename($relative) === 'profile.php' && isset($_GET['id']) && (int) $_GET['id'] > 0) {
        $pretty = profile_url((int) $_GET['id']);
    } else {
        $pretty = pretty_route_lookup(basename($relative));
    }

    if ($pretty === null) {
        return null;
    }

    $query = '';
    if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
        $query = '?' . $_SERVER['QUERY_STRING'];
    }

    return $pretty . $query;
}

function enforce_pretty_url_redirect()
{
    $target = exposed_php_redirect_url();
    if ($target === null) {
        return;
    }

    $current = request_uri_path();
    $target_path = parse_url($target, PHP_URL_PATH);
    if ($target_path == false) {
        $target_path = $target;
    }
    $target_path = rtrim($target_path, '/') === '' ? '/' : rtrim($target_path, '/');

    if ($current === $target_path) {
        return;
    }

    $base = '';
    if (defined('APP_URL') && APP_URL != '') {
        $base = rtrim(APP_URL, '/');
    }

    header('Location: ' . $base . $target, true, 301);
    exit;
}
