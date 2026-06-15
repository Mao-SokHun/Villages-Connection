<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

$file = '';
if (isset($_GET['file'])) {
    $file = trim($_GET['file']);
}

$path = database_backup_resolve_file($file);
if ($path == '') {
    http_response_code(404);
    echo 'Backup not found.';
    exit;
}

header('Content-Type: application/gzip');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
