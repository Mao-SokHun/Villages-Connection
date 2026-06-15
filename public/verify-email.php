<?php

require_once dirname(__DIR__) . '/bootstrap.php';

$token = '';
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
}

if ($token == '') {
    header('Location: login.php');
    exit;
}

$result = verify_email_with_token($pdo, $token);
if ($result['ok']) {
    setFlashMessage('success', __('auth.email_verified'));
} else {
    setFlashMessage('danger', __('auth.verification_invalid'));
}

header('Location: login.php');
exit;
