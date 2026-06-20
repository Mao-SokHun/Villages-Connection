<?php

function oauth_setting($key, $default)
{
    if (function_exists('env_var')) {
        $value = env_var($key, '');
    } else {
        $value = getenv($key);
        if ($value === false) {
            $value = '';
        }
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
        $value = trim($value);
    }
    if ($value == '') {
        return $default;
    }
    return $value;
}

function oauth_base_url()
{
    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != '') {
        $scheme = 'http';
        if (function_exists('request_scheme')) {
            $scheme = request_scheme();
        } elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $scheme = 'https';
        }
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }

    $configured = oauth_setting('OAUTH_BASE_URL', '');
    if ($configured != '') {
        return rtrim($configured, '/');
    }

    if (defined('APP_URL') && APP_URL != '') {
        return rtrim(APP_URL, '/');
    }

    return 'http://localhost:8080';
}

function oauth_redirect_uri($provider)
{
    $path = '/auth/google-callback.php';
    if ($provider == 'facebook') {
        $path = '/auth/facebook-callback.php';
    }

    return oauth_base_url() . $path;
}

function oauth_is_configured($provider)
{
    if ($provider == 'google') {
        $id = oauth_setting('GOOGLE_CLIENT_ID', '');
        $secret = oauth_setting('GOOGLE_CLIENT_SECRET', '');
        if ($id != '' && $secret != '') {
            return true;
        }
        return false;
    }

    if ($provider == 'facebook') {
        $id = oauth_setting('FACEBOOK_APP_ID', '');
        $secret = oauth_setting('FACEBOOK_APP_SECRET', '');
        if ($id != '' && $secret != '') {
            return true;
        }
        return false;
    }

    return false;
}

function oauth_any_configured()
{
    if (oauth_is_configured('google')) {
        return true;
    }
    if (oauth_is_configured('facebook')) {
        return true;
    }
    return false;
}

function oauth_start_state($provider)
{
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state_' . $provider] = $state;
    return $state;
}

function oauth_verify_state($provider, $state)
{
    $key = 'oauth_state_' . $provider;
    if (!isset($_SESSION[$key])) {
        return false;
    }
    $saved = $_SESSION[$key];
    unset($_SESSION[$key]);
    if ($state == '' || !hash_equals((string) $saved, (string) $state)) {
        return false;
    }
    return true;
}

function oauth_last_error()
{
    if (isset($GLOBALS['oauth_last_error'])) {
        return $GLOBALS['oauth_last_error'];
    }
    return '';
}

function oauth_set_last_error($message)
{
    $GLOBALS['oauth_last_error'] = $message;
}

function oauth_parse_api_error($response)
{
    if ($response == false || $response == '') {
        return 'Could not reach the provider API.';
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return 'Unexpected provider response.';
    }

    if (isset($data['error_description']) && $data['error_description'] != '') {
        return $data['error_description'];
    }
    if (isset($data['error']) && is_string($data['error']) && $data['error'] != '') {
        return $data['error'];
    }
    if (isset($data['error']['message'])) {
        return $data['error']['message'];
    }
    if (isset($data['error_message'])) {
        return $data['error_message'];
    }
    if (isset($data['error']['type'])) {
        return $data['error']['type'];
    }

    return 'Provider request failed.';
}

function oauth_http_request($method, $url, $body, $headers)
{
    if (!function_exists('curl_init')) {
        oauth_set_last_error('cURL is not available on the server.');
        return false;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    if (count($headers) > 0) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response == false) {
        oauth_set_last_error('Network error contacting provider.');
        return false;
    }

    if ($status < 200 || $status >= 300) {
        oauth_set_last_error(oauth_parse_api_error($response));
        return false;
    }

    oauth_set_last_error('');
    return $response;
}

function facebook_graph_version()
{
    return 'v22.0';
}

