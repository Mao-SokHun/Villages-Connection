<?php


require_http_method('POST');
require_valid_csrf();

perform_logout('info', 'You have been successfully logged out.');
redirect_to('login.php');
