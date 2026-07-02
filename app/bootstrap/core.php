<?php

/**
 * Model module loader — order is fixed; do not reorder (logic lock).
 * Logic files live in app/Models/*.php — edit those for business rules.
 */

function bootstrap_models_path($file)
{
    return APP_PATH . '/Models/' . ltrim($file, '/');
}

/** @deprecated Use bootstrap_models_path() */
function bootstrap_core_path($file)
{
    return bootstrap_models_path($file);
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
        require_once bootstrap_models_path($file);
    }
}
