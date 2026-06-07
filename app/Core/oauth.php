<?php

function oauth_setting($key, $default)
{
    $value = getenv($key);
    if ($value == false || trim($value) == '') {
        return $default;
    }
    return trim($value);
}

function oauth_base_url()
{
    $configured = oauth_setting('OAUTH_BASE_URL', '');
    if ($configured != '') {
        return rtrim($configured, '/');
    }

    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $scheme = 'https';
    }

    $host = 'localhost';
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    }

    return $scheme . '://' . $host;
}

function oauth_redirect_uri($provider)
{
    if ($provider == 'google') {
        $custom = oauth_setting('GOOGLE_REDIRECT_URI', '');
        if ($custom != '') {
            return $custom;
        }
        return oauth_base_url() . '/auth/google-callback.php';
    }

    $custom = oauth_setting('FACEBOOK_REDIRECT_URI', '');
    if ($custom != '') {
        return $custom;
    }
    return oauth_base_url() . '/auth/facebook-callback.php';
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
    if ($state == '' || $saved != $state) {
        return false;
    }
    return true;
}

function oauth_http_request($method, $url, $body, $headers)
{
    if (!function_exists('curl_init')) {
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

    if ($response == false || $status < 200 || $status >= 300) {
        return false;
    }

    return $response;
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

function facebook_auth_url()
{
    $params = array(
        'client_id' => oauth_setting('FACEBOOK_APP_ID', ''),
        'redirect_uri' => oauth_redirect_uri('facebook'),
        'state' => oauth_start_state('facebook'),
        'scope' => 'email,public_profile',
        'response_type' => 'code'
    );

    return 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query($params);
}

function facebook_fetch_user($code)
{
    $token_url = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query(array(
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
        return false;
    }

    $profile_url = 'https://graph.facebook.com/me?' . http_build_query(array(
        'fields' => 'id,name,email,picture.type(large)',
        'access_token' => $token_data['access_token']
    ));

    $profile_raw = oauth_http_request('GET', $profile_url, '', array());
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

    $name = 'Facebook User';
    if (isset($profile['name']) && trim($profile['name']) != '') {
        $name = trim($profile['name']);
    }

    $avatar = '';
    if (isset($profile['picture']['data']['url'])) {
        $avatar = trim($profile['picture']['data']['url']);
    }

    return array(
        'provider' => 'facebook',
        'oauth_id' => (string) $profile['id'],
        'email' => $email,
        'name' => $name,
        'avatar' => $avatar
    );
}

function oauth_find_or_create_user($pdo, $profile)
{
    $provider = $profile['provider'];
    $oauth_id = $profile['oauth_id'];
    $email = $profile['email'];
    $name = $profile['name'];
    $avatar = $profile['avatar'];

    $sql = 'SELECT * FROM users WHERE oauth_provider = :provider AND oauth_id = :oauth_id LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('provider' => $provider, 'oauth_id' => $oauth_id));
    $user = $stmt->fetch();
    if ($user) {
        return $user;
    }

    if ($email != '') {
        $sql = 'SELECT * FROM users WHERE email = :email LIMIT 1';
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

            if ($avatar != '' && (!isset($user['avatar']) || $user['avatar'] == '')) {
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

    $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
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
}

function oauth_login_redirect($user)
{
    global $pdo;

    if (user_is_banned($user)) {
        setFlashMessage('danger', 'This account has been suspended.');
        header('Location: ../login.php');
        exit;
    }

    login_user_session($user);
    log_activity($pdo, 'user.oauth_login', $user['email']);
    setFlashMessage('success', 'Welcome, ' . $user['name'] . '!');

    if ($user['role'] == 'admin' || $user['role'] == 'author') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

function oauth_handle_callback($provider, $code, $state)
{
    global $pdo;

    if (!oauth_verify_state($provider, $state)) {
        setFlashMessage('danger', 'Social login failed. Please try again.');
        header('Location: ../login.php');
        exit;
    }

    $profile = false;
    if ($provider == 'google') {
        $profile = google_fetch_user($code);
    } elseif ($provider == 'facebook') {
        $profile = facebook_fetch_user($code);
    }

    if ($profile == false) {
        setFlashMessage('danger', 'Could not verify your social account. Check your app keys and redirect URI.');
        header('Location: ../login.php');
        exit;
    }

    $user = oauth_find_or_create_user($pdo, $profile);
    if (!$user) {
        setFlashMessage('danger', 'Could not create your account. Please try again.');
        header('Location: ../register.php');
        exit;
    }

    oauth_login_redirect($user);
}
