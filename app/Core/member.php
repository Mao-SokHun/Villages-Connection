<?php

function create_notification($pdo, $user_id, $type, $title, $message, $link_url = '')
{
    if ($user_id <= 0) {
        return false;
    }

    try {
        $sql = 'INSERT INTO notifications (user_id, type, title, message, link_url) VALUES (:uid, :type, :title, :message, :link)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            'uid' => (int) $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link_url
        ));
        notify_user_by_email($pdo, (int) $user_id, $title, $message, $link_url);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function notify_user_by_email($pdo, $user_id, $subject, $message, $link_url = '')
{
    $user = get_user_by_id($pdo, $user_id);
    if (!$user || !isset($user['email']) || $user['email'] == '') {
        return false;
    }
    require_once APP_PATH . '/Core/mail.php';
    return send_activity_email($user['email'], $user['name'], $subject, $message, $link_url);
}

function unread_notification_count($pdo, $user_id)
{
    if ($user_id <= 0) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = FALSE');
        $stmt->execute(array('uid' => (int) $user_id));
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function get_recent_notifications($pdo, $user_id, $limit = 8)
{
    if ($user_id <= 0) {
        return array();
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute(array('uid' => (int) $user_id));
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return array();
    }
}

function mark_notification_read($pdo, $notification_id, $user_id)
{
    $sql = 'UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :uid';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('id' => (int) $notification_id, 'uid' => (int) $user_id));
    return $stmt->rowCount() > 0;
}

function mark_all_notifications_read($pdo, $user_id)
{
    $sql = 'UPDATE notifications SET is_read = TRUE WHERE user_id = :uid AND is_read = FALSE';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('uid' => (int) $user_id));
}

function is_following_user($pdo, $follower_id, $following_id)
{
    if ($follower_id <= 0 || $following_id <= 0 || $follower_id == $following_id) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_follows WHERE follower_id = :f AND following_id = :t');
        $stmt->execute(array('f' => (int) $follower_id, 't' => (int) $following_id));
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function follow_user($pdo, $follower_id, $following_id)
{
    if ($follower_id <= 0 || $following_id <= 0 || $follower_id == $following_id) {
        return array('ok' => false, 'error' => 'Invalid follow request.');
    }

    if (is_following_user($pdo, $follower_id, $following_id)) {
        return array('ok' => true, 'already' => true);
    }

    try {
        $sql = 'INSERT INTO user_follows (follower_id, following_id) VALUES (:f, :t)';
        $pdo->prepare($sql)->execute(array('f' => (int) $follower_id, 't' => (int) $following_id));

        $follower = get_user_by_id($pdo, $follower_id);
        if ($follower) {
            create_notification(
                $pdo,
                $following_id,
                'new_follower',
                'New follower',
                $follower['name'] . ' started following you.',
                'profile.php?id=' . (int) $follower_id
            );
        }

        log_activity($pdo, 'user.follow', 'User #' . $follower_id . ' followed #' . $following_id);
        return array('ok' => true);
    } catch (PDOException $e) {
        return array('ok' => false, 'error' => 'Could not follow user.');
    }
}

function unfollow_user($pdo, $follower_id, $following_id)
{
    $sql = 'DELETE FROM user_follows WHERE follower_id = :f AND following_id = :t';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array('f' => (int) $follower_id, 't' => (int) $following_id));
    log_activity($pdo, 'user.unfollow', 'User #' . $follower_id . ' unfollowed #' . $following_id);
    return $stmt->rowCount() > 0;
}

function follower_count($pdo, $user_id)
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_follows WHERE following_id = :uid');
        $stmt->execute(array('uid' => (int) $user_id));
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function following_count($pdo, $user_id)
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_follows WHERE follower_id = :uid');
        $stmt->execute(array('uid' => (int) $user_id));
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function get_comment_by_id($pdo, $comment_id)
{
    $stmt = $pdo->prepare('SELECT c.*, p.user_id as post_owner_id, p.slug as post_slug FROM post_comments c LEFT JOIN posts p ON p.id = c.post_id WHERE c.id = :id');
    $stmt->execute(array('id' => (int) $comment_id));
    return $stmt->fetch();
}

function can_manage_comment($comment)
{
    if (!is_array($comment)) {
        return false;
    }
    if (isAdmin()) {
        return true;
    }
    if (!isLoggedIn() || !isset($comment['user_id'])) {
        return false;
    }
    return (int) $comment['user_id'] == (int) $_SESSION['user_id'];
}

