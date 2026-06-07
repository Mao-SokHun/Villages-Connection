<?php

require_once dirname(__DIR__, 2) . '/bootstrap.php';

requireLogin();

$role = '';
if (isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
}

if ($role != 'admin' && $role != 'author') {
    setFlashMessage('danger', 'Access denied.');
    header('Location: ../index.php');
    exit;
}