function google_auth_url()
{
    $params = array(
        'client_id' => oauth_setting('GOOGLE_CLIENT_ID', ''),
        'redirect_uri' => oauth_redirect_uri('google'),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => oauth_start_state('google'),
        'prompt' => 'select_account'
    );

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function google_fetch_user($code)
{
    $body = http_build_query(array(
        'code' => $code,
        'client_id' => oauth_setting('GOOGLE_CLIENT_ID', ''),
        'client_secret' => oauth_setting('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri' => oauth_redirect_uri('google'),
        'grant_type' => 'authorization_code'
    ));

    $token_raw = oauth_http_request('POST', 'https://oauth2.googleapis.com/token', $body, array(
        'Content-Type: application/x-www-form-urlencoded'
    ));

    if ($token_raw == false) {
        return false;
    }

    $token_data = json_decode($token_raw, true);
    if (!is_array($token_data) || !isset($token_data['access_token'])) {
        return false;
    }

    $profile_raw = oauth_http_request('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', '', array(
        'Authorization: Bearer ' . $token_data['access_token']
    ));

    if ($profile_raw == false) {
        return false;
    }

    $profile = json_decode($profile_raw, true);
    if (!is_array($profile) || !isset($profile['id'])) {
        return false;
    }

    $email = '';
    if (isset($profile['email'])) {
        $email = trim($profile['email']);
    }

    $name = 'Google User';
    if (isset($profile['name']) && trim($profile['name']) != '') {
        $name = trim($profile['name']);
    }

    $avatar = '';
    if (isset($profile['picture']) && trim($profile['picture']) != '') {
        $avatar = trim($profile['picture']);
    }

    return array(
        'provider' => 'google',
        'oauth_id' => (string) $profile['id'],
        'email' => $email,
        'name' => $name,
        'avatar' => $avatar
    );
}

function facebook_oauth_scope()
{
    $scope = oauth_setting('FACEBOOK_OAUTH_SCOPE', 'public_profile');
    if ($scope == '') {
        $scope = 'public_profile';
    }
    return $scope;
}

function facebook_auth_url()
{
    $params = array(
        'client_id' => oauth_setting('FACEBOOK_APP_ID', ''),
        'redirect_uri' => oauth_redirect_uri('facebook'),
        'state' => oauth_start_state('facebook'),
        'scope' => facebook_oauth_scope(),
        'response_type' => 'code'
    );

    return 'https://www.facebook.com/' . facebook_graph_version() . '/dialog/oauth?' . http_build_query($params);
}

function facebook_fetch_user($code)
{
    $graph = facebook_graph_version();
    $token_url = 'https://graph.facebook.com/' . $graph . '/oauth/access_token?' . http_build_query(array(
        'client_id' => oauth_setting('FACEBOOK_APP_ID', ''),
        'client_secret' => oauth_setting('FACEBOOK_APP_SECRET', ''),
        'redirect_uri' => oauth_redirect_uri('facebook'),
        'code' => $code
    ));

    $token_raw = oauth_http_request('GET', $token_url, '', array());
    if ($token_raw == false) {
        return false;
    }

    $token_data = json_decode($token_raw, true);
    if (!is_array($token_data) || !isset($token_data['access_token'])) {
        oauth_set_last_error('Facebook did not return an access token.');
        return false;
    }

    $fields = 'id,name,picture.type(large)';
    if (strpos(facebook_oauth_scope(), 'email') !== false) {
        $fields = 'id,name,email,picture.type(large)';
    }

    $profile_url = 'https://graph.facebook.com/' . $graph . '/me?' . http_build_query(array(
        'fields' => $fields,
        'access_token' => $token_data['access_token']
    ));

    $profile_raw = oauth_http_request('GET', $profile_url, '', array());
    if ($profile_raw == false) {
        return false;
    }

    $profile = json_decode($profile_raw, true);
    if (!is_array($profile) || !isset($profile['id'])) {
        oauth_set_last_error('Facebook profile response was invalid.');
        return false;
    }

    $email = '';
    if (isset($profile['email'])) {
        $email = trim($profile['email']);
    }

    $name = 'Facebook User';
    if (isset($profile['name']) && trim($profile['name']) != '') {
        $name = trim($profile['name']);
    }

    $avatar = '';
    if (isset($profile['picture']['data']['url'])) {
        $avatar = trim($profile['picture']['data']['url']);
    } elseif (isset($profile['picture']['url'])) {
        $avatar = trim($profile['picture']['url']);
    }

    return array(
        'provider' => 'facebook',
        'oauth_id' => (string) $profile['id'],
        'email' => $email,
        'name' => $name,
        'avatar' => $avatar
    );
}

function oauth_avatar_url_allowed($avatar_url)
{
    $parts = parse_url($avatar_url);
    if (!$parts || !isset($parts['scheme']) || !isset($parts['host'])) {
        return false;
    }

    if (strtolower($parts['scheme']) !== 'https') {
        return false;
    }

    $host = strtolower($parts['host']);
    $allowed_hosts = array(
        'lh3.googleusercontent.com',
        'lh4.googleusercontent.com',
        'lh5.googleusercontent.com',
        'lh6.googleusercontent.com',
        'platform-lookaside.fbsbx.com',
        'graph.facebook.com',
    );

    foreach ($allowed_hosts as $allowed) {
        if ($host === $allowed) {
            return true;
        }
        $suffix = '.' . $allowed;
        if (strlen($host) > strlen($suffix) && substr($host, -strlen($suffix)) === $suffix) {
            return true;
        }
    }

    return false;
}

function oauth_download_avatar($avatar_url)
{
    if ($avatar_url == '' || !function_exists('upload_path')) {
        return '';
    }

    if (!oauth_avatar_url_allowed($avatar_url)) {
        return '';
    }

    if (!function_exists('curl_init')) {
        return '';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $avatar_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; VillageConnect/1.0)');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8'
    ));

    $data = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($data == false || $status < 200 || $status >= 300 || strlen($data) < 64) {
        return '';
    }

    $ext = 'jpg';
    if (is_string($content_type)) {
        if (strpos($content_type, 'png') !== false) {
            $ext = 'png';
        } elseif (strpos($content_type, 'webp') !== false) {
            $ext = 'webp';
        } elseif (strpos($content_type, 'gif') !== false) {
            $ext = 'gif';
        }
    }

    $name = 'oauth_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $path = upload_path('avatars') . $name;

    if (file_put_contents($path, $data) !== false) {
        return $name;
    }

    return '';
}

