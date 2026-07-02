<?php

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

function push_is_configured()
{
    $public = push_setting('VAPID_PUBLIC_KEY', '');
    $private = push_setting('VAPID_PRIVATE_KEY', '');
    return $public != '' && $private != '' && class_exists(WebPush::class);
}

function push_setting($key, $default = '')
{
    $val = getenv($key);
    if ($val == false || $val == '') {
        return $default;
    }
    return $val;
}

function push_vapid_public_key()
{
    return push_setting('VAPID_PUBLIC_KEY', '');
}

function push_save_subscription($pdo, $user_id, $endpoint, $p256dh, $auth_key, $user_agent = '')
{
    $sql = "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth_key, user_agent, updated_at)
        VALUES (:uid, :endpoint, :p256dh, :auth, :ua, CURRENT_TIMESTAMP)
        ON CONFLICT (endpoint) DO UPDATE SET
            user_id = EXCLUDED.user_id,
            p256dh = EXCLUDED.p256dh,
            auth_key = EXCLUDED.auth_key,
            user_agent = EXCLUDED.user_agent,
            updated_at = CURRENT_TIMESTAMP";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'uid' => (int) $user_id,
        'endpoint' => $endpoint,
        'p256dh' => $p256dh,
        'auth' => $auth_key,
        'ua' => substr($user_agent, 0, 255),
    ));
    return true;
}

function push_remove_subscription($pdo, $user_id, $endpoint)
{
    $stmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE user_id = :uid AND endpoint = :endpoint');
    $stmt->execute(array('uid' => (int) $user_id, 'endpoint' => $endpoint));
    return $stmt->rowCount() > 0;
}

function push_user_subscription_count($pdo, $user_id)
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM push_subscriptions WHERE user_id = :uid');
    $stmt->execute(array('uid' => (int) $user_id));
    return (int) $stmt->fetchColumn();
}

function push_send_to_user($pdo, $user_id, $title, $body, $url = '')
{
    if (!push_is_configured()) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT * FROM push_subscriptions WHERE user_id = :uid');
    $stmt->execute(array('uid' => (int) $user_id));
    $subs = $stmt->fetchAll();
    if (count($subs) == 0) {
        return 0;
    }

    $sent = 0;
    foreach ($subs as $sub) {
        $ok = push_send_one($pdo, $sub, $title, $body, $url);
        if ($ok === true) {
            $sent++;
        }
    }

    return $sent;
}

function push_send_one($pdo, $subscription, $title, $body, $url = '')
{
    if (!push_is_configured()) {
        return 0;
    }

    $auth = array(
        'VAPID' => array(
            'subject' => push_setting('VAPID_SUBJECT', 'mailto:' . SITE_CONTACT_EMAIL),
            'publicKey' => push_vapid_public_key(),
            'privateKey' => push_setting('VAPID_PRIVATE_KEY', ''),
        ),
    );

    try {
        $webPush = new WebPush($auth);
        $sub = Subscription::create(array(
            'endpoint' => $subscription['endpoint'],
            'keys' => array(
                'p256dh' => $subscription['p256dh'],
                'auth' => $subscription['auth_key'],
            ),
        ));

        $payload = json_encode(array(
            'title' => $title,
            'body' => excerpt(strip_tags($body), 180),
            'url' => $url,
            'icon' => app_url('icons/icon-192.svg'),
        ));

        $report = $webPush->sendOneNotification($sub, $payload);
        if ($report->isSuccess()) {
            return true;
        }
        if ($report->isSubscriptionExpired()) {
            $pdo->prepare('DELETE FROM push_subscriptions WHERE id = :id')->execute(array('id' => (int) $subscription['id']));
            return false;
        }
    } catch (Exception $e) {
        return 0;
    }

    return 0;
}

function push_generate_vapid_keys()
{
    if (!class_exists(VAPID::class)) {
        return null;
    }

    return VAPID::createVapidKeys();
}
