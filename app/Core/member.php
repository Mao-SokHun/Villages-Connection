<?php

function create_notification($pdo, $user_id, $type, $title, $message, $link_url = '', $send_email = true)
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
        if ($send_email) {
            notify_user_by_email($pdo, (int) $user_id, $title, $message, $link_url);
        }
        if (function_exists('push_send_to_user')) {
            push_send_to_user($pdo, (int) $user_id, $title, $message, $link_url);
        }
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

function mark_support_notifications_read($pdo, $user_id, $message_id)
{
    if ($user_id <= 0 || $message_id <= 0) {
        return;
    }

    try {
        $sql = "UPDATE notifications SET is_read = TRUE
                WHERE user_id = :uid AND is_read = FALSE
                AND type IN ('contact_reply', 'contact_sent')
                AND link_url LIKE :pat";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            'uid' => (int) $user_id,
            'pat' => '%message=' . (int) $message_id . '%',
        ));
    } catch (PDOException $e) {
        // Ignore if table missing.
    }
}

function mark_admin_contact_notifications_read($pdo, $admin_id, $message_id)
{
    if ($admin_id <= 0 || $message_id <= 0) {
        return;
    }

    try {
        $sql = "UPDATE notifications SET is_read = TRUE
                WHERE user_id = :uid AND is_read = FALSE
                AND type = 'contact_message'
                AND link_url LIKE :pat";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            'uid' => (int) $admin_id,
            'pat' => '%id=' . (int) $message_id . '%',
        ));
    } catch (PDOException $e) {
        // Ignore if table missing.
    }
}

function notification_type_label($type)
{
    if ($type == 'contact_reply' || $type == 'contact_sent') {
        return 'Support';
    }
    if ($type == 'contact_message') {
        return 'Inbox';
    }
    if ($type == 'pending_post' || $type == 'pending_comment') {
        return 'Review';
    }
    if ($type == 'content_report') {
        return 'Report';
    }
    if ($type == 'post_approved' || $type == 'post_rejected') {
        return 'Post';
    }
    if ($type == 'comment_approved' || $type == 'new_comment') {
        return 'Comment';
    }
    if ($type == 'new_follower') {
        return 'Follow';
    }
    if ($type == 'new_post') {
        return 'Update';
    }
    return 'Alert';
}