function update_own_comment($pdo, $comment_id, $content)
{
    $comment = get_comment_by_id($pdo, $comment_id);
    if (!$comment || !can_manage_comment($comment)) {
        return array('ok' => false, 'error' => 'You cannot edit this comment.');
    }

    $content = trim($content);
    if (strlen($content) < 2) {
        return array('ok' => false, 'error' => 'Comment must be at least 2 characters.');
    }
    if (strlen($content) > 1000) {
        return array('ok' => false, 'error' => 'Comment is too long.');
    }

    $status = $comment['status'];
    if (!isAdmin() && comments_require_approval() && $status == 'approved') {
        $status = 'pending';
    }

    $sql = 'UPDATE post_comments SET content = :content, status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
    $pdo->prepare($sql)->execute(array('content' => $content, 'status' => $status, 'id' => (int) $comment_id));
    log_activity($pdo, 'comment.updated', 'Comment #' . $comment_id);
    return array('ok' => true, 'status' => $status);
}

function delete_own_comment($pdo, $comment_id)
{
    $comment = get_comment_by_id($pdo, $comment_id);
    if (!$comment || !can_manage_comment($comment)) {
        return array('ok' => false, 'error' => 'You cannot delete this comment.');
    }

    $pdo->prepare('DELETE FROM post_comments WHERE id = :id')->execute(array('id' => (int) $comment_id));
    log_activity($pdo, 'comment.deleted', 'Comment #' . $comment_id);
    return array('ok' => true);
}

function notify_post_author_on_comment($pdo, $post_id, $commenter_name, $post_slug)
{
    $stmt = $pdo->prepare('SELECT user_id, title FROM posts WHERE id = :id');
    $stmt->execute(array('id' => (int) $post_id));
    $post = $stmt->fetch();
    if (!$post || !$post['user_id']) {
        return;
    }

    if (isLoggedIn() && (int) $post['user_id'] == (int) $_SESSION['user_id']) {
        return;
    }

    create_notification(
        $pdo,
        (int) $post['user_id'],
        'new_comment',
        'New comment on your post',
        $commenter_name . ' commented on "' . excerpt($post['title'], 40) . '".',
        'post/' . rawurlencode($post_slug) . '#comments'
    );
}

function notify_post_status_change($pdo, $post_id, $status)
{
    $stmt = $pdo->prepare('SELECT user_id, title, slug FROM posts WHERE id = :id');
    $stmt->execute(array('id' => (int) $post_id));
    $post = $stmt->fetch();
    if (!$post || !$post['user_id']) {
        return;
    }

    if ($status == 'Published') {
        create_notification(
            $pdo,
            (int) $post['user_id'],
            'post_approved',
            'Post approved',
            'Your post "' . excerpt($post['title'], 40) . '" is now live.',
            'post/' . rawurlencode($post['slug'])
        );
    } elseif ($status == 'Rejected') {
        create_notification(
            $pdo,
            (int) $post['user_id'],
            'post_rejected',
            'Post needs changes',
            'Your post "' . excerpt($post['title'], 40) . '" was not approved.',
            'admin/posts.php?action=edit&id=' . (int) $post_id
        );
    }
}

function notify_comment_approved($pdo, $comment_id)
{
    $comment = get_comment_by_id($pdo, $comment_id);
    if (!$comment || !$comment['user_id']) {
        return;
    }

    create_notification(
        $pdo,
        (int) $comment['user_id'],
        'comment_approved',
        'Comment approved',
        'Your comment is now visible on the post.',
        'post.php?slug=' . urlencode($comment['post_slug']) . '#comments'
    );
}

function author_draft_count($pdo, $user_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :uid AND status = 'Draft'");
    $stmt->execute(array('uid' => (int) $user_id));
    return (int) $stmt->fetchColumn();
}