function oauth_prepare_avatar_for_db($avatar)
{
    if ($avatar == '') {
        return '';
    }

    if (function_exists('is_external_avatar') && is_external_avatar($avatar)) {
        $local = oauth_download_avatar($avatar);
        if ($local != '') {
            return $local;
        }

        return $avatar;
    }

    if (function_exists('normalize_avatar_filename')) {
        $avatar = normalize_avatar_filename($avatar);
    }

    if (strlen($avatar) > 255) {
        return substr($avatar, 0, 255);
    }

    return $avatar;
}

function oauth_fetch_provider_avatar_url($provider, $oauth_id)
{
    $provider = strtolower(trim((string) $provider));
    $oauth_id = trim((string) $oauth_id);
    if ($oauth_id == '') {
        return '';
    }

    if ($provider == 'facebook' && oauth_is_configured('facebook')) {
        $token = oauth_setting('FACEBOOK_APP_ID', '') . '|' . oauth_setting('FACEBOOK_APP_SECRET', '');
        $graph = facebook_graph_version();
        $url = 'https://graph.facebook.com/' . $graph . '/' . rawurlencode($oauth_id) . '/picture?' . http_build_query(array(
            'type' => 'large',
            'redirect' => 'false',
            'access_token' => $token
        ));

        $raw = oauth_http_request('GET', $url, '', array());
        if ($raw == false) {
            return '';
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['data']['url'])) {
            return '';
        }

        return trim($data['data']['url']);
    }

    return '';
}

function oauth_ensure_user_avatar($pdo, $user)
{
    if (!is_array($user) || !isset($user['id']) || !is_oauth_user($user)) {
        return $user;
    }

    $oauth_id = '';
    if (isset($user['oauth_id'])) {
        $oauth_id = trim((string) $user['oauth_id']);
    }

    $provider = '';
    if (isset($user['oauth_provider'])) {
        $provider = trim((string) $user['oauth_provider']);
    }

    $avatar = '';
    if (isset($user['avatar'])) {
        $avatar = normalize_avatar_filename($user['avatar']);
    }

    if ($avatar != '' && user_avatar_exists($avatar)) {
        return $user;
    }

    $picture = oauth_fetch_provider_avatar_url($provider, $oauth_id);
    if ($picture == '') {
        return $user;
    }

    return oauth_refresh_user_profile($pdo, $user, $picture);
}