function notification_is_support_type($type)
{
    return ($type == 'contact_reply' || $type == 'contact_sent');
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

function soft_delete_comment($pdo, $comment_id)
{
    $stmt = $pdo->prepare("UPDATE post_comments SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute(array('id' => (int) $comment_id));
    return $stmt->rowCount() > 0;
}

function delete_own_comment($pdo, $comment_id)
{
    $comment = get_comment_by_id($pdo, $comment_id);
    if (!$comment || !can_manage_comment($comment)) {
        return array('ok' => false, 'error' => 'You cannot delete this comment.');
    }

    soft_delete_comment($pdo, (int) $comment_id);
    log_activity($pdo, 'comment.deleted', 'Comment #' . $comment_id);
    return array('ok' => true);
}

function notify_post_author_on_comment($pdo, $post_id, $commenter_name, $post_slug, $needs_approval = false)
{
    $stmt = $pdo->prepare('SELECT user_id, title FROM posts WHERE id = :id');
    $stmt->execute(array('id' => (int) $post_id));
    $post = $stmt->fetch();
    if (!$post || !$post['user_id']) {
        return;
    }

    $commenter_id = 0;
    if (isLoggedIn()) {
        $commenter_id = (int) $_SESSION['user_id'];
    }
    if ((int) $post['user_id'] == $commenter_id) {
        return;
    }

    if ($needs_approval) {
        $title = 'New comment awaiting approval';
        $message = $commenter_name . ' commented on "' . excerpt($post['title'], 40) . '". Approve it in My Comments.';
        $link = 'admin/my-comments.php?status=pending';
    } else {
        $title = 'New comment on your post';
        $message = $commenter_name . ' commented on "' . excerpt($post['title'], 40) . '".';
        $link = 'post/' . rawurlencode($post_slug) . '#comments';
    }

    create_notification(
        $pdo,
        (int) $post['user_id'],
        'new_comment',
        $title,
        $message,
        $link
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
        notify_followers_on_new_post($pdo, (int) $post_id);
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

function notify_all_admins($pdo, $type, $title, $message, $link_url = '', $send_email = false, $except_user_id = 0)
{
    try {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND COALESCE(account_status, 'active') != 'deleted'");
        $admin_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return;
    }

    foreach ($admin_ids as $admin_id) {
        $admin_id = (int) $admin_id;
        if ($admin_id <= 0 || $admin_id == (int) $except_user_id) {
            continue;
        }
        create_notification($pdo, $admin_id, $type, $title, $message, $link_url, $send_email);
    }
}

function notify_admins_contact_message($pdo, $message_id, $name, $email, $subject)
{
    $title = 'New contact message';
    $message = excerpt($name, 24) . ': "' . excerpt($subject, 48) . '"';
    $link = 'admin/messages.php?action=view&id=' . (int) $message_id;
    notify_all_admins($pdo, 'contact_message', $title, $message, $link);
}

function notify_user_contact_submitted($pdo, $message_id, $user_id, $subject)
{
    if ($user_id <= 0 || $message_id <= 0) {
        return;
    }

    create_notification(
        $pdo,
        (int) $user_id,
        'contact_sent',
        'Message sent to support',
        'We received "' . excerpt($subject, 50) . '". You will get a bell notification when we reply.',
        'support.php?message=' . (int) $message_id,
        false
    );
}

function notify_admins_pending_post($pdo, $post_id)
{
    $stmt = $pdo->prepare('SELECT p.title, u.name AS author_name FROM posts p LEFT JOIN users u ON u.id = p.user_id WHERE p.id = :id');
    $stmt->execute(array('id' => (int) $post_id));
    $post = $stmt->fetch();
    if (!$post) {
        return;
    }

    $author_name = $post['author_name'];
    if ($author_name == '') {
        $author_name = 'An author';
    }

    notify_all_admins(
        $pdo,
        'pending_post',
        'Post awaiting approval',
        $author_name . ' submitted "' . excerpt($post['title'], 40) . '".',
        'admin/posts.php?status=Pending',
        false,
        isLoggedIn() ? (int) $_SESSION['user_id'] : 0
    );
}

function notify_admins_pending_comment($pdo, $post_id, $commenter_name)
{
    $stmt = $pdo->prepare('SELECT title FROM posts WHERE id = :id');
    $stmt->execute(array('id' => (int) $post_id));
    $post = $stmt->fetch();
    if (!$post) {
        return;
    }

    notify_all_admins(
        $pdo,
        'pending_comment',
        'Comment awaiting approval',
        $commenter_name . ' commented on "' . excerpt($post['title'], 40) . '".',
        'admin/comments.php?status=pending'
    );
}

function notify_admins_content_report($pdo, $report_id, $reason)
{
    notify_all_admins(
        $pdo,
        'content_report',
        'New content report',
        excerpt($reason, 80),
        'admin/reports.php?action=view&id=' . (int) $report_id
    );
}

function find_user_id_for_email($pdo, $email)
{
    $email = trim((string) $email);
    if ($email == '' || is_placeholder_oauth_email($email)) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
    $stmt->execute(array('email' => $email));
    $row = $stmt->fetch();
    if (!$row) {
        return 0;
    }

    return (int) $row['id'];
}

function notify_user_contact_reply($pdo, $message)
{
    if (!is_array($message) || !isset($message['id'])) {
        return;
    }

    $user_id = 0;
    if (isset($message['user_id']) && (int) $message['user_id'] > 0) {
        $user_id = (int) $message['user_id'];
    }
    if ($user_id <= 0 && isset($message['email'])) {
        $user_id = find_user_id_for_email($pdo, $message['email']);
    }
    if ($user_id <= 0) {
        return;
    }

    $reply_preview = '';
    if (isset($message['admin_reply'])) {
        $reply_preview = excerpt(trim($message['admin_reply']), 120);
    }

    $subject_line = 'your message';
    if (isset($message['subject']) && trim($message['subject']) != '') {
        $subject_line = trim($message['subject']);
    }

    create_notification(
        $pdo,
        $user_id,
        'contact_reply',
        'Support replied: ' . excerpt($subject_line, 42),
        $reply_preview,
        'support.php?message=' . (int) $message['id'],
        true
    );
}

function notify_followers_on_new_post($pdo, $post_id)
{
    $stmt = $pdo->prepare("SELECT p.id, p.user_id, p.title, p.slug, p.status, u.name AS author_name
        FROM posts p
        LEFT JOIN users u ON u.id = p.user_id
        WHERE p.id = :id");
    $stmt->execute(array('id' => (int) $post_id));
    $post = $stmt->fetch();

    if (!$post || $post['status'] != 'Published' || !$post['user_id']) {
        return;
    }

    try {
        $followers_stmt = $pdo->prepare('SELECT follower_id FROM user_follows WHERE following_id = :uid');
        $followers_stmt->execute(array('uid' => (int) $post['user_id']));
        $follower_ids = $followers_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return;
    }

    if (count($follower_ids) == 0) {
        return;
    }

    $author_name = $post['author_name'];
    if ($author_name == '') {
        $author_name = 'Someone you follow';
    }

    $title = 'New post from ' . excerpt($author_name, 30);
    $message = excerpt($post['title'], 60);
    $link = 'post/' . rawurlencode($post['slug']);

    foreach ($follower_ids as $follower_id) {
        $follower_id = (int) $follower_id;
        if ($follower_id <= 0 || $follower_id == (int) $post['user_id']) {
            continue;
        }
        create_notification(
            $pdo,
            $follower_id,
            'new_post',
            $title,
            $message,
            $link,
            false
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
        'post/' . rawurlencode($comment['post_slug']) . '#comments'
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
        $sql = "SELECT
            (SELECT COUNT(*) FROM posts WHERE user_id = :uid AND status = 'Pending') AS pending_posts,
            (SELECT COUNT(*) FROM post_comments c INNER JOIN posts p ON p.id = c.post_id WHERE p.user_id = :uid AND c.status = 'pending') AS pending_comments,
            (SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = FALSE) AS notifications";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uid' => (int) $user_id));
        $row = $stmt->fetch();
        if ($row) {
            $counts['pending_posts'] = (int) $row['pending_posts'];
            $counts['pending_comments'] = (int) $row['pending_comments'];
            $counts['notifications'] = (int) $row['notifications'];
        }
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

    soft_delete_comment($pdo, (int) $comment_id);
    log_activity($pdo, 'comment.removed_by_author', 'Comment #' . $comment_id);
    return array('ok' => true);
}

function moderate_post_owner_comment($pdo, $comment_id, $status)
{
    $comment = get_comment_by_id($pdo, $comment_id);
    if (!$comment || !can_moderate_post_comment($comment)) {
        return array('ok' => false, 'error' => 'You cannot moderate this comment.');
    }

    if ($status != 'approved' && $status != 'rejected') {
        return array('ok' => false, 'error' => 'Invalid comment status.');
    }

    $pdo->prepare('UPDATE post_comments SET status = :status WHERE id = :id')->execute(array(
        'status' => $status,
        'id' => (int) $comment_id,
    ));

    if ($status == 'approved') {
        notify_comment_approved($pdo, $comment_id);
    }

    log_activity($pdo, 'comment.' . $status . '_by_author', 'Comment #' . $comment_id);
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
    if ($type == 'new_post') {
        return 'fa-newspaper text-warning';
    }
    if ($type == 'contact_message') {
        return 'fa-envelope-open-text text-warning';
    }
    if ($type == 'contact_reply') {
        return 'fa-headset text-success';
    }
    if ($type == 'contact_sent') {
        return 'fa-paper-plane text-info';
    }
    if ($type == 'pending_post') {
        return 'fa-hourglass-half text-warning';
    }
    if ($type == 'pending_comment') {
        return 'fa-comment-medical text-warning';
    }
    if ($type == 'comment_reply') {
        return 'fa-reply text-info';
    }
    if ($type == 'content_report') {
        return 'fa-flag text-danger';
    }
    return 'fa-bell text-secondary';
}

function user_liked_post($pdo, $post_id, $user_id)
{
    if ($post_id <= 0 || $user_id <= 0) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT id FROM post_likes WHERE post_id = :pid AND user_id = :uid');
    $stmt->execute(array('pid' => (int) $post_id, 'uid' => (int) $user_id));
    return (bool) $stmt->fetch();
}

function toggle_post_like($pdo, $post_id, $user_id, $toggle = true)
{
    if ($post_id <= 0 || $user_id <= 0) {
        return array('success' => false, 'message' => 'Invalid request.');
    }

    $stmt = $pdo->prepare('SELECT id, likes FROM posts WHERE id = :id AND status = :status');
    $stmt->execute(array('id' => (int) $post_id, 'status' => 'Published'));
    $post = $stmt->fetch();

    if (!$post) {
        return array('success' => false, 'message' => 'Post not found.');
    }

    $check = $pdo->prepare('SELECT id FROM post_likes WHERE post_id = :pid AND user_id = :uid');
    $check->execute(array('pid' => (int) $post_id, 'uid' => (int) $user_id));
    $existing = $check->fetch();

    if ($existing) {
        if (!$toggle) {
            return array(
                'success' => true,
                'liked' => true,
                'likes' => (int) $post['likes'],
                'message' => 'You already liked this post',
            );
        }

        $pdo->prepare('DELETE FROM post_likes WHERE id = :id')->execute(array('id' => (int) $existing['id']));
        $pdo->prepare('UPDATE posts SET likes = GREATEST(likes - 1, 0) WHERE id = :id')->execute(array('id' => (int) $post_id));
        $likes_stmt = $pdo->prepare('SELECT likes FROM posts WHERE id = :id');
        $likes_stmt->execute(array('id' => (int) $post_id));
        $likes = (int) $likes_stmt->fetchColumn();

        return array('success' => true, 'liked' => false, 'likes' => $likes, 'message' => 'Like removed');
    }

    $insert = $pdo->prepare('INSERT INTO post_likes (post_id, user_id) VALUES (:pid, :uid)');
    $insert->execute(array('pid' => (int) $post_id, 'uid' => (int) $user_id));
    $pdo->prepare('UPDATE posts SET likes = likes + 1 WHERE id = :id')->execute(array('id' => (int) $post_id));

    $likes_stmt = $pdo->prepare('SELECT likes FROM posts WHERE id = :id');
    $likes_stmt->execute(array('id' => (int) $post_id));
    $likes = (int) $likes_stmt->fetchColumn();

    return array('success' => true, 'liked' => true, 'likes' => $likes, 'message' => 'Post liked');
}
