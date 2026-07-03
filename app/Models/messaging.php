<?php

/**
 * Direct Messaging model functions.
 */

function dm_get_or_create_conversation($pdo, $user_a, $user_b)
{
    $user_a = (int) $user_a;
    $user_b = (int) $user_b;
    if ($user_a <= 0 || $user_b <= 0 || $user_a === $user_b) {
        return null;
    }

    // Always store with lower id first to enforce UNIQUE(user_a, user_b)
    $lo = min($user_a, $user_b);
    $hi = max($user_a, $user_b);

    try {
        $stmt = $pdo->prepare('SELECT * FROM dm_conversations WHERE user_a = :a AND user_b = :b');
        $stmt->execute(array('a' => $lo, 'b' => $hi));
        $conv = $stmt->fetch();
        if ($conv) {
            return $conv;
        }

        $ins = $pdo->prepare('INSERT INTO dm_conversations (user_a, user_b) VALUES (:a, :b) RETURNING *');
        $ins->execute(array('a' => $lo, 'b' => $hi));
        return $ins->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function dm_get_user_conversations($pdo, $user_id, $limit = 50)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return array();
    }

    try {
        $sql = "SELECT c.*,
            CASE WHEN c.user_a = :uid THEN c.user_b ELSE c.user_a END AS other_user_id,
            u.name AS other_name,
            u.avatar AS other_avatar,
            u.email AS other_email,
            (SELECT body FROM dm_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_body,
            (SELECT created_at FROM dm_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_at,
            (SELECT COUNT(*) FROM dm_messages m WHERE m.conversation_id = c.id AND m.sender_id != :uid2 AND m.is_read = FALSE) AS unread_count
            FROM dm_conversations c
            JOIN users u ON u.id = CASE WHEN c.user_a = :uid3 THEN c.user_b ELSE c.user_a END
            WHERE c.user_a = :uid4 OR c.user_b = :uid5
            ORDER BY c.last_message_at DESC
            LIMIT " . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            'uid'  => $user_id,
            'uid2' => $user_id,
            'uid3' => $user_id,
            'uid4' => $user_id,
            'uid5' => $user_id,
        ));
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return array();
    }
}

function dm_get_messages($pdo, $conversation_id, $limit = 60)
{
    try {
        $sql = "SELECT m.*, u.name AS sender_name, u.avatar AS sender_avatar, u.email AS sender_email
            FROM dm_messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = :cid
            ORDER BY m.created_at ASC
            LIMIT " . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('cid' => (int) $conversation_id));
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return array();
    }
}

function dm_send_message($pdo, $conversation_id, $sender_id, $body)
{
    $body = trim((string) $body);
    if (strlen($body) < 1 || strlen($body) > 2000) {
        return array('ok' => false, 'error' => 'Message must be 1–2000 characters.');
    }

    try {
        $ins = $pdo->prepare('INSERT INTO dm_messages (conversation_id, sender_id, body) VALUES (:cid, :sid, :body) RETURNING id, created_at');
        $ins->execute(array(
            'cid'  => (int) $conversation_id,
            'sid'  => (int) $sender_id,
            'body' => $body,
        ));
        $row = $ins->fetch();

        // Update conversation last_message_at
        $pdo->prepare('UPDATE dm_conversations SET last_message_at = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute(array('id' => (int) $conversation_id));

        return array('ok' => true, 'id' => (int) $row['id'], 'created_at' => $row['created_at']);
    } catch (PDOException $e) {
        return array('ok' => false, 'error' => 'Could not send message.');
    }
}

function dm_mark_read($pdo, $conversation_id, $reader_id)
{
    try {
        $pdo->prepare('UPDATE dm_messages SET is_read = TRUE WHERE conversation_id = :cid AND sender_id != :uid AND is_read = FALSE')
            ->execute(array('cid' => (int) $conversation_id, 'uid' => (int) $reader_id));
    } catch (PDOException $e) {
        // silently ignore
    }
}

function dm_unread_count($pdo, $user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return 0;
    }
    try {
        $sql = "SELECT COUNT(*) FROM dm_messages m
            JOIN dm_conversations c ON c.id = m.conversation_id
            WHERE (c.user_a = :uid OR c.user_b = :uid2)
              AND m.sender_id != :uid3
              AND m.is_read = FALSE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => $user_id, 'uid2' => $user_id, 'uid3' => $user_id));
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function dm_user_can_access_conversation($pdo, $conversation_id, $user_id)
{
    try {
        $stmt = $pdo->prepare('SELECT id FROM dm_conversations WHERE id = :cid AND (user_a = :uid OR user_b = :uid2)');
        $stmt->execute(array('cid' => (int) $conversation_id, 'uid' => (int) $user_id, 'uid2' => (int) $user_id));
        return (bool) $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

function dm_delete_message($pdo, $message_id, $user_id)
{
    try {
        $stmt = $pdo->prepare('DELETE FROM dm_messages WHERE id = :id AND sender_id = :uid');
        $stmt->execute(array('id' => (int) $message_id, 'uid' => (int) $user_id));
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
