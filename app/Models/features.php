<?php

function search_posts($pdo, $query, $options = array())
{
    $query = trim((string) $query);
    if ($query == '') {
        return array('items' => array(), 'total' => 0);
    }

    $page = 1;
    if (isset($options['page'])) {
        $page = max(1, (int) $options['page']);
    }

    $per_page = 12;
    if (isset($options['per_page'])) {
        $per_page = max(1, min(50, (int) $options['per_page']));
    }

    $sort = 'relevance';
    if (isset($options['sort'])) {
        $sort = trim((string) $options['sort']);
    }

    $where = " WHERE p.status = 'Published'";
    $where .= sql_hide_inactive_authors('u');
    $params = array('search' => '%' . $query . '%');

    $where .= " AND (
        p.title ILIKE :search
        OR p.summary ILIKE :search
        OR p.content ILIKE :search
        OR p.location ILIKE :search
        OR u.name ILIKE :search
        OR c.name ILIKE :search
    )";

    if (!empty($options['category'])) {
        $where .= ' AND c.slug = :category';
        $params['category'] = trim((string) $options['category']);
    }

    $count_sql = 'SELECT COUNT(*) FROM posts p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN users u ON u.id = p.user_id' . $where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = (int) $count_stmt->fetchColumn();

    $order = ' ORDER BY p.created_at DESC, p.id DESC';
    if ($sort == 'popular') {
        $order = ' ORDER BY p.views DESC, p.likes DESC, p.id DESC';
    } elseif ($sort == 'latest') {
        $order = ' ORDER BY p.created_at DESC, p.id DESC';
    } elseif ($sort == 'relevance') {
        $order = " ORDER BY
            CASE WHEN p.title ILIKE :search THEN 0 ELSE 1 END,
            p.views DESC,
            p.created_at DESC";
    }

    $offset = ($page - 1) * $per_page;
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon, u.name as author_name
        FROM posts p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN users u ON u.id = p.user_id" . $where . $order . ' LIMIT ' . (int) $per_page . ' OFFSET ' . (int) $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return array(
        'items' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'pages' => $total > 0 ? (int) ceil($total / $per_page) : 1,
    );
}

function get_featured_posts($pdo, $limit = 3)
{
    $limit = max(1, min(12, (int) $limit));
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon, u.name as author_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.status = 'Published'
          AND p.is_featured = TRUE
          AND (p.expires_at IS NULL OR p.expires_at > CURRENT_TIMESTAMP)" .
        sql_hide_inactive_authors('u') .
        ' ORDER BY p.created_at DESC, p.id DESC LIMIT ' . $limit;

    return $pdo->query($sql)->fetchAll();
}

function search_authors($pdo, $query, $limit = 8)
{
    $query = trim((string) $query);
    if ($query == '') {
        return array();
    }

    $sql = "SELECT u.*
        FROM users u
        WHERE (u.name ILIKE :search OR u.bio ILIKE :search OR u.location ILIKE :search)
            AND COALESCE(u.account_status, 'active') != 'deleted'
            AND COALESCE(u.is_banned, FALSE) = FALSE
        ORDER BY u.name ASC
        LIMIT " . (int) $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('search' => '%' . $query . '%'));
    return $stmt->fetchAll();
}

function highlight_search_term($text, $query)
{
    $text = htmlspecialchars((string) $text);
    $query = trim((string) $query);
    if ($query == '') {
        return $text;
    }

    $pattern = '/' . preg_quote($query, '/') . '/iu';
    return preg_replace($pattern, '<mark class="search-hit">$0</mark>', $text);
}

function build_comment_tree($comments)
{
    $by_parent = array();
    $roots = array();

    foreach ($comments as $comment) {
        $parent_id = 0;
        if (isset($comment['parent_id']) && $comment['parent_id'] != '' && $comment['parent_id'] !== null) {
            $parent_id = (int) $comment['parent_id'];
        }

        if (!isset($by_parent[$parent_id])) {
            $by_parent[$parent_id] = array();
        }
        $by_parent[$parent_id][] = $comment;
    }

    if (isset($by_parent[0])) {
        $roots = $by_parent[0];
    }

    $attach = function ($nodes) use (&$attach, $by_parent) {
        $result = array();
        foreach ($nodes as $node) {
            $id = (int) $node['id'];
            $node['replies'] = array();
            if (isset($by_parent[$id])) {
                $node['replies'] = $attach($by_parent[$id]);
            }
            $result[] = $node;
        }
        return $result;
    };

    return $attach($roots);
}

function count_visible_comments($comments)
{
    return count($comments);
}

