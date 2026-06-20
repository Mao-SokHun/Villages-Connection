<?php

function pretty_urls_enabled()
{
    $value = getenv('PRETTY_URLS');
    if ($value === 'false' || $value === '0') {
        return false;
    }

    return true;
}

function feed_pretty_path($sort, $cat)
{
    $sort = trim((string) $sort);
    $cat = trim((string) $cat);

    if ($cat !== '') {
        if ($sort === 'popular') {
            return '/popular/category/' . rawurlencode($cat);
        }
        if ($sort === 'following') {
            return '/following/category/' . rawurlencode($cat);
        }

        return '/category/' . rawurlencode($cat);
    }

    if ($sort === 'popular') {
        return '/popular';
    }
    if ($sort === 'following') {
        return '/following';
    }

    return '/';
}

function feed_url($params = array(), $base_path = '')
{
    $sort = 'latest';
    if (isset($params['sort'])) {
        $sort = trim((string) $params['sort']);
        if ($sort === '') {
            $sort = 'latest';
        }
    }

    $cat = isset($params['cat']) ? trim((string) $params['cat']) : '';
    $search = isset($params['search']) ? trim((string) $params['search']) : '';
    $author = isset($params['author']) ? (int) $params['author'] : 0;
    $page = isset($params['page']) ? (int) $params['page'] : 0;

    if (!pretty_urls_enabled()) {
        return build_query_url($base_path . 'index.php', array(
            'sort' => $sort !== 'latest' ? $sort : '',
            'cat' => $cat,
            'search' => $search,
            'author' => $author > 0 ? $author : '',
            'page' => $page > 1 ? $page : '',
        ));
    }

    $path = feed_pretty_path($sort, $cat);
    $query = array();

    if ($search !== '') {
        $query['search'] = $search;
    }
    if ($author > 0) {
        $query['author'] = $author;
    }
    if ($page > 1) {
        $query['page'] = $page;
    }

    if (count($query) === 0) {
        return $path;
    }

    return $path . '?' . http_build_query($query);
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

    if (strpos($path, '//') === 0) {
        $path = 'index.php';
    }

    if (strpos($path, '/') === 0 && strpos($path, '//') !== 0) {
        return $path;
    }

    if (pretty_urls_enabled()) {
        $normalized = ltrim(str_replace('\\', '/', (string) $path), '/');
        if (preg_match('#^index\.php(?:\?(.*))?$#i', $normalized, $index_matches)) {
            $feed_params = array();
            if (isset($index_matches[1]) && $index_matches[1] !== '') {
                parse_str($index_matches[1], $feed_params);
            }

            return feed_url($feed_params, $base_path);
        }

        if (preg_match('#^post\.php\?(.*)$#', $normalized, $post_matches)) {
            parse_str($post_matches[1], $post_params);
            if (isset($post_params['slug']) && trim((string) $post_params['slug']) !== '') {
                return post_url(trim((string) $post_params['slug']), $base_path);
            }
        }

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
        return admin_area_url('posts.php?action=add');
    }

    return app_url('register.php', $base_path);
}

function post_url($slug, $base_path = '')
{
    $slug = trim(rawurldecode((string) $slug));
    if ($slug === '') {
        return app_url('index.php', $base_path);
    }

    if (pretty_urls_enabled()) {
        return '/post/' . rawurlencode($slug);
    }

    return app_url('post.php?slug=' . rawurlencode($slug), $base_path);
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
    $path = safe_redirect_path($path, 'index.php');
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

    foreach (route_registry_skip_pretty_prefixes() as $prefix) {
        if (strpos($script, $prefix) === 0) {
            return null;
        }
    }

    if (in_array($script, route_registry_skip_pretty_redirect(), true)) {
        return null;
    }

    $relative = ltrim($script, '/');
    if (strpos($relative, 'admin/') === 0) {
        $pretty = pretty_route_lookup($relative);
    } elseif (basename($relative) === 'profile.php' && isset($_GET['id']) && (int) $_GET['id'] > 0) {
        $pretty = profile_url((int) $_GET['id']);
    } elseif (basename($relative) === 'post.php' && isset($_GET['slug']) && trim((string) $_GET['slug']) !== '') {
        $pretty = post_url(trim((string) $_GET['slug']));
    } elseif (basename($relative) === 'index.php') {
        $feed_params = array();
        if (isset($_GET['sort']) && trim((string) $_GET['sort']) !== '' && trim((string) $_GET['sort']) !== 'latest') {
            $feed_params['sort'] = trim((string) $_GET['sort']);
        }
        if (isset($_GET['cat']) && trim((string) $_GET['cat']) !== '') {
            $feed_params['cat'] = trim((string) $_GET['cat']);
        }
        if (isset($_GET['search']) && trim((string) $_GET['search']) !== '') {
            $feed_params['search'] = trim((string) $_GET['search']);
        }
        if (isset($_GET['author']) && (int) $_GET['author'] > 0) {
            $feed_params['author'] = (int) $_GET['author'];
        }
        if (isset($_GET['page']) && (int) $_GET['page'] > 1) {
            $feed_params['page'] = (int) $_GET['page'];
        }
        $pretty = feed_url($feed_params);
    } else {
        $pretty = pretty_route_lookup(basename($relative));
    }

    if ($pretty === null) {
        return null;
    }

    if (basename($relative) === 'index.php') {
        return $pretty;
    }

    $query = '';
    $slug_in_path = (basename($relative) === 'post.php' && isset($_GET['slug']) && trim((string) $_GET['slug']) !== '')
        || (basename($relative) === 'profile.php' && isset($_GET['id']) && (int) $_GET['id'] > 0);
    if (!$slug_in_path && isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') {
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

    header('Location: ' . $target, true, 301);
    exit;
}
