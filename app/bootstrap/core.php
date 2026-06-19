<?php

/**
 * Core module loader — order is fixed; do not reorder (logic lock).
 * Edit app/Core/*.php for business logic; edit this file only to register new modules.
 */

function bootstrap_core_path($file)
{
    return APP_PATH . '/Core/' . ltrim($file, '/');
}

function bootstrap_load_core_modules($lite = false)
{
    $modules = array(
        'uploads.php',
        'helpers.php',
        'permissions.php',
    );

    if (!$lite) {
        $modules[] = 'route_registry.php';
        $modules[] = 'urls.php';
    }

    $modules = array_merge($modules, array(
        'admin.php',
        'member.php',
    ));

    if (!$lite) {
        $modules = array_merge($modules, array(
            'i18n.php',
            'verification.php',
            'analytics.php',
        ));
    }

    $modules[] = 'features.php';
    $modules[] = 'push.php';

    foreach ($modules as $file) {
        require_once bootstrap_core_path($file);
    }
}