function oauth_refresh_user_profile($pdo, $user, $avatar)
{
    if ($avatar == '' || !is_array($user) || !isset($user['id'])) {
        return $user;
    }

    $prepared = oauth_prepare_avatar_for_db($avatar);
    if ($prepared == '') {
        return $user;
    }

    $current = '';
    if (isset($user['avatar'])) {
        $current = $user['avatar'];
    }

    $should_update = false;
    if ($current == '') {
        $should_update = true;
    } elseif (function_exists('is_external_avatar') && is_external_avatar($current)) {
        $should_update = true;
    } elseif (function_exists('normalize_avatar_filename')) {
        $normalized = normalize_avatar_filename($current);
        if ($normalized != '' && !is_external_avatar($normalized) && !user_avatar_exists($normalized)) {
            $should_update = true;
        }
    }

    if (!$should_update) {
        return $user;
    }

    if ($current != '' && !is_external_avatar($current) && function_exists('delete_upload')) {
        delete_upload($current, 'avatars');
    }

    $stmt = $pdo->prepare('UPDATE users SET avatar = :avatar WHERE id = :id');
    $stmt->execute(array(
        'avatar' => $prepared,
        'id' => $user['id']
    ));

    return get_user_by_id($pdo, $user['id']);
}

function oauth_find_or_create_user($pdo, $profile)
{
    $provider = $profile['provider'];
    $oauth_id = $profile['oauth_id'];
    $email = $profile['email'];
    $name = $profile['name'];
    $avatar = oauth_prepare_avatar_for_db($profile['avatar']);

    $sql = 'SELECT * FROM users WHERE oauth_provider = :provider AND oauth_id = :oauth_id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('provider' => $provider, 'oauth_id' => $oauth_id));
    $user = $stmt->fetch();
    if ($user) {
        return oauth_refresh_user_profile($pdo, $user, $profile['avatar']);
    }

    if ($email != '') {
        $email = normalize_email($email);
        $sql = 'SELECT * FROM users WHERE LOWER(email) = :email LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('email' => $email));
        $user = $stmt->fetch();

        if ($user) {
            $sql = 'UPDATE users SET oauth_provider = :provider, oauth_id = :oauth_id';
            $params = array(
                'provider' => $provider,
                'oauth_id' => $oauth_id,
                'id' => $user['id']
            );

            if ($avatar != '' && (!isset($user['avatar']) || $user['avatar'] == '' || is_external_avatar($user['avatar']))) {
                $sql = $sql . ', avatar = :avatar';
                $params['avatar'] = $avatar;
            }

            $sql = $sql . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return get_user_by_id($pdo, $user['id']);
        }
    }

    if ($email == '') {
        $email = $provider . '_' . $oauth_id . '@oauth.local';
    }

    $email = normalize_email($email);

    $sql = 'SELECT COUNT(*) FROM users WHERE LOWER(email) = :email';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('email' => $email));
    if ((int) $stmt->fetchColumn() > 0) {
        $email = $provider . '_' . $oauth_id . '@oauth.local';
    }

    $hash = password_hash(bin2hex(random_bytes(24)), PASSWORD_BCRYPT);
    $sql = "INSERT INTO users (name, email, password, role, avatar, oauth_provider, oauth_id)
            VALUES (:name, :email, :password, 'author', :avatar, :provider, :oauth_id)
            RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'name' => $name,
        'email' => $email,
        'password' => $hash,
        'avatar' => $avatar,
        'provider' => $provider,
        'oauth_id' => $oauth_id
    ));

    $new_id = (int) $stmt->fetchColumn();
    return get_user_by_id($pdo, $new_id);
}

function login_user_session($user)
{
    regenerate_session_on_login();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_avatar'] = '';
    if (isset($user['avatar'])) {
        $_SESSION['user_avatar'] = $user['avatar'];
    }
    $_SESSION['oauth_provider'] = 'local';
    if (isset($user['oauth_provider']) && $user['oauth_provider'] != '') {
        $_SESSION['oauth_provider'] = $user['oauth_provider'];
    }
    $_SESSION['account_status'] = user_account_status($user);
    $_SESSION['is_banned'] = user_is_banned($user);
    $_SESSION['ui_theme'] = 'system';
    if (isset($user['ui_theme']) && ($user['ui_theme'] == 'light' || $user['ui_theme'] == 'dark' || $user['ui_theme'] == 'system')) {
        $_SESSION['ui_theme'] = $user['ui_theme'];
    }
    $_SESSION['ui_density'] = 'comfortable';
    if (isset($user['ui_density']) && ($user['ui_density'] == 'comfortable' || $user['ui_density'] == 'compact')) {
        $_SESSION['ui_density'] = $user['ui_density'];
    }
    remember_recent_account($user);
}

