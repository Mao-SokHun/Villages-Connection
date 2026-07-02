<?php

function user_email_is_verified($user)
{
    if (!is_array($user)) {
        return false;
    }

    if (isset($user['email_verified_at']) && $user['email_verified_at'] != '' && $user['email_verified_at'] !== null) {
        return true;
    }

    return false;
}

function user_needs_email_verification($user)
{
    if (!email_verification_required()) {
        return false;
    }

    if (!is_array($user)) {
        return false;
    }

    if (is_oauth_user($user)) {
        return false;
    }

    if (user_has_managed_email($user)) {
        return false;
    }

    return !user_email_is_verified($user);
}

function mark_user_email_verified($pdo, $user_id)
{
    $sql = 'UPDATE users SET email_verified_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => (int) $user_id));
    return $stmt->rowCount() > 0;
}

function create_email_verification_token($pdo, $user_id)
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 86400);

    $sql = 'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (:uid, :hash, :exp)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'uid' => (int) $user_id,
        'hash' => $hash,
        'exp' => $expires,
    ));

    return $token;
}

function verify_email_with_token($pdo, $token)
{
    $token = trim((string) $token);
    if ($token == '') {
        return array('ok' => false, 'error' => 'missing_token');
    }

    $hash = hash('sha256', $token);
    $sql = "SELECT t.*, u.email, u.name
        FROM email_verification_tokens t
        INNER JOIN users u ON u.id = t.user_id
        WHERE t.token_hash = :hash AND t.used_at IS NULL AND t.expires_at > CURRENT_TIMESTAMP
        ORDER BY t.id DESC
        LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('hash' => $hash));
    $row = $stmt->fetch();

    if (!$row) {
        return array('ok' => false, 'error' => 'invalid_token');
    }

    $pdo->prepare('UPDATE email_verification_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = :id')
        ->execute(array('id' => (int) $row['id']));
    mark_user_email_verified($pdo, (int) $row['user_id']);

    return array(
        'ok' => true,
        'user_id' => (int) $row['user_id'],
        'email' => $row['email'],
        'name' => $row['name'],
    );
}

function send_user_verification_email($pdo, $user)
{
    if (!is_array($user) || !isset($user['id']) || !isset($user['email'])) {
        return false;
    }

    if (user_has_managed_email($user)) {
        return false;
    }

    require_once APP_PATH . '/Models/mail.php';

    $token = create_email_verification_token($pdo, (int) $user['id']);
    $verify_url = site_base_url() . '/verify-email.php?token=' . urlencode($token);

    return send_email_verification_email($user['email'], $user['name'], $verify_url);
}

function issue_verification_for_new_user($pdo, $user_id)
{
    $user = get_user_by_id($pdo, (int) $user_id);
    if (!$user || user_email_is_verified($user)) {
        return false;
    }

    return send_user_verification_email($pdo, $user);
}

function issue_verification_on_email_change($pdo, $user_id, $old_email, $new_email)
{
    $old_email = normalize_email($old_email);
    $new_email = normalize_email($new_email);

    if ($new_email == '' || $old_email == $new_email || is_placeholder_oauth_email($new_email)) {
        return false;
    }

    if (!email_verification_required()) {
        mark_user_email_verified($pdo, (int) $user_id);
        return false;
    }

    $sql = 'UPDATE users SET email_verified_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => (int) $user_id));

    $user = get_user_by_id($pdo, (int) $user_id);
    if (!$user) {
        return false;
    }

    return send_user_verification_email($pdo, $user);
}
