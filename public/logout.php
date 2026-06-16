<?php

require_once dirname(__DIR__) . '/bootstrap.php';

require_http_method('POST');
require_valid_csrf();

$switch_account = isset($_POST['switch_account']) && $_POST['switch_account'] == '1';
if ($switch_account) {
    perform_logout('info', 'Choose another account to continue.');
    redirect_to('login.php?switch=1');
}

perform_logout('info', 'You have been successfully logged out.');
redirect_to('login.php');
