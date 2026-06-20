<?php

/**
 * Single source of truth for public and admin URL paths.
 * Nginx pretty routes (docker/nginx/snippets/pretty-routes.conf) mirror these paths.
 */

function route_registry_public()
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
        'incident-report.php' => '/incident-report',
        'privacy.php' => '/privacy',
        'terms.php' => '/terms',
        'search.php' => '/search',
        'bookmarks.php' => '/bookmarks',
        'challenges.php' => '/challenges',
        'announcements.php' => '/announcements',
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

function route_registry_admin()
{
    return array(
        'dashboard.php' => '/admin',
        'posts.php' => '/admin/posts',
        'categories.php' => '/admin/categories',
        'comments.php' => '/admin/comments',
        'users.php' => '/admin/users',
        'messages.php' => '/admin/messages',
        'reports.php' => '/admin/reports',
        'incidents.php' => '/admin/incidents',
        'challenges.php' => '/admin/challenges',
        'analytics.php' => '/admin/analytics',
        'settings.php' => '/admin/settings',
        'announcements.php' => '/admin/announcements',
        'activity.php' => '/admin/activity',
        'media.php' => '/admin/media',
        'my-media.php' => '/admin/my-media',
        'my-comments.php' => '/admin/my-comments',
    );
}

function route_registry_named()
{
    return array(
        'home' => 'index.php',
        'login' => 'login.php',
        'register' => 'register.php',
        'logout' => 'logout.php',
        'about' => 'about.php',
        'faq' => 'faq.php',
        'help' => 'help-us.php',
        'contact' => 'contact.php',
        'report' => 'report.php',
        'incident' => 'incident-report.php',
        'privacy' => 'privacy.php',
        'terms' => 'terms.php',
        'search' => 'search.php',
        'bookmarks' => 'bookmarks.php',
        'challenges' => 'challenges.php',
        'announcements' => 'announcements.php',
        'notifications' => 'notifications.php',
        'profile' => 'profile.php',
        'settings.profile' => 'edit-profile.php',
        'settings.delete' => 'delete-account.php',
        'forgot-password' => 'forgot-password.php',
        'reset-password' => 'reset-password.php',
        'verify-email' => 'verify-email.php',
        'resend-verification' => 'resend-verification.php',
        'support' => 'support.php',
        'admin' => 'admin/dashboard.php',
        'admin.posts' => 'admin/posts.php',
        'admin.categories' => 'admin/categories.php',
        'admin.comments' => 'admin/comments.php',
        'admin.users' => 'admin/users.php',
        'admin.messages' => 'admin/messages.php',
        'admin.reports' => 'admin/reports.php',
        'admin.incidents' => 'admin/incidents.php',
        'admin.challenges' => 'admin/challenges.php',
        'admin.analytics' => 'admin/analytics.php',
        'admin.settings' => 'admin/settings.php',
        'admin.announcements' => 'admin/announcements.php',
        'admin.activity' => 'admin/activity.php',
        'admin.media' => 'admin/media.php',
        'admin.my-media' => 'admin/my-media.php',
        'admin.my-comments' => 'admin/my-comments.php',
    );
}

function route_registry_skip_pretty_redirect()
{
    return array(
        '/set-language.php',
        '/sitemap.php',
        '/404.php',
    );
}

function route_registry_skip_pretty_prefixes()
{
    return array(
        '/api/',
        '/auth/',
        '/admin/auth',
    );
}

/**
 * Feed pretty paths — mirrored in public/router.php and docker/nginx/snippets/pretty-routes.conf
 */
function route_registry_feed_match($path)
{
    $path = rtrim((string) $path, '/');
    if ($path === '') {
        $path = '/';
    }

    if ($path === '/popular') {
        return array('sort' => 'popular');
    }

    if ($path === '/following') {
        return array('sort' => 'following');
    }

    if (preg_match('#^/category/([^/]+)$#', $path, $matches)) {
        return array('cat' => rawurldecode($matches[1]));
    }

    if (preg_match('#^/popular/category/([^/]+)$#', $path, $matches)) {
        return array(
            'sort' => 'popular',
            'cat' => rawurldecode($matches[1]),
        );
    }

    if (preg_match('#^/following/category/([^/]+)$#', $path, $matches)) {
        return array(
            'sort' => 'following',
            'cat' => rawurldecode($matches[1]),
        );
    }

    return null;
}

function public_pretty_route_map()
{
    return route_registry_public();
}

function admin_pretty_route_map()
{
    return route_registry_admin();
}

function route_url($name, $query = array())
{
    $named = route_registry_named();
    if (!isset($named[$name])) {
        return app_url('index.php');
    }

    $script = $named[$name];
    $path = $script;

    if ($query !== array() && is_array($query)) {
        $path = $script . '?' . http_build_query($query);
    }

    if (strpos($script, 'admin/') === 0) {
        return admin_area_url($path);
    }

    return app_url($path);
}

function auth_url($provider)
{
    $provider = strtolower(trim((string) $provider));
    if ($provider != 'google' && $provider != 'facebook') {
        return app_url('login.php');
    }

    return '/auth/' . $provider . '.php';
}

function admin_redirect_to($admin_path, $status = 302)
{
    $url = admin_area_url($admin_path);
    header('Location: ' . $url, true, (int) $status);
    exit;
}
