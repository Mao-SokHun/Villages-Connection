<?php


$locale = 'en';
if (isset($_GET['lang'])) {
    $locale = trim((string) $_GET['lang']);
}

set_user_locale($locale);

$redirect = safe_redirect_path(isset($_GET['redirect']) ? $_GET['redirect'] : '', 'index.php');

app_commit_session();

header('Location: ' . app_url($redirect));
exit;
