<?php

/**
 * Extra feature helpers: emoji reactions, polls, @mentions.
 * Loaded automatically by bootstrap/core.php
 */

/* ============================================================
 * Emoji Reactions
 * ============================================================ */

function get_post_reactions($pdo, $post_id)
{
    try {
        $stmt = $pdo->prepare('SELECT reaction, COUNT(*) AS total FROM post_reactions WHERE post_id = :pid GROUP BY reaction');
        $stmt->execute(array('pid' => (int) $post_id));
        $rows = $stmt->fetchAll();
        $counts = array();
        foreach ($rows as $r) {
            $counts[$r['reaction']] = (int) $r['total'];
        }
        return $counts;
    } catch (PDOException $e) {
        return array();
    }
}

function get_user_post_reaction($pdo, $post_id, $user_id)
{
    if ($user_id <= 0) {
        return null;
    }
    try {
        $stmt = $pdo->prepare('SELECT reaction FROM post_reactions WHERE post_id = :pid AND user_id = :uid');
        $stmt->execute(array('pid' => (int) $post_id, 'uid' => (int) $user_id));
        $row = $stmt->fetch();
        return $row ? $row['reaction'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

function reaction_emoji($type)
{
    $map = array(
        'like'  => '👍',
        'love'  => '❤️',
        'haha'  => '😂',
        'sad'   => '😢',
        'angry' => '😡',
        'pray'  => '🙏',
    );
    return isset($map[$type]) ? $map[$type] : '👍';
}

function reaction_label($type)
{
    $map = array(
        'like'  => 'Like',
        'love'  => 'Love',
        'haha'  => 'Haha',
        'sad'   => 'Sad',
        'angry' => 'Angry',
        'pray'  => 'Pray',
    );
    return isset($map[$type]) ? $map[$type] : 'Like';
}

function allowed_reactions()
{
    return array('like', 'love', 'haha', 'sad', 'angry', 'pray');
}

/* ============================================================
 * Poll helpers
 * ============================================================ */

function get_post_poll($pdo, $post_id)
{
    try {
        $stmt = $pdo->prepare('SELECT * FROM polls WHERE post_id = :pid');
        $stmt->execute(array('pid' => (int) $post_id));
        $poll = $stmt->fetch();
        if (!$poll) {
            return null;
        }
        $opt_stmt = $pdo->prepare('SELECT * FROM poll_options WHERE poll_id = :pid ORDER BY sort_order ASC');
        $opt_stmt->execute(array('pid' => (int) $poll['id']));
        $poll['options'] = $opt_stmt->fetchAll();
        return $poll;
    } catch (PDOException $e) {
        return null;
    }
}

function get_poll_vote_counts($pdo, $poll_id)
{
    try {
        $stmt = $pdo->prepare('SELECT option_id, COUNT(*) AS votes FROM poll_votes WHERE poll_id = :pid GROUP BY option_id');
        $stmt->execute(array('pid' => (int) $poll_id));
        $counts = array();
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['option_id']] = (int) $row['votes'];
        }
        return $counts;
    } catch (PDOException $e) {
        return array();
    }
}

function get_user_poll_votes($pdo, $poll_id, $user_id)
{
    if ($user_id <= 0) {
        return array();
    }
    try {
        $stmt = $pdo->prepare('SELECT option_id FROM poll_votes WHERE poll_id = :pid AND user_id = :uid');
        $stmt->execute(array('pid' => (int) $poll_id, 'uid' => (int) $user_id));
        return array_column($stmt->fetchAll(), 'option_id');
    } catch (PDOException $e) {
        return array();
    }
}

/* ============================================================
 * @Mentions in comments
 * ============================================================ */

function parse_mention_usernames($text)
{
    $matches = array();
    preg_match_all('/@([a-zA-Z0-9_\-]{2,40})/', (string) $text, $matches);
    return array_unique($matches[1]);
}

function notify_mentioned_users($pdo, $text, $commenter_name, $post_slug, $post_title, $comment_id, $exclude_user_id = 0)
{
    $usernames = parse_mention_usernames($text);
    if (count($usernames) === 0) {
        return;
    }

    $link = 'post/' . rawurlencode($post_slug) . '#comment-' . (int) $comment_id;
    $safe_title = '"' . excerpt($post_title, 48) . '"';

    foreach ($usernames as $username) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE name = :name AND COALESCE(is_banned, FALSE) = FALSE AND COALESCE(account_status, 'active') != 'deleted' LIMIT 1");
            $stmt->execute(array('name' => $username));
            $user = $stmt->fetch();
            if (!$user || (int) $user['id'] === (int) $exclude_user_id) {
                continue;
            }
            create_notification(
                $pdo,
                (int) $user['id'],
                'mention',
                $commenter_name . ' mentioned you',
                $commenter_name . ' mentioned you in a comment on ' . $safe_title . '.',
                $link,
                false
            );
        } catch (PDOException $e) {
            // Silently ignore
        }
    }
}