function remember_recent_account($user)
{
    if (!is_array($user) || !isset($user['email']) || trim((string) $user['email']) == '') {
        return;
    }

    $items = array();
    if (isset($_COOKIE['vc_recent_accounts']) && $_COOKIE['vc_recent_accounts'] != '') {
        $decoded = json_decode($_COOKIE['vc_recent_accounts'], true);
        if (is_array($decoded)) {
            $items = $decoded;
        }
    }

    $email = trim((string) $user['email']);
    $clean = array();
    foreach ($items as $item) {
        if (!is_array($item) || !isset($item['email'])) {
            continue;
        }
        $item_email = trim((string) $item['email']);
        if ($item_email == '' || strcasecmp($item_email, $email) == 0) {
            continue;
        }
        $clean[] = array(
            'name' => isset($item['name']) ? sanitize_plain_text_field($item['name'], 80) : '',
            'email' => sanitize_plain_text_field($item_email, 120),
            'avatar' => isset($item['avatar']) ? sanitize_plain_text_field($item['avatar'], 255) : '',
            'provider' => isset($item['provider']) ? sanitize_plain_text_field($item['provider'], 20) : 'local',
            'last_login' => isset($item['last_login']) ? (int) $item['last_login'] : 0,
        );
    }

    $clean = array_slice($clean, 0, 4);
    array_unshift($clean, array(
        'name' => isset($user['name']) ? sanitize_plain_text_field($user['name'], 80) : '',
        'email' => sanitize_plain_text_field($email, 120),
        'avatar' => isset($user['avatar']) ? sanitize_plain_text_field($user['avatar'], 255) : '',
        'provider' => isset($user['oauth_provider']) && $user['oauth_provider'] != '' ? sanitize_plain_text_field($user['oauth_provider'], 20) : 'local',
        'last_login' => time(),
    ));

    $value = json_encode($clean);
    if (!is_string($value)) {
        return;
    }

    $is_secure = request_is_https();
    setcookie('vc_recent_accounts', $value, array(
        'expires' => time() + (86400 * 180),
        'path' => '/',
        'secure' => $is_secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
}

function oauth_login_redirect($user)
{
    global $pdo;

    if (user_is_deleted($user)) {
        setFlashMessage('danger', 'This account has been closed.');
        redirect_to('login.php');
    }

    if (user_is_banned($user)) {
        setFlashMessage('danger', 'This account has been suspended.');
        redirect_to('login.php');
    }

    login_user_session($user);
    if (!is_placeholder_oauth_email($user['email'])) {
        mark_user_email_verified($pdo, (int) $user['id']);
    }
    log_activity($pdo, 'user.oauth_login', $user['email']);
    setFlashMessage('success', 'Welcome, ' . $user['name'] . '!');

    if ($user['role'] == 'admin') {
        admin_redirect_to('dashboard.php');
    } else {
        redirect_to('index.php');
    }
}

function oauth_handle_callback($provider, $code, $state)
{
    global $pdo;

    if (!oauth_verify_state($provider, $state)) {
        setFlashMessage('danger', 'Social login failed. Please try again.');
        redirect_to('login.php');
    }

    $profile = false;
    if ($provider == 'google') {
        $profile = google_fetch_user($code);
    } elseif ($provider == 'facebook') {
        $profile = facebook_fetch_user($code);
    }

    if ($profile == false) {
        $message = 'Could not verify your social account. Check your app keys and redirect URI.';
        $provider_error = oauth_last_error();
        if ($provider_error != '') {
            $message = 'Social login failed: ' . $provider_error;
        }
        setFlashMessage('danger', $message);
        redirect_to('login.php');
    }

    $user = oauth_find_or_create_user($pdo, $profile);
    if (!$user) {
        setFlashMessage('danger', 'Could not create your account. Please try again.');
        redirect_to('register.php');
    }

    oauth_login_redirect($user);
}
