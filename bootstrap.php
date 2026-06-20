<?php

require_once __DIR__ . '/app/bootstrap/paths.php';

require_once CONFIG_PATH . '/config.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}
require_once CONFIG_PATH . '/database.php';

require_once APP_PATH . '/bootstrap/core.php';
bootstrap_load_core_modules(false);

init_locale();

ensure_admin_tables_loaded($pdo);
archive_expired_posts($pdo);

$current_script = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $current_script = basename(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
}

if (isLoggedIn() && $current_script !== 'set-language.php') {
    ensure_active_authenticated_user($pdo);
}

ensure_upload_directories();

require_once APP_PATH . '/bootstrap/autoload.php';
bootstrap_register_autoload();

enforce_pretty_url_redirect();
