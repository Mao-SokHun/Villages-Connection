<?php

/**
 * Single source of truth for pretty URL paths.
 * Sync nginx: php scripts/generate_pretty_routes.php
 */

function app_public_routes()
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

function app_admin_routes()
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

function app_route_names()
{
    return array(
        'home' => 'index.php',
        'login' => 'login.php',
        'register' => 'register.php',
        'logout' => 'logout.php',
        'about' => 'about.php',
        'faq' => 'faq.php',
        'help-us' => 'help-us.php',
        'contact' => 'contact.php',
        'report' => 'report.php',
        'incident-report' => 'incident-report.php',
        'privacy' => 'privacy.php',
        'terms' => 'terms.php',
        'search' => 'search.php',
        'bookmarks' => 'bookmarks.php',
        'challenges' => 'challenges.php',
        'announcements' => 'announcements.php',
        'notifications' => 'notifications.php',
        'profile' => 'profile.php',
        'edit-profile' => 'edit-profile.php',
        'delete-account' => 'delete-account.php',
        'forgot-password' => 'forgot-password.php',
        'reset-password' => 'reset-password.php',
        'verify-email' => 'verify-email.php',
        'resend-verification' => 'resend-verification.php',
        'support' => 'support.php',
        'admin.dashboard' => 'admin/dashboard.php',
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

function app_internal_route_files()
{
    return array(
        'set-language.php',
        'sitemap.php',
        '404.php',
        'admin/auth.php',
        'admin/backup-download.php',
    );
}

function app_internal_route_prefixes()
{
    return array('/api/', '/auth/', '/admin/auth');
}

function app_dynamic_route_rules()
{
    return array(
        array(
            'nginx' => 'location ~ ^/post/([^/]+)/?$ { try_files $uri /post.php?slug=$1; }',
            'php_pattern' => '#^/post/([^/]+)/?$#',
            'php_target' => 'post.php',
            'php_query' => 'slug',
        ),
        array(
            'nginx' => 'location ~ ^/profile/([0-9]+)/?$ { rewrite ^ /profile.php?id=$1 last; }',
            'php_pattern' => '#^/profile/([0-9]+)/?$#',
            'php_target' => 'profile.php',
            'php_query' => 'id',
        ),
    );
}
