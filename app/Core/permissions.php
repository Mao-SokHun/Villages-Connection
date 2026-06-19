<?php

function authenticated_user_from_db($pdo)
{
    static $user = null;
    static $resolved = false;

    if ($resolved) {
        return $user;
    }
    $resolved = true;

    if (!isLoggedIn()) {
        return null;
    }

    $user = get_user_by_id($pdo, (int) $_SESSION['user_id']);

    return $user;
}

function apply_user_to_session($user)
{
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['account_status'] = user_account_status($user);
    $_SESSION['is_banned'] = user_is_banned($user);
    if (isset($user['avatar'])) {
        $_SESSION['user_avatar'] = $user['avatar'];
    }
}

function ensure_active_authenticated_user($pdo)
{
    if (!isLoggedIn()) {
        return null;
    }

    $checked_at = isset($_SESSION['auth_checked_at']) ? (int) $_SESSION['auth_checked_at'] : 0;
    if ($checked_at > 0 && (time() - $checked_at) < 60) {
        return array(
            'id' => (int) $_SESSION['user_id'],
            'name' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '',
            'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '',
            'role' => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '',
            'avatar' => isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : '',
            'account_status' => 'active',
            'is_banned' => false,
        );
    }

    $user = authenticated_user_from_db($pdo);
    if (!$user || user_is_deleted($user)) {
        logout_closed_account();
    }
    if (user_is_banned($user)) {
        logout_banned_account();
    }

    apply_user_to_session($user);
    $_SESSION['auth_checked_at'] = time();

    return $user;
}

function authenticated_user_role($pdo)
{
    $user = ensure_active_authenticated_user($pdo);
    if (!$user) {
        return '';
    }

    return $user['role'];
}

function user_has_role($pdo, $roles)
{
    if (!isLoggedIn()) {
        return false;
    }

    if (!is_array($roles)) {
        $roles = array($roles);
    }

    $role = authenticated_user_role($pdo);
    if ($role == '') {
        return false;
    }

    return in_array($role, $roles, true);
}

function is_admin_user($pdo)
{
    return user_has_role($pdo, 'admin');
}

function is_staff_user($pdo)
{
    return user_has_role($pdo, array('admin', 'author'));
}
