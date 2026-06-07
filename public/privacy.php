<?php
require_once dirname(__DIR__) . '/bootstrap.php';
$controller = new App\Controllers\PageController();
$controller->privacy();
