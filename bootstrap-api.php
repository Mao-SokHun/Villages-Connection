<?php

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('BOOTSTRAP_LITE', true);

require_once ROOT_PATH . '/config/config.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}
require_once ROOT_PATH . '/config/database.php';
require_once APP_PATH . '/Core/helpers.php';
require_once APP_PATH . '/Core/permissions.php';
require_once APP_PATH . '/Core/admin.php';
require_once APP_PATH . '/Core/member.php';
require_once APP_PATH . '/Core/features.php';
require_once APP_PATH . '/Core/push.php';

load_admin_settings($pdo);

if (isLoggedIn()) {
    ensure_active_authenticated_user($pdo);
}
