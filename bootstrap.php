<?php

define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');

require_once ROOT_PATH . '/config/config.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}
require_once ROOT_PATH . '/config/database.php';
require_once APP_PATH . '/Core/uploads.php';
require_once APP_PATH . '/Core/helpers.php';
require_once APP_PATH . '/Core/permissions.php';
require_once APP_PATH . '/Core/urls.php';
require_once APP_PATH . '/Core/admin.php';
require_once APP_PATH . '/Core/member.php';
require_once APP_PATH . '/Core/i18n.php';
require_once APP_PATH . '/Core/verification.php';
require_once APP_PATH . '/Core/features.php';
require_once APP_PATH . '/Core/analytics.php';
require_once APP_PATH . '/Core/push.php';
require_once APP_PATH . '/Core/backup.php';

init_locale();

ensure_admin_tables_loaded($pdo);

if (isLoggedIn()) {
    ensure_active_authenticated_user($pdo);
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, $len));
    $file = APP_PATH . '/' . $relative . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
