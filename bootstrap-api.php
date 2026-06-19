<?php

require_once __DIR__ . '/app/bootstrap/paths.php';
define('BOOTSTRAP_LITE', true);

require_once CONFIG_PATH . '/config.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}
require_once CONFIG_PATH . '/database.php';

require_once APP_PATH . '/bootstrap/core.php';
bootstrap_load_core_modules(true);

load_admin_settings($pdo);

if (isLoggedIn()) {
    ensure_active_authenticated_user($pdo);
}
