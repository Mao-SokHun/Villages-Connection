<?php
require_once dirname(__DIR__) . '/bootstrap.php';

http_response_code(404);

$page_title = 'Page Not Found';
$page_description = 'The page you requested could not be found on ' . SITE_NAME . '.';

require_once ROOT_PATH . '/app/Views/layouts/header.php';
require ROOT_PATH . '/app/Views/pages/404.php';
require_once ROOT_PATH . '/app/Views/layouts/footer.php';