function create_post_comment($pdo, $post_id, $user_id, $author_name, $content, $parent_id = 0)
{
    $content = trim((string) $content);
    if (strlen($content) < 2) {
        return array('ok' => false, 'error' => 'Comment must be at least 2 characters.');
    }
    if (strlen($content) > 1000) {
        return array('ok' => false, 'error' => 'Comment is too long (max 1000 characters).');
    }

    if ($parent_id > 0) {
        $parent = get_comment_by_id($pdo, $parent_id);
        if (!$parent || (int) $parent['post_id'] != (int) $post_id) {
            return array('ok' => false, 'error' => 'Invalid reply target.');
        }
        if (isset($parent['parent_id']) && $parent['parent_id'] != '' && $parent['parent_id'] !== null) {
            return array('ok' => false, 'error' => 'Replies are limited to one level deep.');
        }
        if ($parent['status'] == 'deleted' || $parent['status'] == 'rejected') {
            return array('ok' => false, 'error' => 'Cannot reply to this comment.');
        }
    }

    $status = comments_require_approval() ? 'pending' : 'approved';
    $sql = 'INSERT INTO post_comments (post_id, user_id, author_name, content, status, parent_id)
        VALUES (:pid, :uid, :name, :content, :status, :parent_id) RETURNING id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'pid' => (int) $post_id,
        'uid' => (int) $user_id,
        'name' => $author_name,
        'content' => $content,
        'status' => $status,
        'parent_id' => $parent_id > 0 ? (int) $parent_id : null,
    ));

    $comment_id = (int) $stmt->fetchColumn();
    return array('ok' => true, 'id' => $comment_id, 'status' => $status);
}

function notify_comment_reply($pdo, $parent_comment_id, $replier_name, $post_slug, $post_title = '', $reply_comment_id = 0)
{
    $parent = get_comment_by_id($pdo, (int) $parent_comment_id);
    if (!$parent || !$parent['user_id']) {
        return;
    }

    if (isLoggedIn() && (int) $parent['user_id'] == (int) $_SESSION['user_id']) {
        return;
    }

    $safe_post_title = trim((string) $post_title);
    if ($safe_post_title == '') {
        $safe_post_title = 'your post';
    } else {
        $safe_post_title = '"' . excerpt($safe_post_title, 48) . '"';
    }
    $link = 'post/' . rawurlencode($post_slug) . '#comment-' . (int) $parent_comment_id;
    if ((int) $reply_comment_id > 0) {
        $link = 'post/' . rawurlencode($post_slug) . '#comment-' . (int) $reply_comment_id;
    }

    create_notification(
        $pdo,
        (int) $parent['user_id'],
        'comment_reply',
        'New reply on ' . $safe_post_title,
        $replier_name . ' replied to your comment on ' . $safe_post_title . '.',
        $link,
        true
    );
}

function user_bookmarked_post($pdo, $user_id, $post_id)
{
    if ($user_id <= 0 || $post_id <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT id FROM post_bookmarks WHERE user_id = :uid AND post_id = :pid');
    $stmt->execute(array('uid' => (int) $user_id, 'pid' => (int) $post_id));
    return (bool) $stmt->fetch();
}

function toggle_post_bookmark($pdo, $user_id, $post_id)
{
    if ($user_id <= 0 || $post_id <= 0) {
        return array('success' => false, 'message' => 'Invalid request.');
    }

    $stmt = $pdo->prepare("SELECT id, status FROM posts WHERE id = :id AND status = 'Published'");
    $stmt->execute(array('id' => (int) $post_id));
    $post = $stmt->fetch();
    if (!$post) {
        return array('success' => false, 'message' => 'Post not found.');
    }

    $check = $pdo->prepare('SELECT id FROM post_bookmarks WHERE user_id = :uid AND post_id = :pid');
    $check->execute(array('uid' => (int) $user_id, 'pid' => (int) $post_id));
    $existing = $check->fetch();

    if ($existing) {
        $pdo->prepare('DELETE FROM post_bookmarks WHERE id = :id')->execute(array('id' => (int) $existing['id']));
        return array('success' => true, 'bookmarked' => false, 'message' => 'Bookmark removed.');
    }

    $insert = $pdo->prepare('INSERT INTO post_bookmarks (user_id, post_id) VALUES (:uid, :pid)');
    $insert->execute(array('uid' => (int) $user_id, 'pid' => (int) $post_id));
    return array('success' => true, 'bookmarked' => true, 'message' => 'Post saved.');
}

function get_user_bookmarks($pdo, $user_id, $page = 1, $per_page = 12)
{
    if ($user_id <= 0) {
        return array('items' => array(), 'total' => 0, 'page' => 1, 'pages' => 1);
    }

    $page = max(1, (int) $page);
    $per_page = max(1, min(50, (int) $per_page));
    $offset = ($page - 1) * $per_page;

    $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM post_bookmarks b INNER JOIN posts p ON p.id = b.post_id WHERE b.user_id = :uid AND p.status = :status');
    $count_stmt->execute(array('uid' => (int) $user_id, 'status' => 'Published'));
    $total = (int) $count_stmt->fetchColumn();

    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, c.icon as category_icon, u.name as author_name, b.created_at as bookmarked_at
        FROM post_bookmarks b
        INNER JOIN posts p ON p.id = b.post_id
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN users u ON u.id = p.user_id
        WHERE b.user_id = :uid AND p.status = 'Published'" . sql_hide_inactive_authors('u') . "
        ORDER BY b.created_at DESC
        LIMIT " . (int) $per_page . ' OFFSET ' . (int) $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('uid' => (int) $user_id));
    $items = $stmt->fetchAll();

    return array(
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'pages' => $total > 0 ? (int) ceil($total / $per_page) : 1,
    );
}

function bookmark_count($pdo, $user_id)
{
    if ($user_id <= 0) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM post_bookmarks WHERE user_id = :uid');
        $stmt->execute(array('uid' => (int) $user_id));
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
