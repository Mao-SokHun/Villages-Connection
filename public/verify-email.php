<?php

require_once dirname(__DIR__) . '/bootstrap.php';

$token = '';
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
}

if ($token == '') {
    redirect_to('login.php');
}

$result = verify_email_with_token($pdo, $token);
if ($result['ok']) {
    setFlashMessage('success', __('auth.email_verified'));
} else {
    setFlashMessage('danger', __('auth.verification_invalid'));
}

redirect_to('login.php');
