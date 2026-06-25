<?php

require_once __DIR__ . '/app/bootstrap/paths.php';

require_once CONFIG_PATH . '/config.php';
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}
require_once CONFIG_PATH . '/database.php';

app_start_session($pdo);

require_once APP_PATH . '/bootstrap/core.php';
bootstrap_load_core_modules(false);

init_locale();

$bootstrap_light_request = defined('BOOTSTRAP_LIGHT_REQUEST') && BOOTSTRAP_LIGHT_REQUEST;
$bootstrap_path = request_uri_path();
if (strpos($bootstrap_path, '/api/') === 0 || strpos($bootstrap_path, '/auth/') === 0) {
    $bootstrap_light_request = true;
}

$current_script = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $current_script = basename(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
}
$light_scripts = array(
    'set-language.php',
    'health.php',
    'login.php',
    'register.php',
    'forgot-password.php',
    'resend-verification.php',
);
if (in_array($current_script, $light_scripts, true)) {
    $bootstrap_light_request = true;
}

if (!$bootstrap_light_request) {
    ensure_admin_tables_loaded($pdo);
    archive_expired_posts($pdo);
} else {
    load_admin_settings($pdo);
    check_maintenance_mode();
}

if (isLoggedIn() && $current_script !== 'set-language.php') {
    ensure_active_authenticated_user($pdo);
}

if (!app_is_serverless()) {
    ensure_upload_directories();
}

require_once APP_PATH . '/bootstrap/autoload.php';
bootstrap_register_autoload();

enforce_pretty_url_redirect();
