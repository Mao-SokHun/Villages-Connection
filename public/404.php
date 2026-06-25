<?php
if (!defined('BOOTSTRAP_LIGHT_REQUEST')) {
    define('BOOTSTRAP_LIGHT_REQUEST', true);
}
require_once dirname(__DIR__) . '/bootstrap.php';

if (http_response_code() !== 404) {
    http_response_code(404);
}

$page_title = __('page.404.title');
$page_description = __('page.404.desc');

require_once ROOT_PATH . '/app/Views/layouts/header.php';
require ROOT_PATH . '/app/Views/pages/404.php';
require_once ROOT_PATH . '/app/Views/layouts/footer.php';