function author_unread_counts($pdo, $user_id)
{
    $counts = array('pending_posts' => 0, 'pending_comments' => 0, 'notifications' => 0);
    if ($user_id <= 0) {
        return $counts;
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :uid AND status = 'Pending'");
        $stmt->execute(array('uid' => (int) $user_id));
        $counts['pending_posts'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_comments c
            INNER JOIN posts p ON p.id = c.post_id
            WHERE p.user_id = :uid AND c.status = 'pending'");
        $stmt->execute(array('uid' => (int) $user_id));
        $counts['pending_comments'] = (int) $stmt->fetchColumn();

        $counts['notifications'] = unread_notification_count($pdo, (int) $user_id);
    } catch (PDOException $e) {
        // Tables may not exist yet.
    }

    return $counts;
}

function can_moderate_post_comment($comment)
{
    if (isAdmin()) {
        return true;
    }
    if (!isLoggedIn() || !is_array($comment)) {
        return false;
    }
    if (!isset($comment['post_owner_id'])) {
        return false;
    }
    return (int) $comment['post_owner_id'] == (int) $_SESSION['user_id'];
}

function delete_post_owner_comment($pdo, $comment_id)
{
    $comment = get_comment_by_id($pdo, $comment_id);
    if (!$comment || !can_moderate_post_comment($comment)) {
        return array('ok' => false, 'error' => 'You cannot remove this comment.');
    }

    $pdo->prepare('DELETE FROM post_comments WHERE id = :id')->execute(array('id' => (int) $comment_id));
    log_activity($pdo, 'comment.removed_by_author', 'Comment #' . $comment_id);
    return array('ok' => true);
}

function author_media_files($pdo, $user_id)
{
    $files = array();
    $seen = array();

    $stmt = $pdo->prepare('SELECT id, title, image_url, video_url, video_type FROM posts WHERE user_id = :uid');
    $stmt->execute(array('uid' => (int) $user_id));
    foreach ($stmt->fetchAll() as $post) {
        if ($post['image_url'] != '' && !isset($seen['img:' . $post['image_url']])) {
            $seen['img:' . $post['image_url']] = true;
            $path = upload_path('') . $post['image_url'];
            if (is_file($path)) {
                $files[] = array(
                    'name' => $post['image_url'],
                    'subdir' => '',
                    'type' => 'image',
                    'post_id' => (int) $post['id'],
                    'post_title' => $post['title'],
                    'size' => filesize($path),
                    'modified' => filemtime($path),
                    'url' => media_url($post['image_url'], '')
                );
            }
        }

        if (isset($post['video_type']) && $post['video_type'] == 'upload' && $post['video_url'] != '' && !isset($seen['vid:' . $post['video_url']])) {
            $seen['vid:' . $post['video_url']] = true;
            $path = upload_path('videos') . $post['video_url'];
            if (is_file($path)) {
                $files[] = array(
                    'name' => $post['video_url'],
                    'subdir' => 'videos',
                    'type' => 'video',
                    'post_id' => (int) $post['id'],
                    'post_title' => $post['title'],
                    'size' => filesize($path),
                    'modified' => filemtime($path),
                    'url' => media_url($post['video_url'], 'videos')
                );
            }
        }
    }

    $user = get_user_by_id($pdo, (int) $user_id);
    if ($user && isset($user['avatar']) && $user['avatar'] != '' && !isset($seen['ava:' . $user['avatar']])) {
        $path = upload_path('avatars') . $user['avatar'];
        if (is_file($path)) {
            $files[] = array(
                'name' => $user['avatar'],
                'subdir' => 'avatars',
                'type' => 'avatar',
                'post_id' => 0,
                'post_title' => 'Profile avatar',
                'size' => filesize($path),
                'modified' => filemtime($path),
                'url' => media_url($user['avatar'], 'avatars')
            );
        }
    }

    usort($files, function ($a, $b) {
        return $b['modified'] - $a['modified'];
    });

    return $files;
}

function duplicate_author_post($pdo, $post_id)
{
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id');
    $stmt->execute(array('id' => (int) $post_id));
    $post = $stmt->fetch();
    if (!$post || !admin_can_manage_post($post)) {
        return array('ok' => false, 'error' => 'Post not found or access denied.');
    }

    $slug = slugify($post['title'] . '-copy');
    $check = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE slug = :slug');
    $check->execute(array('slug' => $slug));
    if ($check->fetchColumn() > 0) {
        $slug = $slug . '-' . time();
    }

    $sql = 'INSERT INTO posts (category_id, user_id, title, slug, summary, content, image_url, image_alt, video_url, video_type, location, is_featured, status)
            VALUES (:category_id, :user_id, :title, :slug, :summary, :content, :image_url, :image_alt, :video_url, :video_type, :location, FALSE, :status)
            RETURNING id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(
        'category_id' => $post['category_id'],
        'user_id' => (int) $post['user_id'],
        'title' => $post['title'] . ' (Copy)',
        'slug' => $slug,
        'summary' => $post['summary'],
        'content' => $post['content'],
        'image_url' => $post['image_url'],
        'image_alt' => isset($post['image_alt']) ? $post['image_alt'] : '',
        'video_url' => $post['video_url'],
        'video_type' => isset($post['video_type']) ? $post['video_type'] : 'none',
        'location' => isset($post['location']) ? $post['location'] : '',
        'status' => 'Draft'
    ));

    $new_id = (int) $stmt->fetchColumn();
    log_activity($pdo, 'post.duplicated', 'Post #' . $post_id . ' -> #' . $new_id);
    return array('ok' => true, 'id' => $new_id);
}

function notification_icon($type)
{
    if ($type == 'post_approved') {
        return 'fa-circle-check text-success';
    }
    if ($type == 'post_rejected') {
        return 'fa-circle-xmark text-danger';
    }
    if ($type == 'comment_approved') {
        return 'fa-comment-dots text-info';
    }
    if ($type == 'new_comment') {
        return 'fa-comment text-warning';
    }
    if ($type == 'new_follower') {
        return 'fa-user-plus text-info';
    }
    return 'fa-bell text-secondary';
}
